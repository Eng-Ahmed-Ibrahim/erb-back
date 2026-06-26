<?php
// phpinfo();

$status = opcache_get_status();
echo '<pre>';
print_r($status);
echo '</pre>';
?>

