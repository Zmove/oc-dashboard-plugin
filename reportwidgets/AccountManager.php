<?php

namespace Zmove\Dashboard\ReportWidgets;

use Zmove\Dashboard\Classes\WidgetBase;

/**
 * Displays the client's account manager, whose details are entered in the widget configuration,
 * with shortcuts to email or call them.
 */
class AccountManager extends WidgetBase
{
    protected $defaultAlias = 'zmoveAccountManager';

    public function defineProperties()
    {
        return $this->titleProperty('Votre interlocuteur') + [
            'managerName' => [
                'title' => 'Nom de l\'interlocuteur',
                'description' => 'Prénom et nom',
                'type' => 'string',
            ],
            'managerCompany' => [
                'title' => 'Société',
                'type' => 'string',
                'default' => 'Zmove',
            ],
            'managerEmail' => [
                'title' => 'Email',
                'type' => 'string',
            ],
            'managerPhone' => [
                'title' => 'Téléphone',
                'type' => 'string',
            ],
        ];
    }

    protected function prepareVars(): void
    {
        $this->vars['managerName'] = trim((string) $this->property('managerName'));
        $this->vars['managerCompany'] = trim((string) $this->property('managerCompany'));
        $this->vars['managerEmail'] = $this->safeEmail($this->property('managerEmail'));
        $this->vars['managerPhone'] = $this->formatPhone($this->property('managerPhone'));
    }
}
