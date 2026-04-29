<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    evenement: {
        type: Object,
        required: true,
    },
    rapport: {
        type: Object,
        required: true,
    },
});

const exportUrl = (format) => route('rapports.export', {
    evenement: props.evenement.id,
    type: 'rse',
    format,
});

const formatCurrency = (value) => new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    maximumFractionDigits: 0,
}).format(Number(value ?? 0));

const progressWidth = (value, max = 100) => `${Math.min(100, max > 0 ? (Number(value ?? 0) / max) * 100 : 0)}%`;
</script>

<template>
    <Head :title="`Rapport RSE - ${evenement.titre}`" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Rapport RSE</h1>
                    <p class="text-sm text-slate-500">{{ evenement.titre }}</p>
                </div>
                <div class="flex gap-2">
                    <a :href="exportUrl('pdf')" class="rounded-2xl bg-[#0066B3] px-4 py-3 text-sm font-semibold text-white hover:bg-[#005290]">Exporter PDF</a>
                    <a :href="exportUrl('excel')" class="rounded-2xl bg-[#00A651] px-4 py-3 text-sm font-semibold text-white hover:bg-[#008a45]">Exporter Excel</a>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <div class="grid gap-6 xl:grid-cols-[1.15fr,0.85fr]">
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Comparaison objectifs vs realise</h2>
                    <div class="mt-6 space-y-5">
                        <div>
                            <div class="mb-2 flex items-center justify-between text-sm text-slate-600">
                                <span>Impact social</span>
                                <span>{{ rapport.social.score }}%</span>
                            </div>
                            <div class="h-3 rounded-full bg-slate-100"><div class="h-3 rounded-full bg-[#0066B3]" :style="{ width: progressWidth(rapport.social.score) }" /></div>
                        </div>
                        <div>
                            <div class="mb-2 flex items-center justify-between text-sm text-slate-600">
                                <span>Impact environnemental</span>
                                <span>{{ rapport.environmental.score }}%</span>
                            </div>
                            <div class="h-3 rounded-full bg-slate-100"><div class="h-3 rounded-full bg-[#00A651]" :style="{ width: progressWidth(rapport.environmental.score) }" /></div>
                        </div>
                        <div>
                            <div class="mb-2 flex items-center justify-between text-sm text-slate-600">
                                <span>Impact economique</span>
                                <span>{{ rapport.economic.score }}%</span>
                            </div>
                            <div class="h-3 rounded-full bg-slate-100"><div class="h-3 rounded-full bg-[#F59E0B]" :style="{ width: progressWidth(rapport.economic.score) }" /></div>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Score global RSE</h2>
                    <div class="mt-6 flex items-center justify-center">
                        <div class="flex h-52 w-52 items-center justify-center rounded-full border-[18px] border-[#00A651]/20">
                            <div class="flex h-36 w-36 items-center justify-center rounded-full bg-[#00A651]/10 text-center">
                                <div>
                                    <p class="text-4xl font-bold text-[#008a45]">{{ rapport.score_global }}%</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-600">Score global</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-3">
                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Impact social</h2>
                    <div class="mt-5 space-y-4">
                        <div>
                            <div class="mb-2 flex justify-between text-sm text-slate-600"><span>Beneficiaires directs</span><span>{{ rapport.social.nb_beneficiaires_directs }}</span></div>
                            <div class="h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-[#0066B3]" :style="{ width: progressWidth(rapport.social.nb_beneficiaires_directs, 500) }" /></div>
                        </div>
                        <div>
                            <div class="mb-2 flex justify-between text-sm text-slate-600"><span>Beneficiaires indirects</span><span>{{ rapport.social.nb_beneficiaires_indirects }}</span></div>
                            <div class="h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-[#0066B3]" :style="{ width: progressWidth(rapport.social.nb_beneficiaires_indirects, 1000) }" /></div>
                        </div>
                        <div>
                            <div class="mb-2 flex justify-between text-sm text-slate-600"><span>Associations</span><span>{{ rapport.social.nb_associations }}</span></div>
                            <div class="h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-[#0066B3]" :style="{ width: progressWidth(rapport.social.nb_associations, 20) }" /></div>
                        </div>
                        <div>
                            <div class="mb-2 flex justify-between text-sm text-slate-600"><span>Taux femmes beneficiaires</span><span>{{ rapport.social.taux_femmes }}%</span></div>
                            <div class="h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-[#0066B3]" :style="{ width: progressWidth(rapport.social.taux_femmes) }" /></div>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Impact environnemental</h2>
                    <div class="mt-5 space-y-4">
                        <div>
                            <div class="mb-2 flex justify-between text-sm text-slate-600"><span>Score environnemental</span><span>{{ rapport.environmental.score_environnemental }}%</span></div>
                            <div class="h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-[#00A651]" :style="{ width: progressWidth(rapport.environmental.score_environnemental) }" /></div>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
                            Le score environnemental consolide les objectifs saisis sur les evenements et permet un suivi lisible des engagements.
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Impact economique</h2>
                    <div class="mt-5 space-y-4">
                        <div>
                            <div class="mb-2 flex justify-between text-sm text-slate-600"><span>Montants collectes</span><span>{{ formatCurrency(rapport.economic.montants_collectes) }}</span></div>
                            <div class="h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-[#F59E0B]" :style="{ width: progressWidth(rapport.economic.montants_collectes, 1000000) }" /></div>
                        </div>
                        <div>
                            <div class="mb-2 flex justify-between text-sm text-slate-600"><span>Retombees partenaires</span><span>{{ formatCurrency(rapport.economic.retombees_partenaires) }}</span></div>
                            <div class="h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-[#F59E0B]" :style="{ width: progressWidth(rapport.economic.retombees_partenaires, 1000000) }" /></div>
                        </div>
                        <div>
                            <div class="mb-2 flex justify-between text-sm text-slate-600"><span>Emplois crees</span><span>{{ rapport.economic.nb_emplois_crees }}</span></div>
                            <div class="h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-[#F59E0B]" :style="{ width: progressWidth(rapport.economic.nb_emplois_crees, 30) }" /></div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>