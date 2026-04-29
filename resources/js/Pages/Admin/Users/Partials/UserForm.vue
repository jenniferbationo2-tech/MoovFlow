<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        required: true,
    },
    user: {
        type: Object,
        default: null,
    },
    roles: {
        type: Array,
        default: () => [],
    },
    submitUrl: {
        type: String,
        required: true,
    },
    method: {
        type: String,
        default: 'post',
    },
    submitLabel: {
        type: String,
        required: true,
    },
});

const form = useForm({
    nom: props.user?.nom ?? '',
    prenom: props.user?.prenom ?? '',
    email: props.user?.email ?? '',
    telephone: props.user?.telephone ?? '',
    password: '',
    role: props.user?.role ?? props.roles[0]?.name ?? '',
    is_active: props.user?.is_active ?? true,
});

const submit = () => {
    if (props.method === 'patch') {
        form.patch(props.submitUrl);
        return;
    }

    form.post(props.submitUrl);
};
</script>

<template>
    <Head :title="title" />

    <AppLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">{{ title }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ subtitle }}</p>
            </div>
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <div class="grid gap-6 xl:grid-cols-[1.1fr,0.9fr]">
                <div class="space-y-6 rounded-[1.75rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-start justify-between gap-4 rounded-[1.5rem] bg-gradient-to-r from-[#0066B3] to-[#004A82] p-5 text-white">
                        <div>
                            <p class="text-xs uppercase tracking-[0.28em] text-white/70">MoovFlow</p>
                            <h2 class="mt-2 text-xl font-semibold">Fiche utilisateur</h2>
                            <p class="mt-2 text-sm text-white/80">Les permissions seront automatiquement appliquees selon le role choisi.</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 px-4 py-3 text-sm font-medium">
                            Role requis
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nom</label>
                            <input v-model="form.nom" type="text" class="w-full rounded-2xl border-slate-300 bg-slate-50/50 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <p v-if="form.errors.nom" class="mt-2 text-sm text-red-600">{{ form.errors.nom }}</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Prenom</label>
                            <input v-model="form.prenom" type="text" class="w-full rounded-2xl border-slate-300 bg-slate-50/50 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <p v-if="form.errors.prenom" class="mt-2 text-sm text-red-600">{{ form.errors.prenom }}</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Courriel</label>
                            <input v-model="form.email" type="email" class="w-full rounded-2xl border-slate-300 bg-slate-50/50 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <p v-if="form.errors.email" class="mt-2 text-sm text-red-600">{{ form.errors.email }}</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Telephone</label>
                            <input v-model="form.telephone" type="text" class="w-full rounded-2xl border-slate-300 bg-slate-50/50 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <p v-if="form.errors.telephone" class="mt-2 text-sm text-red-600">{{ form.errors.telephone }}</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                {{ method === 'patch' ? 'Nouveau mot de passe' : 'Mot de passe' }}
                            </label>
                            <input v-model="form.password" type="password" class="w-full rounded-2xl border-slate-300 bg-slate-50/50 focus:border-[#0066B3] focus:ring-[#0066B3]">
                            <p v-if="form.errors.password" class="mt-2 text-sm text-red-600">{{ form.errors.password }}</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Role</label>
                            <select v-model="form.role" class="w-full rounded-2xl border-slate-300 bg-slate-50/50 focus:border-[#0066B3] focus:ring-[#0066B3]">
                                <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                            </select>
                            <p v-if="form.errors.role" class="mt-2 text-sm text-red-600">{{ form.errors.role }}</p>
                        </div>
                    </div>

                    <label class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3">
                        <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300 text-[#0066B3] focus:ring-[#0066B3]">
                        <span class="text-sm font-medium text-slate-700">Compte actif</span>
                    </label>
                </div>

                <div class="space-y-6 rounded-[1.75rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Attribution automatique</h2>
                        <p class="mt-1 text-sm text-slate-500">Le role selectionne determine automatiquement les acces et capacites du compte.</p>
                    </div>

                    <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="rounded-2xl bg-white p-4 shadow-sm">
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Acces centralises</p>
                                <p class="mt-2 text-sm text-slate-700">Les droits proviennent du role configure dans le seeder des permissions.</p>
                            </div>
                            <div class="rounded-2xl bg-white p-4 shadow-sm">
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Experience simplifiee</p>
                                <p class="mt-2 text-sm text-slate-700">Aucune case de permission individuelle n'est necessaire dans ce formulaire.</p>
                            </div>
                        </div>

                        <div class="mt-5 rounded-2xl border border-dashed border-[#0066B3]/20 bg-white/80 p-4">
                            <p class="text-sm font-semibold text-slate-800">Role actuellement selectionne</p>
                            <p class="mt-2 text-2xl font-bold text-[#0066B3]">{{ form.role || 'Aucun role choisi' }}</p>
                            <p class="mt-2 text-sm text-slate-500">Roles disponibles : admin, responsable_dcirp, organisateur, participant, intervenant, benevole, jury.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="rounded-2xl bg-[#0066B3] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#005290]" :disabled="form.processing">
                    {{ submitLabel }}
                </button>
                <Link :href="route('admin.users.index')" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Annuler
                </Link>
            </div>
        </form>
    </AppLayout>
</template>