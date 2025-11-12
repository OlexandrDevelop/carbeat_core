import { createServer } from 'http';
import { createClient } from 'redis';
import { Server } from 'socket.io';

const PORT = process.env.SOCKET_IO_PORT
    ? parseInt(process.env.SOCKET_IO_PORT, 10)
    : 3000;
const REDIS_HOST = process.env.REDIS_HOST || 'redis';
const REDIS_PORT = process.env.REDIS_PORT
    ? parseInt(process.env.REDIS_PORT, 10)
    : 6379;
const REDIS_PASSWORD = process.env.REDIS_PASSWORD || undefined;
const CORS_ORIGIN = process.env.CORS_ORIGIN || '*';

const httpServer = createServer();
const io = new Server(httpServer, {
    path: '/socket.io/',
    cors: {
        origin:
            CORS_ORIGIN === '*'
                ? '*'
                : CORS_ORIGIN.split(',').map((s) => s.trim()),
        methods: ['GET', 'POST'],
    },
    transports: ['websocket', 'polling'],
});

// In-memory tracking of currently available masters for resilience
const currentAvailable = new Set();
const RECONCILE_INTERVAL_MS = process.env.RECONCILE_INTERVAL_MS
    ? parseInt(process.env.RECONCILE_INTERVAL_MS, 10)
    : 60000;

io.on('connection', (socket) => {
    // Clients may send ping/pong; no rooms needed for global stream
    socket.on('ping', () => socket.emit('pong'));
});

function sleep(ms) {
    return new Promise((r) => setTimeout(r, ms));
}

async function subscribeWithRetry() {
    for (;;) {
        let subscriber;
        let adminClient;
        try {
            subscriber = createClient({
                url: `redis://${REDIS_HOST}:${REDIS_PORT}`,
                password: REDIS_PASSWORD,
            });
            subscriber.on('error', (err) => {
                console.error('Redis subscriber error', err);
            });
            await subscriber.connect();

            // Try to enable keyevent notifications for expired keys
            try {
                adminClient = createClient({
                    url: `redis://${REDIS_HOST}:${REDIS_PORT}`,
                    password: REDIS_PASSWORD,
                });
                adminClient.on('error', (err) => {
                    console.error('Redis admin error', err);
                });
                await adminClient.connect();
                await adminClient.configSet('notify-keyspace-events', 'Ex');
            } catch (e) {
                console.error('Unable to set notify-keyspace-events Ex', e);
            } finally {
                if (adminClient) {
                    try { await adminClient.quit(); } catch (_) {}
                    adminClient = undefined;
                }
            }
            // Subscribe to both prefixed and non-prefixed channels using pattern
            await subscriber.pSubscribe(
                '*availability:events',
                (message, channel) => {
                    try {
                        const data = JSON.parse(message);
                        console.log('redis event', channel, data);
                        io.emit('availability:update', data);
                        if (data && typeof data.id === 'number') {
                            if (data.available) currentAvailable.add(data.id);
                            else currentAvailable.delete(data.id);
                        }
                    } catch (e) {
                        console.log('redis event raw', channel, message);
                        io.emit('availability:update', { raw: message });
                    }
                },
            );
            // Subscribe to keyevent expired notifications
            await subscriber.pSubscribe(
                '__keyevent@*__:expired',
                (message, channel) => {
                    try {
                        const key = message;
                        // Allow optional Redis key prefix (Laravel adds one)
                        const m = /master:(\d+):available$/.exec(key);
                        if (m) {
                            const id = Number(m[1]);
                            const payload = { id, available: false, expiresAt: null, ts: Math.floor(Date.now() / 1000) };
                            console.log('key expired -> availability:update', payload);
                            io.emit('availability:update', payload);
                            currentAvailable.delete(id);
                        }
                    } catch (e) {
                        console.error('expired handler error', e);
                    }
                },
            );
            // Wait until the connection ends, then retry
            await new Promise((resolve) => subscriber.on('end', resolve));
        } catch (e) {
            console.error('Redis subscribe failed, retrying soon…', e);
        } finally {
            if (subscriber) {
                try {
                    await subscriber.quit();
                } catch (_) {
                    console.error('Redis subscriber quit failed', e);
                }
            }
        }
        await sleep(2000);
    }
}

// Start HTTP server immediately and keep Redis subscription retrying in background
httpServer.listen(PORT, () => {
    console.log(`Socket.IO server listening on :${PORT}`);
});

subscribeWithRetry();

// Periodic reconcile: scan Redis for available flags and emit unavailable for missing ones (self-heal on restarts)
async function reconcileLoop() {
    for (;;) {
        try {
            const client = createClient({
                url: `redis://${REDIS_HOST}:${REDIS_PORT}`,
                password: REDIS_PASSWORD,
            });
            client.on('error', (e) => console.error('Redis reconcile error', e));
            await client.connect();

            // Allow optional Redis key prefix (Laravel sets REDIS_PREFIX)
            const scanPattern = '*master:*:available';
            let cursor = 0;
            const nowAvailable = new Set();
            do {
                const res = await client.scan(cursor, { MATCH: scanPattern, COUNT: 500 });
                cursor = res.cursor;
                const keys = res.keys || res[1] || [];
                for (const key of keys) {
                    const m = /master:(\d+):available$/.exec(key);
                    if (m) nowAvailable.add(Number(m[1]));
                }
            } while (cursor !== 0);

            // Emit unavailable for ids we previously thought available but no longer present
            for (const id of Array.from(currentAvailable)) {
                if (!nowAvailable.has(id)) {
                    const payload = { id, available: false, expiresAt: null, ts: Math.floor(Date.now() / 1000) };
                    console.log('reconcile -> availability:update', payload);
                    io.emit('availability:update', payload);
                    currentAvailable.delete(id);
                }
            }

            // Sync known set to now; do not emit available here to avoid duplicates
            for (const id of nowAvailable) currentAvailable.add(id);

            await client.quit();
        } catch (e) {
            console.error('reconcile loop error', e);
        }
        await sleep(RECONCILE_INTERVAL_MS);
    }
}

reconcileLoop();
