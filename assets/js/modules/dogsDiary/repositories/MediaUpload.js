export default async function uploadMedia(api, path, file) {
    const upload = await api.post(`${path}/uploads`, {
        name: file.name,
        mimeType: file.type,
        sizeBytes: file.size,
    });

    if (upload.mode === 'multipart') {
        const body = new FormData();
        body.append('file', file);
        return api.post(path, body);
    }

    const url = new URL(upload.uploadUrl);
    if (upload.mode !== 'direct' || url.protocol !== 'https:'
        || !url.hostname.endsWith('.r2.cloudflarestorage.com')
        || !/^[a-f0-9]{32}$/.test(upload.uploadId)) {
        throw new Error('Unable to prepare this upload. Please try again.');
    }

    const response = await fetch(url.href, {
        method: 'PUT',
        headers: { 'Content-Type': upload.contentType },
        body: file,
        credentials: 'omit',
        redirect: 'error',
    });
    if (!response.ok) {
        throw new Error('Unable to upload this file. Please try again.');
    }

    return api.post(`${path}/uploads/${upload.uploadId}/complete`, {});
}
