import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, DefineComponent, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import AdminLayout from './Layouts/AdminLayout.vue';
import MasterLayout from './Layouts/MasterLayout.vue';
import i18n from './i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./Pages/**/*.vue'),
        ).then((page) => {
            if (
                name.startsWith('Admin/') &&
                page.default.layout === undefined
            ) {
                page.default.layout = AdminLayout;
            } else if (
                name.startsWith('Master/') &&
                page.default.layout === undefined
            ) {
                page.default.layout = MasterLayout;
            }

            return page;
        }),
    setup({ el, App, props, plugin }) {
        const ziggy = props.initialPage.props.ziggy;

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(i18n)
            // No `location` override here (unlike ssr.ts, which has no
            // `window` and must be told the URL explicitly): freezing it to
            // the initial server-rendered URL would make route().current()
            // keep reporting that URL forever, since Inertia navigates via
            // pushState and never remounts this app. Omitting it lets Ziggy
            // fall back to the live `window.location`, which pushState does
            // keep in sync — this is what makes active-nav-link
            // highlighting (route().current(pattern) in Admin/MasterLayout)
            // update correctly after client-side navigation.
            .use(ZiggyVue, ziggy)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
