<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Засекаем время
$time_start = microtime(true);

require_once '../config/config.php';
require_once '../classes/Func.php';
require_once '../classes/System.php';
$site = new System();

session_start();
$result = false; 
if($site->request->isAJAX() and $site->admins->login()) {
	$query = $site->request->get('query', 'string');
	if(mb_strlen($query)>=1) {
		$list_cities = $site->emails->get_city($query);
		$cities = array();
		foreach($list_cities as $city) {
			$cities[] = array(
				"value"=>$city['name'],
                                "id" => $city['id']
			);
		}

		$result['query'] = $query;
		$result['suggestions'] = $cities;
	}
}
header("Content-type: application/json; charset=UTF-8");
header("Cache-Control: must-revalidate");
header("Pragma: no-cache");
header("Expires: -1");
echo json_encode($result);