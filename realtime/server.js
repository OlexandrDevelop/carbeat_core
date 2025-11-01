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
        try {
            subscriber = createClient({
                url: `redis://${REDIS_HOST}:${REDIS_PORT}`,
                password: REDIS_PASSWORD,
            });
            subscriber.on('error', (err) => {
                console.error('Redis subscriber error', err);
            });
            await subscriber.connect();
            // Subscribe to both prefixed and non-prefixed channels using pattern
            await subscriber.pSubscribe(
                '*availability:events',
                (message, channel) => {
                    try {
                        const data = JSON.parse(message);
                        console.log('redis event', channel, data);
                        io.emit('availability:update', data);
                    } catch (e) {
                        console.log('redis event raw', channel, message);
                        io.emit('availability:update', { raw: message });
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
