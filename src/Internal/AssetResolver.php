<?php
declare(strict_types=1);

namespace cheinisch\MarkdownEditor\Internal;

/**
 * Löst Asset-URLs (CSS, JS, CDN-Libraries) auf.
 *
 * Priorität:
 *   1. Explizite URL-Option (css_href / js_src)
 *   2. asset_base_url + Dateiname
 *   3. Auto-Detect aus Dateisystempfad vs. DOCUMENT_ROOT
 *   4. Fallback auf /vendor/cheinisch/markdown-editor/public/
 *
 * @internal Nur für den internen Gebrauch innerhalb des Pakets.
 */
final class AssetResolver
{
    /**
     * Liefert [cssHref, jsSrc, libs].
     *
     * @param array{
     *   asset_base_url?: string,
     *   css_href?:       string,
     *   js_src?:         string,
     *   include_libs?:   bool,
     *   marked_cdn?:     string,
     *   purify_cdn?:     string
     * } $opts
     * @return array{0: string, 1: string, 2: array{include_libs: bool, marked_cdn: string, purify_cdn: string}}
     */
    public static function resolve(array $opts): array
    {
        $libs = [
            'include_libs' => array_key_exists('include_libs', $opts) ? (bool) $opts['include_libs'] : true,
            'marked_cdn'   => $opts['marked_cdn'] ?? 'https://cdn.jsdelivr.net/npm/marked/marked.min.js',
            'purify_cdn'   => $opts['purify_cdn'] ?? 'https://cdn.jsdelivr.net/npm/dompurify@3.1.7/dist/purify.min.js',
        ];

        $cssHref = $opts['css_href'] ?? null;
        $jsSrc   = $opts['js_src']   ?? null;

        if ($cssHref === null || $jsSrc === null) {
            $publicUrl = null;

            if (!empty($opts['asset_base_url'])) {
                $publicUrl = rtrim((string) $opts['asset_base_url'], '/') . '/public';
            } else {
                $publicUrl = self::detectPublicUrl();
            }

            if ($publicUrl !== null) {
                $cssHref ??= $publicUrl . '/md-editor.css';
                $jsSrc   ??= $publicUrl . '/md-editor.js';
            }
        }

        $cssHref ??= '/vendor/cheinisch/markdown-editor/public/md-editor.css';
        $jsSrc   ??= '/vendor/cheinisch/markdown-editor/public/md-editor.js';

        return [$cssHref, $jsSrc, $libs];
    }

    /**
     * Versucht aus dem Dateisystempfad des Pakets die öffentliche URL
     * zum Unterordner „public" zu berechnen.
     *
     * @return string|null  z. B. "/vendor/cheinisch/markdown-editor/public"
     */
    private static function detectPublicUrl(): ?string
    {
        // Diese Datei liegt in src/Internal/ → zwei Ebenen hoch = Paket-Root
        $packageRoot = \dirname(\dirname(__DIR__));
        $publicDir   = $packageRoot . DIRECTORY_SEPARATOR . 'public';
        $publicPath  = str_replace('\\', '/', $publicDir);
        $docRoot     = str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));

        if ($docRoot !== '' && str_starts_with($publicPath, rtrim($docRoot, '/'))) {
            $relative = substr($publicPath, strlen(rtrim($docRoot, '/')));

            return ($relative === '' || $relative[0] !== '/') ? '/' . $relative : $relative;
        }

        return null;
    }
}