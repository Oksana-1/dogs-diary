import ModalDialog from '../../../../../../../../assets/js/modules/dogsDiary/view/ui/modals/ModalDialog.js';

export default {
    components: { ModalDialog },
    inheritAttrs: false,
    props: {
        empty: { type: Boolean, default: false },
        firstDisabled: { type: Boolean, default: false },
        lastDisabled: { type: Boolean, default: false },
        extra: { type: Boolean, default: false },
    },
    emits: ['close'],
    template: `
        <ModalDialog v-bind="$attrs" @close="$emit('close')">
            <template v-if="!empty">
                <button type="button" :disabled="firstDisabled">First</button>
                <input aria-label="Name" autofocus>
                <button type="button" :disabled="lastDisabled">Last</button>
                <button v-if="extra" type="button">Retry</button>
            </template>
        </ModalDialog>
    `,
};
