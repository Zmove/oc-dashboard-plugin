<?php

namespace Zmove\Dashboard\Classes;

use Backend\Classes\ReportWidgetBase;
use Config;
use Exception;

/**
 * Base class shared by all Zmove dashboard report widgets, compatible with October CMS 3.x and 4.x.
 *
 * It extends Backend\Classes\ReportWidgetBase, the only report widget base class in 3.x, which 4.x
 * keeps as a deprecated subclass of Dashboard\Classes\ReportWidgetBase.
 *
 * Optional properties must not declare 'default' => ''. The inspector drops any value equal
 * to its default before the dashboard merges the result back into the saved configuration,
 * so a field emptied by the user would silently keep its previous value. Remove this
 * workaround once October deletes the dropped keys (report-widget.js, showInspector).
 */
abstract class WidgetBase extends ReportWidgetBase
{
    /**
     * Name of the partial rendered by this widget.
     */
    protected $widgetPartial = 'widget';

    public function render()
    {
        try {
            $this->prepareVars();
        }
        catch (Exception $ex) {
            $this->vars = ['error' => $ex->getMessage()];
        }

        return $this->makePartial($this->widgetPartial);
    }

    protected function prepareVars(): void
    {
    }

    /**
     * usesLegacyDashboard tells whether the widget is shown by the classic ReportContainer
     * dashboard, the default up to October CMS 3.x (the Vue dashboard being an opt-in beta there,
     * through the backend.vue_dashboard setting). Its jQuery inspector lacks some property types,
     * such as the media finder, and it renders widgets without a card around them.
     */
    public function usesLegacyDashboard(): bool
    {
        return !class_exists(\Dashboard\Classes\ReportWidgetBase::class)
            && !Config::get('backend.vue_dashboard', false);
    }

    /**
     * rootClass returns the CSS classes of the widget root, borrowing the classic dashboard's own
     * "report-widget" card on that dashboard.
     */
    public function rootClass(): string
    {
        return $this->usesLegacyDashboard() ? 'zm-dash zm-dash--legacy report-widget' : 'zm-dash';
    }

    /**
     * Renders the stylesheet shared by every Zmove dashboard widget. Static report widget
     * markup is injected through AJAX, so regular asset registration is not applied.
     */
    public function styles(): string
    {
        return $this->makePartial('$/zmove/dashboard/partials/_styles.htm');
    }

    /**
     * Returns the URL only when it uses a browser safe scheme, preventing
     * javascript: or data: payloads from being injected through the inspector.
     */
    protected function safeUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        return filter_var($url, FILTER_VALIDATE_URL) ?: null;
    }

    protected function safeEmail(?string $email): ?string
    {
        $email = trim((string) $email);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ?: null;
    }

    /**
     * Common title property shared by every widget. The dashboard renders it above the widget body.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function titleProperty(string $default): array
    {
        return [
            'title' => [
                'title' => 'Titre',
                'default' => $default,
                'type' => 'string',
                'validationPattern' => '^.+$',
                'validationMessage' => 'Le titre est obligatoire',
            ],
        ];
    }

    /**
     * widgetTitle returns the configured title, used as the header eyebrow so it lines up
     * with the native dashboard "indicator" widgets (icon + title + value).
     */
    protected function widgetTitle(): string
    {
        return trim((string) $this->property('title'));
    }

    /**
     * iconOptions lists the Phosphor icon classes offered by the picker, curated for the
     * scenarios covered by this plugin's widgets.
     *
     * @return array<string, string>
     */
    protected static function iconOptions(): array
    {
        return [
            'ph ph-user' => 'Utilisateur',
            'ph ph-users' => 'Utilisateurs',
            'ph ph-headset' => 'Casque support',
            'ph ph-chat-circle-text' => 'Discussion',
            'ph ph-globe' => 'Globe',
            'ph ph-calendar' => 'Calendrier',
            'ph ph-chart-line-up' => 'Graphique (hausse)',
            'ph ph-gauge' => 'Jauge',
            'ph ph-browser' => 'Navigateur',
            'ph ph-app-window' => 'Fenêtre',
            'ph ph-chart-bar' => 'Statistiques',
            'ph ph-shield-check' => 'Sécurité',
            'ph ph-megaphone' => 'Annonce',
            'ph ph-star' => 'Étoile',
        ];
    }

    /**
     * iconProperty defines the icon picker property, matching the dropdown + "useValuesAsIcons"
     * pattern used by October's own indicator widget so the picker UI is identical.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function iconProperty(string $default): array
    {
        return [
            'icon' => [
                'title' => 'Icône',
                'type' => 'dropdown',
                'default' => $default,
                'options' => static::iconOptions(),
                'useValuesAsIcons' => true,
                'validation' => [
                    'required' => [
                        'message' => 'Veuillez choisir une icône',
                    ],
                ],
            ],
        ];
    }

    /**
     * badgeIcon renders the configured Phosphor icon, sized to match October's own
     * indicator widget icon.
     */
    protected function badgeIcon(string $default): string
    {
        $iconClass = trim((string) $this->property('icon', $default)) ?: $default;

        return '<i class="' . e($iconClass) . '"></i>';
    }

    /**
     * icon renders one of the inline pictograms used for inline contact details, all using
     * currentColor so they follow the light/dark backend theme automatically.
     */
    protected function icon(string $name): string
    {
        $icons = [
            'email' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 6 8 7 8-7"/>',
            'phone' => '<path d="M6 3h3l1.5 4.5L8.5 9a10 10 0 0 0 6.5 6.5l1.5-2L21 15v3a2 2 0 0 1-2 2C11.5 20 4 12.5 4 5a2 2 0 0 1 2-2Z"/>',
            'building' => '<path d="M5 21V5a1 1 0 0 1 1-1h7a1 1 0 0 1 1 1v16"/><path d="M14 9h4a1 1 0 0 1 1 1v11"/>'
                . '<path d="M9 8h.01M9 12h.01M9 16h.01"/><path d="M3 21h18"/>',
            'external' => '<path d="M7 17 17 7"/><path d="M8 7h9v9"/>',
        ];

        $body = $icons[$name] ?? $icons['email'];

        return '<svg class="zm-dash-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
            . 'stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
            . $body . '</svg>';
    }

    /**
     * formatPhone spaces out a French 10-digit number by pairs ("0381679080" becomes
     * "03 81 67 90 80") so every number on the dashboard reads the same way, and leaves any
     * other format (international, extensions...) as entered.
     */
    protected function formatPhone(?string $phone): string
    {
        $phone = trim((string) $phone);
        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) === 10 && str_starts_with($digits, '0') && !str_contains($phone, '+')) {
            return implode(' ', str_split($digits, 2));
        }

        return $phone;
    }

    protected function phoneHref(string $phone): string
    {
        return 'tel:' . preg_replace('/[^0-9+]/', '', $phone);
    }
}
