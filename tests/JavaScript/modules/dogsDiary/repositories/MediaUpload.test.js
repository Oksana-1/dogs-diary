import FetchClient from '../../../../../assets/js/core/http/FetchClient.js';
import uploadMedia from '../../../../../assets/js/modules/dogsDiary/repositories/MediaUpload.js';
import { jsonResponse } from '../../../helpers/jsonResponse.js';

const { module, test } = window.QUnit;

module('MediaUpload', hooks => {
    let originalFetch;
    hooks.beforeEach(() => { originalFetch = globalThis.fetch; });
    hooks.afterEach(() => { globalThis.fetch = originalFetch; });

    test("direct media upload sends bytes to R2 without application credentials before finalizing", async assert => {
        const requests = [];
        globalThis.fetch = async (url, options) => {
            requests.push({ url, options });
            if (url.endsWith('/uploads')) return jsonResponse({
                mode: 'direct', uploadId: 'a'.repeat(32),
                uploadUrl: 'https://account.r2.cloudflarestorage.com/media/staging/file?signature=test',
                contentType: 'image/png',
            });
            if (url.includes('.r2.cloudflarestorage.com/')) return new Response(null, { status: 200 });
            return jsonResponse({ id: 7 }, 201);
        };
        const api = new FetchClient({ baseUrl: '/api', csrfToken: 'csrf-value' });
        const file = new File(['image'], 'photo.png', { type: 'image/png' });
        assert.deepEqual(await uploadMedia(api, '/dogs/1/media', file), { id: 7 });
        assert.strictEqual(requests[1].options.method, 'PUT');
        assert.strictEqual(requests[1].options.body, file);
        assert.strictEqual(requests[1].options.credentials, 'omit');
        assert.strictEqual(requests[1].options.headers['X-CSRF-TOKEN'], undefined);
        assert.strictEqual(requests[2].url, `/api/dogs/1/media/uploads/${'a'.repeat(32)}/complete`);
    });

    test("local media upload retains the multipart upload contract", async assert => {
        const calls = [];
        const api = { post: async (path, body) => {
            calls.push({ path, body });
            return path.endsWith('/uploads') ? { mode: 'multipart' } : { id: 8 };
        } };
        const file = new File(['image'], 'photo.png', { type: 'image/png' });
        assert.deepEqual(await uploadMedia(api, '/dogs/1/treatments/2/media', file), { id: 8 });
        assert.strictEqual(calls[1].path, '/dogs/1/treatments/2/media');
        assert.strictEqual(calls[1].body.get('file'), file);
    });

    test("a failed R2 upload never finalizes a media record", async assert => {
        globalThis.fetch = async () => new Response(null, { status: 403 });
        let calls = 0;
        const api = { post: async () => {
            calls++;
            return { mode: 'direct', uploadId: 'b'.repeat(32), contentType: 'image/png',
                uploadUrl: 'https://account.r2.cloudflarestorage.com/staging/file' };
        } };
        await assert.rejects(uploadMedia(api, '/dogs/1/media', new File(['x'], 'x.png')), /Unable to upload/);
        assert.strictEqual(calls, 1);
    });

    test("media upload rejects an unexpected storage endpoint", async assert => {
        const api = { post: async () => ({ mode: 'direct', uploadId: 'c'.repeat(32),
            uploadUrl: 'https://untrusted.example/upload', contentType: 'image/png' }) };
        await assert.rejects(uploadMedia(api, '/dogs/1/media', new File(['x'], 'x.png')), /Unable to prepare/);
    });
});
