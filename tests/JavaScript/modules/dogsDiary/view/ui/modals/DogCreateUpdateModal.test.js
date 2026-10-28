import DogCreateUpdateModal from '../../../../../../../assets/js/modules/dogsDiary/view/ui/modals/DogCreateUpdateModal.js';
import Dog from '../../../../../../../assets/js/modules/dogsDiary/entities/Dog.js';
import { createComponent } from '../../../../../helpers/createComponent.js';
import { getByLabel, getButton } from '../../../../../helpers/dom.js';

const { module, test } = window.QUnit;

module('DogCreateUpdateModal', () => {
    test('submits trimmed values and normalizes empty optional fields', async assert => {
        const wrapper = createComponent(DogCreateUpdateModal, { props: { dog: new Dog({}), isOpen: true }, mount: true });
        const dialog = document.querySelector('[role="dialog"]');
        assert.strictEqual(dialog.querySelector('h2').textContent, 'Add dog');
        await getByLabel(dialog, 'Name').setValue('  Luna  ');
        await getByLabel(dialog, 'Birth date').setValue('2020-05-12');
        await getByLabel(dialog, 'Weight, kg').setValue('15');
        getButton(dialog, 'Save').element.click();
        assert.deepEqual(wrapper.emitted('submit'), [[{
            name: 'Luna', birthDate: '2020-05-12', gender: null,
            adoptDate: null, status: null, weight: 15, height: null,
        }]]);
    });

    test('native required fields prevent an empty submission', assert => {
        const wrapper = createComponent(DogCreateUpdateModal, { props: { dog: new Dog({}), isOpen: true }, mount: true });
        const dialog = document.querySelector('[role="dialog"]');
        getButton(dialog, 'Save').element.click();
        assert.false(dialog.querySelector('form').checkValidity());
        assert.strictEqual(wrapper.emitted('submit'), undefined);
    });

    test('preserves entered values after a save error without mutating the original dog', async assert => {
        const dog = new Dog({ id: 7, name: 'Milo', birthDate: '2020-05-12' });
        const wrapper = createComponent(DogCreateUpdateModal, { props: { dog, isOpen: true }, mount: true });
        const dialog = document.querySelector('[role="dialog"]');
        assert.strictEqual(dialog.querySelector('h2').textContent, 'Edit Milo');
        await getByLabel(dialog, 'Name').setValue('Luna');
        getButton(dialog, 'Save').element.click();
        await wrapper.setProps({ error: 'Unable to save.' });
        assert.strictEqual(dialog.querySelector('[role="alert"]').textContent, 'Unable to save.');
        assert.strictEqual(getByLabel(dialog, 'Name').element.value, 'Luna');
        assert.strictEqual(dog.name, 'Milo');
        assert.strictEqual(wrapper.emitted('submit')[0][0].name, 'Luna');
    });

    test('pending state disables save and cancellation until the request finishes', async assert => {
        const wrapper = createComponent(DogCreateUpdateModal, {
            props: { dog: new Dog({ name: 'Milo', birthDate: '2020-05-12' }), isOpen: true, disabled: true }, mount: true,
        });
        const dialog = document.querySelector('[role="dialog"]');
        for (const name of ['Saving…', 'Cancel']) {
            const button = getButton(dialog, name).element;
            assert.true(button.disabled);
            button.click();
        }
        assert.strictEqual(wrapper.emitted('submit'), undefined);
        assert.strictEqual(wrapper.emitted('close'), undefined);
        await wrapper.setProps({ disabled: false });
        getButton(dialog, 'Cancel').element.click();
        assert.strictEqual(wrapper.emitted('close')?.length, 1);
    });

    test('reopening resets an abandoned draft from the newly supplied dog', async assert => {
        const wrapper = createComponent(DogCreateUpdateModal, {
            props: { dog: new Dog({ name: 'Milo' }), isOpen: true }, mount: true,
        });
        await getByLabel(document.querySelector('[role="dialog"]'), 'Name').setValue('Abandoned');
        await wrapper.setProps({ isOpen: false });
        await wrapper.setProps({ dog: new Dog({ name: 'Luna' }), isOpen: true });
        assert.strictEqual(getByLabel(document.querySelector('[role="dialog"]'), 'Name').element.value, 'Luna');
    });
});
