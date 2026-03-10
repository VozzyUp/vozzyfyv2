<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { getVideoProviderType } from '@/lib/utils';

const props = defineProps({
    src: { type: String, default: '' },
    poster: { type: String, default: '' },
    playsinline: { type: Boolean, default: true },
    watermarkEnabled: { type: Boolean, default: false },
    watermarkData: { type: Object, default: null },
});

const emit = defineEmits(['ended']);

const watermarkPosition = ref(0);
let watermarkInterval = null;

const POSITIONS = ['top-left', 'top-right', 'bottom-left', 'bottom-right', 'center'];

const providerType = computed(() => getVideoProviderType(props.src));

// Vidstack 1.x aceita URL completa (YouTube, Vimeo ou nativo) no src do media-player
const vidstackSrc = computed(() => {
    if (!props.src || !props.src.trim()) return '';
    const u = props.src.trim();
    const type = providerType.value;
    if (type === 'youtube') {
        const m = u.match(/(?:youtube\.com\/watch\?.*v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/);
        return m ? `youtube/${m[1]}` : u;
    }
    if (type === 'vimeo') {
        const m = u.match(/vimeo\.com\/(?:video\/)?(\d+)/);
        return m ? `vimeo/${m[1]}` : u;
    }
    return u;
});

// Para YouTube: usar thumbnail como poster quando não houver poster customizado, assim o botão do YouTube não aparece no centro
const posterUrl = computed(() => {
    if (props.poster) return props.poster;
    if (providerType.value !== 'youtube' || !props.src) return '';
    const m = props.src.trim().match(/(?:youtube\.com\/watch\?.*v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/);
    if (!m) return '';
    const id = m[1];
    return `https://img.youtube.com/vi/${id}/sddefault.jpg`;
});

const watermarkText = computed(() => {
    if (!props.watermarkEnabled || !props.watermarkData) return '';
    const d = props.watermarkData;
    const name = (d.name ?? '').trim() || 'Aluno';
    if (d.cpf && String(d.cpf).trim()) {
        return `${name} - ${String(d.cpf).trim()}`;
    }
    return (d.email && String(d.email).trim()) ? `${name} - ${String(d.email).trim()}` : name;
});

onMounted(() => {
    if (props.watermarkEnabled && watermarkText.value) {
        watermarkInterval = setInterval(() => {
            watermarkPosition.value = (watermarkPosition.value + 1) % POSITIONS.length;
        }, 20000);
    }
});
onUnmounted(() => {
    if (watermarkInterval) clearInterval(watermarkInterval);
});

function onEnded() {
    emit('ended');
}

function onContextMenu(e) {
    e.preventDefault();
}
</script>

<template>
    <div
        class="member-area-video-player aspect-video w-full overflow-hidden rounded-lg bg-black relative"
        @contextmenu.prevent="onContextMenu"
    >
        <media-player
            v-if="src"
            class="player"
            :src="vidstackSrc"
            :poster="posterUrl"
            :playsinline="playsinline"
            crossorigin
            @vds-ended="onEnded"
            @vds-end="onEnded"
        >
            <media-provider>
                <media-poster v-if="posterUrl" class="vds-poster" :src="posterUrl" alt="" />
            </media-provider>
            <media-video-layout>
                <media-airplay-button slot="airPlayButton">
                    <media-icon type="airplay" />
                </media-airplay-button>
                <media-google-cast-button slot="googleCastButton">
                    <media-icon type="chromecast" />
                </media-google-cast-button>
            </media-video-layout>
        </media-player>
        <div
            v-if="watermarkEnabled && watermarkText"
            class="watermark-overlay"
            :class="POSITIONS[watermarkPosition]"
        >
            {{ watermarkText }}
        </div>
    </div>
</template>

<style scoped>
.member-area-video-player {
    --media-brand: #f5f5f5;
    --media-focus-ring-color: #4e9cf6;
}
.player {
    width: 100%;
    height: 100%;
    display: block;
}
.player[data-view-type='video'] {
    aspect-ratio: 16 / 9;
}
/* Poster por cima do iframe do YouTube até o usuário dar play */
.player :deep(.vds-poster),
.player :deep([data-media-poster]) {
    z-index: 1;
}
.player :deep(media-provider),
.player :deep([data-media-provider]) {
    z-index: 0;
}
/* Camada 1: esconder PiP para dificultar gravação */
.player :deep(media-pip-button) {
    display: none !important;
}
/* Marca d'água: overlay que muda de posição */
.watermark-overlay {
    position: absolute;
    z-index: 2;
    pointer-events: none;
    font-size: clamp(0.75rem, 2vw, 1rem);
    color: rgba(255, 255, 255, 0.6);
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
    transition: left 0.5s, top 0.5s, right 0.5s, bottom 0.5s;
}
.watermark-overlay.top-left {
    left: 8px;
    top: 8px;
}
.watermark-overlay.top-right {
    right: 8px;
    top: 8px;
}
.watermark-overlay.bottom-left {
    left: 8px;
    bottom: 8px;
}
.watermark-overlay.bottom-right {
    right: 8px;
    bottom: 8px;
}
.watermark-overlay.center {
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
}
</style>
