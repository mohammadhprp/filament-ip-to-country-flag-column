<?php

namespace Mohammadhprp\IPToCountryFlagColumn\Columns;

use Closure;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;

class IPToCountryFlagColumn extends TextColumn
{
    protected string $view = 'filament-ip-to-country-flag-column::columns.ip-to-country-flag-column';

    protected array|string|null $ip = null;

    protected ?string $flag = null;

    protected ?string $countryName = null;

    protected ?string $city = null;

    protected array $ipList = [];

    protected array $flagsList = [];

    protected array $locationsList = [];

    protected bool $isIPHide = false;

    protected bool $isFlagHide = false;

    protected bool $isLocationHide = false;

    protected bool $isCountryHide = false;

    protected bool $isCityHide = false;

    protected bool $isLazy = false;

    protected string $flagPosition = 'right';

    protected string $locationPosition = 'below';

    protected string $locationSeparator = ',';

    protected ?Closure $locationResolver = null;

    protected function setUp(): void
    {
        parent::setUp();

        // 防止HTML被转义
        $this->formatStateUsing(function ($state) {
            if (is_string($state) && str_contains($state, '<img')) {
                return new HtmlString($state);
            }
            return $state;
        });
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

    /**
     * Defer external location lookups when the table uses deferred loading.
     */
    public function lazy(bool $condition = true): static
    {
        $this->isLazy = $condition;

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

    public function getIP(): array|string
    {
        $this->city = null;
        $this->countryName = null;
        $this->flag = null;
        $this->ipList = [];
        $this->flagsList = [];
        $this->locationsList = [];

        $state = $this->getStateFromRecord();

        // Handle array input (multiple IPs)
        if (is_array($state)) {
            $this->ip = $state;
            foreach ($state as $singleIP) {
                $this->processSingleIP($singleIP);
            }

            // For compatibility, return formatted string for multiple IPs with HTML flags
            $result = [];
            foreach ($this->ipList as $ipData) {
                $result[] = $ipData['flag'] . ' ' . $ipData['ip'];
            }
            return implode('<br>', $result);
        }

        // Handle single IP - use processSingleIP for all cases
        $this->ip = $state ?? $this->getDefaultState() ?? '-';
        $this->processSingleIP($this->ip);
        return $this->ip;
    }

    protected function processSingleIP(mixed $ip): void
    {
        $city = null;
        $countryName = null;
        $flag = null;

        // Handle empty, null, or default placeholder
        if ($ip === null || $ip === '' || $ip === '-') {
            $this->ipList[] = [
                'ip' => '-',
                'flag' => null,
                'location' => null,
                'city' => null,
                'country' => null
            ];
            // For backward compatibility
            $this->city = null;
            $this->countryName = null;
            $this->flag = null;
            return;
        }

        // Handle array case (shouldn't happen here, but just in case)
        if (is_array($ip)) {
            foreach ($ip as $singleIP) {
                $this->processSingleIP($singleIP);
            }
            return;
        }

        // Convert to string for safety
        $ip = (string)$ip;

        // Check to IP address be valid
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->ipList[] = [
                'ip' => $ip,
                'flag' => null,
                'location' => null,
                'city' => null,
                'country' => null
            ];
            // For backward compatibility
            $this->city = null;
            $this->countryName = null;
            $this->flag = null;
            return;
        }

        // Check to IP address not be localhost
        if ($ip === '127.0.0.1') {
            $this->ipList[] = [
                'ip' => $ip,
                'flag' => '🏠',
                'location' => 'localhost',
                'city' => null,
                'country' => null
            ];
            // For backward compatibility
            $this->city = null;
            $this->countryName = null;
            $this->flag = '🏠';
            return;
        }

        // Check for lazy loading
        if ($this->isLazy && $this->shouldDeferLocationLookup()) {
            $this->ipList[] = [
                'ip' => $ip,
                'flag' => null,
                'location' => null,
                'city' => null,
                'country' => null
            ];
            return;
        }

        $location = $this->ip2Location($ip);
        $countryCode = $location->get('country_code');

        if ($countryCode === null) {
            $this->ipList[] = [
                'ip' => $ip,
                'flag' => null,
                'location' => null,
                'city' => null,
                'country' => null
            ];
            // For backward compatibility
            $this->city = null;
            $this->countryName = null;
            $this->flag = null;
            return;
        }

        $city = $location->get('city');
        $countryName = $location->get('country_name');
        $flagHtml = $this->getCountyFlag($countryCode);

        $this->ipList[] = [
            'ip' => $ip,
            'flag' => $flagHtml,
            'location' => $this->formatLocation($city, $countryName),
            'city' => $city,
            'country' => $countryName
        ];

        // For backward compatibility, set single values
        $this->city = $city;
        $this->countryName = $countryName;
        $this->flag = $flagHtml;
    }

    protected function formatLocation(?string $city, ?string $countryName): string
    {
        if ($this->isCountryHide || $countryName === null) {
            return $city ?? '';
        }

        if ($this->isCityHide || $city === null) {
            return $countryName;
        }

        return "$city$this->locationSeparator $countryName";
    }

    public function getFlag(): string|HtmlString
    {
        // Ensure getIP() is called to populate data
        if (empty($this->ipList)) {
            $this->getIP();
        }

        // For multiple IPs, return first flag for backward compatibility
        // 使用HtmlString包装HTML，防止被转义
        return $this->flag;
    }

    public function getFlagPosition(): string
    {
        return $this->flagPosition;
    }

    public function getLocation(): string
    {
        // Ensure getIP() is called to populate data
        if (empty($this->ipList)) {
            $this->getIP();
        }

        if ($this->isCountryHide || $this->countryName === null) {
            return "$this->city";
        }

        if ($this->isCityHide || $this->city === null) {
            return "$this->countryName";
        }

        return "$this->city$this->locationSeparator $this->countryName";
    }

    public function getIpList(): array
    {
        // 确保每次都重新处理当前记录，不使用缓存
        $this->ipList = []; // 清空之前的数据
        $this->getIP();     // 重新处理当前记录

        // 如果处理后仍然为空，添加默认条目
        if (empty($this->ipList)) {
            $state = $this->getStateFromRecord();
            $this->ipList[] = [
                'ip' => is_array($state) ? 'Array: ' . count($state) . ' IPs' : ($state ?? '-'),
                'flag' => null,
                'location' => null,
                'city' => null,
                'country' => null
            ];
        }

        return $this->ipList;
    }

    public function isMultipleIPs(): bool
    {
        $list = $this->getIpList();
        return count($list) > 1;
    }

    public function hasMultipleIPs(): bool
    {
        return count($this->ipList) > 1;
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

    public function isLazy(): bool
    {
        return $this->isLazy;
    }

    private function getCountyFlag(string $countryCode): string
    {
        try {
            $jsonPath = __DIR__.'/../../resources/jsons/countries-flag.json';

            if (!file_exists($jsonPath)) {
                return '';
            }

            $jsonData = file_get_contents($jsonPath);

            if ($jsonData === false) {
                return '';
            }

            $countries_data = collect(json_decode($jsonData, true));

            $country = $countries_data->where('code', '=', $countryCode)->first();

            // 使用图片URL替代emoji字符，返回HtmlString防止被转义
            if ($country && isset($country['code'])) {
                $html = '<img src="https://flagcdn.com/w20/' . strtolower($country['code']) . '.png" alt="' . $country['name'] . '" class="inline-flag" style="width: 20px; height: auto; vertical-align: middle; border-radius: 2px;" />';
                return $html;
            }

            return '';
        } catch (\Exception $e) {
            return '';
        }
    }

    protected function ip2Location(string $ip): Collection
    {
        if ($this->locationResolver !== null) {
            return collect(($this->locationResolver)($ip));
        }

        return Cache::remember(
            "filament-ip-to-country-flag-column.location.{$ip}",
            now()->addDay(),
            fn (): Collection => $this->requestLocation($ip),
        );
    }

    protected function shouldDeferLocationLookup(): bool
    {
        try {
            $table = $this->getTable();
        } catch (\Throwable) {
            return false;
        }

        return $table->isLoadingDeferred() && ! $table->isLoaded();
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
