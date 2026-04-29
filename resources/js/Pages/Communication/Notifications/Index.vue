<script setup>
import DataTable from '@/Components/DataTable.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { reactive, ref, watch } from 'vue';

const props = defineProps({
    notifications: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    canaux: {
        type: Array,
        default: () => [],
    },
    statuts: {
        type: Array,
        default: () => [],
    },
    users: {
        type: Array,
        default: () => [],
    },
});

const columns = [
    { key: 'destinataire', label: 'Destinataire' },
    { key: 'titre', label: 'Titre' },
    { key: 'canal', label: 'Canal' },
    { key: 'statut', label: 'Statut' },
    { key: 'date_envoi', label: 'Date' },
];

const showForm = ref(false);

const localFilters = reactive({
    canal: props.filters.canal ?? '',
    statut: props.filters.statut ?? '',
});

const form = useForm({
    user_id: props.users[0]?.id ?? '',
    titre: '',
    message: '',
    canal: '',
});

let debounceTimer;

watch(localFilters, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(route('notifications.index'), localFilters, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 250);
}, { deep: true });

const submit = () => {
    form.post(route('notifications.send'), {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    });
};

const iconForCanal = (canal) => ({
    email: '✉️',
    sms: '📱',
    push: '🔔',
}[canal] ?? 'ℹ️');

const formatDate = (value) => value
    ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
    : 'Non envoyé';
</script>

<template>
    <Head title="Notifications" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Notifications multi-canal</h1>
                    <p class="mt-1 text-sm text-slate-500">Suivi des notifications email, SMS et push.</p>
                </div>

                <button type="button" class="rounded-2xl bg-[#0066B3] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#005290]" @click="showForm = !showForm">
                    Envoyer notification
                </button>
            </div>
        </template>

        <div class="space-y-6">
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Filtre canal</label>
                        <select v-model="localFilters.canal" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option value="">Tous</option>
                            <option v-for="canal in canaux" :key="canal" :value="canal">{{ canal }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Filtre statut</label>
                        <select v-model="localFilters.statut" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option value="">Tous</option>
                            <option v-for="statut in statuts" :key="statut" :value="statut">{{ statut }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div v-if="showForm" class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Nouvelle notification manuelle</h2>
                <div class="mt-5 grid gap-4">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Destinataire</label>
                        <select v-model="form.user_id" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }} · {{ user.email }}</option>
                        </select>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Titre</label>
                            <input v-model="form.titre" type="text" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Canal</label>
                            <select v-model="form.canal" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                                <option value="">Automatique</option>
                                <option v-for="canal in canaux" :key="canal" :value="canal">{{ canal }}</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Message</label>
                        <textarea v-model="form.message" rows="4" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>
                </div>

                <div class="mt-5 flex justify-end">
                    <button type="button" class="rounded-2xl bg-[#00A651] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#008a45]" @click="submit">
                        Envoyer
                    </button>
                </div>
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <DataTable :columns="columns" :rows="notifications.data" :pagination="notifications" empty-message="Aucune notification n’a encore été envoyée.">
                    <template #cell-destinataire="{ row }">
                        <div>
                            <p class="font-semibold text-slate-900">{{ row.destinataire.name || 'Utilisateur' }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ row.destinataire.email || row.destinataire.telephone }}</p>
                        </div>
                    </template>

                    <template #cell-titre="{ row }">
                        <div>
                            <p class="font-semibold text-slate-900">{{ row.titre }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ row.message }}</p>
                        </div>
                    </template>

                    <template #cell-canal="{ row }">
                        <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                            <span>{{ iconForCanal(row.canal) }}</span>
                            <span>{{ row.canal }}</span>
                        </span>
                    </template>

                    <template #cell-statut="{ row }">
                        <StatusBadge :status="row.statut" />
                    </template>

                    <template #cell-date_envoi="{ row }">
                        <span>{{ formatDate(row.date_envoi) }}</span>
                    </template>
                </DataTable>
            </div>
        </div>
    </AppLayout>
</template>