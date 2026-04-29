<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
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

    <AppLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Modifier l'événement</h1>
                <p class="mt-1 text-sm text-slate-500">Ajustez les contenus, le budget et les indicateurs de suivi.</p>
            </div>
        </template>

        <EvenementForm
            :evenement="evenement"
            :types-evenement="typesEvenement"
            :lieux="lieux"
            :statuts="statuts"
            :submit-url="route('evenements.update', evenement.id)"
            method="patch"
            :cancel-url="route('evenements.show', evenement.id)"
            submit-label="Sauvegarder"
        />
    </AppLayout>
</template>