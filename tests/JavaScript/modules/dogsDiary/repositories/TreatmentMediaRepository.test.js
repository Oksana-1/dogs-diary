import TreatmentMediaRepository from '../../../../../assets/js/modules/dogsDiary/repositories/TreatmentMediaRepository.js';
import TreatmentMedia from '../../../../../assets/js/modules/dogsDiary/entities/TreatmentMedia.js';

const { module, test } = window.QUnit;

module('TreatmentMediaRepository', () => {
    test('delete uses the expected request and maps its response', async assert => {
        const response = null;
        const calls = [];
        const repository = new TreatmentMediaRepository({
            delete: async (...args) => { calls.push(args); return response; },
        });
        const result = await repository.delete(7, 9, 12);
        assert.deepEqual(calls, [['/dogs/7/treatments/9/media/12']]);
        assert.strictEqual(result, response);
    });

    test('delete propagates request failures unchanged', async assert => {
        const failure = new Error('Request failed');
        const repository = new TreatmentMediaRepository({ delete: async () => { throw failure; } });
        await assert.rejects(repository.delete(7, 9, 12), error => error === failure);
    });

    test('upload negotiates multipart upload and maps the saved media', async assert => {
        const file = new File(['photo'], 'dog.png', { type: 'image/png' });
        const calls = [];
        const repository = new TreatmentMediaRepository({
            post: async (path, body) => {
                calls.push([path, body]);
                return calls.length === 1 ? { mode: 'multipart' } : { id: 12, originalName: file.name };
            },
        });
        const result = await repository.upload(7, 9, file);
        assert.deepEqual(calls[0], ['/dogs/7/treatments/9/media/uploads', { name: 'dog.png', mimeType: 'image/png', sizeBytes: file.size }]);
        assert.strictEqual(calls[1][0], '/dogs/7/treatments/9/media');
        assert.true(calls[1][1] instanceof FormData);
        assert.strictEqual(calls[1][1].get('file').name, file.name);
        assert.true(result instanceof TreatmentMedia);
        assert.strictEqual(result.id, 12);
    });

    test('upload propagates preparation failures', async assert => {
        const file = new File(['photo'], 'dog.png', { type: 'image/png' });
        const failure = new Error('Upload rejected');
        const repository = new TreatmentMediaRepository({ post: async () => { throw failure; } });
        await assert.rejects(repository.upload(7, 9, file), error => error === failure);
    });
});
