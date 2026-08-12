import { ref } from 'vue';
import DogRepository from '../repositories/DogRepository.js';
import DogCreateUpdateModal from './ui/modals/DogCreateUpdateModal.js';
import DogItem from './islands/dogListPage/DogItem.js';
import useAsyncModal from './composables/useAsyncModal.js';
import api from '../../../core/api/ApiClient.js';

const repository = new DogRepository(api);
const emptyDog = Object.freeze({
    name: '',
    birthDate: '',
    gender: '',
    adoptDate: '',
    status: '',
    weight: '',
    height: '',
});

export default {
    name: 'AppDogList',
    components: {
        DogItem,
        DogCreateUpdateModal
    },
    setup() {
        const dogs = ref([]);
        const isLoading = ref(false);
        const error = ref(null);
        const dogCreate = useAsyncModal({
            fallbackError: 'Unable to save the dog. Please try again.',
        });

        async function loadDogs() {
            isLoading.value = true;
            error.value = null;

            try {
                dogs.value = await repository.list();
            } catch (requestError) {
                console.error(requestError);
                error.value = 'Unable to load dogs. Please try again.';
            } finally {
                isLoading.value = false;
            }
        }

        async function createDog(data) {
            await dogCreate.execute(async () => {
                const dog = await repository.create(data);
                dogs.value = [...dogs.value, dog];
                error.value = null;
            });
        }

        void loadDogs();

        return {
            dogs,
            isLoading,
            error,
            dogCreate,
            emptyDog,
            createDog,
        };
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
                    <button type="button" class="btn btn-black" @click="dogCreate.open(emptyDog)">Add dog</button>
                </div>
            </div>
            <DogCreateUpdateModal
                :dog="dogCreate.subject || emptyDog"
                :is-open="dogCreate.isOpen"
                :disabled="dogCreate.isPending"
                :error="dogCreate.error"
                @submit="createDog"
                @close="dogCreate.close"
            />
        </section>
    `,
};
