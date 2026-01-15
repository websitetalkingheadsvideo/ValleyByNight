<?php
require_once __DIR__ . '/../includes/connect.php';

$names = ['Travis Adelson', 'Victoria Sterling', 'Dorikhan Caine', 'Evan Mercer'];

foreach($names as $name) {
    $result = db_fetch_one($conn, 'SELECT id, character_name FROM characters WHERE character_name = ?', 's', [$name]);
    if($result) {
        echo $result['character_name'] . ' - ID: ' . $result['id'] . PHP_EOL;
    } else {
        echo $name . ' - NOT FOUND' . PHP_EOL;
    }
}
