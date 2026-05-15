<script setup>
import { computed, ref } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import logo from '../Components/Moov_Africa_logo-2.png'

const page = usePage()
const user = computed(() => page.props.auth?.user ?? null)
const flash = computed(() => page.props.flash ?? {})
const menuOpen = ref(false)

const logout = () => router.post(route('logout'))

const initials = computed(() => {
    if (!user.value) return ''
    const n = user.value.nom?.[0] ?? ''
    const p = user.value.prenom?.[0] ?? ''
    return (p + n).toUpperCase() || user.value.email?.[0]?.toUpperCase() || 'U'
})

const dashboardUrl = computed(() => {
    if (!user.value) return null
    const roles = user.value.roles ?? []
    if (roles.includes('admin') || roles.includes('responsable_dcirp')) {
        return route('admin.users.index')
    }
    return route('dashboard')
})
</script>

<template>
    <div class="min-h-screen bg-gray-50 font-sans text-slate-800">

        <!-- ═════════ NAVBAR ═════════ -->
        <nav class="sticky top-0 z-50 bg-moov-blue shadow-lg">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">

                    <!-- Logo -->
                    <Link :href="route('evenements.index')" class="flex items-center gap-2.5">
                        <div
                            class="flex h-9 w-9 items-center justify-center width="600 bg-white font-display text-lg font-extrabold text-white shadow>
                            <img src="../Components/Moov_Africa_logo-2.png" width="600px"/>
                        </div>
                        <span class="font-display text-lg font-extrabold text-white tracking-tight">
                            Moov<span class="text-moov-orange">Flow</span>
                        </span>
                    </Link>

                    <!-- Actions desktop -->
                    <div class="hidden items-center gap-3 md:flex">

                        <!-- Visiteur -->
                        <template v-if="!user">
                            <Link :href="route('login')"
                                class="rounded-full border border-white/20 px-4 py-2 text-sm font-medium text-white/90 transition hover:bg-white/10">
                                Connexion
                            </Link>
                            <Link :href="route('register')"
                                class="rounded-full bg-moov-orange px-5 py-2 text-sm font-bold text-white transition hover:bg-moov-orange-dark">
                                S'inscrire
                            </Link>
                        </template>

                        <!-- Connecté -->
                        <template v-else>
                            <Link v-if="dashboardUrl" :href="dashboardUrl"
                                class="rounded-full border border-white/20 px-4 py-2 text-sm font-medium text-white/90 transition hover:bg-white/10">
                                Mon espace
                            </Link>

                            <!-- Menu utilisateur -->
                            <div class="relative">
                                <button @click="menuOpen = !menuOpen"
                                    class="flex h-9 w-9 items-center justify-center rounded-full bg-moov-orange text-sm font-bold text-white shadow hover:bg-moov-orange-dark">
                                    {{ initials }}
                                </button>

                                <div v-if="menuOpen" @click="menuOpen = false"
                                    class="absolute right-0 top-12 w-56 overflow-hidden rounded-xl border border-gray-100 bg-white shadow-xl">
                                    <div class="border-b border-gray-100 px-4 py-3">
                                        <p class="text-sm font-bold text-slate-800">
                                            {{ user.prenom }} {{ user.nom }}
                                        </p>
                                        <p class="mt-0.5 truncate text-xs text-slate-400">{{ user.email }}</p>
                                    </div>
                                    <Link v-if="dashboardUrl" :href="dashboardUrl"
                                        class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-gray-50">
                                        Tableau de bord
                                    </Link>
                                   

                                    <Link href="/mes-inscriptions"
                                        class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-gray-50">
                                        Mes inscriptions
                                    </Link>

                                    <Link href="/profile"
                                        class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-gray-50">
                                        Mon profil
                                    </Link>
                                    
                                    <button @click="logout"
                                        class="block w-full px-4 py-2.5 text-left text-sm font-medium text-red-600 hover:bg-red-50">
                                        Déconnexion
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Burger mobile -->
                    <button @click="menuOpen = !menuOpen" class="md:hidden">
                        <span class="text-2xl text-white">☰</span>
                    </button>
                </div>
            </div>
        </nav>

        <!-- ═════════ FLASH ═════════ -->
        <div v-if="flash.success"
            class="border-b border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            ✅ {{ flash.success }}
        </div>
        <div v-if="flash.error" class="border-b border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
            ❌ {{ flash.error }}
        </div>
        <div v-if="flash.info" class="border-b border-blue-200 bg-blue-50 px-4 py-3 text-sm font-medium text-blue-700">
            ℹ️ {{ flash.info }}
        </div>

        <slot />

        <!-- ═════════ FOOTER ═════════ -->
        <footer class="mt-16 bg-slate-900 py-10 text-white/60">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-center justify-between gap-4 md:flex-row">
                    <div>
                        <p class="font-display text-lg font-extrabold text-white">
                            Moov<span class="text-moov-orange">Flow</span>
                        </p>
                        <p class="mt-1 text-xs">Plateforme de gestion événementielle · dCIRP</p>
                    </div>
                    <p class="text-xs">© 2026 Moov Africa Burkina · Tous droits réservés</p>
                </div>
            </div>
        </footer>
    </div>
</template>