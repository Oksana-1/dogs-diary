import { mdiCircleEditOutline, mdiDeleteCircleOutline, mdiImageOutline, mdiPlusCircleOutline } from '@mdi/js';

export default {
    name: 'TreatmentTable',

    props: {
        treatments: { type: Array, default: () => [] },
    },

    emits: ['add', 'edit', 'delete', 'view-photo'],

    data() {
        return {
            mdiCircleEditOutline,
            mdiDeleteCircleOutline,
            mdiImageOutline,
            mdiPlusCircleOutline,
        };
    },

    computed: {
        sortedTreatments() {
            return [...this.treatments].sort(
                (left, right) => right.treatmentDate.localeCompare(left.treatmentDate),
            );
        },
    },

    methods: {
        formatType(type) {
            return type.replaceAll('_', ' ');
        },
    },

    template: /*language=HTML*/ `
        <div class="treatments-section">
            <div class="treatments-heading">
                <h2 class="h2">Treatments</h2>
                <button type="button" class="btn btn-white action-icon-button"
                        @click="$emit('add')"
                        title="Add treatment" aria-label="Add treatment">
                    <svg class="button-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path :d="mdiPlusCircleOutline"></path>
                    </svg>
                </button>
            </div>
            <table v-if="sortedTreatments.length" class="treatments-table">
                <thead>
                <tr>
                    <th>Type</th>
                    <th>Product</th>
                    <th>Date</th>
                    <th>Next Due</th>
                    <th style="width: 20%">Notes</th>
                    <th class="treatment-photo-column">Photo</th>
                    <th class="treatment-actions">Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="treatment in sortedTreatments" :key="treatment.id">
                    <td>
                        <span v-for="type in treatment.types" :key="type" class="treatment-badge">{{ formatType(type) }}</span>
                    </td>
                    <td>{{ treatment.productName }}</td>
                    <td>{{ treatment.treatmentDate }}</td>
                    <td>{{ treatment.dueDate ?? '—' }}</td>
                    <td>{{ treatment.note ?? '—' }}</td>
                    <td class="treatment-photo-column">
                        <button v-if="treatment.photo"
                                type="button"
                                class="btn btn-white action-icon-button treatment-photo-button"
                                title="View treatment photo"
                                :aria-label="'View photo for ' + treatment.productName"
                                @click="$emit('view-photo', treatment.photo)">
                            <svg class="button-icon" viewBox="0 0 24 24" aria-hidden="true">
                                <path :d="mdiImageOutline"></path>
                            </svg>
                        </button>
                        <span v-else aria-label="No treatment photo">—</span>
                    </td>
                    <td class="treatment-actions">
                        <button type="button" class="btn btn-white action-icon-button"
                                @click="$emit('edit', treatment)"
                                title="Edit treatment" aria-label="Edit treatment">
                            <svg class="button-icon" viewBox="0 0 24 24" aria-hidden="true">
                                <path :d="mdiCircleEditOutline"></path>
                            </svg>
                        </button>
                        <button type="button" class="btn btn-white action-icon-button"
                                @click="$emit('delete', treatment)"
                                title="Delete treatment" aria-label="Delete treatment">
                            <svg class="button-icon" viewBox="0 0 24 24" aria-hidden="true">
                                <path :d="mdiDeleteCircleOutline"></path>
                            </svg>
                        </button>
                    </td>
                </tr>
                </tbody>
            </table>
            <p v-else>No treatments recorded.</p>
        </div>
    `,
};
