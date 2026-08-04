<?php
/**
 * One-off script: strip all comments from PHP/JS/CSS files.
 * Delete after running.
 */

$root = __DIR__;
$phpFiles = [];
$jsCssFiles = [];

$di = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);
foreach ($di as $file) {
    if (!$file->isFile()) continue;
    $path = $file->getRealPath();
    $rel  = substr($path, strlen($root) + 1);

    // Skip vendor / lib / git / this script
    if (preg_match('#(^|/)(vendor|node_modules|lib/fpdf|\.git|\.htaccess)#', $rel)) continue;
    if ($path === __FILE__) continue;

    $ext = strtolower($file->getExtension());
    if ($ext === 'php') {
        $phpFiles[] = $path;
    } elseif (in_array($ext, ['js', 'css'], true)) {
        $jsCssFiles[] = $path;
    }
}

// ── PHP: use tokenizer ─────────────────────────────────────────────
foreach ($phpFiles as $path) {
    $src = file_get_contents($path);
    if ($src === false) continue;

    $tokens   = token_get_all($src);
    $out      = '';
    $changed  = false;
    $prevSignificant = '';

    foreach ($tokens as $tok) {
        if (is_array($tok)) {
            [$id, $text] = $tok;
            if ($id === T_COMMENT || $id === T_DOC_COMMENT) {
                $changed = true;
                // Preserve newline that may have been part of the comment line
                if (substr($text, -1) === "\n") {
                    $out .= "\n";
                }
                continue;
            }
            $out .= $text;
        } else {
            $out .= $tok;
        }
    }

    if ($changed) {
        // Clean up: collapse 3+ blank lines down to 2
        $out = preg_replace('/\n{3,}/', "\n\n", $out);
        file_put_contents($path, $out);
        echo "PHP: $path\n";
    }
}

// ── JS / CSS: regex-based comment removal ──────────────────────────
foreach ($jsCssFiles as $path) {
    $src = file_get_contents($path);
    if ($src === false) continue;

    // Remove /* ... */ block comments
    $noBlock = preg_replace('#/\*.*?\*/#s', '', $src);
    // Remove // line comments (JS only; harmless on CSS since // rarely appears)
    $noLine  = preg_replace('#^\s*//.*$#m', '', $noBlock);
    // Collapse 3+ blank lines
    $clean   = preg_replace('/\n{3,}/', "\n\n", $noLine);

    if ($clean !== $src) {
        file_put_contents($path, $clean);
        echo "JS/CSS: $path\n";
    }
}

echo "Done.\n";