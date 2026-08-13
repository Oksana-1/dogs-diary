import ModalDialog from './ModalDialog.js';
import { mdiDeleteOutline, mdiUpload } from '@mdi/js';

let formId = 0;

function localDate() {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function treatmentDraft(treatment) {
    return {
        types: [...(treatment?.types ?? [])],
        productName: treatment?.productName ?? '',
        treatmentDate: treatment?.treatmentDate ?? localDate(),
        dueDate: treatment?.dueDate ?? '',
        note: treatment?.note ?? '',
    };
}

export default {
    name: 'TreatmentFormModal',

    components: {
        ModalDialog,
    },

    props: {
        treatment: { type: Object, default: null },
        isOpen: { type: Boolean, default: false },
        disabled: { type: Boolean, default: false },
        error: { type: String, default: null },
    },

    emits: ['submit', 'close'],

    data() {
        formId += 1;
        const id = formId;

        return {
            fieldPrefix: `treatment-form-${id}`,
            draft: treatmentDraft(this.treatment),
            photoFile: null,
            removePhoto: false,
            localPreviewUrl: null,
            mdiDeleteOutline,
            mdiUpload,
        };
    },

    computed: {
        title() {
            return this.treatment ? 'Edit Treatment' : 'Add Treatment';
        },

        photoPreviewUrl() {
            if (this.localPreviewUrl) {
                return this.localPreviewUrl;
            }

            return this.removePhoto ? null : this.treatment?.photo?.url ?? null;
        },

    },

    watch: {
        isOpen(isOpen) {
            if (isOpen) {
                this.draft = treatmentDraft(this.treatment);
                this.resetPhotoDraft();
            }
        },
    },

    beforeUnmount() {
        this.revokeLocalPreview();
    },

    methods: {
        submit() {
            if (this.disabled) {
                return;
            }

            this.$emit('submit', {
                data: {
                    types: [...this.draft.types],
                    productName: this.draft.productName.trim(),
                    treatmentDate: this.draft.treatmentDate,
                    dueDate: this.optionalString(this.draft.dueDate),
                    note: this.optionalString(this.draft.note),
                },
                photoFile: this.photoFile,
                removePhoto: this.removePhoto,
            });
        },

        selectPhoto(event) {
            const [file] = event.target.files ?? [];
            this.revokeLocalPreview();
            this.photoFile = file ?? null;
            this.removePhoto = false;

            if (file) {
                this.localPreviewUrl = URL.createObjectURL(file);
            }
        },

        removeSelectedPhoto() {
            this.revokeLocalPreview();
            this.photoFile = null;
            this.removePhoto = Boolean(this.treatment?.photo);

            if (this.$refs.photoInput) {
                this.$refs.photoInput.value = '';
            }
        },

        resetPhotoDraft() {
            this.revokeLocalPreview();
            this.photoFile = null;
            this.removePhoto = false;

            if (this.$refs.photoInput) {
                this.$refs.photoInput.value = '';
            }
        },

        revokeLocalPreview() {
            if (this.localPreviewUrl) {
                URL.revokeObjectURL(this.localPreviewUrl);
                this.localPreviewUrl = null;
            }
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
    },

    template: /*language=HTML*/ `
        <ModalDialog
            :title="title"
            :is-open="isOpen"
            :disabled="disabled"
            content-class="modal-content-treatment-form"
            @close="close"
        >
            <form @submit.prevent="submit">
                        <div class="form-group">
                            <label :for="fieldPrefix + '-types'">Treatment types</label>
                            <select :id="fieldPrefix + '-types'" v-model="draft.types" required multiple size="2" autofocus>
                                <option value="flea_tick">Flea &amp; Tick</option>
                                <option value="anti_worm">Anti Worm</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label :for="fieldPrefix + '-product'">Product / brand name</label>
                            <input :id="fieldPrefix + '-product'" v-model="draft.productName"
                                   type="text" required maxlength="255">
                        </div>
                        <div class="form-group">
                            <label :for="fieldPrefix + '-date'">Date of treatment</label>
                            <input :id="fieldPrefix + '-date'" v-model="draft.treatmentDate" type="date" required>
                        </div>
                        <div class="form-group">
                            <label :for="fieldPrefix + '-due-date'">Next due date</label>
                            <input :id="fieldPrefix + '-due-date'" v-model="draft.dueDate" type="date">
                        </div>
                        <div class="form-group">
                            <label :for="fieldPrefix + '-note'">Notes</label>
                            <textarea :id="fieldPrefix + '-note'" v-model="draft.note" rows="3" maxlength="255"></textarea>
                        </div>
                        <div class="form-group treatment-photo-field">
                            <p class="treatment-photo-label">Treatment photo</p>
                            <div v-if="photoPreviewUrl" class="treatment-photo-preview">
                                <img :src="photoPreviewUrl" alt="Treatment photo preview">
                            </div>
                            <div class="media-upload-control treatment-photo-control">
                                <label v-if="!photoPreviewUrl"
                                       :for="fieldPrefix + '-photo'"
                                       class="btn btn-black action-icon-button treatment-photo-action"
                                       title="Choose treatment photo"
                                       aria-label="Choose treatment photo">
                                    <svg class="button-icon" viewBox="0 0 24 24" aria-hidden="true">
                                        <path :d="mdiUpload"></path>
                                    </svg>
                                </label>
                                <button v-else
                                        type="button"
                                        class="btn btn-black action-icon-button treatment-photo-action"
                                        title="Remove treatment photo"
                                        aria-label="Remove treatment photo"
                                        :disabled="disabled"
                                        @click="removeSelectedPhoto">
                                    <svg class="button-icon" viewBox="0 0 24 24" aria-hidden="true">
                                        <path :d="mdiDeleteOutline"></path>
                                    </svg>
                                </button>
                                <input :id="fieldPrefix + '-photo'"
                                       ref="photoInput"
                                       type="file"
                                       accept="image/jpeg,image/png,image/webp"
                                       :disabled="disabled"
                                       @change="selectPhoto">
                            </div>
                            <p class="treatment-photo-help">One JPEG, PNG, or WebP image up to 10 MB.</p>
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
