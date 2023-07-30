<?php

namespace Mohammadhprp\IPToCountryFlagColumn\Columns;

use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Collection;

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

    protected string $flagPosition = 'right';
    protected string $locationPosition = 'below';
    protected string $locationSeparator = ',';


    protected function setUp(): void
    {
        parent::setUp();
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


    public function getIP(): string
    {
        $this->ip = $this->getStateFromRecord();

        /// Return default state if IP was null
        if ($this->ip === null) {
            $this->city = null;
            $this->countryName = null;
            $this->flag = null;

            return $this->getDefaultState() ?? '-';
        }

        /// Check to IP address be valid
        if (!filter_var($this->ip, FILTER_VALIDATE_IP)) {
            return 'Invalid IP address';
        }

        /// Check to IP address not be localhost
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

        return $this->city . $this->locationSeparator . $this->countryName;
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

    private function getCountyFlag(string $countryCode): string
    {
        $jsonData = file_get_contents(__DIR__ . '/../../resources/jsons/countries-flag.json');
        $countries_data = collect(json_decode($jsonData, true));

        $country = $countries_data->where('code', '=', $countryCode)->first();
        return $country['flag'];
    }

    private function ip2Location(string $ip): Collection
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://iplocation.com/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array('ip' => $ip),
            CURLOPT_HTTPHEADER => array(
                'User-Agent: Mozilla/5.0 (Linux; Android 12.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Mobile Safari/537.36',
                'Accept: */*',
                'Accept-Language: en-US,en;q=0.5',
                'Accept-Encoding: gzip, deflate, br',
                'Referer: https://iplocation.com/',
                'Origin: https://iplocation.com',
                'Connection: keep-alive',
                'Sec-Fetch-Dest: empty',
                'Sec-Fetch-Mode: cors',
                'Sec-Fetch-Site: same-origin',
                'TE: trailers'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return collect(json_decode($response, true));
    }
}