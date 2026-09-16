<?php

namespace Mohammadhprp\IPToCountryFlagColumn\Livewire;

use Filament\Tables\Contracts\HasTable;
use Livewire\Component;
use Livewire\ComponentHook;
use Mohammadhprp\IPToCountryFlagColumn\Resolution\BatchResolver;

/**
 * Intercepts the batch resolution call on any Livewire component hosting a
 * Filament table.
 *
 * Livewire fires the `call` hook BEFORE checking that the method exists, so the
 * host component needs no trait and no method of its own - the browser simply
 * calls resolveIpToCountryFlagColumns() and this hook answers it.
 *
 * The hook is asked about EVERY `call` on EVERY Livewire component, so both guards
 * matter: the method name check keeps every other method from being intercepted,
 * and the HasTable check keeps a component with no Filament table from being
 * touched at all.
 */
class IPToCountryFlagColumnHook extends ComponentHook
{
    public const METHOD = 'resolveIpToCountryFlagColumns';

    /**
     * Livewire discovers this by name (method_exists) rather than through an
     * interface, so the parameters stay untyped at runtime and are described here
     * instead. Adding native types would turn any future signature change on
     * Livewire's side into a fatal TypeError rather than a hook that simply stops
     * matching.
     *
     * @param  array<int, mixed>  $params
     * @param  array<string, mixed>  $metadata
     */
    public function call(string $method, $params, \Closure $returnEarly, $metadata, mixed $componentContext): void
    {
        if ($method !== self::METHOD) {
            return;
        }

        if (! $this->component instanceof HasTable) {
            return;
        }

        // ComponentHook::$component is untyped, so `instanceof HasTable` narrows
        // PHPStan to the interface only - losing the fact that, at runtime, it is
        // always also the hosting Livewire\Component (skipRender() lives on
        // Component, not on HasTable). Re-declare both halves explicitly.
        /** @var Component&HasTable $component */
        $component = $this->component;

        $tokens = $params[0] ?? [];

        $result = app(BatchResolver::class)->resolve(
            $component,
            is_array($tokens) ? $tokens : [],
        );

        // Nothing about the table changed, so skip the re-render entirely: the
        // response carries only the token->HTML map, the table query is never
        // re-run, and the cells already injected on the client survive untouched
        // instead of being fought by a DOM morph.
        $component->skipRender();

        $returnEarly($result);
    }
}
