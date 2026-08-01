import Dog from "./Dog.js";
export default class DogCollection {
    constructor(dogs) {
        return dogs.map(dog => new Dog(dog))
    }
}
