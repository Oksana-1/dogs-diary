# Use Vue islands without a Node build step

Status: Accepted

## Context

The project explores developing a Vue-enhanced Symfony application without a
Node-based asset build. Trying this approach was the original motivation.

## Decision

Render page shells with Twig and mount Vue islands on their designated elements.
Serve JavaScript modules through AssetMapper and ImportMap. Use Vue's browser
build with its runtime template compiler for components written as JavaScript
modules with template strings.

## Consequences

Application assets require no Node build pipeline. Components work within native
module and browser compilation constraints; single-file component compilation
would require additional tooling. Frontend tests used Node at the time of this decision. They have since moved
to QUnit in Chrome; see the [current testing guide](../../testing/frontend/testing-guide.md).

## References

- [Vue entry point](../../../../assets/app.js)
- [ImportMap and Vue build](../../../../importmap.php)
- [AssetMapper configuration](../../../../config/packages/asset_mapper.yaml)
