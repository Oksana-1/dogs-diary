import { mount, shallowMount } from '@vue/test-utils';

const mounted = [];

export function createComponent(component, {
    props = {}, attrs = {}, provide = {}, stubs = {}, global = {}, mount: fullMount = false,
} = {}) {
    const globalStubs = Array.isArray(global.stubs)
        ? Object.fromEntries(global.stubs.map(name => [name, true]))
        : global.stubs;
    const target = document.createElement('div');
    document.body.append(target);
    try {
        const wrapper = (fullMount ? mount : shallowMount)(component, {
            props,
            attrs,
            attachTo: target,
            global: {
                ...global,
                provide: { ...global.provide, ...provide },
                stubs: { ...globalStubs, ...stubs },
            },
        });
        mounted.push({ wrapper, target });
        return wrapper;
    } catch (error) {
        target.remove();
        throw error;
    }
}

// Unmount first so Vue also removes nodes moved outside the mount by Teleport.
window.QUnit.hooks.afterEach(() => {
    for (const { wrapper, target } of mounted.splice(0).reverse()) {
        try {
            wrapper.unmount();
        } finally {
            target.remove();
        }
    }
});
