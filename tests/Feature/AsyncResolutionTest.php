<?php

use Livewire\Component;
use Livewire\Exceptions\MethodNotFoundException;
use Livewire\Livewire;
use Mohammadhprp\IPToCountryFlagColumn\Columns\IPToCountryFlagColumn;
use Mohammadhprp\IPToCountryFlagColumn\Livewire\IPToCountryFlagColumnHook;
use Mohammadhprp\IPToCountryFlagColumn\Resolution\CellToken;
use Tests\Fixtures\Visit;
use Tests\Fixtures\VisitsTable;

function usResolver(): Closure
{
    return fn (string $ip): array => [
        'country_code' => 'US',
        'country_name' => 'United States',
        'city' => 'Mountain View',
    ];
}

function makeToken(Visit $visit, string $column = 'ip_address'): string
{
    return (new CellToken($column, (string) $visit->getKey()))->encode();
}

it('round-trips a cell token through encode and decode', function () {
    $token = (new CellToken('ip_address', '42'))->encode();

    $decoded = CellToken::decode($token);

    expect($decoded)->not->toBeNull()
        ->and($decoded->columnName)->toBe('ip_address')
        ->and($decoded->recordKey)->toBe('42');
});

it('rejects malformed tokens', function (string $token) {
    expect(CellToken::decode($token))->toBeNull();
})->with([
    'no separator' => ['abc'],
    'three segments' => ['a:b:c'],
    'empty segment' => [':YQ'],
    'invalid characters' => ['a!b:c'],
]);

it('never invokes the resolver during the synchronous table render', function () {
    Visit::create(['ip_address' => '8.8.8.8']);

    $calls = 0;

    VisitsTable::$columns = [
        IPToCountryFlagColumn::make('ip_address')->locationResolver(function () use (&$calls): array {
            $calls++;

            return [
                'country_code' => 'US',
                'country_name' => 'United States',
                'city' => 'Mountain View',
            ];
        }),
    ];

    Livewire::test(VisitsTable::class)->assertDontSee('Mountain View');

    expect($calls)->toBe(0);
});

it('emits one placeholder carrying a decodable token per row', function () {
    $visit = Visit::create(['ip_address' => '8.8.8.8']);

    VisitsTable::$columns = [
        IPToCountryFlagColumn::make('ip_address')->locationResolver(usResolver()),
    ];

    $html = Livewire::test(VisitsTable::class)->html();

    expect($html)->toContain('data-ip-country-flag-token="'.makeToken($visit).'"');
});

it('renders the configured loading state', function () {
    Visit::create(['ip_address' => '8.8.8.8']);

    VisitsTable::$columns = [
        IPToCountryFlagColumn::make('ip_address')
            ->locationResolver(usResolver())
            ->loadingState('Looking up…'),
    ];

    Livewire::test(VisitsTable::class)->assertSee('Looking up…');
});

it('resolves a batch through a call the component does not define', function () {
    $visit = Visit::create(['ip_address' => '8.8.8.8']);

    VisitsTable::$columns = [
        IPToCountryFlagColumn::make('ip_address')->locationResolver(usResolver()),
    ];

    $token = makeToken($visit);

    $returned = Livewire::test(VisitsTable::class)
        ->call(IPToCountryFlagColumnHook::METHOD, [$token])
        ->effects['returns'][0] ?? null;

    expect($returned['cells'][$token]['html'] ?? null)
        ->toContain('🇺🇸')
        ->toContain('Mountain View, United States');
});

it('tolerates an empty batch', function () {
    Visit::create(['ip_address' => '8.8.8.8']);

    VisitsTable::$columns = [
        IPToCountryFlagColumn::make('ip_address')->locationResolver(usResolver()),
    ];

    Livewire::test(VisitsTable::class)
        ->call(IPToCountryFlagColumnHook::METHOD, [])
        ->assertOk();
});

it('skips the re-render entirely, so the response carries no re-rendered HTML', function () {
    $visit = Visit::create(['ip_address' => '8.8.8.8']);

    VisitsTable::$columns = [
        IPToCountryFlagColumn::make('ip_address')->locationResolver(usResolver()),
    ];

    $effects = Livewire::test(VisitsTable::class)
        ->call(IPToCountryFlagColumnHook::METHOD, [makeToken($visit)])
        ->effects;

    expect($effects)->not->toHaveKey('html');
});

