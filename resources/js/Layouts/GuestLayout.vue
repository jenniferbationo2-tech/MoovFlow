<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="relative flex min-h-screen overflow-hidden bg-[#F8FAFC]">
        <div
            class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(0,102,179,0.14),_transparent_36%),radial-gradient(circle_at_bottom_right,_rgba(255,152,0,0.12),_transparent_26%)]" />

        <div class="relative hidden flex-1 overflow-hidden lg:flex">
            <div
                class="flex w-full flex-col justify-between bg-gradient-to-br from-[#0066B3] via-[#00589D] to-[#004A82] px-12 py-14 text-white">
                <div>
                    <Link href="/" class="inline-flex items-center gap-3">
                        <div
                            class="flex h-14 w-14 items-center justify-center rounded-3xl bg-white/12 text-2xl font-bold">
                            ◈</div>
                        <div>
                            <h1 class="font-['Outfit'] text-3xl font-bold tracking-wide">MoovFlow</h1>
                            <p class="text-sm text-white/70">Gestion evenementielle </p>
                        </div>
                    </Link>
                </div>
               
            </div>
        </div>

        <div class="relative flex w-full items-center justify-center px-4 py-10 sm:px-6 lg:w-[560px] lg:px-10">
            <div
                class="w-full max-w-md rounded-[2rem] border border-white/70 bg-white/90 p-8 shadow-[0_24px_80px_rgba(15,23,42,0.12)] backdrop-blur-xl sm:p-10">
                <div class="mb-8 text-center lg:hidden">
                    <Link href="/" class="inline-flex items-center gap-3">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-[#0066B3] to-[#00A651] text-lg font-bold text-white">
                            ◈</div>
                        <div class="text-left">
                            <p class="font-['Outfit'] text-2xl font-bold text-slate-900">MoovFlow</p>
                            <p class="text-sm text-slate-500">Plateforme evenementielle</p>
                        </div>
                    </Link>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
