<script setup>
import { computed } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import Button from '@/components/ui/Button.vue';

const page = usePage();
const status = computed(() => page.props.flash?.status ?? null);

const form = useForm({
    email: '',
});

function submit() {
    form.post('/esqueci-senha', {
        preserveScroll: true,
        onFinish: () => form.reset('email'),
    });
}
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-zinc-50 px-4 dark:bg-zinc-950">
        <div class="w-full max-w-sm space-y-6">
            <img
                src="https://cdn.getfy.cloud/collapsed-logo.png"
                alt="Getfy"
                class="mx-auto h-12 w-auto object-contain"
            />
            <h1 class="text-center text-2xl font-bold text-zinc-900 dark:text-white">Recuperar senha</h1>
            <p class="text-center text-sm text-zinc-600 dark:text-zinc-400">
                Informe seu e-mail para receber o link de redefinição.
            </p>

            <div v-if="status" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                {{ status }}
            </div>

            <form class="space-y-5" @submit.prevent="submit">
                <div>
                    <label for="email" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">E-mail</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                        required
                        class="mt-1.5 block w-full rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-4 py-3 text-zinc-900 dark:text-white placeholder-zinc-500 shadow-sm transition focus:border-[var(--color-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/20"
                        placeholder="seu@email.com"
                    />
                    <p v-if="form.errors.email" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ form.errors.email }}</p>
                </div>
                <Button type="submit" class="w-full !bg-[#c8fa64] !text-zinc-900 hover:!opacity-90" :disabled="form.processing">
                    {{ form.processing ? 'Enviando…' : 'Enviar link' }}
                </Button>
            </form>

            <p class="text-center">
                <Link
                    href="/login"
                    class="text-sm font-medium text-[#c8fa64] hover:underline focus:outline-none focus:ring-2 focus:ring-[#c8fa64]/30 focus:ring-offset-2 dark:focus:ring-offset-zinc-950 rounded"
                >
                    Voltar ao login
                </Link>
            </p>
        </div>
    </div>
</template>
