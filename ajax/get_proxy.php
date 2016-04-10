<?php

require_once '../config/config.php';
require_once '../classes/Func.php';
require_once '../classes/System.php';
$site = new System();

$ip = $_SERVER['REMOTE_ADDR'];

@$meta_ips = unserialize($site->settings->get_meta_ips_for_getting_proxy);
if(@$meta_ip and $last_moment = $meta_ips[$ip]){
    if(time() - $last_moment < 60 * 3)
        die("You can't get proxies");
}
if(!$ip){
    $ip = "not";
}

$countries = $site->request->get('countries');
if($countries)
    foreach($countries as &$country)
        $country = $site->request->get_str($country, 'string');

$levels = $site->request->get('levels');
if($levels)
    foreach($levels as &$level)
        $level = $site->request->get_str($level, 'integer');
$filter['enabled'] = 1;
if($countries)
    $filter['countries'] = $countries;
if($levels)
    $filter['levels'] = $levels;

$list = $site->publisher->get_list_proxies($filter);

$meta_ip[$ip] = time();
$site->settings->update_settings(array('get_meta_ips_for_getting_proxy' => serialize($meta_ip)));


header("Content-type: application/json; charset=UTF-8");
header("Cache-Control: must-revalidate");
header("Pragma: no-cache");
header("Expires: -1");
echo json_encode($list);