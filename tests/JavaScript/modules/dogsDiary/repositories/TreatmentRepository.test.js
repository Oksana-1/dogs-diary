import TreatmentRepository from '../../../../../assets/js/modules/dogsDiary/repositories/TreatmentRepository.js';
import Treatment from '../../../../../assets/js/modules/dogsDiary/entities/Treatment.js';

const { module, test } = window.QUnit;

module('TreatmentRepository', () => {
    test('list uses the expected request and maps its response', async assert => {
        const response = [{ id: 9, productName: "Protection" }];
        const calls = [];
        const repository = new TreatmentRepository({
            get: async (...args) => { calls.push(args); return response; },
        });
        const result = await repository.list(7);
        assert.deepEqual(calls, [['/dogs/7/treatments']]);
        assert.strictEqual(result.length, response.length);
        assert.true(result[0] instanceof Treatment);
        assert.strictEqual(result[0].id, response[0].id);
    });

    test('list propagates request failures unchanged', async assert => {
        const failure = new Error('Request failed');
        const repository = new TreatmentRepository({ get: async () => { throw failure; } });
        await assert.rejects(repository.list(7), error => error === failure);
    });

    test('create uses the expected request and maps its response', async assert => {
        const payload = { productName: "Protection", types: ["anti_worm"], treatmentDate: "2026-10-01" };
        const response = { id: 9, ...payload };
        const calls = [];
        const repository = new TreatmentRepository({
            post: async (...args) => { calls.push(args); return response; },
        });
        const result = await repository.create(7, payload);
        assert.deepEqual(calls, [['/dogs/7/treatments', payload]]);
        assert.true(result instanceof Treatment);
        assert.strictEqual(result.id, response.id);
    });

    test('create propagates request failures unchanged', async assert => {
        const payload = { productName: "Protection", types: ["anti_worm"], treatmentDate: "2026-10-01" };
        const failure = new Error('Request failed');
        const repository = new TreatmentRepository({ post: async () => { throw failure; } });
        await assert.rejects(repository.create(7, payload), error => error === failure);
    });

    test('update uses the expected request and maps its response', async assert => {
        const payload = { productName: "Protection", types: ["anti_worm"], treatmentDate: "2026-10-01" };
        const response = { id: 9, ...payload };
        const calls = [];
        const repository = new TreatmentRepository({
            put: async (...args) => { calls.push(args); return response; },
        });
        const result = await repository.update(7, 9, payload);
        assert.deepEqual(calls, [['/dogs/7/treatments/9', payload]]);
        assert.true(result instanceof Treatment);
        assert.strictEqual(result.id, response.id);
    });

    test('update propagates request failures unchanged', async assert => {
        const payload = { productName: "Protection", types: ["anti_worm"], treatmentDate: "2026-10-01" };
        const failure = new Error('Request failed');
        const repository = new TreatmentRepository({ put: async () => { throw failure; } });
        await assert.rejects(repository.update(7, 9, payload), error => error === failure);
    });

    test('delete uses the expected request and maps its response', async assert => {
        const response = null;
        const calls = [];
        const repository = new TreatmentRepository({
            delete: async (...args) => { calls.push(args); return response; },
        });
        const result = await repository.delete(7, 9);
        assert.deepEqual(calls, [['/dogs/7/treatments/9']]);
        assert.strictEqual(result, response);
    });

    test('delete propagates request failures unchanged', async assert => {
        const failure = new Error('Request failed');
        const repository = new TreatmentRepository({ delete: async () => { throw failure; } });
        await assert.rejects(repository.delete(7, 9), error => error === failure);
    });

});
