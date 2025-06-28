<?php
function imageToWebp(string $src, string $dest, int $quality = 80): bool {
    $info = getimagesize($src);
    if (!$info) {
        return false;
    }
    switch ($info['mime']) {
        case 'image/jpeg':
            $img = imagecreatefromjpeg($src);
            break;
        case 'image/png':
            $img = imagecreatefrompng($src);
            break;
        case 'image/gif':
            $img = imagecreatefromgif($src);
            break;
        case 'image/webp':
            $img = imagecreatefromwebp($src);
            break;
        default:
            return false;
    }
    if (!$img) {
        return false;
    }
    imagepalettetotruecolor($img);
    $result = imagewebp($img, $dest, $quality);
    imagedestroy($img);
    return $result;
}

function createWebpThumbnail(string $src, string $dest, int $maxWidth = 300, int $maxHeight = 300, int $quality = 40): bool {
    $info = getimagesize($src);
    if (!$info) {
        return false;
    }
    switch ($info['mime']) {
        case 'image/jpeg':
            $img = imagecreatefromjpeg($src);
            break;
        case 'image/png':
            $img = imagecreatefrompng($src);
            break;
        case 'image/gif':
            $img = imagecreatefromgif($src);
            break;
        case 'image/webp':
            $img = imagecreatefromwebp($src);
            break;
        default:
            return false;
    }
    if (!$img) {
        return false;
    }
    $width  = imagesx($img);
    $height = imagesy($img);
    $scale  = min($maxWidth / $width, $maxHeight / $height, 1);
    $newW   = (int)($width * $scale);
    $newH   = (int)($height * $scale);
    $tmp    = imagecreatetruecolor($newW, $newH);
    imagealphablending($tmp, false);
    imagesavealpha($tmp, true);
    imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newW, $newH, $width, $height);
    $result = imagewebp($tmp, $dest, $quality);
    imagedestroy($img);
    imagedestroy($tmp);
    return $result;
}
?>
