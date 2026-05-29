<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
})


const ongletActif = ref('general')

const onglets = [
    { key: 'general',       label: 'Général',        icon: '' },
    { key: 'security',      label: 'Sécurité',       icon: '' },
    { key: 'notifications', label: 'Notifications',  icon: '' },
]


const formGeneral = useForm({
    app_name:       props.settings.app_name ?? '',
    app_slogan:     props.settings.app_slogan ?? '',
    app_logo:       null,
    pays:           props.settings.pays ?? '',
    fuseau_horaire: props.settings.fuseau_horaire ?? '',
    devise:         props.settings.devise ?? '',
    adresse_moov:   props.settings.adresse_moov ?? '',
    telephone_moov: props.settings.telephone_moov ?? '',
    email_contact:  props.settings.email_contact ?? '',
})

const previewLogo = ref(null)

const onLogoChange = (e) => {
    const file = e.target.files[0]
    formGeneral.app_logo = file
    if (file) {
        const reader = new FileReader()
        reader.onload = (ev) => previewLogo.value = ev.target.result
        reader.readAsDataURL(file)
    }
}

const submitGeneral = () => {
    formGeneral.post('/admin/settings/general', {
        forceFormData: true,
        preserveScroll: true,
    })
}

const formSecurity = useForm({
    security_max_attempts:    props.settings.security_max_attempts ?? 3,
    security_lockout_minutes: props.settings.security_lockout_minutes ?? 30,
    security_password_min:    props.settings.security_password_min ?? 8,
    security_2fa_enabled:     props.settings.security_2fa_enabled ?? false,
})

const submitSecurity = () => {
    formSecurity.post('/admin/settings/security', {
        preserveScroll: true,
    })
}


const formNotifications = useForm({
    mail_from_address:             props.settings.mail_from_address ?? '',
    mail_from_name:                props.settings.mail_from_name ?? '',
    notif_participants_enabled:    props.settings.notif_participants_enabled ?? true,
    notif_organisateurs_enabled:   props.settings.notif_organisateurs_enabled ?? true,
    notif_blocage_compte_enabled:  props.settings.notif_blocage_compte_enabled ?? true,
})

const submitNotifications = () => {
    formNotifications.post('/admin/settings/notifications', {
        preserveScroll: true,
    })
}


const logoUrl = computed(() => {
    if (previewLogo.value) return previewLogo.value
    if (props.settings.app_logo) return `/storage/${props.settings.app_logo}`
    return null
})

const fuseauxHoraires = [
    'Africa/Ouagadougou',
    'Africa/Abidjan',
    'Africa/Dakar',
    'Africa/Lagos',
    'Europe/Paris',
    'UTC',
]
</script>

