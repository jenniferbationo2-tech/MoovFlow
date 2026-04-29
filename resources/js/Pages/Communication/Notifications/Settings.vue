<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    preferences: {
        type: Object,
        required: true,
    },
    canaux: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Paramètres notifications" />

    <AppLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Paramètres de notification</h1>
                <p class="mt-1 text-sm text-slate-500">Canal par défaut et disponibilité des canaux utilisateur.</p>
            </div>
        </template>

        <div class="grid gap-6 md:grid-cols-3">
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <p class="text-sm font-semibold text-slate-500">Canal par défaut</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ preferences.canal_defaut }}</p>
            </div>
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <p class="text-sm font-semibold text-slate-500">Canaux disponibles</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span v-for="canal in canaux" :key="canal" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                        {{ canal }}
                    </span>
                </div>
            </div>
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <dl class="space-y-3 text-sm text-slate-600">
                    <div class="flex items-center justify-between">
                        <dt>Email</dt>
                        <dd class="font-semibold text-slate-900">{{ preferences.email_disponible ? 'Disponible' : 'Indisponible' }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt>SMS</dt>
                        <dd class="font-semibold text-slate-900">{{ preferences.sms_disponible ? 'Disponible' : 'Indisponible' }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt>Push</dt>
                        <dd class="font-semibold text-slate-900">{{ preferences.push_disponible ? 'Disponible' : 'Indisponible' }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </AppLayout>
</template>