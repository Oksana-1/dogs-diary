import TreatmentTable from './TreatmentTable.js';

export default {
    name: 'DogDetail',

    components: {
        TreatmentTable,
    },

    props: {
        dog: { type: Object, required: true },
    },

    methods: {
        avatarUrl(avatar) {
            return avatar?.startsWith('images/') ? `/assets/${avatar}` : avatar;
        },

        formatDate(value, options = { year: 'numeric', month: 'long', day: 'numeric' }) {
            return value ? new Date(`${value}T00:00:00`).toLocaleDateString('en-US', options) : 'Unknown';
        },

        formatGender(gender) {
            return gender ? gender.charAt(0).toUpperCase() + gender.slice(1) : 'Unknown';
        },
    },

    template: /*language=HTML*/ `
        <div class="container">
            <div class="dog-header">
                <div class="dog-avatar">
                    <img v-if="dog.avatar && dog.avatar.startsWith('images/')"
                         :src="avatarUrl(dog.avatar)" :alt="dog.name + ' avatar'">
                    <span v-else-if="dog.avatar">{{ dog.avatar }}</span>
                    <span v-else>🐶</span>
                </div>
                <div class="dog-info">
                    <div class="dog-info-header">
                        <h1>{{ dog.name || 'Unnamed dog' }}</h1>
                        <button type="button" class="icon-button dog-edit-button"
                                :title="'Edit ' + (dog.name || 'dog')"
                                :aria-label="'Edit ' + (dog.name || 'dog')"
                                data-action="dog-edit#open">✏️</button>
                    </div>
                    <span class="breed-tag">{{ dog.status ?? 'No status' }}</span>
                    <dl class="dog-details">
                        <div><dt>Gender</dt><dd>{{ formatGender(dog.gender) }}</dd></div>
                        <div><dt>Born</dt><dd>{{ formatDate(dog.birthDate) }}</dd></div>
                        <div><dt>Adopted</dt><dd>{{ formatDate(dog.adoptDate) }}</dd></div>
                        <div><dt>Weight</dt><dd>{{ dog.weight ?? 'Unknown' }}<span v-if="dog.weight !== null && dog.weight !== undefined"> kg</span></dd></div>
                        <div><dt>Height</dt><dd>{{ dog.height ?? 'Unknown' }}<span v-if="dog.height !== null && dog.height !== undefined"> cm</span></dd></div>
                        <div><dt>Status</dt><dd>{{ dog.status ?? 'No status' }}</dd></div>
                    </dl>
                </div>
            </div>
            <div class="button-line">
                <button class="btn-outline" data-action="modal#open">Add treatment</button>
            </div>

            <TreatmentTable :treatments="dog.treatments" />
        </div>
    `,
};
