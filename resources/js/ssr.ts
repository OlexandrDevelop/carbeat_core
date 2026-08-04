import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { renderToString } from '@vue/server-renderer';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createSSRApp, DefineComponent, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createServer((page) =>
    createInertiaApp({
        page,
        render: renderToString,
        title: (title) => `${title} - ${appName}`,
        resolve: (name) =>
            resolvePageComponent(
                `./Pages/${name}.vue`,
                import.meta.glob<DefineComponent>('./Pages/**/*.vue'),
            ),
        setup({ App, props, plugin }) {
            const app = createSSRApp({ render: () => h(App, props) }).use(
                plugin,
            );

            // Error responses can be rendered before HandleInertiaRequests has
            // shared the Ziggy config (for example, when an earlier middleware
            // throws). SSR must still be able to render the error page so the
            // original exception is not replaced by a Ziggy TypeError.
            const ziggy = page.props?.ziggy;

            if (ziggy?.location) {
                app.use(ZiggyVue, {
                    ...ziggy,
                    location: new URL(ziggy.location),
                });
            }

            return app;
        },
    }),
);
