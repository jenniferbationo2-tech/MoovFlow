<script setup>
import { computed } from 'vue'
import { useForm, usePage, Link } from '@inertiajs/vue3'
import logoMoov from '../Auth/moov-africa-logo.jpg'

defineProps({
    canResetPassword: Boolean,
    status:           String,
})

const flash = computed(() => usePage().props.flash ?? {})

const form = useForm({
    email:    '',
    password: '',
    remember: false,
})

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <div class="relative min-h-screen overflow-hidden bg-gradient-to-br from-[#1B4A8B] via-[#1B4A8B] to-[#0F2F5E] font-sans antialiased">

        <!-- Motifs décoratifs -->
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -right-40 -top-40 h-[28rem] w-[28rem] rounded-full bg-white/5 blur-3xl"/>
            <div class="absolute -bottom-48 -left-32 h-96 w-96 rounded-full bg-[#FF8000]/10 blur-3xl"/>
            <div class="absolute right-1/4 top-1/4 h-2 w-2 rounded-full bg-[#FF8000]"/>
            <div class="absolute left-1/4 top-1/3 h-2 w-2 rounded-full bg-white/40"/>
            <div class="absolute right-1/3 bottom-1/4 h-3 w-3 rounded-full bg-[#FF8000]/50"/>
            <div class="absolute left-1/3 bottom-1/3 h-1.5 w-1.5 rounded-full bg-white/30"/>
        </div>

        <!-- Contenu centré -->
        <div class="relative z-10 flex min-h-screen flex-col items-center justify-center px-4 py-10">

            <div class="w-full max-w-md">

                <!-- CARD BLANCHE -->
                <div class="overflow-hidden rounded-3xl bg-white shadow-2xl">

                    <!-- En-tête de la card : Logo -->
                    <div class="flex flex-col items-center border-b border-slate-100 px-8 pt-8 pb-6">
                        <div class="flex items-center gap-3">
                            <div class="flex h-14 w-20 items-center justify-center rounded-2xl bg-gradient-to-br shadow-lg">
                                <img :src="logoMoov" alt="Moov Africa" class="h-full w-full rounded-lg bg-white object-contain p-1" />
                            </div>
                            <div class="text-left">
                                <h1 class="font-display text-xl font-extrabold leading-none tracking-tight text-[#1B4A8B]">
                                    MOOV AFRICA
                                </h1>
                                <p class="mt-1 text-[11px] font-bold uppercase tracking-[0.2em] text-[#FF8000]">
                                    MoovFlow
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Corps de la card -->
                    <div class="px-8 py-8">

                        <!-- Titre -->
                        <div class="mb-7 text-center">
                            <h2 class="font-display text-2xl font-extrabold text-slate-900">
                                Connexion
                            </h2>
                            <p class="mt-1.5 text-sm text-slate-500">
                                Accédez à votre espace MoovFlow
                            </p>
                        </div>

                        <!-- Status flash -->
                        <div v-if="status"
                             class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                            {{ status }}
                        </div>

                        <div v-if="flash.info"
                             class="mb-5 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                            {{ flash.info }}
                        </div>

                        <!-- Erreur globale -->
                        <div v-if="form.errors.email"
                             class="mb-5 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span>{{ form.errors.email }}</span>
                        </div>

                        <form @submit.prevent="submit" class="space-y-5">

                            <!-- Email -->
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-600">
                                    Adresse e-mail
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </span>
                                    <input v-model="form.email"
                                           type="email"
                                           autocomplete="email"
                                           required
                                           placeholder="vous@moov.bf"
                                           class="w-full rounded-xl border-2 border-slate-200 bg-slate-50/50 py-3 pl-11 pr-4 text-sm font-semibold text-slate-900 outline-none transition placeholder:font-normal placeholder:text-slate-400 focus:border-[#1B4A8B] focus:bg-white focus:ring-4 focus:ring-[#1B4A8B]/10"/>
                                </div>
                            </div>

                            <!-- Mot de passe -->
                            <div>
                                <div class="mb-2 flex items-center justify-between">
                                    <label class="text-xs font-bold uppercase tracking-wider text-slate-600">
                                        Mot de passe
                                    </label>
                                    <Link v-if="canResetPassword" :href="route('password.request')"
                                          class="text-xs font-bold text-[#1B4A8B] transition hover:text-[#FF8000]">
                                        Oublié ?
                                    </Link>
                                </div>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                    </span>
                                    <input v-model="form.password"
                                           type="password"
                                           autocomplete="current-password"
                                           required
                                           placeholder="••••••••"
                                           class="w-full rounded-xl border-2 border-slate-200 bg-slate-50/50 py-3 pl-11 pr-4 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1B4A8B] focus:bg-white focus:ring-4 focus:ring-[#1B4A8B]/10"/>
                                </div>
                                <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">
                                    {{ form.errors.password }}
                                </p>
                            </div>

                            <!-- Remember -->
                            <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
                                <input type="checkbox"
                                       v-model="form.remember"
                                       class="h-4 w-4 rounded border-slate-300 text-[#1B4A8B] focus:ring-[#1B4A8B]"/>
                                Se souvenir de moi
                            </label>

                            <!-- Bouton -->
                            <button type="submit"
                                    :disabled="form.processing"
                                    class="group relative w-full overflow-hidden rounded-xl bg-[#1B4A8B] px-4 py-3.5 text-sm font-bold uppercase tracking-wider text-white shadow-lg transition hover:bg-[#0F2F5E] hover:shadow-xl disabled:opacity-60">
                                <span class="relative z-10 flex items-center justify-center gap-2">
                                    <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                    </svg>
                                    {{ form.processing ? 'Authentification...' : 'Se connecter' }}
                                    <svg v-if="!form.processing" class="h-4 w-4 transition group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                </span>
                                <span class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-[#FF8000]/20 to-transparent transition-transform duration-700 group-hover:translate-x-full"/>
                            </button>

                        </form>

                        <!-- Séparateur -->
                        <div class="relative my-7">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-slate-200"/>
                            </div>
                            <div class="relative flex justify-center">
                                <span class="bg-white px-4 text-xs font-medium text-slate-400">ou</span>
                            </div>
                        </div>

                        <!-- Lien register -->
                        <p class="text-center text-sm text-slate-600">
                            Pas encore de compte ?
                            <Link :href="route('register')"
                                  class="font-bold text-[#FF8000] transition hover:text-[#1B4A8B]">
                                Créer un compte 
                            </Link>
                        </p>

                    </div>
                </div>

                <!-- Footer sous la card -->
                <p class="mt-6 text-center text-xs text-white/60">
                    © 2026 Moov Africa Burkina Faso · <span class="font-bold text-[#FF8000]">dCIRP</span>
                </p>

            </div>
        </div>
    </div>
</template>