import DogMediaRepository from '../../../../../assets/js/modules/dogsDiary/repositories/DogMediaRepository.js';
import DogMedia from '../../../../../assets/js/modules/dogsDiary/entities/DogMedia.js';

const { module, test } = window.QUnit;

module('DogMediaRepository', () => {
    test('list uses the expected request and maps its response', async assert => {
        const response = [{ id: 9, isThumbnail: true }];
        const calls = [];
        const repository = new DogMediaRepository({
            get: async (...args) => { calls.push(args); return response; },
        });
        const result = await repository.list(7);
        assert.deepEqual(calls, [['/dogs/7/media']]);
        assert.strictEqual(result.length, response.length);
        assert.true(result[0] instanceof DogMedia);
        assert.strictEqual(result[0].id, response[0].id);
    });

    test('list propagates request failures unchanged', async assert => {
        const failure = new Error('Request failed');
        const repository = new DogMediaRepository({ get: async () => { throw failure; } });
        await assert.rejects(repository.list(7), error => error === failure);
    });

    test('delete uses the expected request and maps its response', async assert => {
        const response = null;
        const calls = [];
        const repository = new DogMediaRepository({
            delete: async (...args) => { calls.push(args); return response; },
        });
        const result = await repository.delete(7, 9);
        assert.deepEqual(calls, [['/dogs/7/media/9']]);
        assert.strictEqual(result, response);
    });

    test('delete propagates request failures unchanged', async assert => {
        const failure = new Error('Request failed');
        const repository = new DogMediaRepository({ delete: async () => { throw failure; } });
        await assert.rejects(repository.delete(7, 9), error => error === failure);
    });

    test('setThumbnail uses the expected request and maps its response', async assert => {
        const response = { id: 9, isThumbnail: true };
        const calls = [];
        const repository = new DogMediaRepository({
            put: async (...args) => { calls.push(args); return response; },
        });
        const result = await repository.setThumbnail(7, 9);
        assert.deepEqual(calls, [['/dogs/7/media/thumbnail', { mediaId: 9 }]]);
        assert.true(result instanceof DogMedia);
        assert.strictEqual(result.id, response.id);
    });

    test('setThumbnail propagates request failures unchanged', async assert => {
        const failure = new Error('Request failed');
        const repository = new DogMediaRepository({ put: async () => { throw failure; } });
        await assert.rejects(repository.setThumbnail(7, 9), error => error === failure);
    });

    test('clearThumbnail uses the expected request and maps its response', async assert => {
        const response = null;
        const calls = [];
        const repository = new DogMediaRepository({
            delete: async (...args) => { calls.push(args); return response; },
        });
        const result = await repository.clearThumbnail(7);
        assert.deepEqual(calls, [['/dogs/7/media/thumbnail']]);
        assert.strictEqual(result, response);
    });

    test('clearThumbnail propagates request failures unchanged', async assert => {
        const failure = new Error('Request failed');
        const repository = new DogMediaRepository({ delete: async () => { throw failure; } });
        await assert.rejects(repository.clearThumbnail(7), error => error === failure);
    });

    test('setProfile uses the expected request and maps its response', async assert => {
        const response = { id: 9, isProfile: true };
        const calls = [];
        const repository = new DogMediaRepository({
            put: async (...args) => { calls.push(args); return response; },
        });
        const result = await repository.setProfile(7, 9);
        assert.deepEqual(calls, [['/dogs/7/media/profile', { mediaId: 9 }]]);
        assert.true(result instanceof DogMedia);
        assert.strictEqual(result.id, response.id);
    });

    test('setProfile propagates request failures unchanged', async assert => {
        const failure = new Error('Request failed');
        const repository = new DogMediaRepository({ put: async () => { throw failure; } });
        await assert.rejects(repository.setProfile(7, 9), error => error === failure);
    });

    test('clearProfile uses the expected request and maps its response', async assert => {
        const response = null;
        const calls = [];
        const repository = new DogMediaRepository({
            delete: async (...args) => { calls.push(args); return response; },
        });
        const result = await repository.clearProfile(7);
        assert.deepEqual(calls, [['/dogs/7/media/profile']]);
        assert.strictEqual(result, response);
    });

    test('clearProfile propagates request failures unchanged', async assert => {
        const failure = new Error('Request failed');
        const repository = new DogMediaRepository({ delete: async () => { throw failure; } });
        await assert.rejects(repository.clearProfile(7), error => error === failure);
    });

    test('upload negotiates multipart upload and maps the saved media', async assert => {
        const file = new File(['photo'], 'dog.png', { type: 'image/png' });
        const calls = [];
        const repository = new DogMediaRepository({
            post: async (path, body) => {
                calls.push([path, body]);
                return calls.length === 1 ? { mode: 'multipart' } : { id: 12, originalName: file.name };
            },
        });
        const result = await repository.upload(7, file);
        assert.deepEqual(calls[0], ['/dogs/7/media/uploads', { name: 'dog.png', mimeType: 'image/png', sizeBytes: file.size }]);
        assert.strictEqual(calls[1][0], '/dogs/7/media');
        assert.true(calls[1][1] instanceof FormData);
        assert.strictEqual(calls[1][1].get('file').name, file.name);
        assert.true(result instanceof DogMedia);
        assert.strictEqual(result.id, 12);
    });

    test('upload propagates preparation failures', async assert => {
        const file = new File(['photo'], 'dog.png', { type: 'image/png' });
        const failure = new Error('Upload rejected');
        const repository = new DogMediaRepository({ post: async () => { throw failure; } });
        await assert.rejects(repository.upload(7, file), error => error === failure);
    });
});
