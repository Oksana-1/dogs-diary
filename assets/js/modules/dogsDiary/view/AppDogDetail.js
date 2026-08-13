import TreatmentTable from './islands/dogDetailPage/TreatmentTable.js';
import DogProfileMedia from './islands/dogDetailPage/DogProfileMedia.js';
import DogMediaLibrary from './islands/dogDetailPage/DogMediaLibrary.js';
import DogCreateUpdateModal from './ui/modals/DogCreateUpdateModal.js';
import TreatmentFormModal from './ui/modals/TreatmentFormModal.js';
import TreatmentPhotoModal from './ui/modals/TreatmentPhotoModal.js';
import ConfirmModal from './ui/modals/ConfirmModal.js';
import useDogDetails from './composables/useDogDetails.js';
import useDogTreatments from './composables/useDogTreatments.js';
import useDogMedia from './composables/useDogMedia.js';
import DogRepository from '../repositories/DogRepository.js';
import TreatmentRepository from '../repositories/TreatmentRepository.js';
import TreatmentMediaRepository from '../repositories/TreatmentMediaRepository.js';
import DogMediaRepository from '../repositories/DogMediaRepository.js';
import api from '../../../core/api/ApiClient.js';
import { ref } from 'vue';
import { mdiCircleEditOutline, mdiDeleteCircleOutline } from '@mdi/js';

const dogRepository = new DogRepository(api);
const treatmentRepository = new TreatmentRepository(api);
const treatmentMediaRepository = new TreatmentMediaRepository(api);
const mediaRepository = new DogMediaRepository(api);

export default {
    name: 'AppDogDetail',

    components: {
        ConfirmModal,
        DogCreateUpdateModal,
        TreatmentFormModal,
        TreatmentPhotoModal,
        TreatmentTable,
        DogProfileMedia,
        DogMediaLibrary,
    },

    props: {
        dogId: { type: Number, required: true },
    },

    setup(props) {
        const dogDetails = useDogDetails(props.dogId, dogRepository);
        const mediaDetails = useDogMedia(
            props.dogId,
            mediaRepository,
            () => dogDetails.dog,
        );
        const treatmentDetails = useDogTreatments(
            () => props.dogId,
            [],
            treatmentRepository,
            treatmentMediaRepository,
        );
        const treatmentPhoto = ref(null);

        function viewTreatmentPhoto(photo) {
            treatmentPhoto.value = photo;
        }

        function closeTreatmentPhoto() {
            treatmentPhoto.value = null;
        }

        async function loadDog() {
            const dog = await dogDetails.load();

            if (dog) {
                treatmentDetails.replace(dog.treatments);
                await mediaDetails.load();
            }
        }

        void loadDog();

        return {
            dogDetails,
            treatmentDetails,
            mediaDetails,
            treatmentPhoto,
            viewTreatmentPhoto,
            closeTreatmentPhoto,
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
                <DogProfileMedia :media="dogDetails.dog.profileMedia" :dog-name="dogDetails.dog.name" />
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
                @view-photo="viewTreatmentPhoto"
            />
            <DogMediaLibrary :dog-name="dogDetails.dog.name" :state="mediaDetails" />
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
            <TreatmentPhotoModal
                :photo="treatmentPhoto"
                :is-open="Boolean(treatmentPhoto)"
                @close="closeTreatmentPhoto"
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
