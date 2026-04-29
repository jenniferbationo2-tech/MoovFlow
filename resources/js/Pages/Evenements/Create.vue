<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import EvenementForm from '@/Pages/Evenements/Partials/EvenementForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
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
    <Head title="Creer un evenement" />

    <AppLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Creer un evenement</h1>
                <p class="mt-1 text-sm text-slate-500">Configurez les informations cles, le budget et les objectifs RSE.</p>
            </div>
        </template>

        <EvenementForm
            :types-evenement="typesEvenement"
            :lieux="lieux"
            :statuts="statuts"
            :submit-url="route('evenements.store')"
            method="post"
            :cancel-url="route('evenements.index')"
            submit-label="Enregistrer"
        />
    </AppLayout>
</template>