import { computed, ref } from 'vue';
import TreatmentTable from './islands/dogDetailPage/TreatmentTable.js';
import DogCreateUpdateModal from './ui/modals/DogCreateUpdateModal.js';
import TreatmentFormModal from './ui/modals/TreatmentFormModal.js';
import ConfirmModal from './ui/modals/ConfirmModal.js';
import useAsyncModal from './composables/useAsyncModal.js';
import DogRepository from '../repositories/DogRepository.js';
import TreatmentRepository from '../repositories/TreatmentRepository.js';
import api from '../../../core/api/ApiClient.js';

const dogRepository = new DogRepository(api);
const treatmentRepository = new TreatmentRepository(api);

export default {
    name: 'AppDogDetail',

    components: {
        ConfirmModal,
        DogCreateUpdateModal,
        TreatmentFormModal,
        TreatmentTable,
    },

    props: {
        dog: { type: Object, required: true },
    },

    setup(props) {
        const dogState = ref({ ...props.dog });
        const treatments = ref([...(props.dog.treatments ?? [])]);
        const dogEdit = useAsyncModal({
            fallbackError: 'Unable to update the dog. Please try again.',
        });
        const treatmentForm = useAsyncModal({
            fallbackError: 'Unable to save the treatment. Please try again.',
        });
        const treatmentDelete = useAsyncModal();
        const dogDelete = useAsyncModal();

        const treatmentDeleteText = computed(() => {
            if (treatmentDelete.error) {
                return `Unable to delete this treatment: ${treatmentDelete.error}`;
            }

            const product = treatmentDelete.subject?.productName;

            return product
                ? `Are you sure you want to delete “${product}”?`
                : 'Are you sure you want to delete this treatment?';
        });

        const dogDeleteText = computed(() => {
            if (dogDelete.error) {
                return `Unable to delete this dog: ${dogDelete.error}`;
            }

            const dog = dogDelete.subject?.name;

            return dog
                ? `Are you sure you want to delete “${dog}”?`
                : 'Are you sure you want to delete this dog?';
        });

        function openDogEdit() {
            dogEdit.open(dogState.value);
        }

        async function updateDog(data) {
            await dogEdit.execute(async () => {
                dogState.value = await dogRepository.update(dogState.value.id, data);
            });
        }

        function openTreatmentCreate() {
            treatmentForm.open();
        }

        function openTreatmentEdit(treatment) {
            treatmentForm.open(treatment);
        }

        async function saveTreatment(data) {
            await treatmentForm.execute(async (selectedTreatment) => {
                if (selectedTreatment) {
                    const treatment = await treatmentRepository.update(
                        dogState.value.id,
                        selectedTreatment.id,
                        data,
                    );
                    treatments.value = treatments.value.map(
                        (current) => current.id === treatment.id ? treatment : current,
                    );
                } else {
                    const treatment = await treatmentRepository.create(dogState.value.id, data);
                    treatments.value = [treatment, ...treatments.value];
                }
            });
        }

        async function deleteTreatment() {
            if (!treatmentDelete.subject) {
                return;
            }

            await treatmentDelete.execute(async (treatment) => {
                await treatmentRepository.delete(dogState.value.id, treatment.id);
                treatments.value = treatments.value.filter((current) => current.id !== treatment.id);
            });
        }

        async function deleteDog() {
            if (!dogDelete.subject) {
                return;
            }

            const deleted = await dogDelete.execute(async (dog) => {
                await dogRepository.delete(dog.id);

                return true;
            });

            if (deleted) {
                window.location.assign('/');
            }
        }

        function avatarUrl(avatar) {
            return avatar?.startsWith('images/') ? `/assets/${avatar}` : avatar;
        }

        function formatDate(value, options = { year: 'numeric', month: 'long', day: 'numeric' }) {
            return value ? new Date(`${value}T00:00:00`).toLocaleDateString('en-US', options) : 'Unknown';
        }

        function formatGender(gender) {
            return gender ? gender.charAt(0).toUpperCase() + gender.slice(1) : 'Unknown';
        }

        return {
            dogState,
            treatments,
            dogEdit,
            treatmentForm,
            treatmentDelete,
            dogDelete,
            treatmentDeleteText,
            dogDeleteText,
            openDogEdit,
            updateDog,
            openTreatmentCreate,
            openTreatmentEdit,
            saveTreatment,
            deleteTreatment,
            deleteDog,
            avatarUrl,
            formatDate,
            formatGender,
        };
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
                @delete="treatmentDelete.open"
            />
            <div class="btn-row">
                <button type="button" class="btn-auth btn-signup" @click="dogDelete.open(dogState)">Delete dog</button>
            </div>
            <DogCreateUpdateModal
                :dog="dogEdit.subject || dogState"
                :is-open="dogEdit.isOpen"
                :disabled="dogEdit.isPending"
                :error="dogEdit.error"
                @submit="updateDog"
                @close="dogEdit.close"
            />
            <TreatmentFormModal
                :treatment="treatmentForm.subject"
                :is-open="treatmentForm.isOpen"
                :disabled="treatmentForm.isPending"
                :error="treatmentForm.error"
                @submit="saveTreatment"
                @close="treatmentForm.close"
            />
            <ConfirmModal
                title="Delete Treatment"
                :text="treatmentDeleteText"
                :is-open="treatmentDelete.isOpen"
                :disabled="treatmentDelete.isPending"
                @confirm="deleteTreatment"
                @close="treatmentDelete.close"
            />
            <ConfirmModal
                title="Delete Dog"
                :text="dogDeleteText"
                :is-open="dogDelete.isOpen"
                :disabled="dogDelete.isPending"
                @confirm="deleteDog"
                @close="dogDelete.close"
            />
        </div>
    `,
};
