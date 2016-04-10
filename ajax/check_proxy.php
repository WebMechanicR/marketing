<?php

require_once dirname(__DIR__).'/classes/external/ProxyChecker/ProxyChecker.php';
            
$proxy = $_GET['ip'];
if($proxy and $_GET['id']){
    echo "id:".$_GET['id'].',';
    $site_url = 'http://'.$_SERVER['HTTP_HOST'].'/ajax/ping_proxy.php';
    
    $checker = new ProxyChecker($site_url);
      
    if($_GET['port'])
        $proxy .= (":".$_GET['port']);
    if($_GET['username'])
        $proxy .= (",".$_GET['username']).($_GET['pass']?":".$_GET['pass']:'');
    if($_GET['type']){
       list($http, $https, $socks4, $socks5) = explode(",", $_GET['type']);
       $type = 'http';
       if($socks5){
           $type = 'socks5';
       }
       else if($socks4)
           $type = 'socks4';
       
       $proxy .= (",".$type);
    }
    
    $info = $checker->checkProxies(array($proxy));
   
    if($info)
        $info = current($info);
    if($info['allowed']){
        echo "ok_checking_proxy";
        $level = 3;
        if($info['proxy_level'] == 'anonymous') $level = 2; else if($info['proxy_level'] == 'elite') $level = 1;
        echo  ','.$level;
    }
}
?>