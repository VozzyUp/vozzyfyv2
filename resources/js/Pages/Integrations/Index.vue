<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import Button from '@/components/ui/Button.vue';
import AppCard from '@/components/integrations/AppCard.vue';
import SpedySidebar from '@/components/integrations/SpedySidebar.vue';
import UtmifySidebar from '@/components/integrations/UtmifySidebar.vue';
import WebhookSidebar from '@/components/integrations/WebhookSidebar.vue';
import GatewayCard from '@/components/settings/GatewayCard.vue';
import GatewayConfigSidebar from '@/components/settings/GatewayConfigSidebar.vue';
import { CreditCard, Zap } from 'lucide-vue-next';

defineOptions({ layout: LayoutInfoprodutor });

const TABS = [
    { id: 'apps', label: 'Apps', icon: Zap },
    { id: 'gateways', label: 'Gateways', icon: CreditCard },
];

const APPS_BASE = [
    {
        id: 'webhook',
        name: 'Webhook',
        description: 'Envie eventos da plataforma para sua URL. Configure quais eventos deseja receber e use Bearer token para autenticação.',
        image: 'images/integrations/webhook.png',
    },
    {
        id: 'utmify',
        name: 'UTMfy',
        description: 'Rastreie vendas e envie eventos para a UTMfy. Requer apenas a chave de API.',
        image: 'images/integrations/utmify.jpg',
    },
    {
        id: 'spedy',
        name: 'Spedy',
        description: 'Emissão automática de notas fiscais. Envie vendas para a Spedy e emita NF-e/NFS-e.',
        image: 'images/integrations/spedy.png',
    },
];

const props = defineProps({
    gateways: { type: Array, default: () => [] },
    gateway_order: {
        type: Object,
        default: () => ({ pix: [], card: [], boleto: [] }),
    },
    webhooks: { type: Array, default: () => [] },
    webhook_events: { type: Object, default: () => ({}) },
    utmify_integrations: { type: Array, default: () => [] },
    spedy_integrations: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
});

const APPS = computed(() =>
    APPS_BASE.map((app) => {
        if (app.id === 'utmify') {
            const hasActive = (props.utmify_integrations || []).some(
                (i) => i.configured && i.is_active
            );
            return {
                ...app,
                status: hasActive ? 'active' : undefined,
            };
        }
        if (app.id === 'spedy') {
            const hasActive = (props.spedy_integrations || []).some(
                (i) => i.configured && i.is_active
            );
            return {
                ...app,
                status: hasActive ? 'active' : undefined,
            };
        }
        return app;
    })
);

const gatewaySidebarOpen = ref(false);
const selectedGatewaySlug = ref(null);
const webhookSidebarOpen = ref(false);
const utmifySidebarOpen = ref(false);
const spedySidebarOpen = ref(false);

function openGatewaySidebar(slug) {
    selectedGatewaySlug.value = slug;
    gatewaySidebarOpen.value = true;
}

function closeGatewaySidebar() {
    gatewaySidebarOpen.value = false;
    selectedGatewaySlug.value = null;
}

function openWebhookSidebar() {
    webhookSidebarOpen.value = true;
}

function closeWebhookSidebar() {
    webhookSidebarOpen.value = false;
}

function openUtmifySidebar() {
    utmifySidebarOpen.value = true;
}

function closeUtmifySidebar() {
    utmifySidebarOpen.value = false;
}

function openSpedySidebar() {
    spedySidebarOpen.value = true;
}

function closeSpedySidebar() {
    spedySidebarOpen.value = false;
}

function onGatewaySaved() {
    router.reload({ only: ['gateways', 'gateway_order'] });
}

function onWebhookSaved() {
    router.reload();
}

function onUtmifySaved() {
    // Recarrega só a lista de integrações para não perder o valor do input da chave no sidebar
    router.reload({ only: ['utmify_integrations', 'products'] });
}

