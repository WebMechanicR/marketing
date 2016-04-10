check this string in proxy response content
<?php

$addr_file = dirname(__DIR__).'/admin/service/proxy_ping_real_ip.txt';
$ip = trim(file_get_contents($addr_file));
if($ip){
    @list($ip, $moment) = explode(",",$ip);
    if(time() - $moment > 86400*1 or ($ip == 'none' and time() - $moment > 7200))
        $ip = "";
}

if(!$ip){
    $fp = fopen($addr_file, 'w');
    fwrite($fp, "none,".time());
    fclose($fp);
    $real_ip = gethostbyname ($_SERVER['HTTP_HOST']);
    if($real_ip == $_SERVER['HTTP_HOST']){
        $real_ip = 'none';
    }
    $fp = fopen($addr_file, 'w');
    fwrite($fp, $real_ip.",".time());
    fclose($fp);
    $ip = $real_ip;
}

if($ip == 'none')
    $ip = "";


if (!empty($_GET['q']) && ('query' == $_GET['q'])) {
    echo 'allow_get';
}

if (!empty($_POST['r']) && ('request' == $_POST['r'])) {
    echo 'allow_post';
}

if (!empty($_COOKIE['c']) && ('cookie' == $_COOKIE['c'])) {
    echo 'allow_cookie';
}

if (!empty($_SERVER['HTTP_REFERER']) && ('http://www.google.com' == $_SERVER['HTTP_REFERER'])) {
    echo 'allow_referer';
}

if (!empty($_SERVER['HTTP_USER_AGENT']) && ('Mozila/4.0' == $_SERVER['HTTP_USER_AGENT'])) {
    echo 'allow_user_agent';
}


//proxy levels
//Level 3 Elite Proxy, connection looks like a regular client
//Level 2 Anonymous Proxy, no ip is forworded but target site could still tell it's a proxy
//Level 1 Transparent Proxy, ip is forworded and target site would be able to tell it's a proxy
if((!trim($_SERVER['HTTP_X_FORWARDED_FOR'])  or ($ip and mb_stripos($_SERVER['HTTP_X_FORWARDED_FOR'], $ip) === false)) and !trim($_SERVER['HTTP_VIA']) and !trim($_SERVER['HTTP_PROXY_CONNECTION'])) {
    echo 'proxylevel_elite';
} elseif(!trim($_SERVER['HTTP_X_FORWARDED_FOR']) or !$ip or ($ip and mb_stripos($_SERVER['HTTP_X_FORWARDED_FOR'], $ip) === false)) {
    echo 'proxylevel_anonymous';
} else {
    echo 'proxylevel_transparent';
}
