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

The configured suite uses Node's built-in test runner without npm dependencies.
The component suite runs QUnit 2.24.1 and Vue Test Utils 2.4.6 in installed
Chrome/Chromium, served by a separate test-only Symfony kernel and ImportMap.
Do not add npm tooling or a production Node.js requirement. Production assets
use Symfony AssetMapper and ImportMap. The test map derives its Vue entry from
the production map, so tests and components share one full Vue browser build.
The icon dependency also uses the production map's pinned version.
The VTU `esm-browser` artifact imports only `vue`; no fake compiler or server
renderer modules are needed.

Install Composer dependencies first, then install the isolated browser vendors:

```bash
bin/browser-tests --install
bin/browser-tests
bin/browser-tests --file tests/JavaScript/modules/dogsDiary/view/ui/modals/ConfirmModal.test.js
bin/browser-tests --watch
bin/browser-tests --watch --file tests/JavaScript/modules/dogsDiary/view/ui/modals/ConfirmModal.test.js
bin/browser-tests --serve # prints an interactive local runner URL; Ctrl-C stops it
bin/check frontend # transport and component suites
```

The install command invokes the test kernel's real Symfony `importmap:install`.
Pinned vendor downloads and kernel caches live in ignored `var/browser-tests/`.
No npm, package manifest, bundler, or Node process is used by this component suite.
Host PHP with installed Composer dependencies and Chrome/Chromium are required.
Use PHP 8.4 and Node.js 24 to match CI. Composer dependencies are locked in
`composer.lock`; browser vendors are version-pinned in the test ImportMap and
the production map it references. No npm package manifest or lockfile is needed.
Set `CHROME_BIN` to an alternative Chrome/Chromium executable when needed. On
macOS the default is `/Applications/Google Chrome.app/Contents/MacOS/Google Chrome`;
on Linux the launcher searches PATH for google-chrome, chromium, or chromium-browser.
CI uses `/usr/bin/google-chrome` from the Ubuntu runner image and prints its
version. Browser updates follow that image; the browser binary is not pinned.

`--file` accepts an existing test path under `tests/JavaScript/modules/`, from
the current directory or repository root. Fixtures, missing files, and paths
outside that tree are rejected with exit 2. It can be combined with `--serve`
or `--watch`. Only that test and its dependencies are loaded.

Watch mode requires PHP's pcntl extension. It runs immediately, then checks
content changes every 250 ms under `assets/js` and `tests/JavaScript`, plus the
launcher, root ImportMap and Composer lockfile. Adds, edits, removals, and changes
during a running test trigger another run. Runs are serial and use fresh browser
profiles. Failures are printed without ending watch mode; Ctrl-C stops child
processes and exits 130. Watch does not reinstall changed vendor dependencies:
run `bin/browser-tests --install` after changing vendor versions.
The headless command binds an ephemeral loopback port, uses a temporary browser
profile, and cleans up both processes and the profile. It prints QUnit JSON and
fails on assertions, module/resource errors, zero tests, absent completion, or a
30-second browser timeout. Browser vendors must be installed before running.
The launcher drains the test server's output while Chrome runs, preventing
debug logs from blocking module requests as the suite grows. Server and browser
diagnostics are retained for launcher failures.
The runner is not exposed through the production kernel, routes or public files.
`bin/check frontend` and the CI quality job run both frontend suites. The
component runner always uses host PHP and Chrome, including when backend PHP
checks use Docker. Install Composer and browser vendor dependencies beforehand;
`bin/check` never installs them. CI installs the browser vendors before checking.

Transport-only command:

```bash
node --test tests/JavaScript/FetchClient.test.mjs
```

The transport-only command still uses host Node.js directly and does not require
Docker, Composer, Chrome, or npm installation. It has not been duplicated or
migrated into the component suite.

