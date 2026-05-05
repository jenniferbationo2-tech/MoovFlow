<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm({
    nom:                   '',
    prenom:                '',
    email:                 '',
    telephone:             '',
    password:              '',
    password_confirmation: '',
})

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}
</script>

<template>
    <div class="min-h-screen bg-page-bg font-sans antialiased">

        <!-- ═══════════ ENTÊTE LOGO ═══════════ -->
        <div class="pt-12 text-center">
            <h1 class="font-display text-5xl font-extrabold tracking-tight">
                <span class="text-moov-blue">MOOV</span>
                <span class="ml-2 text-moov-blue">AFRICA</span>
            </h1>
            <p class="mt-2 text-xs font-semibold uppercase tracking-[0.3em]">
                <span class="text-moov-blue">Burkina Faso</span>
                <span class="mx-2 text-moov-orange">|</span>
                <span class="text-moov-orange">Plateforme MoovFlow</span>
            </p>
        </div>

        <!-- ═══════════ CARTE INSCRIPTION ═══════════ -->
        <div class="mt-8 flex justify-center px-4">
            <div class="w-full max-w-lg">

                <div class="overflow-hidden rounded-2xl bg-white shadow-card">

                    <!-- En-tête -->
                    <div class="border-b border-border-soft px-8 py-5 text-center">
                        <h2 class="text-sm font-bold uppercase tracking-[0.2em] text-text-main">
                            Création de Compte Participant
                        </h2>
                        <p class="mt-1.5 text-xs text-text-sub">
                            Accédez aux événements RSE de Moov Africa Burkina
                        </p>
                    </div>

                    <!-- Body -->
                    <div class="px-8 py-7">

                        <form @submit.prevent="submit" class="space-y-5">

                            <!-- Nom + Prénom -->
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                        Nom *
                                    </label>
                                    <input v-model="form.nom"
                                           type="text"
                                           required
                                           autofocus
                                           placeholder="BATIONO"
                                           class="w-full rounded-lg border border-border-soft bg-page-bg/50 px-4 py-3 text-sm font-semibold text-text-main outline-none transition focus:border-moov-blue focus:bg-white focus:ring-2 focus:ring-moov-blue/20"/>
                                    <p v-if="form.errors.nom" class="mt-1 text-xs text-red-600">{{ form.errors.nom }}</p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                        Prénom *
                                    </label>
                                    <input v-model="form.prenom"
                                           type="text"
                                           required
                                           placeholder="Carine"
                                           class="w-full rounded-lg border border-border-soft bg-page-bg/50 px-4 py-3 text-sm font-semibold text-text-main outline-none transition focus:border-moov-blue focus:bg-white focus:ring-2 focus:ring-moov-blue/20"/>
                                    <p v-if="form.errors.prenom" class="mt-1 text-xs text-red-600">{{ form.errors.prenom }}</p>
                                </div>
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Adresse email *
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-text-muted">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </span>
                                    <input v-model="form.email"
                                           type="email"
                                           autocomplete="email"
                                           required
                                           placeholder="moov@exemple.bf"
                                           class="w-full rounded-lg border border-border-soft bg-page-bg/50 py-3 pl-11 pr-4 text-sm font-semibold text-text-main outline-none transition focus:border-moov-blue focus:bg-white focus:ring-2 focus:ring-moov-blue/20"/>
                                </div>
                                <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                            </div>

                            <!-- Téléphone -->
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Téléphone
                                    <span class="text-text-muted normal-case">(optionnel)</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-text-muted">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                    </span>
                                    <input v-model="form.telephone"
                                           type="tel"
                                           placeholder="+226 70 12 34 56"
                                           class="w-full rounded-lg border border-border-soft bg-page-bg/50 py-3 pl-11 pr-4 text-sm font-semibold text-text-main outline-none transition focus:border-moov-blue focus:bg-white focus:ring-2 focus:ring-moov-blue/20"/>
                                </div>
                            </div>

                            <!-- Mot de passe -->
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Mot de passe *
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-text-muted">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                    </span>
                                    <input v-model="form.password"
                                           type="password"
                                           autocomplete="new-password"
                                           required
                                           placeholder="Minimum 8 caractères"
                                           class="w-full rounded-lg border border-border-soft bg-page-bg/50 py-3 pl-11 pr-4 text-sm font-semibold text-text-main outline-none transition focus:border-moov-blue focus:bg-white focus:ring-2 focus:ring-moov-blue/20"/>
                                </div>
                                <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
                            </div>

                            
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-text-sub">
                                    Confirmer le mot de passe *
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-text-muted">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                        </svg>
                                    </span>
                                    <input v-model="form.password_confirmation"
                                           type="password"
                                           autocomplete="new-password"
                                           required
                                           placeholder="Retapez le mot de passe"
                                           class="w-full rounded-lg border border-border-soft bg-page-bg/50 py-3 pl-11 pr-4 text-sm font-semibold text-text-main outline-none transition focus:border-moov-blue focus:bg-white focus:ring-2 focus:ring-moov-blue/20"/>
                                </div>
                            </div>

                            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3">
                                <div class="flex items-start gap-2">
                                    <svg class="h-4 w-4 flex-shrink-0 text-emerald-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                    <p class="text-xs text-emerald-800">
                                        Votre compte sera créé avec le rôle <strong>Participant</strong>.
                                        Vous pourrez vous inscrire à tous les événements ouverts.
                                    </p>
                                </div>
                            </div>

                            <button type="submit"
                                    :disabled="form.processing"
                                    class="w-full rounded-lg bg-moov-noir px-4 py-3.5 text-sm font-bold uppercase tracking-wider text-white shadow-md transition hover:bg-moov-noir-soft disabled:opacity-60">
                                {{ form.processing ? 'Création en cours...' : 'Créer mon compte' }}
                            </button>

                        </form>

                    </div>

                    <!-- Footer carte -->
                    <div class="border-t border-border-soft bg-page-bg/50 px-8 py-4 text-center">
                        <p class="text-xs text-text-sub">
                            Déjà inscrit ?
                            <a href="/login"
                               class="ml-1 font-bold text-moov-orange hover:text-moov-orange-dark">
                                Se connecter 
                            </a>
                        </p>
                    </div>
                </div>

            </div>
        </div>

        <div class="mt-8 pb-8 text-center">
            <p class="text-xs font-bold uppercase tracking-[0.25em] text-text-muted">
                Plateforme événementielle MoovFlow
            </p>
            <div class="mx-auto mt-3 h-1 w-12 rounded bg-moov-orange"/>
        </div>

    </div>
</template>