import TreatmentTable from './islands/dogDetailPage/TreatmentTable.js';
import DogCreateUpdateModal from './ui/modals/DogCreateUpdateModal.js';
import TreatmentFormModal from './ui/modals/TreatmentFormModal.js';
import ConfirmModal from './ui/modals/ConfirmModal.js';
import useDogDetails from './composables/useDogDetails.js';
import useDogTreatments from './composables/useDogTreatments.js';
import DogRepository from '../repositories/DogRepository.js';
import TreatmentRepository from '../repositories/TreatmentRepository.js';
import api from '../../../core/api/ApiClient.js';
import { mdiCircleEditOutline, mdiDeleteCircleOutline } from '@mdi/js';

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
        mediaUrl: { type: String, required: true },
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
            mdiCircleEditOutline,
            mdiDeleteCircleOutline,
        };
    },

    template: /*language=HTML*/ `
        <div>
            <p v-if="dogDetails.isLoading" class="container dog-detail-message">Loading dog details…</p>
            <p v-else-if="dogDetails.loadError" class="container dog-detail-message" role="alert">
                Unable to load dog details: {{ dogDetails.loadError }}
            </p>
            <template v-else-if="dogDetails.dog">
            <section class="dog-profile" aria-labelledby="dog-profile-title">
                <div class="dog-profile-media">
                    <video
                        class="dog-profile-video"
                        :src="mediaUrl"
                        :aria-label="'Video of ' + (dogDetails.dog.name || 'the dog')"
                        autoplay
                        muted
                        loop
                        playsinline
                    ></video>
                </div>
                <div class="dog-profile-content">
                    <div class="dog-profile-heading">
                        <h1 id="dog-profile-title">{{ dogDetails.dog.name || 'Unnamed dog' }}</h1>
                        <div class="dog-profile-actions">
                            <button type="button" class="btn btn-white action-icon-button"
                                    :title="'Edit ' + (dogDetails.dog.name || 'dog')"
                                    :aria-label="'Edit ' + (dogDetails.dog.name || 'dog')"
                                    @click="dogDetails.openEdit">
                                <svg class="button-icon" viewBox="0 0 24 24" aria-hidden="true">
                                    <path :d="mdiCircleEditOutline"></path>
                                </svg>
                            </button>
                            <button type="button" class="btn btn-white action-icon-button"
                                    :title="'Delete ' + (dogDetails.dog.name || 'dog')"
                                    :aria-label="'Delete ' + (dogDetails.dog.name || 'dog')"
                                    @click="dogDetails.deleteModal.open(dogDetails.dog)">
                                <svg class="button-icon" viewBox="0 0 24 24" aria-hidden="true">
                                    <path :d="mdiDeleteCircleOutline"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="dog-profile-cards">
                        <dl class="dog-profile-card">
                            <div class="dog-profile-field">
                                <dt>Status</dt>
                                <dd>{{ dogDetails.dog.status || '—' }}</dd>
                            </div>
                            <div class="dog-profile-field">
                                <dt>Date of birth</dt>
                                <dd>{{ dogDetails.formatDate(dogDetails.dog.birthDate) }}</dd>
                            </div>
                            <div class="dog-profile-field">
                                <dt>Date of adoption</dt>
                                <dd>{{ dogDetails.formatDate(dogDetails.dog.adoptDate) }}</dd>
                            </div>
                        </dl>

                        <dl class="dog-profile-card">
                            <div class="dog-profile-field">
                                <dt>Gender</dt>
                                <dd>{{ dogDetails.formatGender(dogDetails.dog.gender) }}</dd>
                            </div>
                            <div class="dog-profile-field">
                                <dt>Height</dt>
                                <dd>
                                    {{ dogDetails.dog.height ?? '—' }}<span v-if="dogDetails.dog.height !== null && dogDetails.dog.height !== undefined"> cm</span>
                                </dd>
                            </div>
                            <div class="dog-profile-field">
                                <dt>Weight</dt>
                                <dd>
                                    {{ dogDetails.dog.weight ?? '—' }}<span v-if="dogDetails.dog.weight !== null && dogDetails.dog.weight !== undefined"> kg</span>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </section>
            <div class="container dog-detail-content">
            <TreatmentTable
                :treatments="treatmentDetails.treatments"
                @add="treatmentDetails.openCreate"
                @edit="treatmentDetails.openEdit"
                @delete="treatmentDetails.deleteModal.open"
            />
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
            </div>
            </template>
        </div>
    `,
};
