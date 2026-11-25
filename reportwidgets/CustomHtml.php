<?php

namespace Zmove\Dashboard\ReportWidgets;

use Zmove\Dashboard\Classes\WidgetBase;

/**
 * Displays raw HTML entered in the widget configuration, scripts included. The HTML is not
 * filtered: anyone allowed to configure the dashboard can run JavaScript in the backend.
 *
 * Dashboards insert widget markup through innerHTML (Vue's v-html in 4.x, AJAX partial updates
 * on the classic 3.x dashboard), which never executes <script> tags, so the widget renders them
 * inert and assets/js/custom-html.js recreates each one, exactly once, when the block appears.
 */
class CustomHtml extends WidgetBase
{
    protected $defaultAlias = 'zmoveCustomHtml';

    /**
     * Script type the browser ignores, marking the scripts left for custom-html.js to run.
     */
    public const DEFERRED_SCRIPT_TYPE = 'text/x-zmove-deferred';

    public function defineProperties()
    {
        $eyebrow = $this->titleProperty('Information');
        $eyebrow['title']['title'] = 'Surtitre';
        $eyebrow['title']['validationMessage'] = 'Le surtitre est obligatoire';

        return $eyebrow + $this->iconProperty('ph ph-megaphone') + [
            'headline' => [
                'title' => 'Titre',
                'description' => 'Optionnel',
                'type' => 'string',
            ],
            'hideHeader' => [
                'title' => 'Masquer l\'en-tête',
                'description' => 'N\'afficher que le code HTML, sans icône ni titre',
                'type' => 'checkbox',
            ],
            'html' => [
                'title' => 'Code HTML',
                'description' => 'Affiché tel quel, scripts compris',
                'type' => 'text',
                'size' => 'large',
            ],
        ];
    }

    protected function prepareVars(): void
    {
        $this->vars['html'] = static::deferScripts(trim((string) $this->property('html')));
        $this->vars['headline'] = trim((string) $this->property('headline'));
        $this->vars['showHeader'] = !filter_var($this->property('hideHeader'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * deferScripts turns every <script> tag inert by giving it the deferred type, keeping its
     * own type (module, text/javascript...) in data-zmove-type for custom-html.js to restore.
     */
    public static function deferScripts(string $html): string
    {
        $typePattern = '/\stype\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i';

        return preg_replace_callback('/<script\b([^>]*)>/i', function (array $matches) use ($typePattern): string {
            $attributes = $matches[1];
            $type = preg_match($typePattern, $attributes, $typeMatch) ? trim($typeMatch[1], '"\'') : '';
            $attributes = preg_replace($typePattern, '', $attributes);

            return '<script type="' . static::DEFERRED_SCRIPT_TYPE . '"'
                . ($type !== '' ? ' data-zmove-type="' . e($type) . '"' : '')
                . $attributes . '>';
        }, $html) ?? $html;
    }
}
