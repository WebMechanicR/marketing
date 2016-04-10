<?php
/**
 * класс отображения текстовых страниц в пользовательской части сайта
 */

class FrontedProxy_servers extends View {
	public function index(){
	    return false;
	}
    
        public function run_proxy_checking() {
	    F::flush_to_non_blocking_socket();
            if($this->request->get('key') == '605023452'){
                $this->proxy_servers->check_proxy();
                exit;
            }
            echo 'wrong key!';
            exit;
	}
	
	public function run_proxy_searching() {
	    F::flush_to_non_blocking_socket();
            if($this->request->get('key') == '603452'){
                $this->proxy_servers->search_proxies();
                exit;
            }
            echo 'wrong key!';
            exit;
	}
}