/**
 * Smallest possible island: shows how props flow from Twig into Vue.
 *
 *     {{ vue_island('Hello', { name: 'Smarty' }) }}
 *
 * Islands are plain ES modules, not single-file components. `<script setup>` /
 * `<template>` / `<style scoped>` blocks need a compiler at build time and
 * there is no build step here — the browser would fail on the first `<` with
 * "Unexpected token '<'". Write the markup as a string template instead.
 *
 * The `language=HTML` comment marker before the template string is what makes
 * WebStorm syntax-highlight and autocomplete inside it.
 */
export default {
    name: 'Hello',

    props: {
        name: { type: String, default: 'World' },
    },

    template: /*language=HTML*/ `
        <div class="hello">Hello {{ name }} from AssetMapper!</div>
    `,
};
