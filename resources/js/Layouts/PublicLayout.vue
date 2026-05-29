<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import logoMoov from '../Components/Moov_Africa_logo-2.png'

const page = usePage()
const user = computed(() => page.props.auth?.user ?? null)
const appSettings = computed(() => page.props.app ?? {})
const flash = computed(() => page.props.flash ?? {})

//
const menuOpen = ref(false)
const menuBurgerOpen = ref(false)
const menuRef = ref(null)

const fermerMenuClickExterieur = (e) => {
    if (menuRef.value && !menuRef.value.contains(e.target)) {
        menuOpen.value = false
    }
}

onMounted(() => document.addEventListener('click', fermerMenuClickExterieur))
onBeforeUnmount(() => document.removeEventListener('click', fermerMenuClickExterieur))


const roles = computed(() => user.value?.roles ?? [])
const estStaff = computed(() =>
    roles.value.some(r => ['admin', 'responsable_dcirp', 'organisateur'].includes(r))
)
const estParticipant = computed(() =>
    roles.value.includes('participant') && !estStaff.value
)

const initials = computed(() => {
    if (!user.value) return ''
    const n = user.value.nom?.[0] ?? ''
    const p = user.value.prenom?.[0] ?? ''
    return (p + n).toUpperCase() || user.value.email?.[0]?.toUpperCase() || 'U'
})

// LIENS DASHBOARD
const dashboardUrl = computed(() => {
    if (!user.value) return null
    if (estStaff.value) return '/dashboard'
    return '/dashboard'  // participant a aussi son dashboard
})

// ACTIONS
const logout = () => router.post('/logout')

// ─── INFO FOOTER ────────────────────────────
const anneeCourante = new Date().getFullYear()
</script>

