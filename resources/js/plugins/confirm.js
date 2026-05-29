import { createApp, h, ref } from 'vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'

let confirmAppInstance = null
let confirmModalRef = null

const state = ref({
    show: false,
    type: 'default',
    title: '',
    message: '',
    confirmText: 'Confirmer',
    cancelText: 'Annuler',
    resolve: null,
})

function initConfirmModal() {
    if (confirmAppInstance) return

    const container = document.createElement('div')
    container.id = 'global-confirm-modal'
    document.body.appendChild(container)

    confirmAppInstance = createApp({
        setup() {
            return () => h(ConfirmModal, {
                show: state.value.show,
                type: state.value.type,
                title: state.value.title,
                message: state.value.message,
                confirmText: state.value.confirmText,
                cancelText: state.value.cancelText,
                onConfirm: () => {
                    state.value.show = false
                    if (state.value.resolve) state.value.resolve(true)
                },
                onCancel: () => {
                    state.value.show = false
                    if (state.value.resolve) state.value.resolve(false)
                },
            })
        }
    })

    confirmAppInstance.mount(container)
}

/**
 * Ouvre une modale de confirmation et retourne une Promise<boolean>
 *
 * @param {Object|string} options - Soit un objet de config, soit juste le titre
 * @returns Promise<boolean> - true si confirmé, false si annulé
 */
export function $confirm(options) {
    initConfirmModal()

    // Si options est une string, on prend ça comme titre simple
    if (typeof options === 'string') {
        options = { title: options }
    }

    return new Promise((resolve) => {
        state.value = {
            show: true,
            type: options.type || 'default',
            title: options.title || 'Confirmer ?',
            message: options.message || '',
            confirmText: options.confirmText || 'Confirmer',
            cancelText: options.cancelText || 'Annuler',
            resolve: resolve,
        }
    })
}

export default {
    install(app) {
        app.config.globalProperties.$confirm = $confirm
        app.provide('$confirm', $confirm)
    }
}