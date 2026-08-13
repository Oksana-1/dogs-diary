import ModalDialog from './ModalDialog.js';

export default {
    name: 'TreatmentPhotoModal',

    components: {
        ModalDialog,
    },

    props: {
        photo: { type: Object, default: null },
        isOpen: { type: Boolean, default: false },
    },

    emits: ['close'],

    template: /*language=HTML*/ `
        <ModalDialog
            title="Treatment photo"
            :is-open="isOpen"
            content-class="modal-content-treatment-photo"
            @close="$emit('close')"
        >
            <img v-if="photo"
                 class="treatment-photo-full"
                 :src="photo.url"
                 :alt="photo.originalName || 'Treatment photo'">
            <div class="modal-actions">
                <button type="button" class="btn btn-black" autofocus @click="$emit('close')">
                    Close
                </button>
            </div>
        </ModalDialog>
    `,
};
