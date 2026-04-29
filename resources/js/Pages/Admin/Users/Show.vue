<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

const formatDate = (value) => value
    ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
    : 'N/A';

const toggleActive = () => {
    router.patch(route('admin.users.toggle', props.user.id), {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Utilisateur ${user.name}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">{{ user.name }}</h1>
                    <p class="mt-1 text-sm text-slate-500">Profil, habilitations et historique d activite.</p>
                </div>

                <div class="flex gap-3">
                    <button type="button" class="rounded-2xl bg-amber-100 px-4 py-3 text-sm font-semibold text-amber-700 transition hover:bg-amber-200" @click="toggleActive">
                        {{ user.is_active ? 'Desactiver' : 'Activer' }}
                    </button>
                    <Link :href="route('admin.users.edit', user.id)" class="rounded-2xl bg-[#0066B3] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#005290]">
                        Modifier
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <div class="grid gap-6 lg:grid-cols-[0.95fr,1.05fr]">
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Profil</h2>
                    <dl class="mt-5 space-y-4">
                        <div>
                            <dt class="text-sm font-semibold text-slate-500">Nom complet</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ user.name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-semibold text-slate-500">Email</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ user.email }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-semibold text-slate-500">Telephone</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ user.telephone || 'Non renseigne' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-semibold text-slate-500">Date de creation</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ formatDate(user.created_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-semibold text-slate-500">Statut</dt>
                            <dd class="mt-1">
                                <span :class="user.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700'" class="rounded-full px-3 py-1 text-xs font-semibold">
                                    {{ user.is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="space-y-6">
                    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <h2 class="text-lg font-semibold text-slate-900">Roles actuels</h2>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span v-for="role in user.roles" :key="role" class="rounded-full bg-[#0066B3]/10 px-3 py-1 text-xs font-semibold text-[#0066B3]">
                                {{ role }}
                            </span>
                        </div>
                    </div>

                    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <h2 class="text-lg font-semibold text-slate-900">Permissions effectives</h2>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span v-for="permission in user.permissions" :key="permission" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                {{ permission }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Historique d activite</h2>
                <div class="mt-6 space-y-4">
                    <div v-for="activity in user.activity" :key="activity.id" class="rounded-2xl border border-slate-200 p-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="font-semibold text-slate-900">{{ activity.description }}</p>
                                <p class="mt-1 text-xs uppercase tracking-wide text-slate-500">
                                    {{ activity.log_name || activity.event || 'audit' }}
                                </p>
                            </div>
                            <span class="text-sm text-slate-500">{{ formatDate(activity.created_at) }}</span>
                        </div>
                        <pre class="mt-4 overflow-x-auto rounded-2xl bg-slate-50 p-4 text-xs text-slate-700">{{ JSON.stringify(activity.properties, null, 2) }}</pre>
                    </div>
                    <p v-if="user.activity.length === 0" class="text-sm text-slate-500">Aucune activite recente pour cet utilisateur.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>