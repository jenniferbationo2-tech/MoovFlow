<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import ModalSelectionType from './Partials/ModalSelectionType.vue'
import EvenementWizard from './Partials/EvenementWizard.vue'
const props = defineProps({
    types:              { type: Array,  required: true },
    lieux:              { type: Array,  required: true },
    typePreselectionne: { type: Object, default: null },
    userRole:           { type: Object, default: () => ({}) },
})

const typeSelectionne = ref(props.typePreselectionne)

const onTypeSelectionne = (type) => {
    typeSelectionne.value = type
    router.get('/evenements/create', { type: type.code }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

const changerType = () => {
    if (confirm('Changer le type d\'événement ? Les données saisies seront perdues.')) {
        typeSelectionne.value = null
        router.get('/evenements/create', {}, { preserveState: false })
    }
}
</script>

<template>
    <DashboardLayout>

        <!-- MODALE si pas de type choisi -->
        <ModalSelectionType v-if="!typeSelectionne"
                             :types="types"
                             @selectionner="onTypeSelectionne"/>

        <!-- WIZARD une fois le type choisi -->
        <div v-else>

            <!-- En-tête -->
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                        Création d'événement
                    </p>
                    <h1 class="mt-1 flex items-center gap-3 font-display text-2xl font-extrabold text-text-main">
                        <span>{{ typeSelectionne.nom }}</span>
                        <button @click="changerType"
                                class="rounded-full bg-page-bg px-3 py-1 text-xs font-bold text-text-sub transition hover:bg-moov-blue hover:text-white">
                            Changer
                        </button>
                    </h1>
                </div>

                <a href="/evenements"
                   class="text-sm font-bold text-text-sub transition hover:text-moov-blue">
                    ← Retour à la liste
                </a>
            </div>

           <EvenementWizard :type-selectionne="typeSelectionne"
                 :lieux="lieux"
                 :is-edit="false"
                 :user-role="userRole"/>
        </div>

    </DashboardLayout>
</template>