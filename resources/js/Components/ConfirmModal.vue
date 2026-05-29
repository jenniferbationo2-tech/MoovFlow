<script setup>
const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: 'Confirmer l\'action' },
    message: { type: String, default: 'Êtes-vous sûr ?' },
    confirmText: { type: String, default: 'Confirmer' },
    cancelText: { type: String, default: 'Annuler' },
    type: { type: String, default: 'default' }, // default | warning | danger | success
})

const emit = defineEmits(['confirm', 'cancel'])

const config = {
    default: {
        iconBg: 'bg-blue-100',
        iconColor: 'text-blue-600',
        confirmBg: 'bg-moov-blue hover:bg-blue-700',
        svgPath: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    warning: {
        iconBg: 'bg-amber-100',
        iconColor: 'text-amber-600',
        confirmBg: 'bg-amber-500 hover:bg-amber-600',
        svgPath: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
    },
    danger: {
        iconBg: 'bg-red-100',
        iconColor: 'text-red-600',
        confirmBg: 'bg-red-600 hover:bg-red-700',
        svgPath: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 3h6a1 1 0 011 1v3H8V4a1 1 0 011-1z',
    },
    success: {
        iconBg: 'bg-emerald-100',
        iconColor: 'text-emerald-600',
        confirmBg: 'bg-emerald-600 hover:bg-emerald-700',
        svgPath: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    },
}

const currentConfig = () => config[props.type] || config.default
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0">

            <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4"
                 @click.self="emit('cancel')">

                <Transition
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95">

                    <div v-if="show" class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl">

                        <!-- Contenu -->
                        <div class="p-6">

                            <!-- Icône -->
                            <div :class="['mx-auto flex h-14 w-14 items-center justify-center rounded-full',
                                currentConfig().iconBg]">
                                <svg :class="['h-7 w-7', currentConfig().iconColor]"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="currentConfig().svgPath"/>
                                </svg>
                            </div>

                            <!-- Titre -->
                            <h3 class="mt-4 text-center font-display text-lg font-extrabold text-slate-900">
                                {{ title }}
                            </h3>

                            <!-- Message -->
                            <p class="mt-2 text-center text-sm text-slate-600">
                                {{ message }}
                            </p>
                        </div>

                        <!-- Boutons -->
                        <div class="flex gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4 rounded-b-2xl">
                            <button @click="emit('cancel')"
                                    class="flex-1 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-100">
                                {{ cancelText }}
                            </button>
                            <button @click="emit('confirm')"
                                    :class="['flex-1 rounded-lg px-4 py-2.5 text-sm font-bold text-white transition shadow-sm',
                                        currentConfig().confirmBg]">
                                {{ confirmText }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>