<?php
require_once dirname(dirname(__FILE__)).'/config/config.php';
require_once dirname(dirname(__FILE__)).'/classes/Func.php';
require_once dirname(dirname(__FILE__)).'/classes/System.php';

if(!isset($_SERVER['HTTP_HOST']) or !$_SERVER['HTTP_HOST'])
    $_SERVER['HTTP_HOST'] = "marketing.ultra-cms.ru";

$system = new System();

if (!$system->settings->is_conveyer_disabled) {
    //general conveyer
    $generalConveyer = new Conveyers("general", "console");
    if (!$generalConveyer->ExecCommand("check")) {
        $generalConveyer = new Conveyers("general", "force_start");
        $generalConveyer->Run('http://'.$_SERVER['HTTP_HOST'] . "/cron/conveyers/general/minor.php", false);
    } 
    
    $system->antivirus->Run(true);  
}

?>
<META HTTP-EQUIV="REFRESH" CONTENT="25;URL=<?php echo SITE_URL.'cron/conveyer_runner.php'; ?>"/>