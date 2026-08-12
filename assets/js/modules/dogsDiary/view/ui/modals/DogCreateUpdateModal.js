import ModalDialog from './ModalDialog.js';

let formId = 0;

function dogDraft(dog) {
    return {
        name: dog.name ?? '',
        birthDate: dog.birthDate ?? '',
        gender: dog.gender ?? '',
        adoptDate: dog.adoptDate ?? '',
        status: dog.status ?? '',
        // Retain the compatibility value while the backend still owns the
        // legacy field, but do not expose it as editable UI.
        avatar: dog.avatar ?? null,
        weight: dog.weight ?? '',
        height: dog.height ?? '',
    };
}

export default {
    name: 'DogCreateUpdateModal',

    components: {
        ModalDialog,
    },

    props: {
        dog: { type: Object, required: true },
        isOpen: { type: Boolean, default: false },
        disabled: { type: Boolean, default: false },
        error: { type: String, default: null },
    },

    emits: ['submit', 'close'],

    data() {
        formId += 1;

        return {
            fieldPrefix: `dog-form-${formId}`,
            draft: dogDraft(this.dog),
        };
    },

    computed: {
        title() {
            return this.dog.id ? `Edit ${this.dog.name}` : 'Add dog';
        },
    },

    watch: {
        isOpen(isOpen) {
            if (isOpen) {
                this.draft = dogDraft(this.dog);
            }
        },
    },

    methods: {
        submit() {
            if (this.disabled) {
                return;
            }

            this.$emit('submit', {
                name: this.draft.name.trim(),
                birthDate: this.draft.birthDate,
                gender: this.optionalString(this.draft.gender),
                adoptDate: this.optionalString(this.draft.adoptDate),
                status: this.optionalString(this.draft.status),
                avatar: this.draft.avatar,
                weight: this.optionalNumber(this.draft.weight),
                height: this.optionalNumber(this.draft.height),
            });
        },

        close() {
            if (!this.disabled) {
                this.$emit('close');
            }
        },

        optionalString(value) {
            const normalized = String(value ?? '').trim();

            return normalized === '' ? null : normalized;
        },

        optionalNumber(value) {
            return value === '' || value === null ? null : Number(value);
        },
    },

    template: /*language=HTML*/ `
        <ModalDialog
            :title="title"
            :is-open="isOpen"
            :disabled="disabled"
            content-class="modal-content-dog-form"
            @close="close"
        >
            <form @submit.prevent="submit">
                <div class="dog-form-grid">
                    <div class="form-group">
                        <label :for="fieldPrefix + '-name'">Name</label>
                        <input :id="fieldPrefix + '-name'" v-model="draft.name" type="text"
                               required minlength="2" maxlength="100" autofocus>
                    </div>
                    <div class="form-group">
                        <label :for="fieldPrefix + '-birth-date'">Birth date</label>
                        <input :id="fieldPrefix + '-birth-date'" v-model="draft.birthDate" type="date" required>
                    </div>
                    <div class="form-group">
                        <label :for="fieldPrefix + '-gender'">Gender</label>
                        <select :id="fieldPrefix + '-gender'" v-model="draft.gender">
                            <option value="">Unknown</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label :for="fieldPrefix + '-adopt-date'">Adopt date</label>
                        <input :id="fieldPrefix + '-adopt-date'" v-model="draft.adoptDate" type="date">
                    </div>
                    <div class="form-group">
                        <label :for="fieldPrefix + '-status'">Status</label>
                        <input :id="fieldPrefix + '-status'" v-model="draft.status" type="text" maxlength="100">
                    </div>
                    <div class="form-group">
                        <label :for="fieldPrefix + '-weight'">Weight, kg</label>
                        <input :id="fieldPrefix + '-weight'" v-model="draft.weight" type="number" min="1" step="1">
                    </div>
                    <div class="form-group">
                        <label :for="fieldPrefix + '-height'">Height, cm</label>
                        <input :id="fieldPrefix + '-height'" v-model="draft.height" type="number" min="1" step="1">
                    </div>
                </div>
                <p v-if="error" class="modal-error" role="alert">{{ error }}</p>
                <div class="modal-actions">
                    <button type="button" class="btn btn-white" :disabled="disabled" @click="close">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-black" :disabled="disabled">
                        {{ disabled ? 'Saving…' : 'Save' }}
                    </button>
                </div>
            </form>
        </ModalDialog>
    `,
};
