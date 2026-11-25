<?php

namespace Zmove\Dashboard\ReportWidgets;

use Zmove\Dashboard\Classes\OctoberVersionChecker;
use Zmove\Dashboard\Classes\WidgetBase;

/**
 * Displays whether the October CMS core is up to date against the latest release of its major
 * version, along with the installed and PHP versions and a link to the October CMS changelog.
 */
class SiteVersion extends WidgetBase
{
    protected $defaultAlias = 'zmoveSiteVersion';

    protected const CHANGELOG_URL = 'https://octobercms.com/changelog';

    public function defineProperties()
    {
        return $this->titleProperty('Version du site');
    }

    protected function prepareVars(): void
    {
        $october = OctoberVersionChecker::check();

        $this->vars['october'] = $october;
        $this->vars['status'] = $this->updateStatus($october['isUpToDate'], $october['latest']);
        $this->vars['phpVersion'] = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION;
        $this->vars['changelogUrl'] = static::CHANGELOG_URL;
    }

    /**
     * updateStatus resolves the header's badge and headline, and the note under it giving the
     * latest release of the installed major version.
     *
     * @return array{tone: string, icon: string, text: string, note: string}
     */
    protected function updateStatus(?bool $isUpToDate, ?string $latestVersion): array
    {
        $note = $latestVersion !== null ? 'Dernière version : ' . $latestVersion : 'Dernière version inconnue';

        if ($isUpToDate === true) {
            return ['tone' => 'online', 'icon' => 'ph ph-seal-check', 'text' => 'À jour', 'note' => $note];
        }

        if ($isUpToDate === false) {
            return ['tone' => 'maintenance', 'icon' => 'ph ph-arrow-circle-up', 'text' => 'Mise à jour disponible', 'note' => $note];
        }

        return ['tone' => 'neutral', 'icon' => 'ph ph-question', 'text' => 'Vérification indisponible', 'note' => $note];
    }
}
