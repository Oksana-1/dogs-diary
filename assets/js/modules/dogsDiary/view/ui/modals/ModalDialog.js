let modalId = 0;

export default {
    name: 'ModalDialog',

    props: {
        title: { type: String, required: true },
        description: { type: String, default: null },
        isOpen: { type: Boolean, default: false },
        disabled: { type: Boolean, default: false },
        contentClass: { type: String, default: '' },
    },

    emits: ['close'],

    data() {
        modalId += 1;

        return {
            titleId: `modal-title-${modalId}`,
            descriptionId: `modal-description-${modalId}`,
        };
    },

    methods: {
        close() {
            if (!this.disabled) {
                this.$emit('close');
            }
        },
    },

    template: /*language=HTML*/ `
        <Teleport to="body">
            <div v-if="isOpen"
                 class="modal modal-open"
                 style="display: flex"
                 role="dialog"
                 aria-modal="true"
                 :aria-labelledby="titleId"
                 :aria-describedby="description ? descriptionId : undefined"
                 @click.self="close"
                 @keydown.esc.prevent="close">
                <div :class="['modal-content', contentClass]">
                    <h2 :id="titleId">{{ title }}</h2>
                    <p v-if="description" :id="descriptionId">{{ description }}</p>
                    <slot></slot>
                </div>
            </div>
        </Teleport>
    `,
};
