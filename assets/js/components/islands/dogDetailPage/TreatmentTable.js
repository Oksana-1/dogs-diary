export default {
    name: 'TreatmentTable',

    props: {
        treatments: { type: Array, default: () => [] },
    },

    emits: ['edit', 'delete'],

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
            <h2>Treatments</h2>
            <table v-if="sortedTreatments.length" class="treatments-table">
                <thead>
                <tr>
                    <th>Type</th><th>Product</th><th>Date</th><th>Next Due</th><th>Notes</th><th>Actions</th>
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
                    <td class="treatment-actions">
                        <button type="button" class="action-button action-edit"
                                @click="$emit('edit', treatment)"
                                title="Edit treatment" aria-label="Edit treatment">✏️</button>
                        <button type="button" class="action-button action-delete"
                                @click="$emit('delete', treatment)"
                                title="Delete treatment" aria-label="Delete treatment">🗑️</button>
                    </td>
                </tr>
                </tbody>
            </table>
            <p v-else>No treatments recorded.</p>
        </div>
    `,
};