function onSpedySaved() {
    router.reload({ only: ['spedy_integrations', 'products'] });
}

function onAppClick(app) {
    if (app.id === 'webhook') {
        openWebhookSidebar();
    } else if (app.id === 'utmify') {
        openUtmifySidebar();
    } else if (app.id === 'spedy') {
        openSpedySidebar();
    }
}

const page = usePage();
const currentTab = computed(() => {
    const url = page.url;
    const idx = url.indexOf('?');
    const search = idx !== -1 ? url.slice(idx) : '';
    const q = new URLSearchParams(search);
    const t = q.get('tab');
    return TABS.some((tab) => tab.id === t) ? t : 'apps';
});

function setTab(tabId) {
    router.get('/integracoes', { tab: tabId }, { preserveState: true });
}
</script>

<template>
    <div class="space-y-6">
        <nav
            class="inline-flex flex-wrap gap-1 rounded-xl bg-zinc-100/80 p-1 dark:bg-zinc-800/80"
            aria-label="Abas de integrações"
        >
            <button
                v-for="tab in TABS"
                :key="tab.id"
                type="button"
                :class="[
                    'flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-200',
                    currentTab === tab.id
                        ? 'bg-white text-[var(--color-primary)] shadow-sm dark:bg-zinc-700 dark:text-[var(--color-primary)]'
                        : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white',
                ]"
                @click="setTab(tab.id)"
            >
                <component :is="tab.icon" class="h-4 w-4 shrink-0" aria-hidden="true" />
                {{ tab.label }}
            </button>
        </nav>

        <!-- Aba Apps -->
        <template v-if="currentTab === 'apps'">
            <section>
                <h2 class="mb-2 text-lg font-semibold text-zinc-900 dark:text-white">
                    Integrações
                </h2>
                <p class="mb-6 text-sm text-zinc-600 dark:text-zinc-400">
                    Conecte sua plataforma com sistemas externos via webhooks e outras integrações.
                </p>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <AppCard
                        v-for="app in APPS"
                        :key="app.id"
                        :app="app"
                        @click="onAppClick(app)"
                    />
                </div>
            </section>
        </template>

        <!-- Aba Gateways -->
        <template v-if="currentTab === 'gateways'">
            <section class="space-y-6">
                <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                    <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                        Gateways de pagamento
                    </h2>
                    <p class="mb-4 text-sm text-zinc-600 dark:text-zinc-400">
                        Configure os gateways que deseja usar no checkout. Clique em um card para configurar credenciais e testar a conexão.
                    </p>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <GatewayCard
                            v-for="g in gateways"
                            :key="g.slug"
                            :gateway="g"
                            @click="openGatewaySidebar(g.slug)"
                        />
                    </div>
                    <div v-if="gateways.length === 0" class="rounded-xl border border-dashed border-zinc-300 py-8 text-center text-sm text-zinc-500 dark:border-zinc-600 dark:text-zinc-400">
                        Nenhum gateway disponível.
                    </div>
                </div>
            </section>
        </template>

        <GatewayConfigSidebar
            :open="gatewaySidebarOpen"
            :gateway-slug="selectedGatewaySlug"
            @close="closeGatewaySidebar"
            @saved="onGatewaySaved"
        />
        <WebhookSidebar
            :open="webhookSidebarOpen"
            :webhooks="webhooks"
            :webhook-events="webhook_events"
            :products="products"
            @close="closeWebhookSidebar"
            @saved="onWebhookSaved"
        />
        <UtmifySidebar
            :open="utmifySidebarOpen"
            :utmify_integrations="utmify_integrations"
            :products="products"
            @close="closeUtmifySidebar"
            @saved="onUtmifySaved"
        />
        <SpedySidebar
            :open="spedySidebarOpen"
            :spedy_integrations="spedy_integrations"
            :products="products"
            @close="closeSpedySidebar"
            @saved="onSpedySaved"
        />
    </div>
</template>
