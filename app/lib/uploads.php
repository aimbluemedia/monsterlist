<?php
// Image uploads: validate → resize with GD → save as JPEG under the webroot's uploads/.
// Shared-hosting safe: no exec, no external tools.

const UPLOAD_MAX_BYTES = 5 * 1024 * 1024;

function uploads_dir(): string
{
    return WEB_ROOT . '/uploads';
}

/**
 * Process one uploaded image from $_FILES[$field] (single or index into a multiple upload).
 * Returns the public web path (/uploads/…) or null; appends problems to $errors.
 */
function save_image(array $file, int $bizId, string $kind, int $maxDim, array &$errors): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK)        { $errors[] = 'Upload failed (' . $file['name'] . ').'; return null; }
    if ($file['size'] > UPLOAD_MAX_BYTES)        { $errors[] = $file['name'] . ' is over 5 MB.'; return null; }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ok   = ['image/jpeg' => 'jpeg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($ok[$mime]))                      { $errors[] = $file['name'] . ': only JPG, PNG, WebP or GIF.'; return null; }

    $create = 'imagecreatefrom' . $ok[$mime];
    $img = @$create($file['tmp_name']);
    if (!$img)                                   { $errors[] = $file['name'] . ' could not be read as an image.'; return null; }

    // resize to fit maxDim, preserving aspect
    $w = imagesx($img); $h = imagesy($img);
    if (max($w, $h) > $maxDim) {
        $scale = $maxDim / max($w, $h);
        $nw = (int)round($w * $scale); $nh = (int)round($h * $scale);
        $dst = imagecreatetruecolor($nw, $nh);
        // white background (JPEG has no alpha)
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($img);
        $img = $dst;
    }

    $dir = uploads_dir() . '/biz' . $bizId;
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) { $errors[] = 'Could not create the upload folder.'; imagedestroy($img); return null; }

    $name = $kind . '-' . bin2hex(random_bytes(6)) . '.jpg';
    if (!imagejpeg($img, "$dir/$name", 84))       { $errors[] = 'Could not save ' . $file['name'] . '.'; imagedestroy($img); return null; }
    imagedestroy($img);
    return "/uploads/biz$bizId/$name";
}

/** Normalize $_FILES for input[multiple] into a list of single-file arrays. */
function files_list(string $field): array
{
    if (empty($_FILES[$field]['name'])) return [];
    $f = $_FILES[$field];
    if (!is_array($f['name'])) return [$f];
    $out = [];
    foreach ($f['name'] as $i => $n) {
        if ($n === '') continue;
        $out[] = ['name' => $n, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i],
                  'error' => $f['error'][$i], 'size' => $f['size'][$i]];
    }
    return $out;
}

/** Delete a stored upload if it lives under /uploads (ignores external URLs). */
function delete_upload(?string $webPath): void
{
    if (!$webPath || !str_starts_with($webPath, '/uploads/')) return;
    $real = realpath(WEB_ROOT . $webPath);
    if ($real && str_starts_with($real, uploads_dir())) @unlink($real);
}
