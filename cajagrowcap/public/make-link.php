<?php
chdir(__DIR__ . '/..'); // sube a la carpeta del proyecto
$target = 'storage/app/public';
$link   = 'public/storage';
if (file_exists($link)) {
    unlink($link);
}
$result = @symlink($target, $link);
var_dump($result, $target, $link, is_link($link) ? readlink($link) : null);
