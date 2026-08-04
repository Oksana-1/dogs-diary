let modalId = 0;

export default {
    name: 'BaseModal',

    props: {
        title: { type: String, required: true },
        text: { type: String, required: true },
        isOpen: { type: Boolean, default: false },
        disabled: { type: Boolean, default: false },
    },

    emits: ['onResolve', 'onReject'],

    data() {
        modalId += 1;

        return {
            titleId: `modal-title-${modalId}`,
            textId: `modal-text-${modalId}`,
        };
    },

    methods: {
        onResolve() {
            if (!this.disabled) {
                this.$emit('onResolve');
            }
        },

        onReject() {
            if (!this.disabled) {
                this.$emit('onReject');
            }
        },
    },

    template: `
        <Teleport to="body">
            <div v-if="isOpen"
                 class="modal modal-open"
                 style="display: flex"
                 role="dialog"
                 aria-modal="true"
                 :aria-labelledby="titleId"
                 :aria-describedby="textId"
                 @click.self="onReject"
                 @keydown.esc.prevent="onReject">
                <div class="modal-content modal-content-confirm">
                    <h2 :id="titleId">{{ title }}</h2>
                    <p :id="textId">{{ text }}</p>
                    <div class="modal-actions">
                        <button type="button"
                                class="btn-secondary"
                                :disabled="disabled"
                                autofocus
                                @click="onReject">
                            Cancel
                        </button>
                        <button type="button"
                                class="btn-primary"
                                :disabled="disabled"
                                @click="onResolve">
                            Confirm
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    `,
};
