<script setup>
import { onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    pixels: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['ready']);

let metaFbqLoaded = false;
let tiktokLoaded = false;
let gtagLoaded = false;
let ga4Loaded = false;

/** Permite apenas IDs alfanuméricos, hífen e underscore para evitar XSS. */
function isValidPixelId(id) {
    if (typeof id !== 'string' || id.length > 64) return false;
    return /^[a-zA-Z0-9_-]+$/.test(id);
}

function loadMetaPixel(pixelId) {
    if (metaFbqLoaded || !pixelId || !isValidPixelId(pixelId)) return;
    const s = document.createElement('script');
    s.async = true;
    s.defer = true;
    s.innerHTML = `!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','${pixelId}');fbq('track','PageView');`;
    document.head.appendChild(s);
    metaFbqLoaded = true;
}

function loadTikTokPixel(pixelId) {
    if (tiktokLoaded || !pixelId || !isValidPixelId(pixelId)) return;
    const s = document.createElement('script');
    s.async = true;
    s.innerHTML = `!function (w, d, t) { w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)}; ttq.load('${pixelId}'); ttq.page(); }(window, document, 'ttq');`;
    document.head.appendChild(s);
    tiktokLoaded = true;
}

function loadGoogleAds(conversionId) {
    if (gtagLoaded || !conversionId || !isValidPixelId(conversionId)) return;
    const s = document.createElement('script');
    s.async = true;
    s.src = `https://www.googletagmanager.com/gtag/js?id=${conversionId}`;
    document.head.appendChild(s);
    const gtag = document.createElement('script');
    gtag.innerHTML = `window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','${conversionId}');`;
    document.head.appendChild(gtag);
    gtagLoaded = true;
}

function loadGA4(measurementId) {
    if (ga4Loaded || !measurementId || !isValidPixelId(measurementId)) return;
    const s = document.createElement('script');
    s.async = true;
    s.src = `https://www.googletagmanager.com/gtag/js?id=${measurementId}`;
    document.head.appendChild(s);
    const gtag = document.createElement('script');
    gtag.innerHTML = `window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','${measurementId}');`;
    document.head.appendChild(gtag);
    ga4Loaded = true;
}

/** Domínios permitidos para script src em pixels customizados (evita XSS). */
const ALLOWED_SCRIPT_ORIGINS = ['https://www.googletagmanager.com', 'https://connect.facebook.net', 'https://analytics.tiktok.com', 'https://js.stripe.com'];

function isAllowedScriptSrc(src) {
    if (!src || typeof src !== 'string') return false;
    try {
        const u = new URL(src, location.origin);
        return ALLOWED_SCRIPT_ORIGINS.some((origin) => u.origin === origin || u.href.startsWith(origin + '/'));
    } catch {
        return false;
    }
}

function injectCustomScripts() {
    const items = props.pixels?.custom_script ?? [];
    if (!Array.isArray(items)) return;
    items.forEach((item) => {
        if (!item?.script || typeof item.script !== 'string') return;
        const s = document.createElement('div');
        s.innerHTML = item.script;
        const scripts = s.querySelectorAll('script');
        scripts.forEach((script) => {
            if (script.src && !isAllowedScriptSrc(script.src)) return;
            const newScript = document.createElement('script');
            if (script.src) newScript.src = script.src;
            if (script.innerHTML) newScript.innerHTML = script.innerHTML;
            newScript.async = script.async ?? true;
            document.head.appendChild(newScript);
        });
        const nonScripts = s.childNodes;
        nonScripts.forEach((node) => {
            if (node.nodeType === 1 && node.tagName !== 'SCRIPT') {
                document.head.appendChild(node.cloneNode(true));
            }
        });
    });
}

function init() {
    const p = props.pixels || {};

    if (p.meta?.enabled && p.meta?.pixel_id) {
        loadMetaPixel(p.meta.pixel_id);
    }
    if (p.tiktok?.enabled && p.tiktok?.pixel_id) {
        loadTikTokPixel(p.tiktok.pixel_id);
    }
    if (p.google_ads?.enabled && p.google_ads?.conversion_id) {
        loadGoogleAds(p.google_ads.conversion_id);
    }
    if (p.google_analytics?.enabled && p.google_analytics?.measurement_id) {
        loadGA4(p.google_analytics.measurement_id);
    }
    injectCustomScripts();

    emit('ready');
}

onMounted(init);
watch(() => props.pixels, init, { deep: true });

function shouldFireForPlatform(platform, triggerType, isOrderBump) {
    if (!platform?.enabled) return false;
    if (isOrderBump && platform?.disable_order_bump_events) return false;
    if (triggerType === 'pix' && !platform?.fire_purchase_on_pix) return false;
    if (triggerType === 'boleto' && !platform?.fire_purchase_on_boleto) return false;
    return true;
}

defineExpose({
    firePurchase(value, currency = 'BRL', orderId = '', isOrderBump = false, triggerType = 'approved') {
        const p = props.pixels || {};
        const num = Number(value) || 0;

        if (p.meta?.enabled && p.meta?.pixel_id && window.fbq && shouldFireForPlatform(p.meta, triggerType, isOrderBump)) {
            window.fbq('track', 'Purchase', { value: num, currency, content_ids: orderId ? [orderId] : [] });
        }
        if (p.tiktok?.enabled && p.tiktok?.pixel_id && window.ttq && shouldFireForPlatform(p.tiktok, triggerType, isOrderBump)) {
            window.ttq.track('CompletePayment', { value: num, currency, content_id: orderId });
        }
        if (p.google_ads?.enabled && p.google_ads?.conversion_id && window.gtag && shouldFireForPlatform(p.google_ads, triggerType, isOrderBump)) {
            window.gtag('event', 'conversion', {
                send_to: `${p.google_ads.conversion_id}/${p.google_ads.conversion_label || ''}`.replace(/\/+$/, ''),
                value: num,
                currency,
                transaction_id: orderId,
            });
        }
        if (p.google_analytics?.enabled && p.google_analytics?.measurement_id && window.gtag && shouldFireForPlatform(p.google_analytics, triggerType, isOrderBump)) {
            window.gtag('event', 'purchase', {
                send_to: p.google_analytics.measurement_id,
                value: num,
                currency,
                transaction_id: orderId,
            });
        }
    },
});
</script>

<template>
    <div class="hidden" aria-hidden="true" />
</template>
