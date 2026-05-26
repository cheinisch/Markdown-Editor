<?php
declare(strict_types=1);

namespace cheinisch\MarkdownEditor\Composer;

use Composer\Script\Event;

final class ScriptHandler
{
    public static function publishAssets(Event $event): void
    {
        $composer = $event->getComposer();
        $io       = $event->getIO();

        // Zielverzeichnis aus composer.json des Projekts lesen
        $extra     = $composer->getPackage()->getExtra();
        $targetDir = $extra['markdown-editor']['public-dir']
            ?? 'public/vendor/markdown-editor';

        $vendorDir  = $composer->getConfig()->get('vendor-dir');
        $sourceDir  = $vendorDir . '/cheinisch/markdown-editor/public';
        $projectDir = dirname($vendorDir);
        $destDir    = $projectDir . '/' . ltrim($targetDir, '/');

        if (!is_dir($destDir) && !mkdir($destDir, 0755, true)) {
            $io->writeError("  <error>Konnte Verzeichnis nicht erstellen: $destDir</error>");
            return;
        }

        foreach (['md-editor.css', 'md-editor.js'] as $file) {
            $src  = $sourceDir . '/' . $file;
            $dest = $destDir   . '/' . $file;

            if (!is_file($src)) {
                $io->writeError("  <warning>Quelldatei nicht gefunden: $src</warning>");
                continue;
            }

            copy($src, $dest);
            $io->write("  <info>Asset veröffentlicht:</info> $targetDir/$file");
        }
    }
}