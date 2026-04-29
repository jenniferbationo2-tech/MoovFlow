<script setup>
import StatusBadge from '@/Components/StatusBadge.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    paiement: {
        type: Object,
        required: true,
    },
});

const formatDate = (value) => value
    ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
    : 'N/A';
</script>

<template>
    <Head :title="`Paiement #${paiement.id}`" />

    <AppLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Paiement</h1>
                <p class="mt-1 text-sm text-slate-500">Detail du paiement et de la facture.</p>
            </div>
        </template>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900">Transaction</h2>
                    <StatusBadge :status="paiement.statut" />
                </div>
                <div class="mt-4 space-y-2 text-sm text-slate-700">
                    <p><strong>Montant :</strong> {{ paiement.montant }} XOF</p>
                    <p><strong>Mode :</strong> {{ paiement.mode }}</p>
                    <p><strong>Reference :</strong> {{ paiement.reference || 'N/A' }}</p>
                    <p><strong>Date :</strong> {{ formatDate(paiement.created_at) }}</p>
                </div>
            </section>

            <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Inscription associee</h2>
                <div class="mt-4 space-y-2 text-sm text-slate-700">
                    <p><strong>Participant :</strong> {{ paiement.inscription.participant }}</p>
                    <p><strong>Email :</strong> {{ paiement.inscription.email }}</p>
                    <p><strong>Evenement :</strong> {{ paiement.inscription.evenement }}</p>
                    <p><strong>Tarif :</strong> {{ paiement.inscription.tarif }}</p>
                </div>
                <div class="mt-4 flex gap-3">
                    <Link :href="route('inscriptions.show', paiement.inscription.id)" class="rounded-xl bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200">
                        Voir inscription
                    </Link>
                    <a v-if="paiement.facture?.url" :href="paiement.facture.url" target="_blank" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                        Telecharger facture
                    </a>
                </div>
            </section>
        </div>
    </AppLayout>
</template>