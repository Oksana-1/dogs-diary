import useDogTreatments from '../../../../../../../assets/js/modules/dogsDiary/view/composables/useDogTreatments.js';

export default {
    props: ['repository', 'mediaRepository', 'initialTreatments', 'payload'],
    setup(props) {
        return { state: useDogTreatments(() => 7, props.initialTreatments, props.repository, props.mediaRepository) };
    },
    template: `
        <button aria-label="Add" @click="state.openCreate()">Add</button>
        <ul aria-label="Treatments">
            <li v-for="item in state.treatments" :key="item.id">
                {{ item.productName }} {{ item.photo?.originalName }}
                <button :aria-label="'Edit ' + item.id" @click="state.openEdit(item)">Edit</button>
                <button :aria-label="'Delete ' + item.id" @click="state.deleteModal.open(item)">Delete</button>
            </li>
        </ul>
        <section v-if="state.formModal.isOpen" role="dialog" aria-label="Treatment form" :aria-busy="state.formModal.isPending">
            <p v-if="state.formModal.error" role="alert">{{ state.formModal.error }}</p>
            <button aria-label="Save" @click="state.save(payload)">Save</button>
            <button aria-label="Cancel" @click="state.formModal.close()">Cancel</button>
        </section>
        <section v-if="state.deleteModal.isOpen" role="dialog" aria-label="Delete treatment">
            <p>{{ state.deleteText }}</p>
            <button aria-label="Confirm delete" @click="state.remove()">Delete</button>
        </section>
    `,
};
