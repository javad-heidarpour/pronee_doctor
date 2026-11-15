import '../css/app.css';
import './bootstrap';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { VueReCaptcha } from 'vue-recaptcha-v3';
import { formatDate, formatDateShort, formatTime } from "./utils/dateUtils";

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,

    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue')
        ),

    setup({ el, App, props, plugin }) {

        const app = createApp({
            render: () => h(App, props),
        });

        app.use(plugin);
        // 🔥 تاریخ شمسی
        app.config.globalProperties.$formatDate = formatDate;
        app.config.globalProperties.$formatDateShort = formatDateShort;
        app.config.globalProperties.$formatTime = formatTime;
        // 🔥 تاریخ شمسی
        app.use(ZiggyVue);

        app.use(VueReCaptcha, {
            siteKey: import.meta.env.VITE_RECAPTCHA_SITE_KEY,
            loaderOptions: {
                autoHideBadge: false,
            },
        });

        app.mount(el);
    },

    progress: {
        color: '#14b8a6',
    },
});