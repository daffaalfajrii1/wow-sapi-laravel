<?php

$src = __DIR__.'/../public/images/wow-sapi-logo.png';
$im = imagecreatefrompng($src);
imagealphablending($im, false);
imagesavealpha($im, true);

$size = 310;
$x = 305;
$y = 0;

$mark = imagecreatetruecolor($size, $size);
imagealphablending($mark, false);
imagesavealpha($mark, true);
$t = imagecolorallocatealpha($mark, 0, 0, 0, 127);
imagefill($mark, 0, 0, $t);
imagecopy($mark, $im, 0, 0, $x, $y, $size, $size);
imagepng($mark, __DIR__.'/../public/images/wow-sapi-mark.png', 6);

$fav = imagecreatetruecolor(32, 32);
imagealphablending($fav, false);
imagesavealpha($fav, true);
imagefill($fav, 0, 0, $t);
imagecopyresampled($fav, $mark, 0, 0, 0, 0, 32, 32, $size, $size);
imagepng($fav, __DIR__.'/../public/images/favicon.png', 6);
copy(__DIR__.'/../public/images/favicon.png', __DIR__.'/../public/favicon.png');

echo "mark recropped\n";
