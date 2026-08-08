import ModalDialog from './ModalDialog.js';

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
        };
    },

    computed: {
        title() {
            return this.treatment ? 'Edit Treatment' : 'Add Treatment';
        },
    },

    watch: {
        isOpen(isOpen) {
            if (isOpen) {
                this.draft = treatmentDraft(this.treatment);
            }
        },
    },

    methods: {
        submit() {
            if (this.disabled) {
                return;
            }

            this.$emit('submit', {
                types: [...this.draft.types],
                productName: this.draft.productName.trim(),
                treatmentDate: this.draft.treatmentDate,
                dueDate: this.optionalString(this.draft.dueDate),
                note: this.optionalString(this.draft.note),
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
    },

    template: /*language=HTML*/ `
        <ModalDialog
            :title="title"
            :is-open="isOpen"
            :disabled="disabled"
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
                        <p v-if="error" class="modal-error" role="alert">{{ error }}</p>
                        <div class="modal-actions">
                            <button type="button" class="btn-secondary" :disabled="disabled" @click="close">
                                Cancel
                            </button>
                            <button type="submit" class="btn-primary" :disabled="disabled">
                                {{ disabled ? 'Saving…' : 'Save' }}
                            </button>
                        </div>
            </form>
        </ModalDialog>
    `,
};
