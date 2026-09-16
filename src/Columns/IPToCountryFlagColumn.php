<?php

namespace Mohammadhprp\IPToCountryFlagColumn\Columns;

use Closure;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Mohammadhprp\IPToCountryFlagColumn\Resolution\CellToken;
use Throwable;

class IPToCountryFlagColumn extends TextColumn
{
    protected string $view = 'filament-ip-to-country-flag-column::columns.ip-to-country-flag-column';

    protected ?string $ip = null;

    protected ?string $flag = null;

    protected ?string $countryName = null;

    protected ?string $city = null;

    protected bool $isIPHide = false;

    protected bool $isFlagHide = false;

    protected bool $isLocationHide = false;

    protected bool $isCountryHide = false;

    protected bool $isCityHide = false;

    protected string|Htmlable|Closure|null $loadingState = null;

    protected string|Htmlable|Closure|null $errorState = null;

    protected bool|Closure $loadsWhenVisible = false;

    protected bool|Closure $isRetryable = true;

    protected string $flagPosition = 'right';

    protected string $locationPosition = 'below';

    protected string $locationSeparator = ',';

    protected ?Closure $locationResolver = null;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * What the cell shows before it resolves. Defaults to a CSS skeleton.
     */
    public function loadingState(string|Htmlable|Closure|null $state): static
    {
        $this->loadingState = $state;

        return $this;
    }

    public function getLoadingState(): string|Htmlable|null
    {
        return $this->evaluate($this->loadingState)
            ?? new HtmlString('<span class="fi-ip-country-flag-skeleton" aria-hidden="true"></span>');
    }

    /**
     * What the cell shows when the resolver throws. Defaults to a translated
     * "Could not load".
     */
    public function errorState(string|Htmlable|Closure|null $state): static
    {
        $this->errorState = $state;

        return $this;
    }

    public function errorStateUsing(?Closure $callback): static
    {
        return $this->errorState($callback);
    }

    /**
     * The exception is only ever exposed to an explicit developer callback. Never
     * render $exception->getMessage() straight into the cell - a failing resolver
     * can leak API credentials or SQL.
     */
    public function getErrorState(?Throwable $exception = null): string|Htmlable|null
    {
        return $this->evaluate($this->errorState, ['exception' => $exception])
            ?? __('ip-to-country-flag-column::ip-to-country-flag-column.error');
    }

    /**
     * Defer a cell's lookup until it scrolls into the viewport.
     */
    public function whenVisible(bool|Closure $condition = true): static
    {
        $this->loadsWhenVisible = $condition;

        return $this;
    }

    public function loadsWhenVisible(): bool
    {
        return (bool) $this->evaluate($this->loadsWhenVisible);
    }

    /**
     * Whether a failed cell can be clicked to retry.
     */
    public function retryable(bool|Closure $condition = true): static
    {
        $this->isRetryable = $condition;

        return $this;
    }

    public function isRetryable(): bool
    {
        return (bool) $this->evaluate($this->isRetryable);
    }

    public function hideIP(): static
    {
        $this->isIPHide = true;

        return $this;
    }

    public function hideFlag(): static
    {
        $this->isFlagHide = true;

        return $this;
    }

    public function hideLocation(): static
    {
        $this->isLocationHide = true;

        return $this;
    }

    public function hideCountry(): static
    {
        $this->isCountryHide = true;

        return $this;
    }

    public function hideCity(): static
    {
        $this->isCityHide = true;

        return $this;
    }

    public function location(string $position = 'below', string $separator = ','): static
    {
        $this->locationPosition = $position;
        $this->locationSeparator = $separator;

        return $this;
    }

    public function flagPosition(?string $position = 'right'): static
    {
        $this->flagPosition = $position;

        return $this;
    }

    /**
     * Set a custom IP location resolver, useful for caching or alternative providers.
     */
    public function locationResolver(?Closure $resolver): static
    {
        $this->locationResolver = $resolver;

        return $this;
    }

