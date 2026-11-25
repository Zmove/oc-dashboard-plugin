<?php

namespace Zmove\Dashboard\Classes;

use Cache;
use Http;
use System;
use System\Classes\UpdateManager;
use Throwable;

/**
 * Tells whether the October CMS core is up to date within its installed major version: a site
 * on 3.2.x is compared against the latest 3.x release only, so the release of a 4.x line does
 * not flag it as outdated. Third-party plugins are deliberately left out of the check.
 *
 * Released versions are read from the public Packagist metadata of october/rain, which is tagged
 * in lockstep with the core (October itself falls back to it to work out its build number), since
 * october/system is only published on the license-protected October gateway.
 */
class OctoberVersionChecker
{
    protected const RELEASES_URL = 'https://repo.packagist.org/p2/october/rain.json';

    protected const RELEASES_CACHE_KEY = 'zmove.dashboard.october.releases';

    protected const CACHE_HOURS = 12;

    /**
     * Shorter lifetime for a failed lookup, so an unreachable Packagist is retried soon
     * without slowing down every dashboard load in the meantime.
     */
    protected const RETRY_MINUTES = 30;

    /**
     * check compares the installed core version with the latest release of its major version.
     *
     * @return array{current: string, major: int, latest: ?string, isUpToDate: ?bool}
     */
    public static function check(): array
    {
        return static::compare(static::currentVersion(), static::releasedVersions());
    }

    /**
     * compare resolves the update status of a version against a list of released versions.
     * "latest" and "isUpToDate" are null when the releases are unknown or none is stable in
     * that major version.
     *
     * @param  array<int, string>|null  $releasedVersions
     * @return array{current: string, major: int, latest: ?string, isUpToDate: ?bool}
     */
    public static function compare(string $currentVersion, ?array $releasedVersions): array
    {
        $major = (int) explode('.', $currentVersion)[0];
        $latest = $releasedVersions !== null ? static::latestInMajor($releasedVersions, $major) : null;

        return [
            'current' => $currentVersion,
            'major' => $major,
            'latest' => $latest,
            'isUpToDate' => $latest !== null ? version_compare($currentVersion, $latest, '>=') : null,
        ];
    }

    /**
     * latestInMajor returns the highest stable version of the given major version, ignoring
     * development branches and pre-releases.
     *
     * @param  array<int, string>  $versions
     */
    public static function latestInMajor(array $versions, int $major): ?string
    {
        $latest = null;

        foreach ($versions as $version) {
            $version = static::normalizeVersion($version);
            if ($version === null || (int) explode('.', $version)[0] !== $major) {
                continue;
            }

            if ($latest === null || version_compare($version, $latest, '>')) {
                $latest = $version;
            }
        }

        return $latest;
    }

    /**
     * normalizeVersion strips the "v" prefix of a stable x.y.z version, and returns null for
     * anything else (dev-main, 4.x-dev, 4.5.0-RC1...).
     */
    public static function normalizeVersion(?string $version): ?string
    {
        $version = trim((string) $version);

        return preg_match('/^v?(\d+\.\d+\.\d+)$/i', $version, $matches) ? $matches[1] : null;
    }

    /**
     * currentVersion returns the installed core version, read from the october/system package
     * Composer installed, or from October's own version and build number when the package
     * version is not a release (a development checkout for instance).
     */
    public static function currentVersion(): string
    {
        try {
            $versions = static::composerManager()->getPackageVersions(['october/system']);
            $version = static::normalizeVersion($versions['october/system'] ?? null);
        }
        catch (Throwable $ex) {
            $version = null;
        }

        if ($version !== null) {
            return $version;
        }

        $updateManager = UpdateManager::instance();

        return method_exists($updateManager, 'getCurrentVersion')
            ? $updateManager->getCurrentVersion()
            : System::VERSION;
    }

    /**
     * composerManager returns October's Composer helper, named Manager up to 3.x and
     * ComposerManager since 4.x.
     *
     * @return \October\Rain\Composer\ComposerManager|\October\Rain\Composer\Manager
     */
    protected static function composerManager(): object
    {
        $className = class_exists(\October\Rain\Composer\ComposerManager::class)
            ? \October\Rain\Composer\ComposerManager::class
            : \October\Rain\Composer\Manager::class;

        return $className::instance();
    }

    /**
     * releasedVersions returns every version of the core published so far, or null when
     * Packagist could not be reached.
     *
     * @return array<int, string>|null
     */
    public static function releasedVersions(): ?array
    {
        return static::remember(static::RELEASES_CACHE_KEY, fn () => static::fetchReleasedVersions());
    }

    /**
     * remember caches a Packagist lookup, keeping a failed one (null) for a shorter time.
     */
    protected static function remember(string $cacheKey, callable $fetch): ?array
    {
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && array_key_exists('value', $cached)) {
            return $cached['value'];
        }

        $value = $fetch();

        Cache::put(
            $cacheKey,
            ['value' => $value],
            $value === null ? static::RETRY_MINUTES * 60 : static::CACHE_HOURS * 3600
        );

        return $value;
    }

    /**
     * @return array<int, string>|null
     */
    protected static function fetchReleasedVersions(): ?array
    {
        $releases = static::fetchJson(static::RELEASES_URL, [], 'packages.october/rain');
        if ($releases === null) {
            return null;
        }

        $versions = array_values(array_filter(
            array_map(fn ($release) => is_array($release) ? (string) ($release['version'] ?? '') : '', $releases),
            fn ($version) => $version !== ''
        ));

        return $versions ?: null;
    }

    /**
     * fetchJson requests a Packagist endpoint and returns the array found at the given key of
     * its JSON response, or null when the request fails.
     *
     * @param  array<string, mixed>  $query
     */
    protected static function fetchJson(string $url, array $query, string $key): ?array
    {
        try {
            $response = Http::timeout(5)->acceptJson()->get($url, $query);
        }
        catch (Throwable $ex) {
            return null;
        }

        $value = $response->successful() ? $response->json($key) : null;

        return is_array($value) ? $value : null;
    }
}