`ConfirmModal.test.js` is the first component suite. It uses real ModalDialog
and native Teleport with full mounting to cover rendered content and native
disabled buttons. These are browser-executed component unit tests, not browser
navigation/E2E tests. Transport tests do not establish rendered UI coverage.

`ModalDialog.test.js` covers accessible title/description relationships, independent
labels for simultaneous dialogs, open-prop updates, backdrop and Escape dismissal,
and disabled dismissal. `useAsyncModal.test.js` uses a rendered harness to cover
subject selection, cancellation, pending and duplicate-submit guards, successful
completion, failure messages, retry, and clearing errors when reopening. These
tests do not establish focus-management or complete form/page coverage.

Additional suites cover dog and treatment form validation, pending controls,
draft preservation, treatment photo selection/removal, and the photo viewer.
`useDogTreatments` covers create, delete, pending guards, photo removal, and retry
after a partial save without creating a duplicate treatment. Repository contract
tests cover request paths, methods, payloads, entity mapping, multipart upload,
and error propagation. Domain tests cover nested hydration, optional photos,
media-role normalization, and collection ordering.

This is not whole-project coverage: dog detail/media composables, page islands,
other reusable UI, authentication forms, and further transport edge cases still
need dedicated tests. No coverage percentage or threshold has been established.

## 3. Test locations and names

Mirror the relevant source structure below `tests/JavaScript/`.

The test-only kernel discovers `*.test.js` recursively under
`tests/JavaScript/modules/`, excluding `fixtures/` directories. Discovery is sorted
and shared with the test ImportMap so relative dependencies resolve through
AssetMapper. Add a test file in that tree and reload the runner; no JavaScript
import list needs editing. Helpers and fixtures are imported by their tests,
while the existing Node `.mjs` suite remains a separate command. An empty discovery
list or a module-load failure is an error, not a successful empty run.

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

Use the shared `tests/JavaScript/helpers/createComponent.js` helper. It attaches
each wrapper to a fresh body node and automatically unmounts wrappers (including
native Teleport content) and removes mount nodes after each QUnit test. Do not
create file-local `createWrapper`, `factory`, or mounting helpers.

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

QUnit does not provide Vitest/Jest-style module mocks or spy functions. Supply
plain test doubles through existing constructor or composable arguments, or
through `provide` where production code already uses it. When replacing a public
method or browser API, retain the original and restore it after the test.
Do not redesign production code solely to make every collaborator injectable.

Prefer realistic resolved or rejected values:

```js
const repository = {
    list: async () => [dog],
    create: async () => { throw new Error('Unable to save dog.'); },
};
```

Do not mock:

- The component or composable being tested
- Vue reactivity
- Small pure children whose rendered behavior is central to the scenario
- Entities merely to avoid constructing valid domain objects

Use real `Dog`, `Treatment`, and media entities when their normalization or getters are relevant. Use minimal plain objects only when the collaborator contract accepts them and entity behavior is irrelevant.

## 8. Cleanup and isolation

Every test must leave global state as it found it.

Create fresh test doubles and call-recording arrays for each test. Restore any
replaced methods or globals in `finally` or a QUnit `afterEach` hook.

Additional cleanup is mandatory when applicable:

- Restore spies on existing functions
- Restore real timers after fake timers
- Restore replaced globals and browser properties
- Revoke created object URLs
- Unmount wrappers if the helper does not do so automatically
- Remove DOM nodes or listeners created outside Vue Test Utils

Do not depend on test execution order. Each test must arrange its own starting state.

## 9. Test structure and naming

Use Arrange, Act, Assert without comments when the phases are already obvious. Add short comments only when setup is unusually complex.

Test names should describe behavior and outcome:

```js
QUnit.test('shows the API error and keeps the treatment form open when saving fails', async assert => {
    // ...
});
```

Avoid names tied to method names:

```js
QUnit.test('calls handleSubmit', assert => {}); // Avoid
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
- New or changed testing commands are documented here and in their executable scripts together; the full frontend check and CI run both suites.
