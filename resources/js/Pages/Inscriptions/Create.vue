<script setup>
import InputError from '@/Components/InputError.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    evenement: {
        type: Object,
        required: true,
    },
    tarifs: {
        type: Array,
        default: () => [],
    },
    places: {
        type: Object,
        default: () => ({
            capacity: null,
            registered: 0,
            available: null,
        }),
    },
    participant: {
        type: Object,
        default: () => ({
            name: '',
            email: '',
            telephone: '',
        }),
    },
    modesPaiement: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    evenement_id: props.evenement.id,
    tarif_id: props.tarifs[0]?.id ?? '',
    participant_name: props.participant.name ?? '',
    participant_email: props.participant.email ?? '',
    participant_telephone: props.participant.telephone ?? '',
    mode: 'moov_money',
});

const selectedTarif = computed(() => props.tarifs.find((tarif) => tarif.id === form.tarif_id) ?? null);
const isPayant = computed(() => Number(selectedTarif.value?.montant ?? 0) > 0);

const submit = () => {
    form.post(route('inscriptions.store'));
};

const formatMoney = (value) => new Intl.NumberFormat('fr-FR').format(Number(value ?? 0));
</script>

<template>
    <Head :title="`Inscription - ${evenement.titre}`" />

    <AppLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">{{ evenement.titre }}</h1>
                <p class="mt-1 text-sm text-slate-500">Formulaire d inscription a l evenement.</p>
            </div>
        </template>

        <div class="grid gap-6 lg:grid-cols-[1.3fr,0.7fr]">
            <form class="space-y-6 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200" @submit.prevent="submit">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Choix du tarif</h2>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <label
                            v-for="tarif in tarifs"
                            :key="tarif.id"
                            class="cursor-pointer rounded-2xl border p-5 transition"
                            :class="Number(form.tarif_id) === tarif.id ? 'border-[#0066B3] bg-[#0066B3]/5' : 'border-slate-200 bg-white hover:border-slate-300'"
                        >
                            <input v-model="form.tarif_id" class="hidden" type="radio" :value="tarif.id" />
                            <p class="text-base font-semibold text-slate-900">{{ tarif.nom }}</p>
                            <p class="mt-2 text-sm text-slate-500">{{ formatMoney(tarif.montant) }} XOF</p>
                        </label>
                    </div>
                    <InputError class="mt-2" :message="form.errors.tarif_id" />
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Nom complet</label>
                        <input v-model="form.participant_name" type="text" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                        <InputError class="mt-2" :message="form.errors.participant_name" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
                        <input v-model="form.participant_email" type="email" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                        <InputError class="mt-2" :message="form.errors.participant_email" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Telephone</label>
                        <input v-model="form.participant_telephone" type="text" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                        <InputError class="mt-2" :message="form.errors.participant_telephone" />
                    </div>
                    <div v-if="isPayant">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Mode de paiement</label>
                        <select v-model="form.mode" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option v-for="mode in modesPaiement" :key="mode.value" :value="mode.value">{{ mode.label }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="rounded-2xl bg-[#0066B3] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#005290]" :disabled="form.processing">
                        S inscrire
                    </button>
                </div>
            </form>

            <div class="space-y-6">
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Places disponibles</h2>
                    <p class="mt-3 text-3xl font-bold text-[#0066B3]">
                        {{ places.available ?? 'Illimite' }}
                    </p>
                    <p class="mt-2 text-sm text-slate-500">
                        {{ places.capacity ? `${places.registered} inscrits sur ${places.capacity} places` : 'Capacite non definie pour ce lieu' }}
                    </p>
                </div>

                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Resume evenement</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ evenement.description || 'Aucune description disponible.' }}</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>