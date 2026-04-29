<script setup>
import Modal from '@/Components/Modal.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import TypeBadge from '@/Components/TypeBadge.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    evenements: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    typesEvenement: {
        type: Array,
        default: () => [],
    },
    statuts: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            en_cours: 0,
            termines: 0,
            taux_remplissage_moyen: 0,
        }),
    },
});

const localFilters = reactive({
    search: props.filters.search ?? '',
    type: props.filters.type ?? '',
    statut: props.filters.statut ?? '',
    date: props.filters.date ?? '',
});

const showDeleteModal = ref(false);
const evenementToDelete = ref(null);
let debounceTimer = null;

const statCards = computed(() => [
    {
        label: 'Total evenements',
        value: props.stats.total,
        icon: '◷',
        gradient: 'from-[#0066B3] to-[#004A82]',
    },
    {
        label: 'En cours',
        value: props.stats.en_cours,
        icon: '◎',
        gradient: 'from-[#00A651] to-[#0B8C4A]',
    },
    {
        label: 'Termines',
        value: props.stats.termines,
        icon: '✓',
        gradient: 'from-[#FF9800] to-[#F97316]',
    },
    {
        label: 'Taux moyen',
        value: `${props.stats.taux_remplissage_moyen ?? 0}%`,
        icon: '◲',
        gradient: 'from-slate-700 to-slate-900',
    },
]);

const rows = computed(() => props.evenements?.data ?? []);

const applyFilters = () => {
    router.get(route('evenements.index'), localFilters, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

watch(localFilters, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 250);
}, { deep: true });

const resetFilters = () => {
    localFilters.search = '';
    localFilters.type = '';
    localFilters.statut = '';
    localFilters.date = '';
    applyFilters();
};

const openDeleteModal = (evenement) => {
    evenementToDelete.value = evenement;
    showDeleteModal.value = true;
};

const deleteEvenement = () => {
    if (!evenementToDelete.value) {
        return;
    }

    router.delete(route('evenements.destroy', evenementToDelete.value.id), {
        preserveScroll: true,
        onFinish: () => {
            showDeleteModal.value = false;
            evenementToDelete.value = null;
        },
    });
};

const formatDate = (value) => value
    ? new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value))
    : 'Date a confirmer';

const formatDateRange = (row) => `${formatDate(row.date_debut)} → ${formatDate(row.date_fin)}`;

const progressClass = (value) => {
    if (value === null || value === undefined) {
        return 'bg-slate-300';
    }

    if (value >= 100) {
        return 'bg-red-500';
    }

    if (value >= 80) {
        return 'bg-[#FF9800]';
    }

    return 'bg-[#00A651]';
};
</script>

