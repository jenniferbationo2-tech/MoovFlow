<script setup>
import StatusBadge from '@/Components/StatusBadge.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    inscription: {
        type: Object,
        required: true,
    },
});

const formatDate = (value) => value
    ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
    : 'N/A';
</script>

<template>
    <Head :title="`Inscription #${inscription.id}`" />

    <AppLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Detail inscription</h1>
                <p class="mt-1 text-sm text-slate-500">Vue complete participant, paiement et presence.</p>
            </div>
        </template>

        <div class="grid gap-6 xl:grid-cols-[1.1fr,0.9fr]">
            <div class="space-y-6">
                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-slate-900">Participant</h2>
                        <StatusBadge :status="inscription.statut" />
                    </div>
                    <div class="mt-4 space-y-2 text-sm text-slate-700">
                        <p><strong>Nom :</strong> {{ inscription.participant.name }}</p>
                        <p><strong>Email :</strong> {{ inscription.participant.email }}</p>
                        <p><strong>Telephone :</strong> {{ inscription.participant.telephone || 'Non renseigne' }}</p>
                        <p><strong>Date inscription :</strong> {{ formatDate(inscription.date_inscription) }}</p>
                    </div>
                </section>

                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Evenement</h2>
                    <div class="mt-4 space-y-2 text-sm text-slate-700">
                        <p><strong>Titre :</strong> {{ inscription.evenement.titre }}</p>
                        <p><strong>Debut :</strong> {{ formatDate(inscription.evenement.date_debut) }}</p>
                        <p><strong>Fin :</strong> {{ formatDate(inscription.evenement.date_fin) }}</p>
                        <p><strong>Tarif :</strong> {{ inscription.tarif.nom }} - {{ inscription.tarif.montant }} XOF</p>
                    </div>
                </section>

                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-slate-900">Paiement</h2>
                        <StatusBadge :status="inscription.paiement?.statut || 'gratuit'" />
                    </div>
                    <div v-if="inscription.paiement" class="mt-4 space-y-2 text-sm text-slate-700">
                        <p><strong>Montant :</strong> {{ inscription.paiement.montant }} XOF</p>
                        <p><strong>Mode :</strong> {{ inscription.paiement.mode }}</p>
                        <p><strong>Reference :</strong> {{ inscription.paiement.reference || 'N/A' }}</p>
                        <div class="flex flex-wrap gap-3 pt-2">
                            <Link :href="inscription.paiement.show_url" class="rounded-xl bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200">
                                Voir paiement
                            </Link>
                            <a v-if="inscription.paiement.facture_url" :href="inscription.paiement.facture_url" target="_blank" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                                Telecharger facture
                            </a>
                        </div>
                    </div>
                    <p v-else class="mt-4 text-sm text-slate-500">Aucun paiement associe.</p>
                </section>
            </div>

            <div class="space-y-6">
                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">QR code</h2>
                    <div class="mt-4 flex items-center justify-center rounded-3xl bg-slate-50 p-6">
                        <img v-if="inscription.qr_code_url" :src="inscription.qr_code_url" alt="QR code" class="h-64 w-64" />
                        <p v-else class="text-sm text-slate-500">QR code indisponible.</p>
                    </div>
                </section>

                <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-slate-900">Presence</h2>
                        <StatusBadge :status="inscription.presence.statut" />
                    </div>
                    <div class="mt-4 space-y-2 text-sm text-slate-700">
                        <p><strong>Statut :</strong> {{ inscription.presence.statut }}</p>
                        <p><strong>Date scan :</strong> {{ formatDate(inscription.presence.scan_time) }}</p>
                        <p><strong>Scanne par :</strong> {{ inscription.presence.scanneur || 'N/A' }}</p>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>