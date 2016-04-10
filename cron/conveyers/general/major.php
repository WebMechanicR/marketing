<?php

define("CONVEYER_FREQUENCY", 10);

require_once $_SERVER['DOCUMENT_ROOT'].'/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/classes/Func.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/classes/System.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/classes/external/simpleHtml/simple_html_dom.php';

$system = new System();

if(!$system->settings->is_conveyer_disabled){
    $conveyer = new Conveyers("general");
    $conveyer->Run("http://".$_SERVER['HTTP_HOST']."/cron/conveyers/general/minor.php", "generalConveyerHandler");
}

function generalConveyerHandler(){
	global $system;
	//$system->catalog->create_yml();
	//siteMap();
        //seoPromo();
        mailSender();
        vk_group_parser();
        publisherProcedures();
}

function mailSender(){
    global $system;
    if((time() - $system->settings->for_smtp_sending_interval) < ($system->settings->sending_interval + rand(0, 10)))
		return;
    $system->mail->sendFromQueue(2);
    $system->settings->update_settings(array("for_smtp_sending_interval" => time()));
}

function vk_group_parser(){
    global $system;
    if(!$system->settings->is_vk_group_parser_disabled){
        $ch = curl_init("http://".$_SERVER['HTTP_HOST']."/vk_group_parser/index.php?continue=1");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $status = curl_exec($ch);
        curl_close($ch);
        
        if($status == 'empty!'){
            $system->settings->update_settings(array("is_vk_group_parser_disabled" => 1));
            $system->settings->update_settings(array("status_for_vk_group_parsing" => 'Работа завершена! Файл источник пуст!'));
        }
        else{
            $system->settings->update_settings(array("status_for_vk_group_parsing" => $status));
        }
    }
}

function publisherProcedures(){
    global $system;
    
    if(!$system->settings->proxy_scaner_disabled)
    {
	$actions = array(array('proxy_servers', 'run_proxy_checking', '605023452'), array('proxy_servers', 'run_proxy_searching', '603452'));
        foreach ($actions as $action) {
	    if(!F::mutex_lock($action[1])) continue;
	    F::mutex_lock($action[1], true);
	    
	    $sh = fsockopen($_SERVER['HTTP_HOST'], 80, $errno, $errstr);
	    if (!$errno) {
		fputs($sh, "GET http://" . ($_SERVER['HTTP_HOST']) . "/index.php?module={$action[0]}&action=" . $action[1] . "&key={$action[2]} HTTP/1.1\n");
		fputs($sh, "Host: " . $_SERVER['HTTP_HOST'] . "\n");
		fputs($sh, "User-Agent: Conveyer\n");
		fputs($sh, "Connection: close\n\n");

		$str = fgets($sh, 50);
		if (strpos($str, "200") === false) {
		    $system->errorHandlerObject->Push(ERROR_HANDLER_WARNING, 1, "Невозможно запустить фоновый процесс ".$action[0].'_'.$action[1]);
		}

		stream_set_blocking($sh, 0);  //non-blocking mode
	    }
	    else
	       $system->errorHandlerObject->Push(ERROR_HANDLER_WARNING, 1, "Невозможно запустить фоновый процесс ".$action[0].'_'.$action[1]); 
	}
    }
}

?>