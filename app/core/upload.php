<?php
declare(strict_types=1);

function handle_upload(array $file, string $subdir): array
{
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        $msg = match ($file['error'] ?? -1) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File exceeds maximum allowed size.',
            UPLOAD_ERR_PARTIAL  => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE  => 'No file was uploaded.',
            default             => 'Upload error code ' . ($file['error'] ?? 'unknown') . '.',
        };
        return ['ok' => false, 'path' => null, 'error' => $msg];
    }

    if ($file['size'] > MAX_UPLOAD_BYTES) {
        return ['ok' => false, 'path' => null, 'error' => 'File exceeds the 5 MB limit.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    if (!in_array($mime, ALLOWED_IMAGE_MIME, true)) {
        return ['ok' => false, 'path' => null, 'error' => 'Only JPEG, PNG, and WebP images are allowed.'];
    }

    if (getimagesize($file['tmp_name']) === false) {
        return ['ok' => false, 'path' => null, 'error' => 'File does not appear to be a valid image.'];
    }

    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default      => 'bin',
    };

    $filename  = bin2hex(random_bytes(16)) . '.' . $ext;
    $targetDir = UPLOAD_DIR . '/' . $subdir;

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0775, true);
    }

    $targetPath = $targetDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['ok' => false, 'path' => null, 'error' => 'Failed to save the uploaded file.'];
    }

    return ['ok' => true, 'path' => $subdir . '/' . $filename, 'error' => null];
}

function upload_url(string $rel): string
{
    return UPLOAD_URL . '/' . $rel;
}

function photo_url(?string $rel, string $fallback = '/img/placeholder-pet.svg'): string
{
    $rel = trim((string)$rel);

    if ($rel !== '' && !str_contains($rel, '..') && is_file(UPLOAD_DIR . '/' . $rel)) {
        return upload_url($rel);
    }

    return asset($fallback);
}

function delete_upload(string $rel): void
{
    $path = UPLOAD_DIR . '/' . ltrim($rel, '/');
    if (is_file($path)) {
        unlink($path);
    }
}
