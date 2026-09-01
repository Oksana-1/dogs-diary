# Frontend testing patterns

Canonical rules for frontend unit and component tests in Dogs Diary.

These rules apply to JavaScript under `assets/` and tests under `tests/JavaScript/`. They describe the intended Vue test suite as well as the existing framework-independent Node tests.

## 1. Testing philosophy

Test observable behavior through public boundaries:

- Rendered content and accessible state
- User interactions
- Component events
- Props passed to child components
- Calls to repositories, HTTP clients, and browser APIs
- Loading, success, empty, validation, cancellation, and failure states

Do not test implementation details:

- Never call `wrapper.vm.someMethod()`.
- Never mutate component state through `wrapper.vm`.
- Never expose or export an internal function only to test it.
- Do not assert private refs, computed-property names, or exact internal call order unless order is part of an external contract.
- Do not assert an entire HTML string or use broad snapshots as a substitute for behavioral assertions.

A refactor that preserves user-visible behavior and public collaborator contracts should normally keep the tests green.

## 2. Test stack

The target component-test stack is:

- Vitest
- Vue Test Utils for Vue 3
- jsdom

Use Vitest APIs (`describe`, `it`, `expect`, `vi`) for new component, composable, entity, repository, and HTTP-client unit tests once the package toolchain is configured.

`FetchClient.test.mjs` currently uses Node's built-in test runner because it predates the component toolchain. Keep its current command working until it is deliberately migrated; do not silently maintain two versions of the same test.

Current executable command:

```bash
node --test tests/JavaScript/FetchClient.test.mjs
```

When Vitest and package scripts are added, update this section in the same change with the canonical commands for a full run, watch mode, and a single file.

## 3. Test locations and names

Mirror the relevant source structure below `tests/JavaScript/`.

```text
assets/js/modules/dogsDiary/view/ui/modals/ModalDialog.js
tests/JavaScript/modules/dogsDiary/view/ui/modals/ModalDialog.test.js
```

Naming rules:

- Component: `ComponentName.test.js`
- Composable or feature: `featureName.test.js`
- Plain class: `ClassName.test.js`
- One component per test file
- Do not mix parent and child component suites in one file

Keep a one-off fixture in its test file. Move it to the nearest `fixtures/` directory when it is shared by two or more test files or is longer than roughly 20 lines.

## 4. Mounting components

Use the shared `createComponent` helper once it exists. Do not create file-local `createWrapper`, `factory`, or mounting helpers.

The Dogs Diary helper must use an options object rather than positional arguments:

```js
const wrapper = createComponent(MyComponent, {
    props: {},
    attrs: {},
    provide: {},
    stubs: {},
    global: {},
    mount: false,
});
```

Defaults belong in the shared helper. Individual tests should specify only meaningful differences.

Mounting policy:

- Prefer shallow mounting when testing a leaf component or a parent's contract with child components.
- Use full mounting when behavior depends on a small set of cooperating Dogs Diary components.
- Do not full-mount a large island merely to avoid defining its external boundaries.
- Browser-level navigation, native dialog behavior, and complete authenticated workflows belong in browser tests, not unit tests.

Choose the smallest render boundary that still exercises the behavior honestly.

## 5. Selectors

Use selectors in this order:

1. Accessible role and name for buttons, links, dialogs, alerts, and headings
2. Associated label for form controls
3. Visible text when the text itself is the behavior
4. `data-test` for ambiguous structural elements or state containers

Never select by styling class, generated ID, or incidental DOM nesting.

Examples:

```js
wrapper.get('button[aria-label="Add treatment"]');
wrapper.get('[data-test="dog-list-error"]');
```

Add `data-test` only when semantic markup does not provide a stable, meaningful selector. A test hook must not carry production behavior or styling.

## 6. Interactions

Allowed interactions include:

- Supplying props, attrs, or provided dependencies
- Setting form values through wrapper APIs
- Triggering user events such as click, input, change, submit, and keydown
- Emitting an event from a deliberately stubbed child
- Awaiting Vue updates and pending promises
- Asserting rendered output, emitted events, child props, or collaborator calls

