import TreatmentTable from './islands/dogDetailPage/TreatmentTable.js';
import DogCreateUpdateModal from './ui/modals/DogCreateUpdateModal.js';
import TreatmentFormModal from './ui/modals/TreatmentFormModal.js';
import ConfirmModal from './ui/modals/ConfirmModal.js';
import useDogDetails from './composables/useDogDetails.js';
import useDogTreatments from './composables/useDogTreatments.js';
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
        dogId: { type: Number, required: true },
    },

    setup(props) {
        const dogDetails = useDogDetails(props.dogId, dogRepository);
        const treatmentDetails = useDogTreatments(
            () => props.dogId,
            [],
            treatmentRepository,
        );

        async function loadDog() {
            const dog = await dogDetails.load();

            if (dog) {
                treatmentDetails.replace(dog.treatments);
            }
        }

        void loadDog();

        return {
            dogDetails,
            treatmentDetails,
        };
    },

    template: /*language=HTML*/ `
        <div class="container">
            <p v-if="dogDetails.isLoading">Loading dog details…</p>
            <p v-else-if="dogDetails.loadError" role="alert">
                Unable to load dog details: {{ dogDetails.loadError }}
            </p>
            <template v-else-if="dogDetails.dog">
            <div class="dog-header">
                <div class="dog-avatar">
                    <img v-if="dogDetails.dog.avatar && dogDetails.dog.avatar.startsWith('images/')"
                         :src="dogDetails.avatarUrl(dogDetails.dog.avatar)" :alt="dogDetails.dog.name + ' avatar'">
                    <span v-else-if="dogDetails.dog.avatar">{{ dogDetails.dog.avatar }}</span>
                    <span v-else>🐶</span>
                </div>
                <div class="dog-info">
                    <div class="dog-info-header">
                        <h1>{{ dogDetails.dog.name || 'Unnamed dog' }}</h1>
                        <button type="button" class="icon-button dog-edit-button"
                                :title="'Edit ' + (dogDetails.dog.name || 'dog')"
                                :aria-label="'Edit ' + (dogDetails.dog.name || 'dog')"
                                @click="dogDetails.openEdit">✏️</button>
                    </div>
                    <span class="breed-tag">{{ dogDetails.dog.status ?? 'No status' }}</span>
                    <dl class="dog-details">
                        <div><dt>Gender</dt><dd>{{ dogDetails.formatGender(dogDetails.dog.gender) }}</dd></div>
                        <div><dt>Born</dt><dd>{{ dogDetails.formatDate(dogDetails.dog.birthDate) }}</dd></div>
                        <div><dt>Adopted</dt><dd>{{ dogDetails.formatDate(dogDetails.dog.adoptDate) }}</dd></div>
                        <div><dt>Weight</dt><dd>{{ dogDetails.dog.weight ?? 'Unknown' }}<span v-if="dogDetails.dog.weight !== null && dogDetails.dog.weight !== undefined"> kg</span></dd></div>
                        <div><dt>Height</dt><dd>{{ dogDetails.dog.height ?? 'Unknown' }}<span v-if="dogDetails.dog.height !== null && dogDetails.dog.height !== undefined"> cm</span></dd></div>
                        <div><dt>Status</dt><dd>{{ dogDetails.dog.status ?? 'No status' }}</dd></div>
                    </dl>
                </div>
            </div>
            <div class="button-line">
                <button type="button" class="btn-outline" @click="treatmentDetails.openCreate">Add treatment</button>
            </div>

            <TreatmentTable
                :treatments="treatmentDetails.treatments"
                @edit="treatmentDetails.openEdit"
                @delete="treatmentDetails.deleteModal.open"
            />
            <div class="btn-row">
                <button type="button" class="btn-auth btn-signup"
                        @click="dogDetails.deleteModal.open(dogDetails.dog)">Delete dog</button>
            </div>
            <DogCreateUpdateModal
                :dog="dogDetails.editModal.subject || dogDetails.dog"
                :is-open="dogDetails.editModal.isOpen"
                :disabled="dogDetails.editModal.isPending"
                :error="dogDetails.editModal.error"
                @submit="dogDetails.update"
                @close="dogDetails.editModal.close"
            />
            <TreatmentFormModal
                :treatment="treatmentDetails.formModal.subject"
                :is-open="treatmentDetails.formModal.isOpen"
                :disabled="treatmentDetails.formModal.isPending"
                :error="treatmentDetails.formModal.error"
                @submit="treatmentDetails.save"
                @close="treatmentDetails.formModal.close"
            />
            <ConfirmModal
                title="Delete Treatment"
                :text="treatmentDetails.deleteText"
                :is-open="treatmentDetails.deleteModal.isOpen"
                :disabled="treatmentDetails.deleteModal.isPending"
                @confirm="treatmentDetails.remove"
                @close="treatmentDetails.deleteModal.close"
            />
            <ConfirmModal
                title="Delete Dog"
                :text="dogDetails.deleteText"
                :is-open="dogDetails.deleteModal.isOpen"
                :disabled="dogDetails.deleteModal.isPending"
                @confirm="dogDetails.remove"
                @close="dogDetails.deleteModal.close"
            />
            </template>
        </div>
    `,
};
