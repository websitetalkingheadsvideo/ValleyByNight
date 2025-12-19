<?php
/**
 * Security wrapper - prevents direct access to tests directory
 */
header('HTTP/1.0 403 Forbidden');
exit('Access denied');
?>

