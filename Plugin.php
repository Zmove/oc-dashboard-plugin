<?php

namespace Zmove\Dashboard;

use System\Classes\PluginBase;

use Zmove\Dashboard\ReportWidgets\AccountManager;
use Zmove\Dashboard\ReportWidgets\Iframe;
use Zmove\Dashboard\ReportWidgets\SiteStatus;
use Zmove\Dashboard\ReportWidgets\SiteVersion;

class Plugin extends PluginBase
{
    public function pluginDetails(): array
    {
        return [
            'name' => 'Zmove Dashboard',
            'description' => 'Modules personnalisables pour le tableau de bord du back-office',
            'author' => 'Zmove',
            'icon' => 'icon-dashboard',
        ];
    }

    public function registerReportWidgets(): array
    {
        return [
            AccountManager::class => [
                'label' => 'Votre interlocuteur',
                'context' => 'dashboard',
                'group' => 'Zmove',
            ],
            SiteStatus::class => [
                'label' => 'Statut du site',
                'context' => 'dashboard',
                'group' => 'Zmove',
            ],
            SiteVersion::class => [
                'label' => 'Version d\'October CMS et de PHP',
                'context' => 'dashboard',
                'group' => 'Zmove',
            ],
            Iframe::class => [
                'label' => 'Iframe',
                'context' => 'dashboard',
                'group' => 'Zmove',
            ],
        ];
    }
}
