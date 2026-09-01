<?php
/**
 * UploadService — secure image upload handling (spec section 20).
 *
 * Validates MIME type (via finfo, not just the client-supplied Content-Type),
 * extension, file size, and that the file is actually a valid image
 * (getimagesize, which also rejects disguised PHP/executable files).
 * Generates a random, safe filename — never trusts the client filename.
 */

/**
 * @param array  $file   a single item from $_FILES
 * @param string $destDir absolute directory to save into (created if missing)
 * @return array{success:bool, path?:string, error?:string} $path is web-relative e.g. "images/rooms/xxxx.jpg"
 */
function handleSecureImageUpload(array $file, string $destDir, string $webPathPrefix) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload failed. Please try again.'];
    }
    if ($file['size'] <= 0 || $file['size'] > UPLOAD_MAX_BYTES) {
        return ['success' => false, 'error' => 'Image must be smaller than 5MB.'];
    }

    $allowedExt = unserialize(UPLOAD_ALLOWED_EXT);
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        return ['success' => false, 'error' => 'Only JPG, JPEG, PNG and WEBP images are allowed.'];
    }

    // Verify actual MIME type from file content, not the client-supplied header
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $actualMime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $allowedMime = unserialize(UPLOAD_ALLOWED_MIME);
    if (!in_array($actualMime, $allowedMime, true)) {
        return ['success' => false, 'error' => 'The uploaded file is not a valid image.'];
    }

    // getimagesize() fails on non-image files (including PHP scripts renamed
    // with an image extension), giving us a second, content-based check.
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['success' => false, 'error' => 'The uploaded file is not a valid image.'];
    }
    [$width, $height] = $imageInfo;
    if ($width < 50 || $height < 50 || $width > 8000 || $height > 8000) {
        return ['success' => false, 'error' => 'Image dimensions must be between 50x50 and 8000x8000 pixels.'];
    }

    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }

    // Random filename — never derived from user input, so it can't be used
    // for path traversal or to overwrite another file.
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $destPath = rtrim($destDir, '/') . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['success' => false, 'error' => 'Could not save the uploaded image.'];
    }
    // Ensure the saved file is never executable
    chmod($destPath, 0644);

    return ['success' => true, 'path' => rtrim($webPathPrefix, '/') . '/' . $filename];
}
