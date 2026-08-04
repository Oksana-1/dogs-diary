import TreatmentTable from './TreatmentTable.js';
import DogEditModal from '../../ui/modals/DogEditModal.js';
import DogRepository from '../../../js/modules/dogsDiary/repositories/DogRepository.js';
import api from '../../../js/core/api/ApiClient.js';

const dogRepository = new DogRepository(api);

export default {
    name: 'DogDetail',

    components: {
        DogEditModal,
        TreatmentTable,
    },

    props: {
        dog: { type: Object, required: true },
    },

    data() {
        return {
            dogState: { ...this.dog },
            isDogEditOpen: false,
            isDogSaving: false,
            dogEditError: null,
        };
    },

    methods: {
        openDogEdit() {
            this.dogEditError = null;
            this.isDogEditOpen = true;
        },

        closeDogEdit() {
            if (!this.isDogSaving) {
                this.isDogEditOpen = false;
            }
        },

        async updateDog(data) {
            this.isDogSaving = true;
            this.dogEditError = null;

            try {
                this.dogState = await dogRepository.update(this.dogState.id, data);
                this.isDogEditOpen = false;
            } catch (error) {
                console.error('Dog update failed:', error);
                this.dogEditError = error.message || 'Unable to update the dog. Please try again.';
            } finally {
                this.isDogSaving = false;
            }
        },

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
                    <img v-if="dogState.avatar && dogState.avatar.startsWith('images/')"
                         :src="avatarUrl(dogState.avatar)" :alt="dogState.name + ' avatar'">
                    <span v-else-if="dogState.avatar">{{ dogState.avatar }}</span>
                    <span v-else>🐶</span>
                </div>
                <div class="dog-info">
                    <div class="dog-info-header">
                        <h1>{{ dogState.name || 'Unnamed dog' }}</h1>
                        <button type="button" class="icon-button dog-edit-button"
                                :title="'Edit ' + (dogState.name || 'dog')"
                                :aria-label="'Edit ' + (dogState.name || 'dog')"
                                @click="openDogEdit">✏️</button>
                    </div>
                    <span class="breed-tag">{{ dogState.status ?? 'No status' }}</span>
                    <dl class="dog-details">
                        <div><dt>Gender</dt><dd>{{ formatGender(dogState.gender) }}</dd></div>
                        <div><dt>Born</dt><dd>{{ formatDate(dogState.birthDate) }}</dd></div>
                        <div><dt>Adopted</dt><dd>{{ formatDate(dogState.adoptDate) }}</dd></div>
                        <div><dt>Weight</dt><dd>{{ dogState.weight ?? 'Unknown' }}<span v-if="dogState.weight !== null && dogState.weight !== undefined"> kg</span></dd></div>
                        <div><dt>Height</dt><dd>{{ dogState.height ?? 'Unknown' }}<span v-if="dogState.height !== null && dogState.height !== undefined"> cm</span></dd></div>
                        <div><dt>Status</dt><dd>{{ dogState.status ?? 'No status' }}</dd></div>
                    </dl>
                </div>
            </div>
            <div class="button-line">
                <button class="btn-outline" data-action="modal#open">Add treatment</button>
            </div>

            <TreatmentTable :treatments="dogState.treatments" />
            <DogEditModal
                :dog="dogState"
                :is-open="isDogEditOpen"
                :disabled="isDogSaving"
                :error="dogEditError"
                @on-resolve="updateDog"
                @on-reject="closeDogEdit"
            />
        </div>
    `,
};
