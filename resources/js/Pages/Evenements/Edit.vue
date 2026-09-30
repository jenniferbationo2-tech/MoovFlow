<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import EvenementForm from '@/Pages/Evenements/Partials/EvenementForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    evenement: {
        type: Object,
        required: true,
    },
    typesEvenement: {
        type: Array,
        default: () => [],
    },
    lieux: {
        type: Array,
        default: () => [],
    },
    statuts: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Modifier un événement" />

    <DashboardLayout>
        <div class="mb-6">
            <h1 class="font-display text-2xl font-bold text-slate-900">Modifier l'événement</h1>
            <p class="mt-1 text-sm text-slate-500">Ajustez les contenus, le budget et les indicateurs de suivi.</p>
        </div>

        <EvenementForm
            :evenement="evenement"
            :types-evenement="typesEvenement"
            :lieux="lieux"
            :is-edit="true"
        />
    </DashboardLayout>
</template>