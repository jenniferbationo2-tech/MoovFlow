<script setup>
import Modal from '@/Components/Modal.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    evenement: {
        type: Object,
        required: true,
    },
    documents: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    types: {
        type: Array,
        default: () => [],
    },
    accesOptions: {
        type: Array,
        default: () => [],
    },
});

const showUploadModal = ref(false);
const localFilters = ref({
    type: props.filters.type ?? '',
    acces: props.filters.acces ?? '',
});

const form = useForm({
    titre: '',
    type: props.types[0] ?? 'presentation',
    acces: props.accesOptions[0] ?? 'public',
    fichier: null,
});

const filteredDocuments = computed(() => props.documents.filter((document) => {
    const typeMatch = !localFilters.value.type || document.type === localFilters.value.type;
    const accesMatch = !localFilters.value.acces || document.acces === localFilters.value.acces;

    return typeMatch && accesMatch;
}));

const setFile = (event) => {
    form.fichier = event.target.files[0];
};

const submit = () => {
    form.post(route('communication.documents.store', props.evenement.id), {
        forceFormData: true,
        onSuccess: () => {
            showUploadModal.value = false;
            form.reset();
        },
    });
};

const removeDocument = (documentId) => {
    router.delete(route('communication.documents.destroy', {
        evenement: props.evenement.id,
        document: documentId,
    }));
};

const formatDate = (value) => value
    ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
    : 'Date inconnue';

const formatSize = (size) => {
    if (!size) {
        return '0 Ko';
    }

    if (size >= 1024 * 1024) {
        return `${(size / (1024 * 1024)).toFixed(1)} Mo`;
    }

    return `${Math.ceil(size / 1024)} Ko`;
};
</script>

<template>
    <Head :title="`Documents - ${evenement.titre}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Bibliothèque documentaire</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ evenement.titre }} · centralisation des fichiers partagés.</p>
                </div>

                <button type="button" class="rounded-2xl bg-[#0066B3] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#005290]" @click="showUploadModal = true">
                    Uploader document
                </button>
            </div>
        </template>

        <div class="space-y-6">
            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Filtre type</label>
                        <select v-model="localFilters.type" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option value="">Tous les types</option>
                            <option v-for="type in types" :key="type" :value="type">{{ type }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Filtre accès</label>
                        <select v-model="localFilters.acces" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <option value="">Tous les accès</option>
                            <option v-for="acces in accesOptions" :key="acces" :value="acces">{{ acces }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                <div
                    v-for="document in filteredDocuments"
                    :key="document.id"
                    class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">{{ document.titre }}</h2>
                            <p class="mt-1 text-sm text-slate-500">Ajouté le {{ formatDate(document.date_upload) }}</p>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ document.type }}</span>
                        <StatusBadge :status="document.acces === 'public' ? 'publie' : (document.acces === 'participants' ? 'attente' : 'en_cours')" />
                    </div>

                    <dl class="mt-5 space-y-2 text-sm text-slate-600">
                        <div class="flex items-center justify-between gap-4">
                            <dt>Taille</dt>
                            <dd class="font-semibold text-slate-900">{{ formatSize(document.taille) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt>Accès</dt>
                            <dd class="font-semibold text-slate-900">{{ document.acces }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt>Ajouté par</dt>
                            <dd class="font-semibold text-slate-900">{{ document.uploaded_by || 'Utilisateur système' }}</dd>
                        </div>
                    </dl>

                    <div class="mt-5 flex flex-wrap gap-3">
                        <a :href="document.download_url" class="rounded-xl bg-[#00A651] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#008a45]">
                            Télécharger
                        </a>
                        <button type="button" class="rounded-xl bg-red-100 px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-200" @click="removeDocument(document.id)">
                            Supprimer
                        </button>
                    </div>
                </div>

                <div v-if="filteredDocuments.length === 0" class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">
                    Aucun document ne correspond aux filtres sélectionnés.
                </div>
            </div>
        </div>

        <Modal :show="showUploadModal" max-width="lg" @close="showUploadModal = false">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-slate-900">Uploader un document</h2>
                <p class="mt-1 text-sm text-slate-500">Ajoutez un fichier avec ses métadonnées et son niveau d’accès.</p>

                <div class="mt-5 space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Titre</label>
                        <input v-model="form.titre" type="text" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]" />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Type</label>
                            <select v-model="form.type" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                                <option v-for="type in types" :key="type" :value="type">{{ type }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Accès</label>
                            <select v-model="form.acces" class="w-full rounded-2xl border-slate-300 focus:border-[#0066B3] focus:ring-[#0066B3]">
                                <option v-for="acces in accesOptions" :key="acces" :value="acces">{{ acces }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-dashed border-slate-300 p-6 text-center">
                        <input type="file" class="mx-auto block text-sm text-slate-600" @change="setFile" />
                        <p class="mt-2 text-sm text-slate-500">Glissez-déposez votre fichier ou utilisez le sélecteur.</p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700" @click="showUploadModal = false">
                        Annuler
                    </button>
                    <button type="button" class="rounded-2xl bg-[#0066B3] px-4 py-2 text-sm font-semibold text-white" @click="submit">
                        Enregistrer
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>