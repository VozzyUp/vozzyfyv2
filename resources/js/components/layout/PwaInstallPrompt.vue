<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
import { X, Smartphone, Share } from 'lucide-vue-next';
import { usePwaInstall } from '@/composables/usePwaInstall';
import { usePage } from '@inertiajs/vue3';

const slug = 'painel';
const {
    installPromptEvent,
    showIosInstructions,
    isStandalone,
    isIos,
    isMobile,
    tryGetDismissed,
    dismiss,
    triggerInstall,
    registerListener,
    unregisterListener,
} = usePwaInstall(slug);

const page = usePage();
const appName = computed(() => page.props.appSettings?.app_name || 'Getfy');

const showBanner = ref(false);

watch(
    installPromptEvent,
    (e) => {
        if (e && !isStandalone.value && !tryGetDismissed() && isMobile.value) {
            showBanner.value = true;
        }
    },
    { immediate: true }
);

async function install() {
    await triggerInstall();
    showBanner.value = false;
}

onMounted(() => {
    if (isStandalone.value) return;
    registerListener();
});

onUnmounted(() => {
    unregisterListener();
});
</script>

<template>
    <!-- Banner Android: prompt de instalação fixo no mobile -->
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-full opacity-0"
    >
        <div
            v-if="showBanner && installPromptEvent && !isStandalone && isMobile"
            class="fixed bottom-0 left-0 right-0 z-[99999] border-t border-zinc-200 bg-white p-4 pb-[max(1rem,env(safe-area-inset-bottom))] shadow-2xl dark:border-zinc-700 dark:bg-zinc-800"
        >
            <div class="mx-auto flex max-w-md items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400">
                        <Smartphone class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="font-semibold text-zinc-900 dark:text-zinc-100">Instalar {{ appName }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Acesso rápido pela tela inicial</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="rounded-xl bg-zinc-900 px-4 py-2.5 font-medium text-white shadow-lg transition hover:bg-zinc-800 active:scale-[0.98] dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100"
                        @click="install"
                    >
                        Instalar
                    </button>
                    <button
                        type="button"
                        class="shrink-0 rounded-lg p-2 text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-700 dark:hover:text-zinc-300"
                        aria-label="Fechar"
                        @click="dismiss(); showBanner = false"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Modal iOS: instruções para adicionar à tela inicial -->
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
    >
        <div
            v-if="showIosInstructions && isIos && !isStandalone"
            class="fixed inset-0 z-[99999] flex items-end justify-center p-4 pb-[max(2rem,calc(env(safe-area-inset-bottom)+1rem))] sm:items-center sm:p-6"
        >
            <!-- Backdrop -->
            <div
                class="absolute inset-0 bg-black/50"
                aria-hidden="true"
                @click="dismiss"
            />
            <!-- Card -->
            <div
                class="relative w-full max-w-md rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-700 dark:bg-zinc-800"
            >
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400">
                        <Share class="h-6 w-6" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                            Adicionar à tela inicial
                        </h3>
                        <p class="mt-3 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                            No Safari, toque no ícone <strong class="text-zinc-800 dark:text-zinc-200">Compartilhar</strong>
                            (quadrado com seta para cima) na barra inferior.
                        </p>
                        <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                            Em seguida, toque em <strong class="text-zinc-800 dark:text-zinc-200">« Adicionar à Tela de Início »</strong>.
                        </p>
                        <button
                            type="button"
                            class="mt-6 w-full rounded-xl bg-zinc-900 px-4 py-3 font-medium text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100"
                            @click="dismiss"
                        >
                            Entendi
                        </button>
                    </div>
                    <button
                        type="button"
                        class="absolute right-4 top-4 rounded-lg p-2 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-700 dark:hover:text-zinc-300"
                        aria-label="Fechar"
                        @click="dismiss"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
