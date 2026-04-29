import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig(({ mode }) => {
    process.env.NODE_ENV = mode;

    const isProd = mode === 'production';
    const viteOrigin = process.env.VITE_ORIGIN;

    // Allow enabling polling via env (USE_POLLING=1) as a fallback when
    // system inotify watchers are exhausted (ENOSPC). Polling is less
    // efficient but avoids the "ENOSPC: System limit for number of file
    // watchers reached" error on some developer machines / CI.
    const usePolling = !!process.env.USE_POLLING;

    return {
        base: isProd ? '/build/' : '/',
        server: {
            https: false,
            host: process.env.VITE_HOST || '127.0.0.1',
            port: Number(process.env.VITE_PORT || 5173),
            origin: viteOrigin || undefined,
            strictPort: true,
            cors: true,
            hmr: {
                host: process.env.VITE_HMR_HOST || '127.0.0.1',
                port: Number(process.env.VITE_HMR_PORT || process.env.VITE_PORT || 5173),
                protocol: 'ws',
                clientPort: Number(process.env.VITE_HMR_PORT || process.env.VITE_PORT || 5173),
            },
            // Configure watcher options to ignore large vendor/storage folders
            // and to optionally use polling when enabled via env.
            watch: {
                // Don't try to watch vendor, build and git metadata
                ignored: ['**/vendor/**', '**/storage/**', '**/.git/**', '**/node_modules/**'],
                // Use polling when explicitly requested (safer on low-watcher systems)
                usePolling: usePolling,
                // Poll interval when polling is enabled (ms)
                interval: usePolling ? 150 : undefined,
                // Disable atomic writes handling to avoid extra watchers in some setups
                atomic: false,
            },
        },
        plugins: [
            laravel({
                input: [
                    'resources/js/app.ts',
                    'resources/css/app.css',
                ],
                ssr: 'resources/js/ssr.ts',
                refresh: true,
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],
        define: {
            'process.env.NODE_ENV': JSON.stringify(mode),
        },
    };
});