it('silently drops a token for a column that is not defined on the table', function () {
    $visit = Visit::create(['ip_address' => '8.8.8.8']);

    VisitsTable::$columns = [
        IPToCountryFlagColumn::make('ip_address')->locationResolver(usResolver()),
    ];

    $forged = (new CellToken('not_a_column', (string) $visit->getKey()))->encode();

    $returned = Livewire::test(VisitsTable::class)
        ->call(IPToCountryFlagColumnHook::METHOD, [$forged])
        ->effects['returns'][0] ?? null;

    expect($returned['cells'] ?? [])->toBeEmpty();
});

it('degrades a failing cell to the configured error state', function () {
    $visit = Visit::create(['ip_address' => '8.8.8.8']);

    VisitsTable::$columns = [
        IPToCountryFlagColumn::make('ip_address')
            ->locationResolver(fn (): array => throw new RuntimeException('lookup failed'))
            ->errorState('Nope'),
    ];

    $token = makeToken($visit);

    $returned = Livewire::test(VisitsTable::class)
        ->call(IPToCountryFlagColumnHook::METHOD, [$token])
        ->effects['returns'][0] ?? null;

    expect($returned['cells'][$token]['error'])->toBeTrue()
        ->and($returned['cells'][$token]['html'])->toContain('Nope');
});

it('uses the translated default when no error state is configured', function () {
    $visit = Visit::create(['ip_address' => '8.8.8.8']);

    VisitsTable::$columns = [
        IPToCountryFlagColumn::make('ip_address')
            ->locationResolver(fn (): array => throw new RuntimeException('lookup failed')),
    ];

    $token = makeToken($visit);

    $returned = Livewire::test(VisitsTable::class)
        ->call(IPToCountryFlagColumnHook::METHOD, [$token])
        ->effects['returns'][0] ?? null;

    expect($returned['cells'][$token]['error'])->toBeTrue()
        ->and($returned['cells'][$token]['html'])->toContain(__('ip-to-country-flag-column::ip-to-country-flag-column.error'));
});

it('emits the when-visible and retryable hints on the placeholder', function () {
    Visit::create(['ip_address' => '8.8.8.8']);

    VisitsTable::$columns = [
        IPToCountryFlagColumn::make('ip_address')
            ->locationResolver(usResolver())
            ->whenVisible(),
    ];

    $html = Livewire::test(VisitsTable::class)->html();

    expect($html)->toContain('data-ip-country-flag-when-visible="1"')
        ->and($html)->toContain('data-ip-country-flag-retryable="1"');
});

it('does not intercept an unrelated, genuinely undefined method', function () {
    VisitsTable::$columns = [
        IPToCountryFlagColumn::make('ip_address')->locationResolver(usResolver()),
    ];

    Livewire::test(VisitsTable::class)->call('someUndefinedMethod');
})->throws(MethodNotFoundException::class);

it('does not intercept the batch method on a component that has no table', function () {
    $plain = new class extends Component
    {
        public function greet(): string
        {
            return 'hello';
        }

        public function render(): string
        {
            return '<div></div>';
        }
    };

    $returned = Livewire::test($plain)
        ->call('greet')
        ->effects['returns'][0] ?? null;

    expect($returned)->toBe('hello');

    Livewire::test($plain)->call(IPToCountryFlagColumnHook::METHOD, ['whatever']);
})->throws(MethodNotFoundException::class);

it('never renders the resolved value or a tokenless fallback during the table render', function () {
    Visit::create(['ip_address' => '8.8.8.8']);

    VisitsTable::$columns = [
        IPToCountryFlagColumn::make('ip_address')->locationResolver(usResolver()),
    ];

    $html = Livewire::test(VisitsTable::class)->html();

    expect($html)->not->toContain('Mountain View, United States')
        ->and($html)->not->toContain('🇺🇸')
        ->and($html)->toContain('data-ip-country-flag-token');
});
