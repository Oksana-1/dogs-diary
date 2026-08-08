import ModalDialog from './ModalDialog.js';

export default {
    name: 'ConfirmModal',

    components: {
        ModalDialog,
    },

    props: {
        title: { type: String, required: true },
        text: { type: String, required: true },
        isOpen: { type: Boolean, default: false },
        disabled: { type: Boolean, default: false },
    },

    emits: ['confirm', 'close'],

    methods: {
        confirm() {
            if (!this.disabled) {
                this.$emit('confirm');
            }
        },
    },

    template: /*language=HTML*/ `
        <ModalDialog
            :title="title"
            :description="text"
            :is-open="isOpen"
            :disabled="disabled"
            content-class="modal-content-confirm"
            @close="$emit('close')"
        >
            <div class="modal-actions">
                <button type="button"
                        class="btn-secondary"
                        :disabled="disabled"
                        autofocus
                        @click="$emit('close')">
                    Cancel
                </button>
                <button type="button"
                        class="btn-primary"
                        :disabled="disabled"
                        @click="confirm">
                    Confirm
                </button>
            </div>
        </ModalDialog>
    `,
};
