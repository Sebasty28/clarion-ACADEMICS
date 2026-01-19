<?php
// core/supabase_storage.php
// Server-side only: uses SUPABASE_SERVICE_ROLE_KEY. Do NOT expose this in JS.

function sb_env(string $key, ?string $default = null): ?string {
    $v = getenv($key);
    if ($v === false || $v === '') return $default;
    return $v;
}

function sb_enabled(): bool {
    return (bool)(sb_env('SUPABASE_URL') && sb_env('SUPABASE_SERVICE_ROLE_KEY'));
}

function sb_bucket_pdf(): string {
    return sb_env('SUPABASE_PDF_BUCKET', 'pdf') ?: 'pdf';
}

function sb_bucket_thumbs(): string {
    return sb_env('SUPABASE_THUMB_BUCKET', 'thumbs') ?: 'thumbs';
}

function sb_normalize_url(string $url): string {
    return rtrim($url, "/ \t\n\r\0\x0B");
}

function sb_public_url(string $bucket, string $path): string {
    $base = sb_normalize_url(sb_env('SUPABASE_URL', '') ?? '');
    return $base . "/storage/v1/object/public/" . rawurlencode($bucket) . "/" . str_replace('%2F', '/', rawurlencode($path));
}

/**
 * Uploads a local file to Supabase Storage (public bucket).
 * Returns [true, publicUrl, null] or [false, null, errorMessage]
 */
function sb_upload_public(string $bucket, string $pathInBucket, string $localFilePath, string $contentType): array {
    $base = sb_env('SUPABASE_URL');
    $key  = sb_env('SUPABASE_SERVICE_ROLE_KEY');

    if (!$base || !$key) {
        return [false, null, "Supabase env vars missing (SUPABASE_URL / SUPABASE_SERVICE_ROLE_KEY)."];
    }

    if (!is_file($localFilePath)) {
        return [false, null, "Local file missing: " . $localFilePath];
    }

    $base = sb_normalize_url($base);

    // Upsert so same path overwrites
    $objectUrl = $base . "/storage/v1/object/" . rawurlencode($bucket) . "/" .
        str_replace('%2F', '/', rawurlencode($pathInBucket)) . "?upsert=true";

    $ch = curl_init($objectUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

    $fh = fopen($localFilePath, 'rb');
    curl_setopt($ch, CURLOPT_UPLOAD, true);
    curl_setopt($ch, CURLOPT_INFILE, $fh);
    curl_setopt($ch, CURLOPT_INFILESIZE, filesize($localFilePath));

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $key,
        "apikey: " . $key,
        "Content-Type: " . $contentType,
        "x-upsert: true",
    ]);

    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);
    if (is_resource($fh)) fclose($fh);

    if ($resp === false) {
        return [false, null, "Supabase upload failed: " . $err];
    }

    if ($code < 200 || $code >= 300) {
        return [false, null, "Supabase upload failed (HTTP $code): " . $resp];
    }

    return [true, sb_public_url($bucket, $pathInBucket), null];
}
