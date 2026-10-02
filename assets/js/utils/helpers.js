export function formatDate(value) {
    return value
        ? new Date(`${value}T00:00:00Z`).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            timeZone: 'UTC',
        })
        : '—';
}