Forbidden interactions include:

- Calling methods through `wrapper.vm`
- Assigning refs or component data through `wrapper.vm`
- Reaching into a composable returned by a mounted component
- Manually invoking lifecycle hooks
- Calling a child component's private method instead of emitting its public event

Always await asynchronous UI work:

```js
await wrapper.get('button[type="submit"]').trigger('click');
await flushPromises();
```

Avoid arbitrary time delays. Use fake timers only when time is the behavior under test.

## 7. Mocking boundaries

Mock at architectural boundaries, not inside the behavior under test.

Appropriate mock targets:

- Repository modules such as `DogRepository` and `TreatmentRepository`
- The shared HTTP client when testing a repository
- Browser APIs such as `fetch`, `URL.createObjectURL`, or navigation
- Time, when behavior explicitly depends on the current date
- Large child components when testing only the parent's contract

Use `vi.mock()` for module-level imports. Use dependency injection through `provide` when production code already exposes that boundary. Do not redesign production code solely to make every collaborator injectable.

Prefer realistic resolved or rejected values:

```js
repository.list.mockResolvedValue([dog]);
repository.create.mockRejectedValue(new Error('Unable to save dog.'));
```

Do not mock:

- The component or composable being tested
- Vue reactivity
- Small pure children whose rendered behavior is central to the scenario
- Entities merely to avoid constructing valid domain objects

Use real `Dog`, `Treatment`, and media entities when their normalization or getters are relevant. Use minimal plain objects only when the collaborator contract accepts them and entity behavior is irrelevant.

## 8. Cleanup and isolation

Every test must leave global state as it found it.

Baseline cleanup:

```js
afterEach(() => {
    vi.clearAllMocks();
});
```

Additional cleanup is mandatory when applicable:

- `vi.restoreAllMocks()` for spies on existing functions
- `vi.useRealTimers()` after fake timers
- Restore replaced globals and browser properties
- Revoke created object URLs
- Unmount wrappers if the helper does not do so automatically
- Remove DOM nodes or listeners created outside Vue Test Utils

Do not depend on test execution order. Each test must arrange its own starting state.

## 9. Test structure and naming

Use Arrange, Act, Assert without comments when the phases are already obvious. Add short comments only when setup is unusually complex.

Test names should describe behavior and outcome:

```js
it('shows the API error and keeps the treatment form open when saving fails', async () => {
    // ...
});
```

Avoid names tied to method names:

```js
it('calls handleSubmit', () => {}); // Avoid
```

Prefer one behavioral reason for failure per test. Several closely related assertions about the same rendered state are fine.

## 10. Required behavioral coverage

For a component or composable, cover only applicable states:

- Initial rendering
- Meaningful prop variants
- Empty data
- Loading and disabled/pending behavior
- Successful interaction
- Validation failure
- Repository/API failure
- Retry or recovery
- Cancellation or stale-response protection
- Emitted events and parent/child contracts
- Keyboard interaction and accessible state for dialogs and controls

Do not create trivial tests solely to increase counts. A test must protect a user-visible behavior, domain normalization rule, or architectural boundary.

## 11. Repository and HTTP tests

Repository tests should verify request contracts rather than repeat component behavior:

- URL and HTTP method
- Payload or `FormData`
- Response mapping into entities
- Error propagation
- Cancellation propagation where supported

HTTP-client tests should verify cross-cutting transport behavior:

- Same-origin credentials
- CSRF only on mutating requests
- Normalized error codes and field violations
- Session-expiry notification
- Cancellation semantics

Do not duplicate these transport assertions in every repository or component test.

## 12. Review checklist

Before considering a frontend unit-test change complete, confirm:

- The test describes externally visible behavior.
- No test uses `wrapper.vm` or production-private state.
- Selectors are semantic or deliberate `data-test` hooks.
- Repository and browser boundaries are mocked at module/public boundaries.
- Async behavior is awaited without arbitrary sleeps.
- Mocks, timers, globals, URLs, and wrappers are cleaned up.
- Fixtures follow the size and reuse rule.
- The relevant configured test command passes.
- New or changed testing commands are documented here and in package scripts together.