<template>
    <Head title="Evenements" />

    <AppLayout>
        <template #header>
            <div>
                <h1 class="font-['Outfit'] text-3xl font-bold text-slate-900">MoovFlow Evenements</h1>
                <p class="mt-1 text-sm text-slate-500">Une vue premium de votre programmation, inspiree des plateformes evenementielles les plus abouties.</p>
            </div>
        </template>

        <div class="space-y-6">
            <section class="overflow-hidden rounded-[2rem] bg-gradient-to-r from-[#0066B3] to-[#004A82] px-6 py-7 text-white shadow-sm">
                <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-[0.28em] text-white/70">Collection MoovFlow</p>
                        <h2 class="mt-3 font-['Outfit'] text-4xl font-bold">Vos evenements en vitrine</h2>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-white/80">
                            Explorez, filtrez et pilotez chaque evenement dans une experience visuelle plus chaleureuse, plus claire et plus vendeuse.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-4">
                        <div class="rounded-[1.5rem] border border-white/10 bg-white/10 px-5 py-4 backdrop-blur-sm">
                            <p class="text-xs uppercase tracking-[0.24em] text-white/70">Total</p>
                            <p class="mt-2 text-3xl font-bold">{{ stats.total }}</p>
                        </div>
                        <Link
                            :href="route('evenements.create')"
                            class="inline-flex items-center justify-center rounded-[1.5rem] bg-white px-6 py-4 text-sm font-semibold text-[#0066B3] transition hover:-translate-y-0.5 hover:shadow-lg"
                        >
                            Creer un evenement
                        </Link>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="item in statCards"
                    :key="item.label"
                    class="rounded-[1.75rem] bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:shadow-md"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ item.label }}</p>
                            <p class="mt-3 text-3xl font-bold text-slate-900">{{ item.value }}</p>
                        </div>
                        <div :class="`bg-gradient-to-br ${item.gradient}`" class="flex h-14 w-14 items-center justify-center rounded-2xl text-xl text-white shadow-sm">
                            {{ item.icon }}
                        </div>
                    </div>
                </article>
            </section>

            <section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="grid gap-4 lg:grid-cols-[1.5fr,1fr,1fr,auto]">
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">Rechercher</span>
                        <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 focus-within:border-[#0066B3] focus-within:bg-white">
                            <span class="text-slate-400">⌕</span>
                            <input v-model="localFilters.search" type="text" placeholder="Titre, description, lieu..." class="w-full border-0 bg-transparent p-0 text-sm text-slate-700 placeholder:text-slate-400 focus:ring-0">
                        </div>
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">Type</span>
                        <select v-model="localFilters.type" class="w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option value="">Tous les types</option>
                            <option v-for="type in typesEvenement" :key="type.id" :value="type.id">{{ type.nom }}</option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">Statut</span>
                        <select v-model="localFilters.statut" class="w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option value="">Tous les statuts</option>
                            <option v-for="statut in statuts" :key="statut.value" :value="statut.value">{{ statut.label }}</option>
                        </select>
                    </label>

                    <div class="flex items-end">
                        <button
                            type="button"
                            class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                            @click="resetFilters"
                        >
                            Reinitialiser
                        </button>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="row in rows"
                    :key="row.id"
                    class="group overflow-hidden rounded-[2rem] bg-white shadow-sm ring-1 ring-slate-200 transition duration-300 hover:scale-[1.02] hover:shadow-lg"
                >
                    <div class="relative h-56 overflow-hidden">
                        <img
                            v-if="row.visuel_url"
                            :src="row.visuel_url"
                            :alt="row.titre"
                            class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                        >
                        <div
                            v-else
                            class="flex h-full items-end bg-gradient-to-br from-[#0066B3] via-[#004A82] to-[#00A651] p-6"
                        >
                            <div>
                                <p class="text-xs uppercase tracking-[0.28em] text-white/70">MoovFlow</p>
                                <p class="mt-2 font-['Outfit'] text-2xl font-bold text-white">{{ row.titre }}</p>
                            </div>
                        </div>

                        <div class="absolute left-4 top-4">
                            <TypeBadge :type="row.type_evenement" />
                        </div>
                        <div class="absolute right-4 top-4">
                            <StatusBadge :status="row.statut" />
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="min-h-[3.5rem]">
                            <h3 class="line-clamp-2 text-xl font-semibold text-slate-900">{{ row.titre }}</h3>
                        </div>

                        <div class="mt-5 space-y-3 text-sm text-slate-600">
                            <div class="flex items-start gap-3">
                                <span class="mt-0.5 text-[#0066B3]">◷</span>
                                <div>
                                    <p class="font-medium text-slate-800">{{ formatDateRange(row) }}</p>
                                    <p class="text-xs text-slate-500">Calendrier principal</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <span class="mt-0.5 text-[#0066B3]">⌂</span>
                                <div>
                                    <p class="font-medium text-slate-800">{{ row.lieu?.nom ?? 'Lieu a confirmer' }}</p>
                                    <p class="text-xs text-slate-500">{{ row.lieu?.adresse ?? 'Adresse non renseignee' }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <span class="mt-0.5 text-[#0066B3]">◎</span>
                                <div>
                                    <p class="font-medium text-slate-800">
                                        {{ row.inscriptions_count }} inscrits<span v-if="row.capacite"> / {{ row.capacite }}</span>
                                    </p>
                                    <p class="text-xs text-slate-500">Occupation de l'evenement</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5">
                            <div class="mb-2 flex items-center justify-between text-xs font-medium text-slate-500">
                                <span>Remplissage</span>
                                <span>{{ row.taux_remplissage ?? 'N/A' }}<template v-if="row.taux_remplissage !== null">%</template></span>
                            </div>
                            <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                                <div
                                    class="h-full rounded-full transition-all"
                                    :class="progressClass(row.taux_remplissage)"
                                    :style="{ width: `${row.taux_remplissage ?? 12}%` }"
                                />
                            </div>
                        </div>

                        <div class="mt-6 flex items-center justify-between gap-3">
                            <Link
                                :href="route('evenements.show', row.id)"
                                class="inline-flex items-center justify-center rounded-2xl border border-[#0066B3]/20 px-4 py-3 text-sm font-semibold text-[#0066B3] transition hover:bg-[#0066B3]/5"
                            >
                                Voir details
                            </Link>
                            <div class="flex items-center gap-2">
                                <Link :href="route('evenements.edit', row.id)" class="rounded-2xl bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">
                                    Modifier
                                </Link>
                                <button
                                    type="button"
                                    class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                    @click="openDeleteModal(row)"
                                >
                                    ...
                                </button>
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            <section v-if="rows.length === 0" class="rounded-[2rem] border border-dashed border-slate-300 bg-white p-12 text-center shadow-sm">
                <p class="text-lg font-semibold text-slate-900">Aucun evenement ne correspond a vos filtres.</p>
                <p class="mt-2 text-sm text-slate-500">Essayez une autre recherche ou reinitialisez les criteres.</p>
            </section>

            <section v-if="evenements?.links?.length" class="rounded-[2rem] bg-white px-5 py-4 shadow-sm ring-1 ring-slate-200">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <p class="text-sm text-slate-500">
                        Affichage de {{ evenements.from ?? 0 }} a {{ evenements.to ?? 0 }} sur {{ evenements.total ?? 0 }} resultats
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="(link, index) in evenements.links"
                            :key="`${index}-${link.label}`"
                            type="button"
                            class="rounded-xl px-4 py-2 text-sm font-medium transition"
                            :class="link.active ? 'bg-[#0066B3] text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                            :disabled="!link.url"
                            @click="link.url && router.visit(link.url, { preserveScroll: true, preserveState: true })"
                        >
                            <span v-html="link.label" />
                        </button>
                    </div>
                </div>
            </section>
        </div>

        <Modal
            :show="showDeleteModal"
            title="Supprimer cet evenement"
            :description="`L'evenement ${evenementToDelete?.titre ?? ''} sera archive via une suppression logique.`"
            confirm-text="Supprimer"
            cancel-text="Annuler"
            :danger="true"
            @close="showDeleteModal = false"
            @confirm="deleteEvenement"
        />
    </AppLayout>
</template>