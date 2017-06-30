<?php

/**
 *      30 2 * * * /usr/local/cpanel/3rdparty/bin/php -q /usr/local/cpanel/whostmgr/docroot/cgi/cpanel-account-dns-check/cron.php
 */
$hostname = gethostname();
ob_start();
include('dns-check.php');
$value = ob_get_contents();
ob_end_clean();
$to = 'root@' . $hostname;
$subject = $hostname . ' - DNS Check Account WHM Plugin';
$message = $value;
$headers = 'From: root@' . $hostname . "\r\n" .
        'X-Mailer: PHP/' . phpversion() . "\r\n" .
        'Content-Type: text/html; charset=ISO-8859-1\r\n';

mail($to, $subject, $message, $headers);
