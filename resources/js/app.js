import './bootstrap';

// Registrar Service Worker do painel o mais cedo possível (necessário para beforeinstallprompt no Android)
if (typeof navigator !== 'undefined' && navigator.serviceWorker) {
    navigator.serviceWorker.register('/painel-sw.js', { scope: '/' }).catch(() => {});
}

// Vidstack Player 1.x (Web Components) – estilos e registros antes do Vue
import 'vidstack/player/styles/default/theme.css';
import 'vidstack/player/styles/default/layouts/audio.css';
import 'vidstack/player/styles/default/layouts/video.css';
import 'vidstack/player';
import 'vidstack/player/layouts';
import 'vidstack/player/ui';
import { createInertiaApp } from '@inertiajs/vue3';
import { createApp as createVueApp, h } from 'vue';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';

const appName = import.meta.env.VITE_APP_NAME || 'Infoprodutor';

const el = document.getElementById('app');
const dataPage = el?.getAttribute('data-page');
let initialPage = null;
try {
    initialPage = dataPage ? JSON.parse(dataPage) : null;
} catch (_) {}
const defaultProps = {
    auth: { user: null },
    flash: { success: null, error: null },
    platform: null,
};
if (!initialPage?.component) {
    initialPage = {
        component: 'Welcome',
        props: { ...defaultProps },
        url: '/',
        version: null,
    };
} else if (initialPage.props) {
    initialPage.props = { ...defaultProps, ...initialPage.props };
    if (!initialPage.props.flash || typeof initialPage.props.flash !== 'object') {
        initialPage.props.flash = { success: null, error: null };
    }
}

const pluginPagesGlob = import.meta.glob('./PluginPages/**/*.vue');

function resolvePluginPage(name) {
    if (!name.startsWith('Plugin/')) return null;
    const path = `./PluginPages/${name.slice(7).replace(/\//g, '/')}.vue`;
    const loader = pluginPagesGlob[path];
    return loader ? loader() : null;
}

createInertiaApp({
    id: 'app',
    page: initialPage,
    title: (title) => title || appName,
    resolve: (name) => {
        const pluginPage = resolvePluginPage(name);
        if (pluginPage) return pluginPage;
        return resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue')
        );
    },
    setup({ el, App, props, plugin }) {
        const vueApp = createVueApp({
            render: () => h(App, props),
        });
        vueApp.use(plugin);
        vueApp.use(createPinia());
        vueApp.mount(el);
    },
    progress: false,
});
