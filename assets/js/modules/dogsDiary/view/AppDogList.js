import DogRepository from "../repositories/DogRepository.js";
import DogCreateUpdateModal from './ui/modals/DogCreateUpdateModal.js';
import DogItem from './islands/dogListPage/DogItem.js';
import api from "../../../core/api/ApiClient.js";
const repository = new DogRepository(api);
export default {
    name: 'AppDogList',
    components: {
        DogItem,
        DogCreateUpdateModal
    },
    data() {
        return {
            dogs: [],
            isLoading: false,
            error: null,
            isDogCreateOpen: false,
            isDogSaving: false,
            dogCreateError: null,
            dogInitial: {
                name: '',
                birthDate:'',
                gender: '',
                adoptDate: '',
                status:  '',
                avatar:  '',
                weight: '',
                height:'',
            }
        }
    },
    methods: {
        async loadDogs() {
            this.isLoading = true;
            this.error = null;
            try {
                this.dogs = await repository.list();
            } catch (error) {
                console.error(error);
                this.error = 'Unable to load dogs. Please try again.';
            } finally {
                this.isLoading = false;
            }
        },
        showDogCreateModal() {
            this.dogCreateError = null;
            this.isDogCreateOpen = true;
        },
        closeDogCreateModal() {
            this.isDogCreateOpen = false;
        },
        async createDog(data) {
            this.isDogSaving = true;
            this.dogCreateError = null;
            try {
                const dog = await repository.create(data);
                this.dogs = [...this.dogs, dog];
                this.error = null;
                this.isDogCreateOpen = false;
            } catch (error) {
                console.error('Dog save failed:', error);
                this.dogCreateError = error.message || 'Unable to save the dog. Please try again.';
            } finally {
                this.isDogSaving = false;
            }
        }
    },
    created() {
      this.loadDogs();
    },
    template: `
        <section class="dogs-section">
            <div class="dogs-container">
                <div v-if="isLoading">Is loading...</div>
                <div v-else-if="error" role="alert">{{ error }}</div>
                <template v-else>
                     <div v-if="dogs.length">
                        <DogItem v-for="dog in dogs" :dog="dog" :key="dog.id"/>
                    </div>
                    <div v-else>No dogs found.</div>
                </template>
                <div class="btn-row">
                    <button class="btn-auth btn-signup" @click="showDogCreateModal">Add dog</button>
                </div>
            </div>
            <DogCreateUpdateModal
                :dog="dogInitial"
                :is-open="isDogCreateOpen"
                :disabled="isDogSaving"
                :error="dogCreateError"
                @on-resolve="createDog"
                @on-reject="closeDogCreateModal"
            />
        </section>
    `,
};
