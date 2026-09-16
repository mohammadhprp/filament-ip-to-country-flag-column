<?php

namespace Mohammadhprp\IPToCountryFlagColumn\Resolution;

use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Mohammadhprp\IPToCountryFlagColumn\Columns\IPToCountryFlagColumn;
use ReflectionMethod;
use RuntimeException;
use Throwable;

/**
 * Turns a batch of client-supplied cell tokens into rendered HTML.
 *
 * This is the package's security boundary: tokens arrive from the browser and are
 * therefore attacker-controllable, so a few rules apply:
 *
 *  - Records are hydrated exclusively through the table's OWN query, scoped the
 *    way Filament scopes a single-record lookup. Tenancy, global scopes, table
 *    filters and ->modifyQueryUsing() constraints therefore apply automatically.
 *    A key for a row outside that query simply never comes back, so no cell is
 *    produced for it.
 *  - Only columns that are actually defined on the table (and are
 *    IPToCountryFlagColumn instances) are resolved. An unknown, hidden or foreign
 *    column name is silently dropped rather than turned into an error that could
 *    leak table structure.
 *  - The incoming token array is capped at self::MAX_BATCH_SIZE (truncated, never
 *    an error), so a single request cannot force arbitrarily many resolver
 *    invocations.
 *  - A failure while resolving one column degrades that column's cells to error
 *    cells instead of aborting the whole batch.
 */
class BatchResolver
{
    /**
     * Maximum number of tokens processed in a single request.
     *
     * A single call cannot force arbitrarily many resolver invocations, so a page
     * with more cells than this simply resolves them across more than one batch.
     */
    protected const MAX_BATCH_SIZE = 100;

    /**
     * @param  array<int, mixed>  $tokens
     * @return array{cells: array<string, array{html: string, error: bool}>}
     */
    public function resolve(HasTable $component, array $tokens): array
    {
        $table = $component->getTable();

        $tokens = array_slice($tokens, 0, self::MAX_BATCH_SIZE);

        // Group valid tokens by column, keyed by the (still-untrusted) record key,
        // so each column's records can be loaded in a single query. The resolved
        // column instance travels alongside its tokens so the resolution loop below
        // never has to re-derive or re-validate it.
        $groups = [];

        foreach ($tokens as $rawToken) {
            if (! is_string($rawToken)) {
                continue;
            }

            $token = CellToken::decode($rawToken);

            if (! $token instanceof CellToken) {
                continue;
            }

            // getVisibleColumns() is the "defined on this table" check AND the
            // "currently visible, not toggled off" check in one lookup.
            $column = $table->getVisibleColumns()[$token->columnName] ?? null;

            if (! $column instanceof IPToCountryFlagColumn) {
                continue;
            }

            $groups[$token->columnName]['column'] ??= $column;
            $groups[$token->columnName]['tokens'][$token->recordKey] = $rawToken;
        }

        $cells = [];

        foreach ($groups as ['column' => $column, 'tokens' => $tokensByRecordKey]) {
            try {
                $query = $this->scopedQuery($component, $table);

                if ($query === null) {
                    continue;
                }

                // Match Filament's own single-record resolution: every visible
                // column's aggregate selects must be present too, or a resolver
                // reading an aggregate attribute would silently see null.
                foreach ($table->getVisibleColumns() as $visibleColumn) {
                    $visibleColumn->applyRelationshipAggregates($query);
                }

                $records = $query->whereKey(array_keys($tokensByRecordKey))->get();
            } catch (Throwable $exception) {
                // A query-level failure must not abort the whole batch - every
                // cell in this column degrades to an error cell instead.
                report($exception);

                foreach ($tokensByRecordKey as $rawToken) {
                    $cells[$rawToken] = $this->buildErrorCell($column, $exception);
                }

                continue;
            }

            foreach ($records as $record) {
                $recordKey = $table->getRecordKey($record);
                $rawToken = $tokensByRecordKey[$recordKey] ?? null;

                if ($rawToken === null) {
                    continue;
                }

                $cells[$rawToken] = $this->resolveCell($column, $record, $recordKey);
            }
        }

        return ['cells' => $cells];
    }

    /**
     * Builds the query used to hydrate records, scoped the way Filament scopes a
     * single-record lookup - filters applied, search deliberately NOT applied (a
     * search term in flight must not make an in-flight cell disappear).
     *
     * applyFiltersToTableQuery() is protected on the hosting Livewire component and
     * not part of the public HasTable contract, so it is reached via reflection
     * instead of reimplementing filter application and risking silent drift. If it
     * is ever renamed or removed, fall back to the unscoped query and report, so
     * the gap surfaces instead of quietly reintroducing "a forged token bypasses
     * table filters". Never throw: one Filament upgrade should not take down every
     * table using this column.
     *
     * @return Builder<Model>|Relation<Model, Model, mixed>|null
     */
    protected function scopedQuery(HasTable $component, Table $table): Builder|Relation|null
    {
        $query = $table->getQuery(isResolvingRecord: true);

        if ($query === null) {
            return null;
        }

        if (! method_exists($component, 'applyFiltersToTableQuery')) {
            report(new RuntimeException(
                'IPToCountryFlagColumn\'s BatchResolver could not find Filament\'s '
                .'applyFiltersToTableQuery() method on ['.$component::class.']. Table filters will '
                .'NOT be applied when resolving async cells until this is fixed - Filament appears '
                .'to have changed an internal API this package depends on.'
            ));

            return $query;
        }

        $method = new ReflectionMethod($component, 'applyFiltersToTableQuery');
        $method->setAccessible(true);

        $supportsIsResolvingRecord = collect($method->getParameters())
            ->contains(fn ($parameter): bool => $parameter->getName() === 'isResolvingRecord');

        return $supportsIsResolvingRecord
            ? $method->invoke($component, $query, true)
            : $method->invoke($component, $query);
    }

    /**
     * @return array{html: string, error: bool}
     */
    protected function resolveCell(IPToCountryFlagColumn $column, Model $record, string $recordKey): array
    {
        // A fresh column instance per cell: configuration (resolver, view, ...) is
        // shared, but record and resolved state must not bleed between rows.
        $cell = clone $column;
        $cell->record($record);
        $cell->recordKey($recordKey);

        try {
            return [
                'html' => $cell->renderResolvedHtml($record),
                'error' => false,
            ];
        } catch (Throwable $exception) {
            report($exception);

            return $this->buildErrorCell($cell, $exception);
        }
    }

    /**
     * @return array{html: string, error: bool}
     */
    protected function buildErrorCell(IPToCountryFlagColumn $column, Throwable $exception): array
    {
        // The exception is only ever exposed to an explicit developer callback
        // (errorStateUsing()). Never interpolate $exception or its message directly
        // here - resolvers hit APIs that may echo credentials or SQL back.
        try {
            $errorState = $column->getErrorState($exception);
        } catch (Throwable $errorStateException) {
            report($errorStateException);

            $errorState = __('ip-to-country-flag-column::ip-to-country-flag-column.error');
        }

        return [
            'html' => $errorState instanceof Htmlable
                ? $errorState->toHtml()
                : e((string) $errorState),
            'error' => true,
        ];
    }
}
