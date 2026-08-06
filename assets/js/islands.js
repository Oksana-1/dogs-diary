import { createApp } from 'vue';

/**
 * Island registry.
 *
 * Each entry is a dynamic import, so a component's code is only downloaded when
 * a page actually contains that island. AssetMapper rewrites these relative
 * paths to versioned URLs at build time — no bundler involved.
 *
 * Add a component: drop a file in assets/js/components/islands/ and register it here.
 */
const islands = {
    AppDogList: () => import('./components/AppDogList.js'),
    AppDogDetail: () => import('./components/AppDogDetail.js'),
};

const MOUNTED_FLAG = 'vueIslandMounted';
const apps = new WeakMap();

async function mountIsland(el) {
    if (el.dataset[MOUNTED_FLAG]) {
        return;
    }

    const name = el.dataset.vueIsland;
    const load = islands[name];

    if (!load) {
        console.error(
            `[vue-islands] Unknown island "${name}". Register it in assets/js/islands.js. Known: ${Object.keys(islands).join(', ')}`,
        );
        return;
    }

    // Set the flag before awaiting, so a concurrent scan cannot mount twice.
    el.dataset[MOUNTED_FLAG] = '1';

    let props = {};
    if (el.dataset.vueIslandProps) {
        try {
            props = JSON.parse(el.dataset.vueIslandProps);
        } catch (error) {
            console.error(`[vue-islands] Invalid props JSON on island "${name}"`, error);
        }
    }

    try {
        const { default: component } = await load();
        const app = createApp(component, props);
        app.mount(el);
        apps.set(el, app);
    } catch (error) {
        delete el.dataset[MOUNTED_FLAG];
        console.error(`[vue-islands] Failed to mount island "${name}"`, error);
    }
}

function mountAll(root = document) {
    root.querySelectorAll('[data-vue-island]').forEach((el) => void mountIsland(el));
}

function unmountAll(root = document) {
    root.querySelectorAll('[data-vue-island]').forEach((el) => {
        const app = apps.get(el);
        if (!app) {
            return;
        }

        app.unmount();
        apps.delete(el);
        delete el.dataset[MOUNTED_FLAG];
    });
}

/**
 * Mount islands inserted after the initial page load, including any future
 * Turbo Frame or Stream content.
 */
const observer = new MutationObserver((mutations) => {
    for (const mutation of mutations) {
        for (const node of mutation.addedNodes) {
            if (!(node instanceof HTMLElement)) {
                continue;
            }

            if (node.hasAttribute('data-vue-island')) {
                void mountIsland(node);
            }

            mountAll(node);
        }
    }
});

export function startVueIslands() {
    mountAll();

    // Turbo is not installed in this project yet. These listeners are inert
    // until it is, and then they keep islands alive across Turbo navigation:
    // Turbo caches a page snapshot before navigating away, so unmount first to
    // keep half-alive Vue markup out of the cache and let components clean up.
    document.addEventListener('turbo:load', () => mountAll());
    document.addEventListener('turbo:before-cache', () => unmountAll());

    observer.observe(document.body, { childList: true, subtree: true });
}
