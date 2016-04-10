<?php
/**
 * класс отображения текстовых страниц в пользовательской части сайта
 */

class FrontedPublisher extends View {
	public function index() {
		return false;
	}
        
        public function run_proxy_checking() {
            
	    if($this->request->get('run_checking')){
                ignore_user_abort(true);
                echo str_repeat("OL", 600000);
                @ob_end_flush();
                $this->publisher->check_proxy();
            }
            else {
                $sh = fsockopen($_SERVER['HTTP_HOST'], 80, $errno, $errstr);
		
		fputs($sh, "GET http://".$_SERVER['HTTP_HOST']."/?module=publisher&action=run_proxy_checking&run_checking=1 HTTP/1.1\n");
		fputs($sh, "Host: ".$_SERVER['HTTP_HOST']."\n");
		fputs($sh, "User-Agent: Conveyer\n");
		fputs($sh, "Connection: close\n\n");
		
		$str = fgets($sh, 50);
		
		stream_set_blocking($sh, 0);    
            }
            
            echo 1;
            exit;
	}
}