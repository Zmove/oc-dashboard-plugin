<?php

namespace Zmove\Dashboard;

use Backend\Classes\Controller;
use Event;
use File;
use System\Classes\PluginBase;
use Url;

use Zmove\Dashboard\ReportWidgets\AccountManager;
use Zmove\Dashboard\ReportWidgets\CustomHtml;
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

    /**
     * boot loads the script running the "HTML personnalisé" blocks' scripts on every backend
     * page, since a dashboard can be embedded in any backend controller in 4.x.
     */
    public function boot(): void
    {
        Event::listen('backend.page.beforeDisplay', function (Controller $controller) {
            $controller->addJs(Url::asset(File::localToPublic(__DIR__ . '/assets/js/custom-html.js')));
        });
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
            CustomHtml::class => [
                'label' => 'HTML personnalisé',
                'context' => 'dashboard',
                'group' => 'Zmove',
            ],
        ];
    }
}
