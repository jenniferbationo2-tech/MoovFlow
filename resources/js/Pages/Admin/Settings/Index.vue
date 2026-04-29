<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    app_name: props.settings.app_name ?? '',
    app_logo: props.settings.app_logo ?? '',
    smtp_host: props.settings.smtp_host ?? '',
    smtp_port: props.settings.smtp_port ?? '',
    smtp_username: props.settings.smtp_username ?? '',
    payment_public_key: props.settings.payment_public_key ?? '',
    payment_secret_key: props.settings.payment_secret_key ?? '',
});

const submit = () => {
    form.post(route('admin.settings.update'));
};
</script>

<template>
    <Head title="Parametres generaux" />

    <AppLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Parametres generaux</h1>
                <p class="mt-1 text-sm text-slate-500">Configuration applicative, email et paiement.</p>
            </div>
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <div class="grid gap-6 xl:grid-cols-3">
                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Application</h2>
                    <div class="mt-5 space-y-4">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nom de l application</label>
                            <input v-model="form.app_name" type="text" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Logo</label>
                            <input v-model="form.app_logo" type="text" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Email / SMTP</h2>
                    <div class="mt-5 space-y-4">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Serveur SMTP</label>
                            <input v-model="form.smtp_host" type="text" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Port SMTP</label>
                            <input v-model="form.smtp_port" type="text" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Utilisateur SMTP</label>
                            <input v-model="form.smtp_username" type="text" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Paiement</h2>
                    <div class="mt-5 space-y-4">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Cle publique API</label>
                            <input v-model="form.payment_public_key" type="text" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Cle secrete API</label>
                            <input v-model="form.payment_secret_key" type="text" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                        </div>
                    </div>
                </section>
            </div>

            <button type="submit" class="rounded-2xl bg-[#0066B3] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#005290]" :disabled="form.processing">
                Enregistrer les parametres
            </button>
        </form>
    </AppLayout>
</template>