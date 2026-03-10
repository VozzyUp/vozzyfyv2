<script setup>
import { onMounted } from 'vue';
import { Smartphone } from 'lucide-vue-next';
import { usePwaInstall } from '@/composables/usePwaInstall';

const { canShowInstallButton, triggerInstall, registerListener } = usePwaInstall('painel');

onMounted(() => {
    registerListener();
});
</script>

<template>
    <button
        v-if="canShowInstallButton"
        type="button"
        class="menu-item group w-full justify-start menu-item-inactive"
        @click="triggerInstall"
    >
        <span class="shrink-0 menu-item-icon-inactive">
            <Smartphone class="h-5 w-5" aria-hidden="true" />
        </span>
        <span class="truncate">Instalar App</span>
    </button>
</template>
