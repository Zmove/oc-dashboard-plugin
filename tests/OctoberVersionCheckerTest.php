<?php namespace Zmove\Dashboard\Tests;

use Cache;
use Http;
use PluginTestCase;
use Zmove\Dashboard\Classes\OctoberVersionChecker;

/**
 * OctoberVersionCheckerTest covers the core update status: only the installed major version
 * counts, pre-releases are ignored, and an unreachable Packagist leaves the status unknown.
 */
class OctoberVersionCheckerTest extends PluginTestCase
{
    /**
     * @var array<int, string>
     */
    protected const RELEASES = ['v4.0.0', 'v3.7.19', 'v3.2.23', 'v3.2.22', 'v3.2.0', 'v3.3.0-RC1', '3.x-dev', 'dev-develop'];

    public function setUp(): void
    {
        parent::setUp();

        Cache::forget('zmove.dashboard.october.releases');
    }

    /**
     * testSiteOnLatestReleaseOfItsMajorIsUpToDateDespiteANewerMajor
     */
    public function testSiteOnLatestReleaseOfItsMajorIsUpToDateDespiteANewerMajor()
    {
        $status = OctoberVersionChecker::compare('3.7.19', static::RELEASES);

        $this->assertSame(['current' => '3.7.19', 'major' => 3, 'latest' => '3.7.19', 'isUpToDate' => true], $status);
    }

    /**
     * testNewerMinorOfTheSameMajorIsAnUpdate, 3.2 being outdated once 3.7 is out.
     */
    public function testNewerMinorOfTheSameMajorIsAnUpdate()
    {
        $status = OctoberVersionChecker::compare('3.2.23', static::RELEASES);

        $this->assertSame('3.7.19', $status['latest']);
        $this->assertFalse($status['isUpToDate']);
    }

    /**
     * testNewerBuildOfTheSameMinorIsAnUpdate
     */
    public function testNewerBuildOfTheSameMinorIsAnUpdate()
    {
        $status = OctoberVersionChecker::compare('4.4.6', ['v4.4.14', 'v4.4.6', 'v4.3.8']);

        $this->assertSame('4.4.14', $status['latest']);
        $this->assertFalse($status['isUpToDate']);
    }

    /**
     * testVersionsAreComparedNumerically, 4.4.10 being newer than 4.4.9.
     */
    public function testVersionsAreComparedNumerically()
    {
        $this->assertSame('4.4.10', OctoberVersionChecker::latestInMajor(['v4.4.9', 'v4.4.10', 'v4.4.2'], 4));
    }

    /**
     * testPreReleasesAndDevelopmentBranchesAreIgnored
     */
    public function testPreReleasesAndDevelopmentBranchesAreIgnored()
    {
        $this->assertSame('4.4.0', OctoberVersionChecker::latestInMajor(['v4.4.0', 'v4.5.0-RC1', '4.x-dev', 'dev-develop'], 4));
        $this->assertNull(OctoberVersionChecker::latestInMajor(['v5.0.0-beta1', '5.x-dev'], 5));
    }

    /**
     * testUnknownReleasesLeaveTheStatusUnknown
     */
    public function testUnknownReleasesLeaveTheStatusUnknown()
    {
        $status = OctoberVersionChecker::compare('4.4.6', null);

        $this->assertSame(['current' => '4.4.6', 'major' => 4, 'latest' => null, 'isUpToDate' => null], $status);
    }

    /**
     * testNormalizeVersion
     */
    public function testNormalizeVersion()
    {
        $this->assertSame('4.4.6', OctoberVersionChecker::normalizeVersion('v4.4.6'));
        $this->assertSame('4.4.6', OctoberVersionChecker::normalizeVersion('4.4.6'));
        $this->assertNull(OctoberVersionChecker::normalizeVersion('dev-develop'));
        $this->assertNull(OctoberVersionChecker::normalizeVersion('4.5.0-RC1'));
        $this->assertNull(OctoberVersionChecker::normalizeVersion(null));
    }

    /**
     * testReleasedVersionsAreReadFromPackagistAndCached
     */
    public function testReleasedVersionsAreReadFromPackagistAndCached()
    {
        Http::fake([
            'repo.packagist.org/*' => Http::response(['packages' => ['october/rain' => [
                ['name' => 'october/rain', 'version' => 'v4.4.14'],
                ['version' => 'v4.4.13'],
            ]]]),
        ]);

        $this->assertSame(['v4.4.14', 'v4.4.13'], OctoberVersionChecker::releasedVersions());
        $this->assertSame(['v4.4.14', 'v4.4.13'], OctoberVersionChecker::releasedVersions());

        Http::assertSentCount(1);
    }

    /**
     * testUnreachablePackagistIsCachedAsUnknown, so every dashboard load does not wait for it.
     */
    public function testUnreachablePackagistIsCachedAsUnknown()
    {
        Http::fake(['repo.packagist.org/*' => Http::response('Service unavailable', 503)]);

        $this->assertNull(OctoberVersionChecker::releasedVersions());
        $this->assertNull(OctoberVersionChecker::releasedVersions());

        Http::assertSentCount(1);
    }

    /**
     * testCurrentVersionIsTheInstalledCoreRelease
     */
    public function testCurrentVersionIsTheInstalledCoreRelease()
    {
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', OctoberVersionChecker::currentVersion());
    }
}
