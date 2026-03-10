<script setup>
import { computed } from 'vue';

const props = defineProps({
    url: { type: String, default: '' },
});

const videoId = computed(() => {
    if (!props.url || typeof props.url !== 'string') return null;
    const u = props.url.trim();
    const m = u.match(/(?:youtube(?:-nocookie)?\.com\/(?:[^/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?/\s]{11})/i);
    const id = m ? m[1] : null;
    return id && /^[a-zA-Z0-9_-]{11}$/.test(id) ? id : null;
});
</script>

<template>
    <div v-if="videoId" class="mb-8">
        <div class="aspect-video overflow-hidden rounded-2xl shadow-xl ring-1 ring-black/5">
            <iframe
                :src="`https://www.youtube.com/embed/${videoId}`"
                title="Vídeo"
                class="h-full w-full"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
            />
        </div>
    </div>
</template>
