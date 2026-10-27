import { DOMWrapper } from '@vue/test-utils';

export function getByLabel(root, name) {
    const label = [...root.querySelectorAll('label')].find(label => label.textContent.trim() === name);
    if (!label?.control) throw new Error(`No control labelled "${name}".`);
    return new DOMWrapper(label.control);
}

export function getButton(root, name) {
    const button = [...root.querySelectorAll('button')].find(button =>
        (button.getAttribute('aria-label') ?? button.textContent.trim()) === name,
    );
    if (!button) throw new Error(`No button named "${name}".`);
    return new DOMWrapper(button);
}
