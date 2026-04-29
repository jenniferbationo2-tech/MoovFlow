<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    activity: {
        type: Object,
        required: true,
    },
});

const formatDate = (value) => value
    ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
    : 'N/A';
</script>

<template>
    <Head title="Detail audit" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Detail d audit</h1>
                    <p class="mt-1 text-sm text-slate-500">Analyse complete d une action journalisee.</p>
                </div>
                <Link :href="route('admin.audit.index')" class="rounded-2xl bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">
                    Retour au journal
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <dl class="grid gap-4 md:grid-cols-2">
                    <div>
                        <dt class="text-sm font-semibold text-slate-500">Description</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ activity.description }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-semibold text-slate-500">Date</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ formatDate(activity.created_at) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-semibold text-slate-500">Utilisateur</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ activity.causer?.name || 'Systeme' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-semibold text-slate-500">Sujet</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ activity.subject_type || 'Aucun' }} #{{ activity.subject_id || '-' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Details techniques</h2>
                <pre class="mt-4 overflow-x-auto rounded-2xl bg-slate-50 p-4 text-xs text-slate-700">{{ JSON.stringify(activity.properties, null, 2) }}</pre>
            </div>
        </div>
    </AppLayout>
</template>