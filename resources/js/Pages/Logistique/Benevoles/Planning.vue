<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    evenement: {
        type: Object,
        required: true,
    },
    planning: {
        type: Array,
        default: () => [],
    },
});

const formatDateTime = (value) => value
    ? new Intl.DateTimeFormat('fr-FR', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value))
    : 'N/A';

const posteColors = {
    accueil: 'bg-[#0066B3]/10 text-[#0066B3] border-[#0066B3]/20',
    securite: 'bg-red-50 text-red-700 border-red-200',
    orientation: 'bg-amber-50 text-amber-700 border-amber-200',
    technique: 'bg-violet-50 text-violet-700 border-violet-200',
    restauration: 'bg-[#00A651]/10 text-[#008a45] border-[#00A651]/20',
};

const cardClass = (poste) => posteColors[(poste || '').toLowerCase()] || 'bg-slate-50 text-slate-700 border-slate-200';
</script>

<template>
    <Head :title="`Planning benevoles - ${evenement.titre}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Planning benevoles</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ evenement.titre }} · vue timeline des rotations par poste.</p>
                </div>

                <Link
                    :href="route('logistique.benevoles.index', { evenement: evenement.id })"
                    class="rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    Retour aux affectations
                </Link>
            </div>
        </template>

        <div class="space-y-4">
            <div
                v-for="item in planning"
                :key="item.user_id"
                class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200"
            >
                <div class="flex flex-col gap-2 border-b border-slate-100 pb-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ item.benevole.name }}</h2>
                        <p class="text-sm text-slate-500">{{ item.benevole.email }}</p>
                    </div>
                    <span class="text-sm font-medium text-slate-500">{{ item.creneaux.length }} creneau(x)</span>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <div
                        v-for="creneau in item.creneaux"
                        :key="creneau.id"
                        class="rounded-2xl border p-4"
                        :class="cardClass(creneau.poste)"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-wide">{{ creneau.poste }}</p>
                                <p class="mt-2 text-sm">{{ formatDateTime(creneau.creneau_debut) }}</p>
                                <p class="text-sm">{{ formatDateTime(creneau.creneau_fin) }}</p>
                            </div>
                            <div class="h-full w-2 rounded-full bg-current opacity-50" />
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="!planning.length" class="rounded-3xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">
                Aucun planning de rotation n'est encore disponible pour cet événement.
            </div>
        </div>
    </AppLayout>
</template>