<script setup>
import { PanelsTopLeft } from 'lucide-vue-next';
import { useSidebar } from '@/composables/useSidebar';
import ConquistasWidget from '@/components/layout/ConquistasWidget.vue';
import ThemeToggler from '@/components/layout/ThemeToggler.vue';
import UserMenu from '@/components/layout/UserMenu.vue';

defineProps({
    pageTitle: { type: String, default: null },
    pageTitleBadge: { type: String, default: null },
});

const { toggleSidebar, isMobileOpen, isMobile } = useSidebar();
</script>

<template>
    <header class="z-[99998] flex shrink-0 w-full items-center justify-between gap-4 bg-transparent px-4 py-3 lg:px-6 lg:py-4">
        <div class="flex min-w-0 flex-1 items-center gap-3">
            <button
                v-if="isMobile && !isMobileOpen"
                type="button"
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                aria-label="Abrir menu"
                @click="toggleSidebar"
            >
                <PanelsTopLeft class="h-5 w-5" aria-hidden="true" />
            </button>
            <template v-if="pageTitle">
                <h1 class="truncate text-xl font-semibold text-zinc-900 dark:text-white md:text-2xl">
                    {{ pageTitle }}
                </h1>
                <span
                    v-if="pageTitleBadge"
                    class="shrink-0 truncate max-w-[160px] md:max-w-[220px] rounded-md bg-[var(--color-primary)]/15 px-2.5 py-0.5 text-xs font-medium text-[var(--color-primary)] dark:bg-[var(--color-primary)]/25 dark:text-[var(--color-primary)]"
                    :title="pageTitleBadge"
                >
                    {{ pageTitleBadge }}
                </span>
            </template>
        </div>
        <div class="flex shrink-0 items-center gap-2">
            <ConquistasWidget />
            <ThemeToggler />
            <UserMenu />
        </div>
    </header>
</template>