<template>
    <div class="min-h-screen flex flex-col bg-slate-50 font-sans text-slate-800">

        
        <header class="sticky top-0 z-50 border-b border-slate-200 bg-moov-blue/95 backdrop-blur">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between gap-4">

                    <!-- ─── LOGO ─── -->
                    <Link href="/evenements" class="flex items-center gap-2.5 transition hover:opacity-80">
                        <div class="flex h-20 w-20 items-center justify-center overflow-hidden  ">
                            <img :src="logoMoov" alt="Moov Africa" class="h-15 w-15 object-contain" />
                        </div>
                        <span class="font-display text-lg font-extrabold tracking-tight text-slate-50">
                            Moov<span class="text-moov-orange">Flow</span>
                        </span>
                    </Link>

                    <!-- ─── ACTIONS DESKTOP ─── -->
                    <div class="hidden md:flex items-center gap-4">

                        <!-- Favoris (décoratif) -->
                        <button type="button"
                                title="Favoris (bientôt disponible)"
                                class="relative flex h-10 w-10 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-rose-500">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 016.364 0L12 7.636l1.318-1.318a4.5 4.5 0 116.364 6.364L12 20.364l-7.682-7.682a4.5 4.5 0 010-6.364z"/>
                            </svg>
                        </button>

                        <!-- Séparateur visuel -->
                        <div class="h-6 w-px bg-slate-200"/>

                        <!-- Visiteur (non connecté) -->
                        <template v-if="!user">
                            <Link href="/login"
                                  class="text-sm font-bold text-slate-700 transition hover:text-white">
                                Se connecter
                            </Link>
                            <Link href="/register"
                                  class="rounded-lg bg-moov-orange px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700">
                                Créer un compte
                            </Link>
                        </template>

                        <!-- Utilisateur connecté → menu dropdown -->
                        <template v-else>
                            <div ref="menuRef" class="relative">
                                <button @click.stop="menuOpen = !menuOpen"
                                        class="flex items-center gap-2 rounded-full pl-2 pr-3 py-1.5 transition hover:bg-slate-100">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-moov-blue to-blue-700 text-xs font-extrabold text-white shadow-sm">
                                        {{ initials }}
                                    </div>
                                    <span class="text-sm font-bold text-slate-700 max-w-[140px] truncate">
                                        {{ user.prenom }}
                                    </span>
                                    <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>

                                <div v-if="menuOpen"
                                     class="absolute right-0 mt-2 w-64 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl">

                                    <!-- En-tête utilisateur -->
                                    <div class="border-b border-slate-100 bg-slate-50/50 px-4 py-3">
                                        <p class="text-sm font-bold text-slate-900">
                                            {{ user.prenom }} {{ user.nom }}
                                        </p>
                                        <p class="mt-0.5 truncate text-xs text-slate-500">{{ user.email }}</p>
                                        <span v-if="estStaff" class="mt-2 inline-block rounded-md bg-blue-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-blue-700">
                                            Staff
                                        </span>
                                        <span v-else-if="estParticipant" class="mt-2 inline-block rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-700">
                                            Participant
                                        </span>
                                    </div>

                                    <!-- Liens du menu -->
                                    <div class="py-1">
                                        <Link :href="dashboardUrl"
                                              class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50 hover:text-moov-blue">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/>
                                            </svg>
                                            {{ estStaff ? 'Mon tableau de bord' : 'Mon espace' }}
                                        </Link>

                                        <template v-if="estParticipant">
                                            <Link href="/mes-inscriptions"
                                                  class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50 hover:text-moov-blue">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                Mes inscriptions
                                            </Link>

                                            <Link href="/mes-certificats"
                                                  class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50 hover:text-moov-blue">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                                </svg>
                                                Mes certificats
                                            </Link>

                                            <Link href="/mes-enquetes"
                                                  class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50 hover:text-moov-blue">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                                </svg>
                                                Mes enquêtes
                                            </Link>
                                        </template>

                                        <Link href="/profile"
                                              class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50 hover:text-moov-blue">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                            Mon profil
                                        </Link>
                                    </div>

                                    <!-- Déconnexion -->
                                    <div class="border-t border-slate-100">
                                        <button @click="logout"
                                                class="flex w-full items-center gap-3 px-4 py-2.5 text-sm font-bold text-red-600 transition hover:bg-red-50">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                            </svg>
                                            Se déconnecter
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                  
                    <button @click="menuBurgerOpen = !menuBurgerOpen"
                            class="md:hidden flex h-10 w-10 items-center justify-center rounded-lg text-slate-700 hover:bg-slate-100">
                        <svg v-if="!menuBurgerOpen" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg v-else class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                
                <div v-if="menuBurgerOpen" class="md:hidden border-t border-slate-200 py-3 space-y-2">
                    <template v-if="!user">
                        <Link href="/login" class="block rounded-lg px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100">
                            Se connecter
                        </Link>
                        <Link href="/register" class="block rounded-lg bg-moov-blue px-3 py-2 text-center text-sm font-bold text-white">
                            Créer un compte
                        </Link>
                    </template>
                    <template v-else>
                        <div class="flex items-center gap-3 rounded-lg bg-slate-50 px-3 py-2">
                            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-moov-blue to-blue-700 text-xs font-extrabold text-white">
                                {{ initials }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-slate-900 truncate">{{ user.prenom }} {{ user.nom }}</p>
                                <p class="text-xs text-slate-500 truncate">{{ user.email }}</p>
                            </div>
                        </div>
                        <Link :href="dashboardUrl" class="block rounded-lg px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100">
                            {{ estStaff ? 'Tableau de bord' : 'Mon espace' }}
                        </Link>
                        <template v-if="estParticipant">
                            <Link href="/mes-inscriptions" class="block rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">
                                Mes inscriptions
                            </Link>
                            <Link href="/mes-certificats" class="block rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">
                                Mes certificats
                            </Link>
                            <Link href="/mes-enquetes" class="block rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">
                                Mes enquêtes
                            </Link>
                        </template>
                        <button @click="logout" class="block w-full text-left rounded-lg px-3 py-2 text-sm font-bold text-red-600 hover:bg-red-50">
                            Se déconnecter
                        </button>
                    </template>
                </div>
            </div>
        </header>

        <div v-if="flash.success"
             class="border-b border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">
            <div class="mx-auto max-w-7xl">✓ {{ flash.success }}</div>
        </div>
        <div v-if="flash.error"
             class="border-b border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">
            <div class="mx-auto max-w-7xl">✕ {{ flash.error }}</div>
        </div>

        
        <main class="flex-1">
            <slot />
        </main>

        <footer class="mt-12 border-t border-slate-200 bg-moov-blue-dark">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">

                <div class="grid grid-cols-1 gap-8 md:grid-cols-4">

                    <!-- COLONNE 1 : Logo + slogan -->
                    <div class="md:col-span-2">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
                                <img :src="logoMoov" alt="Moov Africa" class="h-7 w-7 object-contain" />
                            </div>
                            <span class="font-display text-lg font-extrabold tracking-tight text-slate-900">
                                Moov<span class="text-moov-orange">Flow</span>
                            </span>
                        </div>
                        <p class="mt-3 max-w-md text-sm text-slate-50 leading-relaxed">
                            Plateforme officielle de gestion événementielle de Moov Africa Burkina,
                            développée par la Direction de la Communication Institutionnelle et des Relations Publiques.
                        </p>
                        <p class="mt-2 text-xs font-bold uppercase tracking-wider text-moov-orange">
                            Moov Africa Burkina · dCIRP
                        </p>
                    </div>

                    <!-- COLONNE 2 : Liens utiles -->
                    <div>
                        <h3 class="text-sm font-bold text-slate-50">Plateforme</h3>
                        <ul class="mt-3 space-y-2 text-sm text-slate-50">
                            <li>
                                <Link href="/evenements" class="hover:text-moov-blue">Tous les évènements</Link>
                            </li>
                            <li>
                                <Link href="/a-propos" class="hover:text-moov-blue">À propos</Link>
                            </li>
                            <li>
                                <Link href="/aide" class="hover:text-moov-blue">Aide & FAQ</Link>
                            </li>
                        </ul>
                    </div>

                    <!-- COLONNE 3 : Légal -->
                    <div>
                        <h3 class="text-sm font-bold text-slate-50">Légal</h3>
                        <ul class="mt-3 space-y-2 text-sm text-slate-50">
                            <li>
                                <Link href="/mentions-legales" class="hover:text-moov-blue">Mentions légales</Link>
                            </li>
                            <li>
                                <Link href="/confidentialite" class="hover:text-moov-blue">Confidentialité</Link>
                            </li>
                            <li>
                                <Link href="/cgu" class="hover:text-moov-blue">CGU</Link>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- ─── COPYRIGHT BAR ─── -->
                <div class="mt-10 border-t border-slate-200 pt-6 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p class="text-xs text-slate-50">
                        © {{ anneeCourante }} Moov Africa Burkina · Tous droits réservés
                    </p>
                    <p class="text-xs text-slate-50">
                        Fait par <a href="https://moov-africa.bf" target="_blank" class="font-bold text-moov-orange hover:underline">Moov Africa</a>
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>