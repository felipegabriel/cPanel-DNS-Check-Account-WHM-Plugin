<?php

function resolve_domain($domain) {
    $dns = '8.8.8.8';  // Google Public DNS
    if (rand(0, 1) == 1) {
        $dns = '208.67.222.222'; // Open DNS
    }
    $ip = `nslookup $domain $dns`; // the backticks execute the command in the shell
    $ips = array();
    if (preg_match_all('/Address: ((?:\d{1,3}\.){3}\d{1,3})/', $ip, $match) > 0) {
        $ips = $match[1];
    }
    return $ips;
}

function open_file_per_line($file) {
    $handle = fopen($file, "r");
    if ($handle) {
        $lines = array();
        while (($line = fgets($handle)) !== false) {
            $lines[] = trim($line);
        }
        return $lines;
        fclose($handle);
    } else {
        return false;
    }
}

function check_valid_resolve_ip($ip, $domain) {
    if ($domain == '_SERVER_HOSTNAME_') {
        return array('label' => 'info', 'msg' => '');
    }
    if (filter_var($ip, FILTER_VALIDATE_IP) == false) {
        return array('label' => 'danger', 'msg' => 'Invalid IP');
    }
    $domain_local_ip = get_domain_ip_local_file($domain);
    if ($domain_local_ip['ip'] != $ip) {
        return array('label' => 'danger', 'msg' => 'Different IP');
    }
    return array('label' => 'success', 'msg' => '');
}

function get_domain_ip_local_file($domain) {
    $file_lines = open_file_per_line('/etc/userdatadomains');
    $file_ip_nat_lines = open_file_per_line('/var/cpanel/cpnat');
    foreach ($file_lines as $line) {
        $explode = explode('==', $line);
        $explode_two = explode(':', $explode[0]);
        if (trim($explode_two[0]) == trim($domain)) {
            $ip_port = $explode[5];
            $explode_ip = explode(':', $ip_port);
            foreach ($file_ip_nat_lines as $line_ip_nat) {
                $explode_ip_nat = explode(' ', $line_ip_nat);
                if ($explode_ip_nat[0] == $explode_ip[0]) {
                    $explode_ip[0] = $explode_ip_nat[1];
                }
            }
            return array('ip' => $explode_ip[0], 'acc' => trim($explode_two[1]), 'reseller' => trim($explode[1]), 'type' => trim($explode[2]));
        }
    }
}

$all_domains_local = open_file_per_line('/etc/localdomains');
$hostname = gethostname();
?>
<html>
    <head>
        <title>DNS Check Account</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootswatch/3.3.7/cosmo/bootstrap.min.css">
    </head>
    <body>
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <h1>cPanel DNS Check Account WHM Plugin</h1>
                </div>
            </div>
        </div>
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <table class="table">
                        <thead>
                            <tr>
                                <td>User</td>
                                <td>Reseller User</td>
                                <td>Domain</td>
                                <td>Local IP</td>
                                <td></td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            foreach ($all_domains_local as $domain) {
                                $domain_local_acc = get_domain_ip_local_file($domain);
                                $resolve_ips = resolve_domain($domain);
                                $ips_ = '';
                                foreach ($resolve_ips as $ip) {
                                    if ($domain == $hostname) {
                                        $domain = '_SERVER_HOSTNAME_';
                                    }
                                    $check = check_valid_resolve_ip($ip, $domain);
                                    $ips_ .= '<span class="label label-' . $check['label'] . '">' . $ip . '</span> ' . $check['msg'] . '<br><br>';
                                }
                                $ips = rtrim($ips_, '<br>');
                                $ip_result_html = $ips != '' ? $ips : '<span class="label label-danger">Not Resolve</span>';
                                if ($domain == '_SERVER_HOSTNAME_') {
                                    $domain = $hostname;
                                    $domain_local_acc['acc'] = 'root';
                                }
                                ?>
                                <tr>
                                    <td><?= $domain_local_acc['acc'] ?></td>
                                    <td><?= $domain_local_acc['reseller'] ?></td>
                                    <td>(<?= $domain_local_acc['type'] ?>) <?= $domain ?></td>
                                    <td><?= $domain_local_acc['ip'] ?></td>
                                    <td><?= $ip_result_html ?><br></td>
                                </tr>
                                <?php
                                $i++;
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </body>
</html>
