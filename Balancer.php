<?php

if(function_exists("posix_getuid") and posix_getuid() != 0)
{
    echo "Please run as root\n";
    exit(1);
}


exec("/sbin/sysctl net.ipv4.ip_forward=1 ; /sbin/iptables --new POCKETMINELB ; /sbin/iptables --insert INPUT --proto udp --match state --state NEW --dport 19132 -j POCKETMINELB ; /sbin/iptables --insert POCKETMINELB --jump LOG --log-prefix=\"MCPE_NEW_CONNECTION \" ; /sbin/iptables -t nat -A POSTROUTING -j MASQUERADE");

$handle = popen('/usr/bin/tail -f /var/log/kern.log', 'r');

$isEstablished = array();

$Servers_Pool = array(
    "192.168.0.5:19133",
    "192.168.0.5:19134",
);

while(true)
{
    $string = fgets($handle);

    if(strpos($string, 'MCPE_NEW_CONNECTION') !== false)
    {
        preg_match_all("/SRC=.+?\..+?\..+?\..+?/", $string, $output);
        $SOURCE_IP = str_replace("SRC=", '', $output[0][0]);
        if(!isset($isEstablished[$SOURCE_IP]))
        {
            $RAND_SERVER = $Servers_Pool[array_rand($Servers_Pool)];
            //$RAND_IP = explode(':', $Servers_Pool[array_rand($Servers_Pool)])[0];
            //$RAND_PORT = explode(':', $Servers_Pool[array_rand($Servers_Pool)])[1];

            exec("/sbin/iptables -t nat -A PREROUTING --src $SOURCE_IP --proto udp --dport 19132 -j DNAT --to-destination $RAND_SERVER");
            $isEstablished[$SOURCE_IP] = true;
            //Make PocketMine API call to unset this value when user disconnects.

            echo "NEW CONN SOURCE IP: $SOURCE_IP REDIRECT TO $RAND_SERVER\n";
        }
    }
}
