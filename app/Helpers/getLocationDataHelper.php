<?php

namespace App\Helpers;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class GetLocationDataHelper
{
    /**
     * Path to the JSON file containing PH locations
     */
    protected static string $path = 'philippine_provinces_cities_municipalities_and_barangays_2019v2.json';

    /**
     * Load PH location data from cache or JSON file
     *
     * - Caches the data forever after first load
     * - Avoids disk I/O on every request
     * - Throws Exception if JSON is missing or malformed
     * -
     * - NOTE: This is used for speed and performance of the website, since technically you are making a request every
     * - time you load the page in your previous code, and this is a lot of data to load every time.
     * - If you need to clear the cache, run `php artisan cache:clear`
     * * @throws Exception
     */
    protected static function data(): array
    {
        return Cache::rememberForever('ph_locations', function () {
            if (!Storage::exists(self::$path)) {
                throw new Exception("Missing JSON at " . Storage::path(self::$path));
            }
            $data = json_decode(Storage::get(self::$path), true);
            return is_array($data) ? $data : throw new Exception("Malformed JSON");
        });
    }

    /**
     *
     * Returns: [region_code => region_name]
     */
    public static function getRegions(): array
    {
        return collect(self::data()['region_code'] ?? [])
            ->mapWithKeys(fn($region, $code) => [$code => $region['region_name'] ?? $code])
            ->all();
    }

    /**
     * Get all provinces in a given region
     *
     * @param string|null $region Region code (e.g., "01")
     * Returns: [province_code => province_name]
     */
    public static function getProvinces(?string $region): array
    {
        return collect(Arr::get(self::data(), "region_code.$region.province_list", []))
            ->mapWithKeys(fn($province, $code) => [$code => $province['province_name'] ?? $code])
            ->all();
    }

    /**
     * Get all municipalities in a given province
     *
     * @param string|null $region Region code
     * @param string|null $province Province code
     * Returns: [municipality_code => municipality_name]
     */
    public static function getMunicipalities(?string $region, ?string $province): array
    {
        return collect(Arr::get(self::data(), "region_code.$region.province_list.$province.municipality_list", []))
            ->mapWithKeys(fn($municipality, $code) => [$code => $municipality['municipality_name'] ?? $code])
            ->all();
    }

    /**
     * Get all barangays in a given municipality
     *
     * @param string|null $region Region code
     * @param string|null $province Province code
     * @param string|null $municipality Municipality code
     * Returns: [barangay_name => barangay_name]
     */
    public static function getBarangays(?string $region, ?string $province, ?string $municipality): array
    {
        return collect(Arr::get(self::data(), "region_code.$region.province_list.$province.municipality_list.$municipality.barangay_list", []))
            ->mapWithKeys(fn($barangay) => [$barangay => $barangay])
            ->all();
    }
}
