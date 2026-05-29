<script setup>
import { ref, computed } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { $confirm } from '@/plugins/confirm'

const props = defineProps({
    users:            Object,
    stats:            Object,
    rolesDisponibles: Array,
    filters:          Object,
})


const recherche    = ref(props.filters?.search ?? '')
const filtreRole   = ref(props.filters?.role ?? '')
const filtreStatut = ref(props.filters?.statut ?? '')

const filtrer = () => {
    router.get('/admin/users', {
        search: recherche.value || undefined,
        role:   filtreRole.value || undefined,
        statut: filtreStatut.value || undefined,
    }, { preserveState: true, preserveScroll: true })
}

const reinitialiser = () => {
    recherche.value = ''
    filtreRole.value = ''
    filtreStatut.value = ''
    router.get('/admin/users')
}

 
const labelRole = (role) => ({
    admin:             'Administrateur',
    responsable_dcirp: 'Responsable dCIRP',
    organisateur:      'Organisateur',
    participant:       'Participant',
    intervenant:       'Intervenant',
    benevole:          'Bénévole',
    jury:              'Jury',
}[role] || role)

const couleurRole = (role) => ({
    admin:             'bg-red-50 text-red-700',
    responsable_dcirp: 'bg-purple-50 text-purple-700',
    organisateur:      'bg-orange-50 text-orange-700',
    participant:       'bg-emerald-50 text-emerald-700',
    intervenant:       'bg-blue-50 text-blue-700',
    benevole:          'bg-amber-50 text-amber-700',
    jury:              'bg-violet-50 text-violet-700',
}[role] || 'bg-slate-50 text-slate-700')

const formaterDate = (d) => {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'short', year: 'numeric'
    })
}

const initiales = (u) => `${u.prenom?.[0] ?? ''}${u.nom?.[0] ?? ''}`.toUpperCase()

const statutUtilisateur = (u) => {
    if (!u.is_active) return { label: 'Désactivé',  text: 'text-slate-500', dot: 'bg-slate-400' }
    if (u.bloque_jusqu_a && new Date(u.bloque_jusqu_a) > new Date()) {
        return { label: 'Bloqué', text: 'text-red-700', dot: 'bg-red-500' }
    }
    return { label: 'Actif', text: 'text-emerald-700', dot: 'bg-emerald-500' }
}


const modalCreationOuvert = ref(false)

const formCreation = useForm({
    nom:       '',
    prenom:    '',
    email:     '',
    telephone: '',
    password:  '',
    role:      'organisateur',
})

const ouvrirModalCreation = () => {
    formCreation.reset()
    formCreation.role = 'organisateur'
    modalCreationOuvert.value = true
}

const creerUtilisateur = () => {
    formCreation.post('/admin/users', {
        onSuccess: () => modalCreationOuvert.value = false,
        preserveScroll: true,
    })
}


const userResetMdp = ref(null)
const formResetMdp = useForm({ password: '' })

const ouvrirModalResetMdp = (user) => {
    userResetMdp.value = user
    formResetMdp.reset()
}

const confirmerResetMdp = () => {
    formResetMdp.post(`/admin/users/${userResetMdp.value.id}/reset-password`, {
        onSuccess: () => userResetMdp.value = null,
        preserveScroll: true,
    })
}

const toggleActif = (user) => {
    const action = user.is_active ? 'désactiver' : 'activer'
    if (confirm(`Voulez-vous vraiment ${action} le compte de ${user.prenom} ${user.nom} ?`)) {
        router.patch(`/admin/users/${user.id}/toggle-active`)
    }
}

const debloquer = (user) => {
    if (await $confirm(`Débloquer le compte de ${user.prenom} ${user.nom} ?`)) {
        router.post(`/admin/users/${user.id}/debloquer`)
    }
}
</script>

