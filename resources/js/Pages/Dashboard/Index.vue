<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import VueApexCharts from 'vue3-apexcharts';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import { CircleDollarSign, ShoppingCart, CreditCard, ShoppingBag, RotateCcw, Package, Eye, EyeOff } from 'lucide-vue-next';

defineOptions({ layout: LayoutInfoprodutor });

const valuesVisible = ref(true);
const isDarkMode = ref(false);

onMounted(() => {
    isDarkMode.value = document.documentElement.classList.contains('dark');
});

const props = defineProps({
    period: { type: String, default: 'hoje' },
    vendas_totais: { type: Number, default: 0 },
    vendas_pendentes: { type: Number, default: 0 },
    quantidade_vendas: { type: Number, default: 0 },
    ticket_medio: { type: Number, default: 0 },
    formas_pagamento: { type: Array, default: () => [] },
    taxa_conversao: { type: Number, default: 0 },
    abandono_carrinho: { type: Number, default: 0 },
    reembolsos_count: { type: Number, default: 0 },
    reembolsos_total: { type: Number, default: 0 },
    quantidade_produtos: { type: Number, default: 0 },
    grafico_vendas: { type: Array, default: () => [] },
});

const periodOptions = [
    { value: 'hoje', label: 'Hoje' },
    { value: 'ontem', label: 'Ontem' },
    { value: '7dias', label: '7 dias' },
    { value: 'mes', label: 'Mês' },
    { value: 'ano', label: 'Ano' },
    { value: 'total', label: 'Total' },
];

function setPeriod(value) {
    router.get('/dashboard', { period: value }, { preserveState: false });
}

function formatBRL(value) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
}

function displayCurrency(value) {
    return valuesVisible.value ? formatBRL(value) : '••••••';
}

function displayNumber(value) {
    return valuesVisible.value ? String(value) : '—';
}

const chartSeries = computed(() => [
    {
        name: 'Vendas',
        data: valuesVisible.value
            ? props.grafico_vendas.map((d) => d.total)
            : props.grafico_vendas.map(() => 0),
    },
]);

const chartOptions = computed(() => ({
    chart: {
        type: 'area',
        toolbar: { show: false },
        zoom: { enabled: false },
        fontFamily: 'inherit',
        animations: { enabled: true, speed: 600 },
    },
    colors: ['var(--color-primary)'],
    dataLabels: {
        enabled: true,
        formatter: (v) => (valuesVisible.value ? formatBRL(v) : ''),
        style: { fontSize: '11px' },
        offsetY: -4,
    },
    stroke: { curve: 'smooth', width: 2.5 },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 0.3,
            opacityFrom: 0.5,
            opacityTo: 0.08,
        },
    },
    markers: {
        size: 4,
        strokeWidth: 2,
        hover: { size: 6 },
    },
    xaxis: {
        categories: (props.period === 'hoje' || props.period === 'ontem')
            ? props.grafico_vendas.map((d) => `${Number(d.data)}h`)
            : props.grafico_vendas.map((d) => {
                const [y, m, day] = (d.data || '').split('-');
                return day && m ? `${day}/${m}` : d.data;
            }),
        labels: { style: { colors: '#71717a', fontSize: '12px' } },
        axisBorder: { show: true },
        crosshairs: { show: true },
    },
    yaxis: {
        labels: {
            style: { colors: '#71717a', fontSize: '12px' },
            formatter: (v) => formatBRL(v),
        },
    },
    grid: {
        borderColor: 'var(--chart-grid, #e4e4e7)',
        strokeDashArray: 4,
        xaxis: { lines: { show: false } },
        yaxis: { lines: { show: true } },
        padding: { top: 20, right: 10, bottom: 0, left: 0 },
    },
    tooltip: {
        theme: isDarkMode.value ? 'dark' : 'light',
        shared: true,
        intersect: false,
        x: { format: props.period === 'hoje' || props.period === 'ontem' ? 'HH' : 'dd/MM/yyyy' },
        y: { formatter: (v) => (valuesVisible.value ? formatBRL(v) : '••••••') },
        style: { fontSize: '13px' },
    },
    crosshairs: {
        stroke: { width: 1, dashArray: 4 },
    },
}));
</script>

