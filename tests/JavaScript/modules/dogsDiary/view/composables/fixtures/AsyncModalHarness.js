import useAsyncModal from '../../../../../../../assets/js/modules/dogsDiary/view/composables/useAsyncModal.js';

export default {
    props: {
        action: { type: Function, required: true },
        subject: { default: null },
        fallbackError: { type: String, default: undefined },
    },
    setup(props) {
        return { modal: useAsyncModal({ fallbackError: props.fallbackError }) };
    },
    template: `
        <button aria-label="Open" @click="modal.open(subject)">Open</button>
        <section v-if="modal.isOpen" role="dialog" aria-label="Test action" :aria-busy="modal.isPending">
            <p role="status">{{ modal.subject?.name }}</p>
            <p v-if="modal.error" role="alert">{{ modal.error }}</p>
            <button aria-label="Submit" @click="modal.execute(action)">Submit</button>
            <button aria-label="Close" @click="modal.close()">Close</button>
        </section>
    `,
};
