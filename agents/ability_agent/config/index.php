<?php
/**
 * Security wrapper - prevents direct access to config directory
 */
header('HTTP/1.0 403 Forbidden');
exit('Access denied');
?>

