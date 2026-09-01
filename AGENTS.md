# Repository instructions

## Frontend unit tests

Before creating or modifying frontend tests, test helpers, or frontend testing configuration, read `tests/JavaScript/TESTING.md` completely and follow it as the canonical project testing guide.

- Test observable behavior; do not call component or composable internals through `wrapper.vm`.
- Keep production changes driven by product behavior, not by test-only access requirements.
- Use only test commands that are configured in the repository. If the frontend test toolchain changes, update `tests/JavaScript/TESTING.md` and the package scripts in the same change.
- Run the relevant frontend tests after every frontend test or implementation change and report the exact command and result.
