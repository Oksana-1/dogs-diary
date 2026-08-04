let modalId = 0;

function dogDraft(dog) {
    return {
        name: dog.name ?? '',
        birthDate: dog.birthDate ?? '',
        gender: dog.gender ?? '',
        adoptDate: dog.adoptDate ?? '',
        status: dog.status ?? '',
        avatar: dog.avatar ?? '',
        weight: dog.weight ?? '',
        height: dog.height ?? '',
    };
}

export default {
    name: 'DogEditModal',

    props: {
        dog: { type: Object, required: true },
        isOpen: { type: Boolean, default: false },
        disabled: { type: Boolean, default: false },
        error: { type: String, default: null },
    },

    emits: ['onResolve', 'onReject'],

    data() {
        modalId += 1;

        return {
            titleId: `dog-edit-modal-title-${modalId}`,
            draft: dogDraft(this.dog),
        };
    },

    watch: {
        isOpen(isOpen) {
            if (isOpen) {
                this.draft = dogDraft(this.dog);
            }
        },
    },

    methods: {
        onResolve() {
            if (this.disabled) {
                return;
            }

            this.$emit('onResolve', {
                name: this.draft.name.trim(),
                birthDate: this.draft.birthDate,
                gender: this.optionalString(this.draft.gender),
                adoptDate: this.optionalString(this.draft.adoptDate),
                status: this.optionalString(this.draft.status),
                avatar: this.optionalString(this.draft.avatar),
                weight: this.optionalNumber(this.draft.weight),
                height: this.optionalNumber(this.draft.height),
            });
        },

        onReject() {
            if (!this.disabled) {
                this.$emit('onReject');
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
        <Teleport to="body">
            <div v-if="isOpen"
                 class="modal modal-open"
                 style="display: flex"
                 role="dialog"
                 aria-modal="true"
                 :aria-labelledby="titleId"
                 @click.self="onReject"
                 @keydown.esc.prevent="onReject">
                <div class="modal-content">
                    <h2 :id="titleId">Edit {{ dog.name }}</h2>
                    <form @submit.prevent="onResolve">
                        <div class="form-group">
                            <label for="edit-dog-name">Name</label>
                            <input id="edit-dog-name" v-model="draft.name" type="text"
                                   required minlength="2" maxlength="100" autofocus>
                        </div>
                        <div class="form-group">
                            <label for="edit-dog-birth-date">Birth date</label>
                            <input id="edit-dog-birth-date" v-model="draft.birthDate" type="date" required>
                        </div>
                        <div class="form-group">
                            <label for="edit-dog-gender">Gender</label>
                            <select id="edit-dog-gender" v-model="draft.gender">
                                <option value="">Unknown</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit-dog-adopt-date">Adopt date</label>
                            <input id="edit-dog-adopt-date" v-model="draft.adoptDate" type="date">
                        </div>
                        <div class="form-group">
                            <label for="edit-dog-status">Status</label>
                            <input id="edit-dog-status" v-model="draft.status" type="text" maxlength="100">
                        </div>
                        <div class="form-group">
                            <label for="edit-dog-avatar">Avatar</label>
                            <input id="edit-dog-avatar" v-model="draft.avatar" type="text" maxlength="255">
                        </div>
                        <div class="form-group">
                            <label for="edit-dog-weight">Weight, kg</label>
                            <input id="edit-dog-weight" v-model="draft.weight" type="number" min="1" step="1">
                        </div>
                        <div class="form-group">
                            <label for="edit-dog-height">Height, cm</label>
                            <input id="edit-dog-height" v-model="draft.height" type="number" min="1" step="1">
                        </div>
                        <p v-if="error" class="modal-error" role="alert">{{ error }}</p>
                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" :disabled="disabled" @click="onReject">
                                Cancel
                            </button>
                            <button type="submit" class="btn-primary" :disabled="disabled">
                                {{ disabled ? 'Saving…' : 'Save' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    `,
};
