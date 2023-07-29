<?php

namespace Mohammadhprp\IPToCountryFlagColumn\Columns;

use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Collection;

class IPToCountryFlagColumn extends TextColumn
{
    protected string $view = 'filament-ip-to-country-flag-column::columns.ip-to-country-flag-column';

    protected ?string $countryName = null;
    protected ?string $city = null;

    public function getIpWithFlag(): string
    {
        $ip = $this->getStateFromRecord();

        /// Return default state if IP was null
        if ($ip === null) {
            return $this->getDefaultState() ?? '-';
        }

        /// Check to IP address be valid
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return 'Invalid IP address';
        }

        /// Check to IP address not be localhost
        if ($ip === '127.0.0.1') {
            return "$ip 🏠";
        }

        $location = $this->ip2Location($ip);

        $countryCode = $location->get('country_code');

        if ($countryCode === null) {
            return $ip;
        }

        $this->city = $location->get('city');
        $this->countryName = $location->get('country_name');

        $countryFlag = $this->getCountyFlag($countryCode);

        return "$ip $countryFlag";
    }

    public function getIpInfo(): string
    {
        if ($this->countryName === null) {
            return "$this->city";
        }

        if ($this->city === null) {
            return "$this->countryName";
        }

        return "$this->city, $this->countryName";
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