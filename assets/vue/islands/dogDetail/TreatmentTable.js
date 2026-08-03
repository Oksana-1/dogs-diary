export default {
    name: 'TreatmentTable',

    props: {
        treatments: { type: Array, default: () => [] },
    },

    computed: {
        todayTreatments() {
            const today = new Date().toISOString().slice(0, 10);

            return this.treatments.filter((treatment) => treatment.treatmentDate === today);
        },
    },

    methods: {
        formatType(type) {
            return type.replaceAll('_', ' ');
        },
    },

    template: /*language=HTML*/ `
        <div v-if="todayTreatments.length" class="treatments-section">
            <h2>Today's Treatments</h2>
            <table class="treatments-table">
                <thead>
                <tr>
                    <th>Type</th><th>Product</th><th>Date</th><th>Next Due</th><th>Notes</th><th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="treatment in todayTreatments" :key="treatment.id" :data-treatment-id="treatment.id">
                    <td>
                        <span v-for="type in treatment.types" :key="type" class="treatment-badge">{{ formatType(type) }}</span>
                    </td>
                    <td>{{ treatment.productName }}</td>
                    <td>{{ treatment.treatmentDate }}</td>
                    <td>{{ treatment.dueDate ?? '—' }}</td>
                    <td>{{ treatment.note ?? '—' }}</td>
                    <td class="treatment-actions">
                        <button type="button" class="action-button action-edit"
                                :data-treatment-id="treatment.id"
                                :data-treatment-type="treatment.types.join(',')"
                                :data-treatment-product="treatment.productName"
                                :data-treatment-date="treatment.treatmentDate"
                                :data-treatment-due="treatment.dueDate"
                                :data-treatment-note="treatment.note"
                                title="Edit treatment" aria-label="Edit treatment">✏️</button>
                        <button type="button" class="action-button action-delete"
                                :data-treatment-id="treatment.id"
                                :data-treatment-product="treatment.productName"
                                title="Delete treatment" aria-label="Delete treatment">🗑️</button>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    `,
};
