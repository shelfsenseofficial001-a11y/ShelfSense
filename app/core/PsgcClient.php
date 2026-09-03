<?php
namespace App\Core;

/**
 * Thin client for the Philippine Standard Geographic Code (PSGC) hierarchy
 * (psgc.gitlab.io/api), used to populate and validate the career
 * application form's Province -> City/Municipality -> Barangay cascade.
 *
 * The upstream API has no per-request filtering -- each endpoint always
 * returns its entire national list (provinces: small, cities/municipalities:
 * ~390KB, barangays: ~11MB) -- so responses are cached to disk and filtered
 * locally, refreshed once every CACHE_TTL_SECONDS. A stale cache is served
 * (rather than failing the request) if a refresh fetch fails, since this
 * data changes on the order of years, not days.
 *
 * There is deliberately no postal-code data here: no reliable, complete,
 * Philippines-wide dataset ties postal codes to barangay/city (checked
 * against several candidate sources), so postal code stays a plain
 * applicant-entered, format-validated field elsewhere in the app.
 */
class PsgcClient
{
    private const BASE_URL = 'https://psgc.gitlab.io/api';
    private const CACHE_TTL_SECONDS = 7 * 24 * 60 * 60; // 1 week
    private const CACHE_DIR = __DIR__ . '/../../storage/cache/psgc';

    public static function getProvinces(): array
    {
        $data = self::loadCached('provinces');
        $list = array_map(function ($p) {
            return ['code' => $p['code'], 'name' => $p['name']];
        }, $data);
        usort($list, fn($a, $b) => strcmp($a['name'], $b['name']));
        return $list;
    }

    public static function getCitiesByProvince(string $provinceCode): array
    {
        $data = self::loadCached('cities-municipalities');
        $list = array_values(array_filter($data, function ($c) use ($provinceCode) {
            return ($c['provinceCode'] ?? null) === $provinceCode;
        }));
        $list = array_map(function ($c) {
            return ['code' => $c['code'], 'name' => $c['name']];
        }, $list);
        usort($list, fn($a, $b) => strcmp($a['name'], $b['name']));
        return $list;
    }

    public static function getBarangaysByCity(string $cityOrMunicipalityCode): array
    {
        $data = self::loadCached('barangays');
        $list = array_values(array_filter($data, function ($b) use ($cityOrMunicipalityCode) {
            return ($b['cityCode'] ?? null) === $cityOrMunicipalityCode
                || ($b['municipalityCode'] ?? null) === $cityOrMunicipalityCode;
        }));
        $list = array_map(function ($b) {
            return ['code' => $b['code'], 'name' => $b['name']];
        }, $list);
        usort($list, fn($a, $b) => strcmp($a['name'], $b['name']));
        return $list;
    }

    /**
     * True only if the three codes form a real, currently-valid PSGC chain
     * (barangay belongs to that city/municipality, which belongs to that
     * province). Used to reject a tampered or stale submission server-side.
     */
    public static function isValidChain(string $provinceCode, string $cityCode, string $barangayCode): bool
    {
        $cities = self::getCitiesByProvince($provinceCode);
        if (!self::containsCode($cities, $cityCode)) {
            return false;
        }
        $barangays = self::getBarangaysByCity($cityCode);
        return self::containsCode($barangays, $barangayCode);
    }

    public static function findName(array $list, string $code): ?string
    {
        foreach ($list as $item) {
            if ($item['code'] === $code) {
                return $item['name'];
            }
        }
        return null;
    }

    private static function containsCode(array $list, string $code): bool
    {
        return self::findName($list, $code) !== null;
    }

    private static function loadCached(string $endpoint): array
    {
        if (!is_dir(self::CACHE_DIR)) {
            mkdir(self::CACHE_DIR, 0777, true);
        }

        $cacheFile = self::CACHE_DIR . '/' . $endpoint . '.json';
        $isFresh = is_file($cacheFile) && (time() - filemtime($cacheFile)) < self::CACHE_TTL_SECONDS;

        if (!$isFresh) {
            $fetched = self::fetch($endpoint);
            if ($fetched !== null) {
                file_put_contents($cacheFile, $fetched);
            }
        }

        if (!is_file($cacheFile)) {
            throw new \RuntimeException("PSGC data for '{$endpoint}' is unavailable (no cache, upstream fetch failed).");
        }

        $decoded = json_decode(file_get_contents($cacheFile), true);
        return is_array($decoded) ? $decoded : [];
    }

    private static function fetch(string $endpoint): ?string
    {
        $context = stream_context_create(['http' => [
            'method' => 'GET',
            'timeout' => 10,
            'header' => "User-Agent: ShelfSense/1.0\r\n",
        ]]);

        $body = @file_get_contents(self::BASE_URL . '/' . $endpoint . '/', false, $context);
        if ($body === false) {
            error_log("PsgcClient: failed to fetch '{$endpoint}' from PSGC API");
            return null;
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            error_log("PsgcClient: PSGC API returned invalid JSON for '{$endpoint}'");
            return null;
        }

        return $body;
    }
}
