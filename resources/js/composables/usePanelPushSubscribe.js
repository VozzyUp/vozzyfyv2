import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);
    return outputArray;
}

/**
 * Registra o Service Worker do painel e subscribe para push.
 * Usar no AppLayout - usa push_enabled e vapid_public das props compartilhadas.
 */
export function usePanelPushSubscribe() {
    const page = usePage();
    const pushEnabled = computed(() => !!page.props.push_enabled);
    const vapidPublic = computed(() => page.props.vapid_public ?? null);
    const pushSubscribing = ref(false);
    const pushRegistered = ref(false);

    async function registerAndSubscribe() {
        if (typeof navigator === 'undefined' || !navigator.serviceWorker) return;

        try {
            const reg = await navigator.serviceWorker.register('/painel-sw.js', { scope: '/' });
        } catch (e) {
            console.warn('Panel SW registration failed:', e);
            return;
        }

        if (!pushEnabled.value || !vapidPublic.value) return;
        if (typeof Notification !== 'undefined' && Notification.permission === 'denied') return;
        if (pushSubscribing.value || pushRegistered.value) return;

        pushSubscribing.value = true;
        try {
            const reg = await navigator.serviceWorker.getRegistration('/');
            if (!reg) return;
            const existing = await reg.pushManager?.getSubscription?.();
            if (existing) {
                pushRegistered.value = true;
                return;
            }
            const sub = await reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidPublic.value),
            });
            const p256dh = sub.getKey('p256dh');
            const auth = sub.getKey('auth');
            await axios.post('/painel/push-subscribe', {
                endpoint: sub.endpoint,
                keys: {
                    p256dh: p256dh ? btoa(String.fromCharCode.apply(null, new Uint8Array(p256dh))) : '',
                    auth: auth ? btoa(String.fromCharCode.apply(null, new Uint8Array(auth))) : '',
                },
            });
            pushRegistered.value = true;
        } catch (e) {
            if (e?.name === 'NotAllowedError') return;
            console.warn('Panel push subscribe failed:', e);
        } finally {
            pushSubscribing.value = false;
        }
    }

    onMounted(() => {
        registerAndSubscribe();
    });

    return { pushSubscribing, pushRegistered, registerAndSubscribe };
}
