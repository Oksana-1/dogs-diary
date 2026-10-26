import DogRepository from '../../../../../assets/js/modules/dogsDiary/repositories/DogRepository.js';
import Dog from '../../../../../assets/js/modules/dogsDiary/entities/Dog.js';

const { module, test } = window.QUnit;

module('DogRepository', () => {
    test('find uses the expected request and maps its response', async assert => {
        const response = { id: 7, name: "Milo" };
        const calls = [];
        const repository = new DogRepository({
            get: async (...args) => { calls.push(args); return response; },
        });
        const result = await repository.find(7);
        assert.deepEqual(calls, [['/dogs/7']]);
        assert.true(result instanceof Dog);
        assert.strictEqual(result.id, response.id);
    });

    test('find propagates request failures unchanged', async assert => {
        const failure = new Error('Request failed');
        const repository = new DogRepository({ get: async () => { throw failure; } });
        await assert.rejects(repository.find(7), error => error === failure);
    });

    test('list uses the expected request and maps its response', async assert => {
        const response = [{ id: 7, name: "Milo" }];
        const calls = [];
        const repository = new DogRepository({
            get: async (...args) => { calls.push(args); return response; },
        });
        const result = await repository.list();
        assert.deepEqual(calls, [['/dogs']]);
        assert.strictEqual(result.length, response.length);
        assert.true(result[0] instanceof Dog);
        assert.strictEqual(result[0].id, response[0].id);
    });

    test('list propagates request failures unchanged', async assert => {
        const failure = new Error('Request failed');
        const repository = new DogRepository({ get: async () => { throw failure; } });
        await assert.rejects(repository.list(), error => error === failure);
    });

    test('create uses the expected request and maps its response', async assert => {
        const payload = { name: "Luna", birthDate: "2020-01-01" };
        const response = { id: 7, ...payload };
        const calls = [];
        const repository = new DogRepository({
            post: async (...args) => { calls.push(args); return response; },
        });
        const result = await repository.create(payload);
        assert.deepEqual(calls, [['/dogs', payload]]);
        assert.true(result instanceof Dog);
        assert.strictEqual(result.id, response.id);
    });

    test('create propagates request failures unchanged', async assert => {
        const payload = { name: "Luna", birthDate: "2020-01-01" };
        const failure = new Error('Request failed');
        const repository = new DogRepository({ post: async () => { throw failure; } });
        await assert.rejects(repository.create(payload), error => error === failure);
    });

    test('update uses the expected request and maps its response', async assert => {
        const payload = { name: "Luna", birthDate: "2020-01-01" };
        const response = { id: 7, ...payload };
        const calls = [];
        const repository = new DogRepository({
            put: async (...args) => { calls.push(args); return response; },
        });
        const result = await repository.update(7, payload);
        assert.deepEqual(calls, [['/dogs/7', payload]]);
        assert.true(result instanceof Dog);
        assert.strictEqual(result.id, response.id);
    });

    test('update propagates request failures unchanged', async assert => {
        const payload = { name: "Luna", birthDate: "2020-01-01" };
        const failure = new Error('Request failed');
        const repository = new DogRepository({ put: async () => { throw failure; } });
        await assert.rejects(repository.update(7, payload), error => error === failure);
    });

    test('delete uses the expected request and maps its response', async assert => {
        const response = null;
        const calls = [];
        const repository = new DogRepository({
            delete: async (...args) => { calls.push(args); return response; },
        });
        const result = await repository.delete(7);
        assert.deepEqual(calls, [['/dogs/7']]);
        assert.strictEqual(result, response);
    });

    test('delete propagates request failures unchanged', async assert => {
        const failure = new Error('Request failed');
        const repository = new DogRepository({ delete: async () => { throw failure; } });
        await assert.rejects(repository.delete(7), error => error === failure);
    });

});
