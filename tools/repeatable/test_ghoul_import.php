<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$_SERVER['REQUEST_URI'] = '/test';
$argv = ['test_ghoul_import.php', 'reference/Characters/Ghouls/Jennifer Torrance.json'];

require_once __DIR__ . '/import_ghouls.php';
?>

