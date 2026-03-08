<?php
declare(strict_types=1);

$config = json_decode(file_get_contents(__DIR__ . '/config.json'), true);
if (!is_array($config)) {
    $config = [];
}
