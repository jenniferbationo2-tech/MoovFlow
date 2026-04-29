<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        required: true,
    },
});

const config = computed(() => ({
    brouillon: { label: 'Brouillon', classes: 'bg-slate-100 text-slate-700 ring-slate-200' },
    publie: { label: 'Publie', classes: 'bg-[#0066B3]/10 text-[#0066B3] ring-[#0066B3]/20' },
    en_cours: { label: 'En cours', classes: 'bg-[#00A651]/10 text-[#008a45] ring-[#00A651]/20' },
    termine: { label: 'Termine', classes: 'bg-emerald-100 text-emerald-700 ring-emerald-200' },
    annule: { label: 'Annule', classes: 'bg-red-100 text-red-700 ring-red-200' },
    fait: { label: 'Fait', classes: 'bg-emerald-100 text-emerald-700 ring-emerald-200' },
    a_faire: { label: 'A faire', classes: 'bg-amber-100 text-amber-700 ring-amber-200' },
    attente: { label: 'En attente', classes: 'bg-amber-100 text-amber-700 ring-amber-200' },
    valide: { label: 'Validee', classes: 'bg-emerald-100 text-emerald-700 ring-emerald-200' },
    initie: { label: 'Initie', classes: 'bg-sky-100 text-sky-700 ring-sky-200' },
    paye: { label: 'Paye', classes: 'bg-emerald-100 text-emerald-700 ring-emerald-200' },
    echec: { label: 'Echec', classes: 'bg-red-100 text-red-700 ring-red-200' },
    rembourse: { label: 'Rembourse', classes: 'bg-violet-100 text-violet-700 ring-violet-200' },
    gratuit: { label: 'Gratuit', classes: 'bg-slate-100 text-slate-700 ring-slate-200' },
    present: { label: 'Present', classes: 'bg-emerald-100 text-emerald-700 ring-emerald-200' },
    absent: { label: 'Absent', classes: 'bg-slate-100 text-slate-700 ring-slate-200' },
    disponible: { label: 'Disponible', classes: 'bg-emerald-100 text-emerald-700 ring-emerald-200' },
    reserve: { label: 'Reservee', classes: 'bg-amber-100 text-amber-700 ring-amber-200' },
    utilise: { label: 'Utilisee', classes: 'bg-[#0066B3]/10 text-[#0066B3] ring-[#0066B3]/20' },
    retourne: { label: 'Retourne', classes: 'bg-emerald-100 text-emerald-700 ring-emerald-200' },
    manquant: { label: 'Manquant', classes: 'bg-red-100 text-red-700 ring-red-200' },
    planifie: { label: 'Planifie', classes: 'bg-sky-100 text-sky-700 ring-sky-200' },
    confirme: { label: 'Confirme', classes: 'bg-[#0066B3]/10 text-[#0066B3] ring-[#0066B3]/20' },
    realise: { label: 'Realise', classes: 'bg-emerald-100 text-emerald-700 ring-emerald-200' },
    chaud: { label: 'Chaud', classes: 'bg-red-100 text-red-700 ring-red-200' },
    tiede: { label: 'Tiede', classes: 'bg-amber-100 text-amber-700 ring-amber-200' },
    froid: { label: 'Froid', classes: 'bg-slate-100 text-slate-700 ring-slate-200' },
    envoye: { label: 'Envoye', classes: 'bg-emerald-100 text-emerald-700 ring-emerald-200' },
}[props.status] ?? { label: props.status, classes: 'bg-slate-100 text-slate-700 ring-slate-200' }));
</script>

<template>
    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset shadow-sm" :class="config.classes">
        {{ config.label }}
    </span>
</template>