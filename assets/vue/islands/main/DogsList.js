import DogRepository from "../../../js/modules/dogsDiary/repositories/DogRepository.js";
import DogItem from "./DogItem.js";
import api from "../../../js/core/api/ApiClient.js";
const repository = new DogRepository(api);
export default {
    name: 'DogsList',
    components: {
      DogItem,
    },
    data() {
        return {
            dogs: null,
            isLoading: false,
        }
    },
    methods: {
        async loadDogs() {
            this.isLoading = true;
            try {
                this.dogs = await repository.list();
            } catch (error) {
                console.error(error);
            } finally {
                this.isLoading = false;
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
                <div v-else>
                    <DogItem v-for="dog in dogs" :dog="dog" :key="dog.id"/>
                </div>
            </div>
        </section>
    `,
};