    public function getIP(): string
    {
        $this->city = null;
        $this->countryName = null;
        $this->flag = null;
        $this->ip = $this->getStateFromRecord();

        // / Return default state if IP was null
        if ($this->ip === null) {
            return $this->getDefaultState() ?? '-';
        }

        // / Check to IP address be valid
        if (! filter_var($this->ip, FILTER_VALIDATE_IP)) {
            return 'Invalid IP address';
        }

        // / Check to IP address not be localhost
        if ($this->ip === '127.0.0.1') {
            return "$this->ip 🏠";
        }

        $location = $this->ip2Location($this->ip);

        $countryCode = $location->get('country_code');

        if ($countryCode === null) {
            return $this->ip;
        }

        $this->city = $location->get('city');
        $this->countryName = $location->get('country_name');

        $this->flag = $this->getCountyFlag($countryCode);

        return $this->ip;
    }

    public function getFlag(): ?string
    {
        return $this->flag;
    }

    public function getFlagPosition(): string
    {
        return $this->flagPosition;
    }

    public function getLocation(): string
    {
        if ($this->isCountryHide || $this->countryName === null) {
            return "$this->city";
        }

        if ($this->isCityHide || $this->city === null) {
            return "$this->countryName";
        }

        return "$this->city$this->locationSeparator $this->countryName";
    }

    public function getLocationPosition(): string
    {
        return $this->locationPosition;
    }

    public function getHideIP(): bool
    {
        return $this->isIPHide;
    }

    public function getHideFlag(): bool
    {
        return $this->isFlagHide;
    }

    public function getHideLocation(): bool
    {
        return $this->isLocationHide;
    }

    /**
     * Identifies this cell for the batched resolution round-trip.
     */
    public function getCellToken(): ?CellToken
    {
        $recordKey = $this->getRecordKey();

        if (blank($recordKey)) {
            return null;
        }

        return new CellToken($this->getName(), $recordKey);
    }

    /**
     * Render the fully resolved cell - IP, flag and location - for one record.
     *
     * Called by the BatchResolver after the page has painted, never during the
     * synchronous table render. The record is set on this (cloned) column instance
     * so the column's own view can resolve everything it needs through the same
     * getters it has always used.
     *
     * @param  Model|array<string, mixed>  $record
     */
    public function renderResolvedHtml(Model|array $record): string
    {
        $this->record($record);

        // Deliberately not toHtml(): that is overridden to always emit the
        // placeholder. render() renders the column's own view synchronously,
        // which is exactly what a resolved cell needs.
        return $this->render()->render();
    }

    /**
     * The location lookup never runs during the synchronous table render. Every
     * cell renders the placeholder instead, and the value is resolved afterwards in
     * one batched Livewire request.
     */
    public function toHtml(): string
    {
        $token = $this->getCellToken();

        if (! $token instanceof CellToken) {
            return '';
        }

        return view('filament-ip-to-country-flag-column::columns.placeholder', [
            'token' => $token->encode(),
            'loadingState' => $this->getLoadingState(),
            'whenVisible' => $this->loadsWhenVisible(),
            'retryable' => $this->isRetryable(),
        ])->render();
    }

    private function getCountyFlag(string $countryCode): string
    {
        $jsonData = file_get_contents(__DIR__.'/../../resources/jsons/countries-flag.json');
        $countries_data = collect(json_decode($jsonData, true));

        $country = $countries_data->where('code', '=', $countryCode)->first();

        return $country['flag'] ?? '';
    }

    protected function ip2Location(string $ip): Collection
    {
        if ($this->locationResolver !== null) {
            return collect(($this->locationResolver)($ip));
        }

        return $this->requestLocation($ip);
    }

    protected function requestLocation(string $ip): Collection
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://iplocation.com/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => http_build_query(['ip' => $ip]),
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Linux; Android 12.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Mobile Safari/537.36',
                'Accept: */*',
                'Content-Type: application/x-www-form-urlencoded',
                'Accept-Language: en-US,en;q=0.5',
                'Accept-Encoding: gzip, deflate',
                'Referer: https://iplocation.com/',
                'Origin: https://iplocation.com',
                'Connection: keep-alive',
                'Sec-Fetch-Dest: empty',
                'Sec-Fetch-Mode: cors',
                'Sec-Fetch-Site: same-origin',
                'TE: trailers',
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        return collect(json_decode($response, true));
    }
}