<template>
    <DashboardLayout>

        <!-- ── HEADER ── -->
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl font-extrabold text-text-main">
                    Gestion des Utilisateurs
                </h1>
                <p class="mt-1 text-sm text-text-sub">
                    Création et administration des comptes Moov dCIRP
                </p>
            </div>
            <button @click="ouvrirModalCreation"
                    class="inline-flex items-center gap-2 rounded-lg bg-moov-noir px-5 py-3 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nouvel Utilisateur
            </button>
        </div>

        <!-- ── KPIs PAR RÔLE ── -->
        <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Total</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-moov-blue">{{ stats.total }}</p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Admins</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-red-600">{{ stats.admin }}</p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Resp. dCIRP</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-purple-600">{{ stats.responsable_dcirp }}</p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Organisateurs</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-orange-600">{{ stats.organisateur }}</p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Participants</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-emerald-600">{{ stats.participant }}</p>
            </div>
            <div class="rounded-xl bg-card p-4 shadow-card">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Inactifs</p>
                <p class="mt-2 font-display text-2xl font-extrabold text-slate-500">{{ stats.inactifs }}</p>
            </div>
        </div>

        <!-- ── TABLEAU ── -->
        <div class="overflow-hidden rounded-xl bg-card shadow-card">
            <div class="border-b border-border-soft p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-sm font-bold text-text-main">Liste des utilisateurs</h2>
                    <div class="ml-auto flex flex-wrap items-center gap-2">
                        <select v-model="filtreRole" @change="filtrer"
                                class="rounded-lg border border-border-soft px-3 py-1.5 text-xs font-semibold outline-none focus:border-moov-blue">
                            <option value="">Tous les rôles</option>
                            <option v-for="r in rolesDisponibles" :key="r" :value="r">
                                {{ labelRole(r) }}
                            </option>
                        </select>
                        <select v-model="filtreStatut" @change="filtrer"
                                class="rounded-lg border border-border-soft px-3 py-1.5 text-xs font-semibold outline-none focus:border-moov-blue">
                            <option value="">Tous les statuts</option>
                            <option value="actif">Actifs</option>
                            <option value="bloque">Bloqués</option>
                            <option value="desactive">Désactivés</option>
                        </select>
                        <input v-model="recherche" @keyup.enter="filtrer"
                               type="search" placeholder="Nom, email, téléphone..."
                               class="w-56 rounded-lg border border-border-soft px-3 py-1.5 text-xs outline-none focus:border-moov-blue"/>
                        <button v-if="recherche || filtreRole || filtreStatut"
                                @click="reinitialiser"
                                class="text-xs font-bold text-moov-orange hover:underline">
                            Réinitialiser
                        </button>
                    </div>
                </div>
            </div>

            <table class="min-w-full divide-y divide-border-soft text-sm">
                <thead class="bg-page-bg/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Utilisateur</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Contact</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Rôle</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Statut</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-sub">Créé le</th>
                        <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-text-sub">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-soft bg-card">
                    <tr v-for="u in users.data" :key="u.id"
                        class="transition hover:bg-page-bg/50">
                        <!-- Utilisateur -->
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-moov-blue text-xs font-bold text-white">
                                    {{ initiales(u) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-bold text-text-main">{{ u.prenom }} {{ u.nom }}</p>
                                    <p class="text-xs text-text-muted">#{{ u.id }}</p>
                                </div>
                            </div>
                        </td>

                        <!-- Contact -->
                        <td class="px-4 py-3">
                            <p class="text-text-main">{{ u.email }}</p>
                            <p v-if="u.telephone" class="text-xs text-text-sub">{{ u.telephone }}</p>
                        </td>

                        <!-- Rôle -->
                        <td class="px-4 py-3">
                            <span v-for="r in u.roles" :key="r"
                                  :class="['inline-block rounded px-2.5 py-0.5 text-xs font-bold uppercase tracking-wider', couleurRole(r)]">
                                {{ labelRole(r) }}
                            </span>
                            <span v-if="!u.roles.length"
                                  class="rounded bg-slate-100 px-2.5 py-0.5 text-xs font-bold uppercase tracking-wider text-slate-500">
                                Aucun rôle
                            </span>
                        </td>

                        <!-- Statut -->
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1.5">
                                <span :class="['h-1.5 w-1.5 rounded-full', statutUtilisateur(u).dot]"/>
                                <span :class="['text-xs font-bold uppercase tracking-wider', statutUtilisateur(u).text]">
                                    {{ statutUtilisateur(u).label }}
                                </span>
                            </span>
                            <p v-if="u.tentatives_connexion > 0 && statutUtilisateur(u).label === 'Bloqué'"
                               class="mt-0.5 text-[10px] text-red-600">
                                {{ u.tentatives_connexion }} tentative(s)
                            </p>
                        </td>

                        <!-- Créé le -->
                        <td class="px-4 py-3 text-text-sub">
                            {{ formaterDate(u.created_at) }}
                        </td>

                        <!-- Actions -->
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <!-- Débloquer (si bloqué) -->
                                <button v-if="u.bloque_jusqu_a && new Date(u.bloque_jusqu_a) > new Date()"
                                        @click="debloquer(u)"
                                        class="rounded p-1.5 text-text-sub transition hover:bg-blue-50 hover:text-blue-600"
                                        title="Débloquer">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                                    </svg>
                                </button>

                                <!-- Reset MDP -->
                                <button @click="ouvrirModalResetMdp(u)"
                                        class="rounded p-1.5 text-text-sub transition hover:bg-amber-50 hover:text-amber-600"
                                        title="Réinitialiser le mot de passe">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                    </svg>
                                </button>

                                <!-- Activer/Désactiver -->
                                <button @click="toggleActif(u)"
                                        :class="['rounded p-1.5 transition',
                                            u.is_active
                                                ? 'text-text-sub hover:bg-red-50 hover:text-red-600'
                                                : 'text-text-sub hover:bg-emerald-50 hover:text-emerald-600']"
                                        :title="u.is_active ? 'Désactiver' : 'Activer'">
                                    <svg v-if="u.is_active" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                    </svg>
                                    <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Vide -->
            <div v-if="!users.data?.length" class="px-6 py-12 text-center">
                <p class="font-bold text-text-main">Aucun utilisateur trouvé</p>
                <p class="mt-1 text-sm text-text-sub">
                    {{ recherche || filtreRole || filtreStatut
                        ? 'Aucun résultat avec ces filtres.'
                        : 'Commencez par créer un utilisateur.' }}
                </p>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="users.links?.length > 3" class="mt-6 flex justify-center gap-1">
            <template v-for="link in users.links" :key="link.label">
                <Link v-if="link.url" :href="link.url" v-html="link.label"
                      :class="['rounded-lg px-3 py-1.5 text-sm font-semibold transition',
                          link.active
                              ? 'bg-moov-blue text-white'
                              : 'border border-border-soft bg-white text-text-sub hover:border-moov-blue/30']"/>
                <span v-else v-html="link.label"
                      class="rounded-lg border border-border-soft bg-white px-3 py-1.5 text-sm text-text-muted"/>
            </template>
        </div>

        <!-- ════════════════════════════════════════ -->
        <!--   MODALE CRÉATION UTILISATEUR             -->
        <!-- ════════════════════════════════════════ -->
        <div v-if="modalCreationOuvert"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
             @click.self="modalCreationOuvert = false">
            <div class="w-full max-w-xl rounded-xl bg-card shadow-2xl">
                <div class="border-b border-border-soft p-5">
                    <h3 class="font-display text-lg font-extrabold text-text-main">
                        Créer un nouvel utilisateur
                    </h3>
                    <p class="mt-1 text-sm text-text-sub">
                        Le compte sera actif immédiatement après création
                    </p>
                </div>

                <form @submit.prevent="creerUtilisateur" class="space-y-4 p-5">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Nom *
                            </label>
                            <input v-model="formCreation.nom" type="text" required
                                   placeholder="OUEDRAOGO"
                                   class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                            <p v-if="formCreation.errors.nom" class="mt-1 text-xs text-red-600">{{ formCreation.errors.nom }}</p>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Prénom *
                            </label>
                            <input v-model="formCreation.prenom" type="text" required
                                   placeholder="Aminata"
                                   class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                            <p v-if="formCreation.errors.prenom" class="mt-1 text-xs text-red-600">{{ formCreation.errors.prenom }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Email *
                            </label>
                            <input v-model="formCreation.email" type="email" required
                                   placeholder="aminata@moov.bf"
                                   class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                            <p v-if="formCreation.errors.email" class="mt-1 text-xs text-red-600">{{ formCreation.errors.email }}</p>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                Téléphone
                            </label>
                            <input v-model="formCreation.telephone" type="tel"
                                   placeholder="+226 70 12 34 56"
                                   class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Rôle *
                        </label>
                        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                            <label v-for="r in rolesDisponibles.filter(r => ['admin','responsable_dcirp','organisateur','participant'].includes(r))" :key="r"
                                   :class="['cursor-pointer rounded-lg border-2 p-3 text-center transition',
                                       formCreation.role === r
                                           ? 'border-moov-blue bg-moov-blue-50'
                                           : 'border-border-soft bg-white hover:border-moov-blue/30']">
                                <input type="radio" v-model="formCreation.role" :value="r" class="sr-only"/>
                                <p class="text-xs font-bold text-text-main">{{ labelRole(r) }}</p>
                            </label>
                        </div>
                        <p v-if="formCreation.errors.role" class="mt-1 text-xs text-red-600">{{ formCreation.errors.role }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                            Mot de passe initial * <span class="text-text-muted normal-case">(min. 8 caractères)</span>
                        </label>
                        <input v-model="formCreation.password" type="text" required minlength="8"
                               placeholder="Mot de passe temporaire"
                               class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-moov-blue focus:ring-2 focus:ring-moov-blue/10"/>
                        <p class="mt-1 text-xs text-text-muted">
                            L'utilisateur devra changer son mot de passe à la première connexion
                        </p>
                        <p v-if="formCreation.errors.password" class="mt-1 text-xs text-red-600">{{ formCreation.errors.password }}</p>
                    </div>
                </form>

                <div class="flex justify-end gap-2 border-t border-border-soft bg-page-bg/50 p-4">
                    <button @click="modalCreationOuvert = false"
                            class="rounded-lg border border-border-soft bg-white px-5 py-2 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                        Annuler
                    </button>
                    <button @click="creerUtilisateur" :disabled="formCreation.processing"
                            class="rounded-lg bg-moov-noir px-5 py-2 text-sm font-bold text-white transition hover:bg-moov-noir-soft disabled:opacity-50">
                        {{ formCreation.processing ? 'Création...' : 'Créer le compte' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════ -->
        <!--   MODALE RESET MOT DE PASSE               -->
        <!-- ════════════════════════════════════════ -->
        <div v-if="userResetMdp"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
             @click.self="userResetMdp = null">
            <div class="w-full max-w-md rounded-xl bg-card shadow-2xl">
                <div class="border-b border-border-soft p-5">
                    <h3 class="font-display text-lg font-extrabold text-text-main">
                        Réinitialiser le mot de passe
                    </h3>
                    <p class="mt-1 text-sm text-text-sub">
                        Pour : <span class="font-bold">{{ userResetMdp.prenom }} {{ userResetMdp.nom }}</span>
                    </p>
                </div>
                <div class="p-5">
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                        Nouveau mot de passe * <span class="text-text-muted normal-case">(min. 8 caractères)</span>
                    </label>
                    <input v-model="formResetMdp.password" type="text" required minlength="8"
                           placeholder="Nouveau mot de passe"
                           class="w-full rounded-lg border border-border-soft px-3 py-2 text-sm outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/10"/>
                    <p v-if="formResetMdp.errors.password" class="mt-1 text-xs text-red-600">{{ formResetMdp.errors.password }}</p>
                </div>
                <div class="flex justify-end gap-2 border-t border-border-soft bg-page-bg/50 p-4">
                    <button @click="userResetMdp = null"
                            class="rounded-lg border border-border-soft bg-white px-5 py-2 text-sm font-bold text-text-sub transition hover:bg-page-bg">
                        Annuler
                    </button>
                    <button @click="confirmerResetMdp" :disabled="formResetMdp.processing"
                            class="rounded-lg bg-amber-600 px-5 py-2 text-sm font-bold text-white transition hover:bg-amber-700 disabled:opacity-50">
                        Réinitialiser
                    </button>
                </div>
            </div>
        </div>

    </DashboardLayout>
</template>