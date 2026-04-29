<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    templates: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Templates email" />

    <AppLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Catalogue des templates email</h1>
                <p class="mt-1 text-sm text-slate-500">Bibliothèque réutilisable pour campagnes et relances.</p>
            </div>
        </template>

        <div class="grid gap-4 lg:grid-cols-2">
            <div
                v-for="template in templates"
                :key="template.id"
                class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200"
            >
                <p class="text-xs font-semibold uppercase tracking-wide text-[#0066B3]">{{ template.nom }}</p>
                <h2 class="mt-2 text-xl font-bold text-slate-900">{{ template.objet }}</h2>
                <div class="mt-4 whitespace-pre-line rounded-2xl bg-slate-50 p-4 text-sm leading-6 text-slate-700">
                    {{ template.contenu }}
                </div>
            </div>
        </div>
    </AppLayout>
</template>