<template>
    <DashboardLayout>

        
        <div class="mb-6">
            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                Administration
            </p>
            <h1 class="mt-1 font-display text-2xl font-extrabold text-text-main sm:text-3xl">
                Paramètres Système
            </h1>
            <p class="mt-1 text-sm text-text-sub">
                Configuration centralisée de la plateforme MoovFlow
            </p>
        </div>

        
        <div class="overflow-hidden rounded-xl bg-card shadow-card">

            <div class="border-b border-border-soft">
                <nav class="flex gap-1 px-4">
                    <button v-for="o in onglets" :key="o.key"
                            @click="ongletActif = o.key"
                            :class="['flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-bold transition',
                                ongletActif === o.key
                                    ? 'border-moov-blue text-moov-blue'
                                    : 'border-transparent text-text-sub hover:text-text-main']">
                        <span class="text-base">{{ o.icon }}</span>
                        {{ o.label }}
                    </button>
                </nav>
            </div>

        
            <div v-if="ongletActif === 'general'" class="p-6">

                <form @submit.prevent="submitGeneral" class="space-y-6">
                    <div>
                        <h2 class="font-display text-base font-extrabold text-text-main">
                            Identité de l'application
                        </h2>
                        <p class="mt-1 text-xs text-text-sub">
                            Visible dans la sidebar, le footer et les emails
                        </p>

                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">

                            
                            <div class="md:col-span-1">
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Logo
                                </label>
                                <label class="block cursor-pointer">
                                    <div class="flex aspect-square items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-border-soft bg-page-bg/50 transition hover:bg-page-bg">
                                        <img v-if="logoUrl" :src="logoUrl" class="h-full w-full object-contain p-3"/>
                                        <div v-else class="text-center">
                                            <svg class="mx-auto h-10 w-10 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z"/>
                                            </svg>
                                            <p class="mt-1 text-xs font-bold text-text-sub">Choisir un logo</p>
                                            <p class="text-[10px] text-text-muted">PNG, JPG · Max 2 Mo</p>
                                        </div>
                                    </div>
                                    <input @change="onLogoChange" type="file" accept="image/*" class="hidden"/>
                                </label>
                            </div>

            
                            <div class="space-y-4 md:col-span-2">
                                <div>
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                        Nom de l'application *
                                    </label>
                                    <input v-model="formGeneral.app_name" type="text" required
                                           placeholder="MoovFlow"
                                           class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                                    <p v-if="formGeneral.errors.app_name" class="mt-1 text-xs text-red-600">{{ formGeneral.errors.app_name }}</p>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                        Slogan / Description
                                    </label>
                                    <input v-model="formGeneral.app_slogan" type="text"
                                           placeholder="Portail dCIRP - Moov Africa Burkina"
                                           class="w-full rounded-lg border-2 border-border-soft bg-moov-orange px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ─── Régionalisation ─── -->
                    <div class="border-t border-border-soft pt-6">
                        <h2 class="font-display text-base font-extrabold text-text-main">
                            Régionalisation
                        </h2>
                        <p class="mt-1 text-xs text-text-sub">
                            Pays, fuseau horaire et devise par défaut
                        </p>

                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Pays
                                </label>
                                <input v-model="formGeneral.pays" type="text"
                                       placeholder="Burkina Faso"
                                       class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Fuseau horaire
                                </label>
                                <select v-model="formGeneral.fuseau_horaire"
                                        class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue">
                                    <option v-for="fh in fuseauxHoraires" :key="fh" :value="fh">{{ fh }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Devise
                                </label>
                                <input v-model="formGeneral.devise" type="text"
                                       placeholder="FCFA"
                                       class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                            </div>
                        </div>
                    </div>

                    <!-- ─── Coordonnées Moov ─── -->
                    <div class="border-t border-border-soft pt-6">
                        <h2 class="font-display text-base font-extrabold text-text-main">
                            Coordonnées Moov Africa Burkina
                        </h2>
                        <p class="mt-1 text-xs text-text-sub">
                            Affichées dans le footer et les emails sortants
                        </p>

                        <div class="mt-4 space-y-4">
                            <div>
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Adresse
                                </label>
                                <textarea v-model="formGeneral.adresse_moov" rows="2"
                                          placeholder="Ouaga 2000, Burkina Faso"
                                          class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                            </div>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                        Téléphone
                                    </label>
                                    <input v-model="formGeneral.telephone_moov" type="text"
                                           placeholder="+226 70 00 00 01"
                                           class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                        Email contact
                                    </label>
                                    <input v-model="formGeneral.email_contact" type="email"
                                           placeholder="contact@moov.bf"
                                           class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bouton enregistrer -->
                    <div class="flex justify-end border-t border-border-soft pt-4">
                        <button type="submit" :disabled="formGeneral.processing"
                                class="rounded-lg bg-moov-noir px-6 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft disabled:opacity-50">
                            {{ formGeneral.processing ? 'Enregistrement...' : 'Enregistrer' }}
                        </button>
                    </div>
                </form>
            </div>

           
            <div v-else-if="ongletActif === 'security'" class="p-6">

                <form @submit.prevent="submitSecurity" class="space-y-6">

                    <div>
                        <h2 class="font-display text-base font-extrabold text-text-main">
                            Politique de connexion
                        </h2>
                        <p class="mt-1 text-xs text-text-sub">
                            Protection contre les tentatives de connexion frauduleuses
                        </p>

                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Tentatives max avant blocage *
                                </label>
                                <input v-model.number="formSecurity.security_max_attempts" type="number" min="1" max="10" required
                                       class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                                <p class="mt-1 text-xs text-text-muted">Entre 1 et 10</p>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Durée blocage (minutes) *
                                </label>
                                <input v-model.number="formSecurity.security_lockout_minutes" type="number" min="5" max="1440" required
                                       class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                                <p class="mt-1 text-xs text-text-muted">Entre 5 min et 24h</p>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Force min du mot de passe *
                                </label>
                                <input v-model.number="formSecurity.security_password_min" type="number" min="6" max="32" required
                                       class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                                <p class="mt-1 text-xs text-text-muted">Nombre de caractères</p>
                            </div>
                        </div>
                    </div>

                    <!-- 2FA -->
                    <div class="border-t border-border-soft pt-6">
                        <h2 class="font-display text-base font-extrabold text-text-main">
                            Authentification renforcée
                        </h2>

                        <label class="mt-4 flex cursor-pointer items-start gap-4 rounded-lg border border-border-soft bg-page-bg/50 p-4 transition hover:border-moov-blue/30">
                            <input v-model="formSecurity.security_2fa_enabled" type="checkbox" class="mt-1 h-5 w-5 rounded border-border-soft text-moov-blue focus:ring-moov-blue"/>
                            <div>
                                <p class="font-bold text-text-main">Activer l'authentification à deux facteurs </p>
                                <p class="mt-0.5 text-xs text-text-sub">
                                    Les utilisateurs devront confirmer leur identité via un code envoyé par email à chaque connexion.
                                </p>
                            </div>
                        </label>
                    </div>

                    <!-- Bouton -->
                    <div class="flex justify-end border-t border-border-soft pt-4">
                        <button type="submit" :disabled="formSecurity.processing"
                                class="rounded-lg bg-moov-noir px-6 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft disabled:opacity-50">
                            {{ formSecurity.processing ? 'Enregistrement...' : ' Enregistrer Sécurité' }}
                        </button>
                    </div>
                </form>
            </div>


            <div v-else-if="ongletActif === 'notifications'" class="p-6">

                <form @submit.prevent="submitNotifications" class="space-y-6">

                    <div>
                        <h2 class="font-display text-base font-extrabold text-text-main">
                            Expéditeur des emails
                        </h2>
                        <p class="mt-1 text-xs text-text-sub">
                            Identité affichée dans tous les emails envoyés par la plateforme
                        </p>

                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Email expéditeur *
                                </label>
                                <input v-model="formNotifications.mail_from_address" type="email" required
                                       placeholder="no-reply@moov.bf"
                                       class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Nom expéditeur *
                                </label>
                                <input v-model="formNotifications.mail_from_name" type="text" required
                                       placeholder="MoovFlow"
                                       class="w-full rounded-lg border-2 border-border-soft bg-white px-3 py-2 text-sm outline-none focus:border-moov-blue"/>
                            </div>
                        </div>
                    </div>

                    <!-- Toggles -->
                    <div class="border-t border-border-soft pt-6">
                        <h2 class="font-display text-base font-extrabold text-text-main">
                            Notifications activées
                        </h2>
                        <p class="mt-1 text-xs text-text-sub">
                            Quels types d'événements déclenchent un envoi d'email
                        </p>

                        <div class="mt-4 space-y-3">

                            <label class="flex cursor-pointer items-start gap-4 rounded-lg border border-border-soft bg-white p-4 transition hover:border-moov-blue/30">
                                <input v-model="formNotifications.notif_participants_enabled" type="checkbox" class="mt-1 h-5 w-5 rounded border-border-soft text-moov-blue focus:ring-moov-blue"/>
                                <div>
                                    <p class="font-bold text-text-main">Notifications aux participants</p>
                                    <p class="mt-0.5 text-xs text-text-sub">
                                        Confirmation d'inscription, acceptation, rappels, etc.
                                    </p>
                                </div>
                            </label>

                            <label class="flex cursor-pointer items-start gap-4 rounded-lg border border-border-soft bg-white p-4 transition hover:border-moov-blue/30">
                                <input v-model="formNotifications.notif_organisateurs_enabled" type="checkbox" class="mt-1 h-5 w-5 rounded border-border-soft text-moov-blue focus:ring-moov-blue"/>
                                <div>
                                    <p class="font-bold text-text-main">Notifications aux organisateurs</p>
                                    <p class="mt-0.5 text-xs text-text-sub">
                                        Nouvelles inscriptions, validations, alertes événement.
                                    </p>
                                </div>
                            </label>

                            <label class="flex cursor-pointer items-start gap-4 rounded-lg border border-border-soft bg-white p-4 transition hover:border-moov-blue/30">
                                <input v-model="formNotifications.notif_blocage_compte_enabled" type="checkbox" class="mt-1 h-5 w-5 rounded border-border-soft text-moov-blue focus:ring-moov-blue"/>
                                <div>
                                    <p class="font-bold text-text-main">Notifications de blocage de compte</p>
                                    <p class="mt-0.5 text-xs text-text-sub">
                                        Email automatique quand un compte est bloqué après trop de tentatives.
                                    </p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Bouton -->
                    <div class="flex justify-end border-t border-border-soft pt-4">
                        <button type="submit" :disabled="formNotifications.processing"
                                class="rounded-lg bg-moov-noir px-6 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-moov-noir-soft disabled:opacity-50">
                            {{ formNotifications.processing ? 'Enregistrement...' : '💾 Enregistrer Notifications' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </DashboardLayout>
</template>