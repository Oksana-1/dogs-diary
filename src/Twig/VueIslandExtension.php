<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

/**
 * Renders the mount point for a Vue "island": a small interactive component
 * inside an otherwise server-rendered Twig page.
 *
 * The matching JavaScript loader lives in assets/vue/islands.js — it looks for
 * elements carrying `data-vue-island` and mounts the requested component,
 * lazy-loading its module on demand.
 *
 * Usage in Twig:
 *
 *     {{ vue_island('Hello', { name: 'Smarty' }) }}
 *     {{ vue_island('TreatmentList', { endpoint: path('api_treatments_index') }, { class: 'card' }) }}
 */
final class VueIslandExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('vue_island', $this->renderIsland(...), ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param string               $component  Name registered in assets/vue/islands.js
     * @param array<string, mixed> $props      Passed to the component as props (JSON-encoded)
     * @param array<string, mixed> $attributes Extra HTML attributes for the mount element
     * @param string               $tag        HTML tag used for the mount element
     */
    public function renderIsland(
        string $component,
        array $props = [],
        array $attributes = [],
        string $tag = 'div',
    ): Markup {
        $attributes['data-vue-island'] = $component;

        if ([] !== $props) {
            $attributes['data-vue-island-props'] = json_encode($props, \JSON_THROW_ON_ERROR);
        }

        $rendered = [];
        foreach ($attributes as $name => $value) {
            if (true === $value) {
                $rendered[] = htmlspecialchars($name, \ENT_QUOTES, 'UTF-8');

                continue;
            }

            if (false === $value || null === $value) {
                continue;
            }

            $rendered[] = \sprintf(
                '%s="%s"',
                htmlspecialchars($name, \ENT_QUOTES, 'UTF-8'),
                htmlspecialchars((string) $value, \ENT_QUOTES, 'UTF-8'),
            );
        }

        // Vue replaces the element's content on mount, so anything inside acts
        // as a placeholder until the island's JavaScript has loaded.
        return new Markup(
            \sprintf(
                '<%1$s %2$s><span class="island-loading">Loading %3$s…</span></%1$s>',
                $tag,
                implode(' ', $rendered),
                htmlspecialchars($component, \ENT_QUOTES, 'UTF-8'),
            ),
            'UTF-8',
        );
    }
}