<template>
    <div class="space-y-6">
        <!-- Barra de período + olho -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <nav class="flex flex-wrap items-center gap-1" aria-label="Período">
                <button
                    v-for="opt in periodOptions"
                    :key="opt.value"
                    type="button"
                    :aria-current="period === opt.value ? 'true' : undefined"
                    class="rounded-lg px-3 py-2 text-sm font-medium transition-colors"
                    :class="period === opt.value
                        ? 'bg-[var(--color-primary)] text-white'
                        : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200'"
                    @click="setPeriod(opt.value)"
                >
                    {{ opt.label }}
                </button>
            </nav>
            <button
                type="button"
                :aria-label="valuesVisible ? 'Ocultar valores' : 'Mostrar valores'"
                class="flex h-9 w-9 items-center justify-center rounded-lg text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                @click="valuesVisible = !valuesVisible"
            >
                <Eye v-if="valuesVisible" class="h-5 w-5" aria-hidden="true" />
                <EyeOff v-else class="h-5 w-5" aria-hidden="true" />
            </button>
        </div>

        <!-- Cards de destaque -->
        <div class="grid gap-4 sm:grid-cols-2">
            <div
                class="rounded-xl border border-zinc-200 bg-zinc-50 p-5 dark:border-zinc-700 dark:bg-zinc-800/50"
            >
                <div class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400">
                    <CircleDollarSign class="h-5 w-5" />
                    <span class="text-sm font-medium">Vendas totais</span>
                </div>
                <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-white">
                    {{ displayCurrency(vendas_totais) }}
                </p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Vendas pendentes: {{ displayCurrency(vendas_pendentes) }}
                </p>
            </div>
            <div
                class="rounded-xl border border-zinc-200 bg-zinc-50 p-5 dark:border-zinc-700 dark:bg-zinc-800/50"
            >
                <div class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400">
                    <ShoppingCart class="h-5 w-5" />
                    <span class="text-sm font-medium">Quantidade de vendas</span>
                </div>
                <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-white">
                    {{ displayNumber(quantidade_vendas) }}
                </p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Ticket médio: {{ displayCurrency(ticket_medio) }}
                </p>
            </div>
        </div>

        <!-- Formas de pagamento + lateral -->
        <div class="grid gap-4 lg:grid-cols-3">
            <div
                class="rounded-xl border border-zinc-200 bg-zinc-50 p-5 dark:border-zinc-700 dark:bg-zinc-800/50 lg:col-span-2"
            >
                <h2 class="flex items-center gap-2 text-sm font-semibold text-zinc-900 dark:text-white">
                    <CreditCard class="h-4 w-4 text-zinc-500" />
                    Formas de pagamento
                </h2>
                <ul class="mt-4 space-y-3">
                    <li
                        v-for="fp in formas_pagamento"
                        :key="fp.metodo"
                        class="flex items-center justify-between border-b border-zinc-200/60 py-2 last:border-0 dark:border-zinc-700/60"
                    >
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ fp.label }}</span>
                        <span class="text-sm font-medium text-zinc-900 dark:text-white">
                            {{ displayCurrency(fp.total) }}
                            <span class="font-normal text-zinc-500">({{ displayNumber(fp.quantidade) }})</span>
                        </span>
                    </li>
                    <li v-if="!formas_pagamento.length" class="py-4 text-center text-sm text-zinc-500">
                        Nenhum pagamento no período
                    </li>
                </ul>
                <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Taxa de conversão geral</p>
                    <p class="text-xl font-semibold text-zinc-900 dark:text-white">
                        {{ valuesVisible ? `${taxa_conversao}%` : '—' }}
                    </p>
                </div>
            </div>
            <div class="space-y-4">
                <div
                    class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50"
                >
                    <div class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400">
                        <ShoppingBag class="h-4 w-4" />
                        <span class="text-sm font-medium">Abandono de carrinho</span>
                    </div>
                    <p class="mt-2 text-lg font-bold text-zinc-900 dark:text-white">
                        {{ displayNumber(abandono_carrinho) }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50"
                >
                    <div class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400">
                        <RotateCcw class="h-4 w-4" />
                        <span class="text-sm font-medium">Reembolso</span>
                    </div>
                    <p class="mt-2 text-lg font-bold text-zinc-900 dark:text-white">
                        {{ displayCurrency(reembolsos_total) }}
                    </p>
                    <p class="text-xs text-zinc-500">{{ displayNumber(reembolsos_count) }} pedido(s)</p>
                </div>
                <div
                    class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50"
                >
                    <div class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400">
                        <Package class="h-4 w-4" />
                        <span class="text-sm font-medium">Produtos</span>
                    </div>
                    <p class="mt-2 text-lg font-bold text-zinc-900 dark:text-white">
                        {{ displayNumber(quantidade_produtos) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Gráfico de vendas -->
        <div
            class="rounded-xl border border-zinc-200 bg-zinc-50 p-5 dark:border-zinc-700 dark:bg-zinc-800/50"
        >
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">Desempenho de vendas</h2>
            <div class="mt-4 min-h-[280px]">
                <VueApexCharts
                    v-if="grafico_vendas.length"
                    type="area"
                    height="280"
                    :options="chartOptions"
                    :series="chartSeries"
                />
                <p
                    v-else
                    class="flex h-[280px] items-center justify-center text-sm text-zinc-500 dark:text-zinc-400"
                >
                    Nenhum dado de vendas no período
                </p>
            </div>
        </div>
    </div>
</template>
