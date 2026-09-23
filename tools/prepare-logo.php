<?php

$srcPath = $argv[1] ?? null;
$outDir = $argv[2] ?? __DIR__.'/../public/images';

if (! $srcPath || ! is_file($srcPath)) {
    fwrite(STDERR, "Source logo not found\n");
    exit(1);
}

if (! is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

$src = imagecreatefrompng($srcPath);
if (! $src) {
    fwrite(STDERR, "Could not read PNG\n");
    exit(1);
}

$w = imagesx($src);
$h = imagesy($src);
echo "source {$w}x{$h}\n";

$tmp = imagecreatetruecolor($w, $h);
imagealphablending($tmp, false);
imagesavealpha($tmp, true);
$transparent = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
imagefilledrectangle($tmp, 0, 0, $w - 1, $h - 1, $transparent);

$minX = $w;
$minY = $h;
$maxX = 0;
$maxY = 0;

for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $rgb = imagecolorat($src, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        $luma = (0.299 * $r) + (0.587 * $g) + (0.114 * $b);
        $nearWhite = $r > 248 && $g > 248 && $b > 248 && $luma > 248;
        if ($nearWhite) {
            continue;
        }
        $a = 0;
        if ($r > 230 && $g > 230 && $b > 230) {
            $a = (int) min(127, round((($r + $g + $b) / 3 - 230) / 25 * 127));
        }
        $col = imagecolorallocatealpha($tmp, $r, $g, $b, $a);
        imagesetpixel($tmp, $x, $y, $col);
        $minX = min($minX, $x);
        $minY = min($minY, $y);
        $maxX = max($maxX, $x);
        $maxY = max($maxY, $y);
    }
}

imagedestroy($src);

$pad = 12;
$minX = max(0, $minX - $pad);
$minY = max(0, $minY - $pad);
$maxX = min($w - 1, $maxX + $pad);
$maxY = min($h - 1, $maxY + $pad);
$cw = $maxX - $minX + 1;
$ch = $maxY - $minY + 1;
echo "trim {$cw}x{$ch} from {$minX},{$minY}\n";

$logo = imagecreatetruecolor($cw, $ch);
imagealphablending($logo, false);
imagesavealpha($logo, true);
imagefill($logo, 0, 0, $transparent);
imagecopy($logo, $tmp, 0, 0, $minX, $minY, $cw, $ch);

$logoPath = rtrim($outDir, '/\\').DIRECTORY_SEPARATOR.'wow-sapi-logo.png';
imagepng($logo, $logoPath, 6);
echo "wrote {$logoPath} ".filesize($logoPath)." bytes\n";

// Cow face sits in the "O" of WOW: left-center, above the tagline.
$cowX = (int) round($cw * 0.20);
$cowY = 0;
$cowSize = (int) round($ch * 0.78);
$cowX = min($cowX, $cw - $cowSize);
$cowY = min($cowY, $ch - $cowSize);

$mark = imagecreatetruecolor($cowSize, $cowSize);
imagealphablending($mark, false);
imagesavealpha($mark, true);
imagefill($mark, 0, 0, $transparent);
imagecopy($mark, $logo, 0, 0, $cowX, $cowY, $cowSize, $cowSize);

$markPath = rtrim($outDir, '/\\').DIRECTORY_SEPARATOR.'wow-sapi-mark.png';
imagepng($mark, $markPath, 6);
echo "wrote {$markPath} cow crop {$cowSize}x{$cowSize} at {$cowX},{$cowY}\n";

$favicon = imagecreatetruecolor(32, 32);
imagealphablending($favicon, false);
imagesavealpha($favicon, true);
imagefill($favicon, 0, 0, $transparent);
imagecopyresampled($favicon, $mark, 0, 0, 0, 0, 32, 32, $cowSize, $cowSize);
$favPath = rtrim($outDir, '/\\').DIRECTORY_SEPARATOR.'favicon.png';
$publicFav = dirname($outDir).DIRECTORY_SEPARATOR.'favicon.ico';
imagepng($favicon, $favPath, 6);

// Simple ICO (PNG-in-ICO works in modern browsers; also copy PNG as favicon.ico fallback via PNG 32).
copy($favPath, dirname($outDir).DIRECTORY_SEPARATOR.'favicon.png');
echo "wrote favicon png\n";

imagedestroy($tmp);
imagedestroy($logo);
imagedestroy($mark);
imagedestroy($favicon);
