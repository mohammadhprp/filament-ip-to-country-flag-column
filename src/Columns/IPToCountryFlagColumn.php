<?php

namespace Mohammadhprp\IPToCountryFlagColumn\Columns;

use Filament\Tables\Columns\TextColumn;

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

        /// Reset state to default
        $this->city = null;
        $this->countryName = null;
        $this->flag = null;

        /// Return default state if IP was null
        if ($this->ip === null) {
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

        $location = geoip($this->ip);
        $countryCode = $location->iso_code;

        if ($countryCode === null) {
            return $this->ip;
        }

        $this->city = $location->city;
        $this->countryName = $location->country;
        $this->flag = $this->getCountryFlag($countryCode);

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

    private function getCountryFlag(string $countryCode): string
    {
        return country($countryCode)->getEmoji();
    }
}