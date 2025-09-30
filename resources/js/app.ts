import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { initializeTheme } from './composables/useAppearance';
import i18n from './i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(i18n);
        
        // Set initial locale and direction
        const locale = (props.initialPage.props as any)?.locale || 'en';
        i18n.global.locale.value = locale as 'en' | 'ar';
        document.documentElement.lang = locale;
        document.documentElement.dir = ['ar', 'he', 'fa'].includes(locale) ? 'rtl' : 'ltr';
        
        app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// Add global error handler to catch null reference errors
window.addEventListener('error', (event) => {
    if (event.error && event.error.message) {
        const message = event.error.message;
        if (message.includes("Cannot read properties of null")) {
            console.error('Null reference error caught:', {
                message: message,
                filename: event.filename,
                lineno: event.lineno,
                colno: event.colno,
                stack: event.error.stack
            });
        }
    }
});

// Add unhandled promise rejection handler
window.addEventListener('unhandledrejection', (event) => {
    if (event.reason && event.reason.message && event.reason.message.includes("Cannot read properties of null")) {
        console.error('Unhandled null reference error:', event.reason);
    }
});
