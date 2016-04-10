<?php
require_once '../config/config.php';
require_once '../classes/Func.php';
require_once '../classes/System.php';
$site = new System();


if($site->request->get("key") == "369856842134"){
	$not_ips = $site->request->get("not_ips", "string");
	$proxies = $site->db->query("SELECT * FROM proxies WHERE enabled = 1 AND login = '' ".($not_ips?" AND ip NOT IN(".implode(",", array_map(function($arg){ return "'".$arg."'"; }, explode("|", $not_ips))).")":"")."
	AND (SELECT last_checking FROM proxies WHERE enabled = 1 ORDER BY last_checking DESC LIMIT 1) - last_checking < 40 ORDER BY uptime ASC");
	$result = array();
	if($proxies){
		foreach($proxies as $proxy){
			$result [] = $proxy['ip'].':'.$proxy['port'];
		}
		
		?>
			<html><head></head><body><?php echo implode("|", $result); ?></body></html>
		<?php
	}
}
?>