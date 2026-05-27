<?php
declare(strict_types=1);

namespace cheinisch\MarkdownEditor\Internal;

/**
 * Verwaltet die Button-Konfiguration der Formatbar.
 *
 * @internal Nur für den internen Gebrauch innerhalb des Pakets.
 */
final class ButtonResolver
{
    /**
     * Alle bekannten Button-Namen in Anzeigereihenfolge.
     *
     * Nicht enthalten: Headline-Dropdown und Vorschau-Toggle —
     * diese sind immer sichtbar und nicht konfigurierbar.
     */
    public const KNOWN = [
        'bold', 'underline', 'italic',
        'list', 'quote', 'code', 'table',
        'link', 'image',
    ];

    /**
     * Erzeugt eine vollständige Button-Map (name => bool).
     *
     * @param  list<string>|array<string, bool>|null $userButtons
     *         null  → alle Buttons anzeigen (Key wurde nicht übergeben)
     *         array → nur explizit gelistete anzeigen (Whitelist)
     *
     * Unterstützte Array-Formate:
     *   ['bold', 'italic', 'link']           – einfache Liste
     *   ['bold' => true, 'italic' => false]  – Key-Bool-Map
     *   Mischformen sind erlaubt.
     *
     * @return array<string, bool>
     */
    public static function resolve(?array $userButtons): array
    {
        // Kein buttons-Key → alle anzeigen
        if ($userButtons === null) {
            return array_fill_keys(self::KNOWN, true);
        }

        // Whitelist: alle auf false, nur gelistete aktivieren
        $resolved = array_fill_keys(self::KNOWN, false);

        foreach ($userButtons as $key => $value) {
            if (is_int($key) && is_string($value)) {
                // Format: ['bold', 'italic', …]
                if (array_key_exists($value, $resolved)) {
                    $resolved[$value] = true;
                }
            } elseif (is_string($key) && array_key_exists($key, $resolved)) {
                // Format: ['bold' => true, 'image' => false, …]
                $resolved[$key] = (bool) $value;
            }
        }

        return $resolved;
    }
}