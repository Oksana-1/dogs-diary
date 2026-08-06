import TreatmentTable from './islands/dogDetailPage/TreatmentTable.js';
import DogCreateUpdateModal from './ui/modals/DogCreateUpdateModal.js';
import TreatmentFormModal from './ui/modals/TreatmentFormModal.js';
import BaseModal from './ui/modals/BaseModal.js';
import DogRepository from '../modules/dogsDiary/repositories/DogRepository.js';
import TreatmentRepository from '../modules/dogsDiary/repositories/TreatmentRepository.js';
import api from '../core/api/ApiClient.js';

const dogRepository = new DogRepository(api);
const treatmentRepository = new TreatmentRepository(api);

export default {
    name: 'AppDogDetail',

    components: {
        BaseModal,
        DogCreateUpdateModal,
        TreatmentFormModal,
        TreatmentTable,
    },

    props: {
        dog: { type: Object, required: true },
    },

    data() {
        return {
            dogState: { ...this.dog },
            treatments: [...(this.dog.treatments ?? [])],
            isDogEditOpen: false,
            isDogSaving: false,
            dogEditError: null,
            isTreatmentFormOpen: false,
            selectedTreatment: null,
            isTreatmentSaving: false,
            treatmentFormError: null,
            treatmentToDelete: null,
            isTreatmentDeleting: false,
            treatmentDeleteError: null,
            dogToDelete: null,
            dogDeleteError: null,
            isDogDeleting: false,
        };
    },

    computed: {
        treatmentDeleteText() {
            if (this.treatmentDeleteError) {
                return `Unable to delete this treatment: ${this.treatmentDeleteError}`;
            }

            const product = this.treatmentToDelete?.productName;

            return product
                ? `Are you sure you want to delete “${product}”?`
                : 'Are you sure you want to delete this treatment?';
        },
        dogDeleteText() {
            if (this.dogDeleteError) {
                return `Unable to delete this dog: ${this.dogDeleteError}`;
            }

            const dog = this.dogToDelete?.name;

            return dog
                ? `Are you sure you want to delete “${dog}”?`
                : 'Are you sure you want to delete this dog?';
        }
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

        openTreatmentCreate() {
            this.selectedTreatment = null;
            this.treatmentFormError = null;
            this.isTreatmentFormOpen = true;
        },

        openTreatmentEdit(treatment) {
            this.selectedTreatment = treatment;
            this.treatmentFormError = null;
            this.isTreatmentFormOpen = true;
        },

        closeTreatmentForm() {
            if (!this.isTreatmentSaving) {
                this.isTreatmentFormOpen = false;
                this.selectedTreatment = null;
            }
        },

        async saveTreatment(data) {
            this.isTreatmentSaving = true;
            this.treatmentFormError = null;

            try {
                if (this.selectedTreatment) {
                    const treatment = await treatmentRepository.update(
                        this.dogState.id,
                        this.selectedTreatment.id,
                        data,
                    );
                    this.treatments = this.treatments.map(
                        (current) => current.id === treatment.id ? treatment : current,
                    );
                } else {
                    const treatment = await treatmentRepository.create(this.dogState.id, data);
                    this.treatments = [treatment, ...this.treatments];
                }

                this.isTreatmentFormOpen = false;
                this.selectedTreatment = null;
            } catch (error) {
                console.error('Treatment save failed:', error);
                this.treatmentFormError = error.message || 'Unable to save the treatment. Please try again.';
            } finally {
                this.isTreatmentSaving = false;
            }
        },

        openTreatmentDelete(treatment) {
            this.treatmentToDelete = treatment;
            this.treatmentDeleteError = null;
        },

        closeTreatmentDelete() {
            if (!this.isTreatmentDeleting) {
                this.treatmentToDelete = null;
                this.treatmentDeleteError = null;
            }
        },
        closeDogDelete() {
            if (!this.isDogDeleting) {
                this.dogToDelete = null;
                this.dogDeleteError = null;
            }
        },
        async deleteTreatment() {
            if (!this.treatmentToDelete) {
                return;
            }

            const treatmentId = this.treatmentToDelete.id;
            this.isTreatmentDeleting = true;
            this.treatmentDeleteError = null;

            try {
                await treatmentRepository.delete(this.dogState.id, treatmentId);
                this.treatments = this.treatments.filter((treatment) => treatment.id !== treatmentId);
                this.treatmentToDelete = null;
            } catch (error) {
                console.error('Treatment delete failed:', error);
                this.treatmentDeleteError = error.message || 'Please try again.';
            } finally {
                this.isTreatmentDeleting = false;
            }
        },
        async deleteDog() {
            if (!this.dogToDelete) {
                return;
            }

            const dogId = this.dogToDelete.id;
            this.isDogDeleting = true;
            this.dogDeleteError = null;

            try {
                await dogRepository.delete(dogId);
                this.dogToDelete = null;
                window.location.assign('/');
            } catch (error) {
                console.error('Dog delete failed:', error);
                this.dogDeleteError = error.message || 'Please try again.';
            } finally {
                this.isDogDeleting = false;
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
        openDogDelete(dog) {
            this.dogToDelete = dog;
            this.dogDeleteError = null;
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
                <button type="button" class="btn-outline" @click="openTreatmentCreate">Add treatment</button>
            </div>

            <TreatmentTable
                :treatments="treatments"
                @edit="openTreatmentEdit"
                @delete="openTreatmentDelete"
            />
            <div class="btn-row">
                <button type="button" class="btn-auth btn-signup" @click="openDogDelete(dogState)">Delete dog</button>
            </div>
            <DogCreateUpdateModal
                :dog="dogState"
                :is-open="isDogEditOpen"
                :disabled="isDogSaving"
                :error="dogEditError"
                @on-resolve="updateDog"
                @on-reject="closeDogEdit"
            />
            <TreatmentFormModal
                :treatment="selectedTreatment"
                :is-open="isTreatmentFormOpen"
                :disabled="isTreatmentSaving"
                :error="treatmentFormError"
                @on-resolve="saveTreatment"
                @on-reject="closeTreatmentForm"
            />
            <BaseModal
                title="Delete Treatment"
                :text="treatmentDeleteText"
                :is-open="treatmentToDelete !== null"
                :disabled="isTreatmentDeleting"
                @on-resolve="deleteTreatment"
                @on-reject="closeTreatmentDelete"
            />
            <BaseModal
                title="Delete Dog"
                :text="dogDeleteText"
                :is-open="dogToDelete !== null"
                :disabled="isDogDeleting"
                @on-resolve="deleteDog"
                @on-reject="closeDogDelete"
            />
        </div>
    `,
};
