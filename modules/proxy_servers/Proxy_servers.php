<?php

require_once dirname(dirname(dirname(__FILE__))).'/classes/external/simpleHtml/simple_html_dom.php';
require_once(dirname(dirname(dirname(__FILE__)))."/classes/external/AngryCurl/RollingCurl.class.php");

class Proxy_servers extends Module implements IElement {

	protected $module_name = "proxy_servers";
	private $module_table = "proxies";
	private $module_nesting = false; //возможность вкладывать подстраницы в модуль
        private $curl = null;
	
	private $module_settings = array(
			
	);

	
	public function __construct() {
	    parent::__construct();
	    if (!$this->curl)
		$this->curl = new RollingCurl();
	}
	
	/**
	 * добавляет новый элемент в базу
	 */
	public function add($proxy_server) {
		//чистим кеш
		$this->cache->clean("list_proxy_servers");
		return $this->db->query("INSERT INTO ?_".$this->module_table." (?#) VALUES (?a)", array_keys($proxy_server), array_values($proxy_server));
	}

	/**
	 * обновляет элемент в базе
	 */
	public function update($id, $proxy_server) {

		//чистим кеш
		$this->cache->clean("proxy_serverid_".$id);
		
		if($this->db->query("UPDATE ?_".$this->module_table." SET ?a WHERE id IN (?a)", $proxy_server, (array)$id))
			return $id;
		else
			return false;
	}

	/**
	 * удаляет элемент из базы
	 */
	public function delete($id) {
		if($proxy_server = $this->get_proxy_server($id)) {
			$this->db->query("DELETE FROM ?_".$this->module_table." WHERE id=?", $id);

			//чистим кеш
			$this->cache->clean("proxy_serverid_".$id);
			$this->clear_revisions($id);
		}
	}

	/**
	 * создает копию элемента
	 */
	public function duplicate($id) {
		$new_id = null;
		if($proxy_server = $this->get_proxy_server($id)) {
				
			unset($proxy_server['id']);
			
			$proxy_server['enabled'] = 0;
				
			$new_id = (int)$this->add($proxy_server);
		}
		return $new_id;
	}

	/**
	 * возвращает историю версий элемента
	 */
	public function get_list_revisions($for_id) {
		return $this->revision->get_list_revisions($for_id, $this->setting("revisions_content_type"));
	}

	/**
	 * добавляет версию элемента в историю
	 */
	public function add_revision($for_id) {
		if($content = $this->get_proxy_server($for_id)) {
			return $this->revision->add_revision($for_id, $this->setting("revisions_content_type"), $content);
		}
		return null;
	}

	/**
	 * возвращает данные элемента из определенной ревизии
	 */
	public function get_from_revision($id, $for_id) {
		return $this->revision->get_from_revision($id, $for_id, $this->setting("revisions_content_type"));
	}

	/**
	 * удаляет все ревизии элемента
	 */
	public function clear_revisions($for_id) {
		return $this->revision->clear_revisions($for_id, $this->setting("revisions_content_type"));
	}

	/**
	 * возвращает новость по id
	 * @param mixed $id
	 * @return array
	 */
	public function get_proxy_server($id) {
		return @$this->db->selectRow("SELECT * FROM ?_".$this->module_table." WHERE id=?d", $id);
	}

	/**
	 * возвращает новости удовлетворяющие фильтрам
	 * @param array $filter
	 */
	public function get_list_proxy_servers($filter=array()) {
		$limit = "";
		$where = "";
		$sort_by = " ORDER BY n.enabled DESC, n.last_checking DESC, n.uptime ASC";
		
		if(isset($filter['limit']) and count($filter['limit'])==2) {
			$filter['limit'] = array_map("intval", $filter['limit']);
			$limit = " LIMIT ".($filter['limit'][0]-1)*$filter['limit'][1].", ".$filter['limit'][1];
		}

		if(isset($filter['sort']) and count($filter['sort'])==2) {
			if(!in_array($filter['sort'][0], array("last_checking", "uptime", "id"))) $filter['sort'][0] = "last_checking";
			if(!in_array($filter['sort'][1], array("asc", "desc")) ) $filter['sort'][1] = "desc";
			$sort_by = " ORDER BY n.".$filter['sort'][0]." ".$filter['sort'][1];
		}
		
		if(isset($filter['enabled'])) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.enabled=".intval($filter['enabled']);
		}
              
		if(isset($filter['levels']) and $filter['levels']) { //1 elite; 2-anonymous; 3-transparent
			if(!is_array($filter['levels']))
			    $filter['levels'] = array($filter['levels']);
			
			$where .= (empty($where) ? " WHERE " : " AND ")."n.anonymous IN(".implode(",", $filter['levels']).")";
		}
                
                if(isset($filter['countries']) and $filter['countries']) {
			if(!is_array($filter['countries']))
			    $filter['countries'] = array($filter['countries']);
                        foreach($filter['countries'] as &$country)
                            $country = "n.country LIKE '%".trim($country)."%'";
			$where .= (empty($where) ? " WHERE " : " AND ")."(".implode(" OR ", $filter['countries']).")";
		}
                
                if(isset($filter['date_from'])) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.last_checking >= ".$filter['date_from'];
		}

		if(isset($filter['date_to']) and $filter['date_to'] > 0){
			$where .= (empty($where) ? " WHERE " : " AND ")."n.last_checking <= ".$filter['date_to'];
		}
		
		if(isset($filter['uptime'])) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.uptime <= ".$filter['uptime'];
		}
                
		if(isset($filter['private'])) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.login ".($filter['private']?'!=':'=')." ''";
		}
		
		if(isset($filter['types']) and $filter['types']) { //1 elite; 2-anonymous; 3-transparent
			if(!is_array($filter['types']))
			    $filter['types'] = array($filter['types']);
			if(in_array(1, $filter['types']))
			    $where .= (empty($where) ? " WHERE " : " AND ")."n.type_http != 0";
			if(in_array(2, $filter['types']))
			    $where .= (empty($where) ? " WHERE " : " AND ")."n.type_https != 0";
			if(in_array(3, $filter['types']))
			    $where .= (empty($where) ? " WHERE " : " AND ")."n.type_socks4 != 0";
			if(in_array(4, $filter['types']))
			    $where .= (empty($where) ? " WHERE " : " AND ")."n.type_socks5 != 0";
		}
		
		return $this->db->select("SELECT n.*
				FROM ?_".$this->module_table." n".$where.$sort_by.$limit);
	}
        
         public function get_count_proxy_servers($filter=array()) {
		$where = "";

		if(isset($filter['enabled'])) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.enabled=".intval($filter['enabled']);
		}
              
		if(isset($filter['levels']) and $filter['levels']) { //1 elite; 2-anonymous; 3-transparent
			if(!is_array($filter['levels']))
			    $filter['levels'] = array($filter['levels']);
			$where .= (empty($where) ? " WHERE " : " AND ")."n.anonymous IN(".implode(",", $filter['levels']).")";
		}
		
		if(isset($filter['types']) and $filter['types']) { //1 elite; 2-anonymous; 3-transparent
			if(!is_array($filter['types']))
			    $filter['types'] = array($filter['types']);
			if(in_array(1, $filter['types']))
			    $where .= (empty($where) ? " WHERE " : " AND ")."n.type_http != 0";
			if(in_array(2, $filter['types']))
			    $where .= (empty($where) ? " WHERE " : " AND ")."n.type_https != 0";
			if(in_array(3, $filter['types']))
			    $where .= (empty($where) ? " WHERE " : " AND ")."n.type_socks4 != 0";
			if(in_array(4, $filter['types']))
			    $where .= (empty($where) ? " WHERE " : " AND ")."n.type_socks5 != 0";
		}
                
                if(isset($filter['countries']) and $filter['countries']) {
			if(!is_array($filter['countries']))
			    $filter['countries'] = array($filter['countries']);
                        foreach($filter['countries'] as &$country)
                            $country = "n.country LIKE '%".trim($country)."%'";
			$where .= (empty($where) ? " WHERE " : " AND ")."(".implode(" OR ", $filter['countries']).")";
		}
                
                if(isset($filter['date_from'])) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.last_checking >= ".$filter['date_from'];
		}

		if(isset($filter['date_to']) and $filter['date_to'] > 0){
			$where .= (empty($where) ? " WHERE " : " AND ")."n.last_checking <= ".$filter['date_to'];
		}
		
		if(isset($filter['uptime'])) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.uptime <= ".$filter['uptime'];
		}
                
		if(isset($filter['private'])) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.login ".($filter['private']?'!=':'=')." ''";
		}
                
		return $this->db->selectCell("SELECT count(n.id)
				FROM ?_".$this->module_table." n".$where);
	}
	
	public function get_familiar_proxy($filter){
            $where = "";
            
            if(isset($filter['ip']) and $filter['ip']) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.ip='".($filter['ip'])."'";
	    }
            
            if(isset($filter['port'])  and $filter['port']) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.port='".($filter['port'])."'";
	    }
            
            if(isset($filter['login']) and $filter['login']) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.login='".($filter['login'])."'";
	    }
            
            if($where)
                return @$this->db->selectCell("SELECT count(n.id)
				FROM ?_".$this->module_table." n".$where);
        }

	/**
	 * возвращает настройку модуля
	 * @param string $id
	 * @return Ambigous <NULL, multitype:string >
	 */
	public function setting($id) {
		return (isset($this->module_settings[$id]) ? $this->module_settings[$id] : null);
	}

	/**
	 * @return boolean
	 */
	public function is_nesting() {
		return $this->module_nesting;
	}
	
	public function for_proxy_checking_request_end($response, $info, $request) {
	    global $m_system;
	   
	    if(isset($request->url) and preg_match('#^'.preg_quote(SITE_URL).'#isu', $request->url)){
		if (preg_match('/^id:(\d+)/is', $response, $pockets)) {
		    $id = intval($pockets[1]);
		    if (preg_match('/ok_checking_proxy,(\d+)/is', $response, $pockets)) {
			$level = intval($pockets[1]);
			$m_system->update($id, array('enabled' => 1, 'anonymous' => $level, 'last_checking' => time(), 'checking_count' => 0, 'uptime' => $info['total_time']));
			$GLOBALS['m_scanned_successful']++;
		    } else {
			$old = $m_system->get_proxy_server($id);
			if ($old) {
			    if ($old['checking_count'] >= 3 and !$old['do_not_delete']) {
				$m_system->delete($id);
			    } else {
				$m_system->update($id, array('enabled' => 0, 'last_checking' => time(), 'checking_count' => $old['checking_count'] + 1));
			    }
			}
		    }
		}
	    }
	    else {
		$id = $site_id = $is_check = 0;
		if(isset($request->headers[0]))
		    $id = intval(str_replace('X-custom-my-id: ', '', $request->headers[0]));
		if(isset($request->headers[0]))
		    $is_check = intval(str_replace('X-custom-dest-ch: ', '', $request->headers[0]));
		if(isset($request->headers[1]))
		    $site_id = intval(str_replace('X-custom-my-site-id: ', '', $request->headers[1]));
		
	        $working = false;
		if(in_array($site_id, array(0, 3, 4, 6, 8))){
			@$response = iconv('windows-1251', 'utf-8//IGNORE', $response);
		}
		
		if($is_check){
		    if(stripos($response, $this->destinations[$site_id]['str']) !== false){
			$GLOBALS['m_successful_destinations'][] = $site_id;
		    }
		}
		else{
		    if(stripos($response, $this->destinations[$site_id]['str']) !== false){
			$working = true;
		    }
		    
		    if($working){
			$m_system->update($id, array('enabled' => 1, 'last_checking' => time(), 'checking_count' => 0, 'uptime' => $info['total_time']));
			$GLOBALS['m_scanned_successful']++;
		    }
		    else{
			$old = $m_system->get_proxy_server($id);
			if ($old) {
				if ($old['checking_count'] >= 3 and !$old['do_not_delete']) {
				    $m_system->delete($id);
				} else {
				    $m_system->update($id, array('enabled' => 0, 'last_checking' => time(), 'checking_count' => $old['checking_count'] + 1));
				}
			}
		    }
		} 
	    }
	}
	
	public function check_proxy_now($proxies, $check_hard = false, $test_destinations = false){
	    $GLOBALS['m_system'] = $this;
	    $GLOBALS['m_scanned_successful'] = 0;
	    
	    @$destinations = unserialize($this->settings->destinations_for_proxies);
	    
	    if(!$destinations or $this->settings->next_moment_for_destinations_checking - time() <= 0 or $test_destinations){
		$GLOBALS['m_successful_destinations'] = array();
		$curl = new RollingCurl(array($this, 'for_proxy_checking_request_end'));
		$options = array();
		$headers = array('X-custom-dest-ch: 1', '',
		    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                    'Cache-Control: max-age=0',
                    'Connection: keep-alive'
		);
		$options[CURLOPT_USERAGENT] = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.101 Safari/537.36';
		$options[CURLOPT_TIMEOUT] = 40;
		
		foreach($this->destinations as $id => $destination){
		    $headers[1] = 'X-custom-my-site-id: '.$id;
		    $headers[6] = 'Host: '.preg_replace('#^https?://#', '', $destination['url']);
		  
		    $curl->get($destination['url'], $headers, $options);
		}
		
		$curl->execute(count($this->destinations));
		$destinations = $GLOBALS['m_successful_destinations'];
		$this->settings->update_settings(array('destinations_for_proxies' => serialize($destinations), 'next_moment_for_destinations_checking' => time() + 15*60));
	    }
	    
	    if($test_destinations){
		header("Content-type: text/html; charset=utf-8");
		echo "NOT WORKING: <br/>";
		$first_not_working = "";
		foreach($this->destinations as $key => $dest)
		    if(!in_array($key, $destinations)){
			    echo $dest['url'], '<br/>';
			    if(!$first_not_working)
				$first_not_working = $dest['url'];
		    }
		if($first_not_working){
		    echo '<br/><br/>CONTENT OF ONE OF NOT WORKING:<br/><br/>';
		    $options['html'] = 1;
                    $options['headers'] = array(
                       'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                       'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                       'Cache-Control' => 'max-age=0',
                       'Connection' => 'keep-alive',
                       'Host' => preg_replace('#^https?://#', '', $first_not_working)
                     );
                     $options[CURLOPT_USERAGENT] = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.101 Safari/537.36';
		     $options[CURLOPT_FOLLOWLOCATION] = 1;
		     $response =  $this->request($first_not_working, $options);
		     $response = $response['response'];
		     echo $response;
		     exit;
		}
		exit;
	    }
	    
	    if(!$destinations and !$check_hard){
		$this->errorHandlerObject->Push(ERROR_HANDLER_ERROR, 1, 'No destinations for proxy checking!');
	    }
	    
	    $curl = new RollingCurl(array($this, 'for_proxy_checking_request_end'));
	    $count = 0;
	    
	    $dests = array();
	    $http_destinations = array();
	    foreach($this->destinations as $site_id => $dest){
		if(!in_array($site_id, $destinations)){
		    continue;
		}
		
		$dests[] = array('id' => $site_id, 'url' =>  $dest['url']); 
		if(!preg_match('#^https://#is', $dest['url'])){
		    $http_destinations[] = array('id' => $site_id, 'url' => $dest['url']); 
		}
	    }
	   
	    $http_count = $all_count = 0;
	    
            foreach($proxies as $proxy){
		if($check_hard){
		    $type = $proxy['type_http'].','.$proxy['type_https'].','.$proxy['type_socks4'].','.$proxy['type_socks5'];
		    $curl->get(SITE_URL.'ajax/check_proxy.php?ip='.$proxy['ip'].'&id='.$proxy['id'].'&username='.$proxy['login'].'&pass='.$proxy['password'].'&type='.$type.'&port='.$proxy['port']);
		}
		else{
		    $url = "";
		    $site_id = 0;
		    
		    if(!$proxy['type_https'] and !$proxy['type_socks4'] and !$proxy['type_socks5'] and $http_destinations)
		    {
			$site = $http_destinations[$http_count++];
			$url = $site['url'];
			$site_id = $site['id'];
			if($http_count == count($http_destinations)) $http_count = 0;
		    }
		    else{
			$site = $dests[$all_count++];
			$url = $site['url'];
			$site_id = $site['id'];
			if($all_count == count($dests)) $all_count = 0;
		    }
		    
		    $options = array();
		    $options[CURLOPT_PROXY] = $proxy['ip'].':'.$proxy['port'];
		    $options[CURLOPT_TIMEOUT] = 60;
		    if($proxy['login']){
			$options[CURLOPT_PROXYUSERPWD] = $proxy['login'].":".$proxy['password'];
		    }
		    
		    $headers = array('X-custom-my-id: '.$proxy['id'], 'X-custom-my-site-id: '.$site_id,
			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
			'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
			'Cache-Control: max-age=0',
			'Connection: keep-alive',
			'Host: '.preg_replace('#^https?://#', '', $url)
		    );
		    
		    $options[CURLOPT_USERAGENT] = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.101 Safari/537.36';
		    $options[CURLOPT_TIMEOUT] = 40;
		    
		    $curl->get($url, $headers, $options);
		}
	    }
            $curl->execute(count($proxies));
	    return $GLOBALS['m_scanned_successful'];
	}
	
	public function check_proxy(){
            if(!F::mutex_lock("run_proxy_checking"))
                return;
	    
	    if($this->settings->proxy_scaner_recommended_next_query_time - time() <= 0){
		@$status = unserialize($this->settings->proxy_scaner_status);
		@$settings = unserialize($this->settings->proxy_scaner_settings);
		if(!isset($settings['proxy_scaner_threads']))
		    $settings['proxy_scaner_threads'] = 16;
		
		$threads = max(16, min(816, $settings['proxy_scaner_threads']));
		
		if(!$status){
		   $status['scanned'] = 0;
		   $status['scanned_successful'] = 0;
		   $status['p1'] = 0;
		   $status['p2'] = 0;
		}
		
		$a_limit = ceil($threads * (0.60));
		$not_limit = ceil($threads * (0.40));
		$active_proxies = $this->db->select("SELECT * FROM ?_".$this->module_table." WHERE enabled = 1 AND id > ".$status['p1']." ORDER BY id ASC LIMIT ".$a_limit);
		$not_active_proxies = $this->db->select("SELECT * FROM ?_".$this->module_table." WHERE enabled = 0 AND id > ".$status['p2']." ORDER BY id ASC LIMIT ".($not_limit + $a_limit - count($active_proxies)));

		if(!$active_proxies or ($active_proxies and (time() - $active_proxies[0]['last_checking'] <= 33))){
		      if(count($not_active_proxies) < ($not_limit + $a_limit - count($active_proxies)))
			    $this->settings->update_settings(array("proxy_scaner_recommended_next_query_time" => time() + $settings['interval']*60));
		   
		      $status['p1'] = 0;
		      $active_proxies = array();
		}
		else{
		    $status['p1'] = $active_proxies[count($active_proxies) - 1]['id'];
		}
		
		if(!$not_active_proxies or ($not_active_proxies and (time() - $not_active_proxies[0]['last_checking'] <= 33))){
		      if(count($active_proxies) < $a_limit)
			    $this->settings->update_settings(array("proxy_scaner_recommended_next_query_time" => time() + $settings['interval']*60));
		   
		      $status['p2'] = 0;
		      $not_active_proxies = array();
		}
		else{
		    $status['p2'] = $not_active_proxies[count($not_active_proxies) - 1]['id'];
		}
		
		if(!$active_proxies and !$not_active_proxies){
		    $status['scanned_successful'] = 0;
		    $status['scanned'] = 0;
		}

		$proxies = array_merge($not_active_proxies, $active_proxies);
		
		if($proxies){
		    $status['scanned_successful'] += $this->check_proxy_now($proxies);
		    $status['scanned'] +=count($proxies);
		}
		$this->settings->update_settings(array('proxy_scaner_status' => serialize($status)));
	    }
            F::mutex_lock("run_proxy_checking", true);
        }
        
        public function search_proxies($test = false){
	    if(!F::mutex_lock("run_proxy_searching") and !$test)
                return;
	    
	    @$status = unserialize($this->settings->proxy_searching_status);
	    @$settings = unserialize($this->settings->proxy_scaner_settings);
	    
            if(!isset($status['next_query_time_for_moment']))
                $status['next_query_time_for_moment'] = 0;
	    
            $options[CURLOPT_CONNECTTIMEOUT] = 10;
            $options[CURLOPT_TIMEOUT] = 30;
            
            if($status['next_query_time_for_moment'] - time() <= 0 or $test){
                $status['importing_stopped'] = 0;
                if(!isset($status['current_import_source']))
                    $status['current_import_source'] = 'google-proxy.net';
		
		if($test)
		    $status['current_import_source'] = $test;
		
		
		
		if(!isset($status['last_founded_proxies_in_source']))
		    $status['last_founded_proxies_in_source'] = array();
		
                $result = array();
                
		$current_source = $status['current_import_source'];
		
		
                if($current_source == 'google-proxy.net'){
                     $options['html'] = 1;
                     $options['headers'] = array(
                       'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                       'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                       'Cache-Control' => 'max-age=0',
                       'Connection' => 'keep-alive',
                       'Host' => 'www.google-proxy.net'
                     );
                     $options['referer'] = 'http://www.google-proxy.net/';
                     $options[CURLOPT_USERAGENT] = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.101 Safari/537.36';
                     $p = 1;
                     $table = array();
                     $tryings = 0;
                     $table = array();
                     $res = $this->proxy_servers->get('http://www.google-proxy.net/', $options); 
                     
                     if($res and @$table = $res->find('#proxylisttable tbody', 0)){
                             $rows = $table->find('tr');
                             if($rows){
                                 for($i = 1; $i < count($rows); $i++){
                                     $tr = $rows[$i];
                                     $item = array();
                                     @$item['ip'] = $this->request->get_str($tr->find('td', 0)->plaintext, 'string');
                                     @$item['port'] = $this->request->get_str($tr->find('td', 1)->plaintext, 'integer');
                                     @$item['country'] = $this->request->get_str(str_replace('&nbsp;', ' ', $tr->find('td', 3)->plaintext), 'string');
                                     $level = 1;
                                     @$level_str = $this->request->get_str($tr->find('td', 4)->plaintext, 'string');
                                     if(mb_stripos($level_str, 'anonymous') !== false)
                                             $level = 2;
                                     else if(mb_stripos($level_str, 'elite proxy') !== false)
                                             $level = 3;
                                     $item['anonymous'] = $level;
                                     $item['type_http'] = 1;
                                     @$type_str = $this->request->get_str($tr->find('td', 6)->plaintext, 'string');
                                     if(mb_stripos($type_str, 'yes') !== false)
                                              $item['type_https'] = 1;
                                     $result[] = $item;
                                 }
                           }
                    }
                    $status['current_import_source'] = 'cool-proxy.net';
                }
		else if($current_source == 'cool-proxy.net'){
			$options['html'] = 1;
			$options['headers'] = array(
			   'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
			   'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
			   'Cache-Control' => 'max-age=0',
			   'Connection' => 'keep-alive',
			   'Host' => 'www.cool-proxy.net'
			 );
			$options['referer'] = 'http://www.cool-proxy.net/proxies/http_proxy_list/';
		    
		    $p = 1;
		      $table = array();
		      $tryings = 0;
		      do{
			 $res = $this->get('http://www.cool-proxy.net/proxies/http_proxy_list/page:'.$p, $options); 
			 $table = false;
			 if($res)
			    @$table = $res->find('#main', 0);
			 if($table)
			    @$table = $table->find('table', 0);
			 $failure = !$table;

			 if($table){
			      $rows = $table->find('tr');
			      $found = false;
			      if($rows){
				  for($i = 1; $i < count($rows); $i++){
				      $tr = $rows[$i];
				      if(@$tr->find('td', 0)->colspan or @$tr->find('th.tHeader', 0))
					  continue;
				      $item = array();
				      @$ip = $tr->find('td', 0)->innertext;

				      if(preg_match('/Base64\.decode\(str_rot13\("([\w=\/\+]+)"\)/is', $ip, $pockets) and $ip = base64_decode(str_rot13 ($pockets[1]))){
					 @$item['ip'] = $ip;
					 @$item['port'] = $this->request->get_str($tr->find('td', 1)->plaintext, 'integer');
					 @$item['country'] = $this->request->get_str($tr->find('td', 3)->plaintext, 'string');
					 $level = 1;
					 @$level_str = mb_strtolower($this->request->get_str($tr->find('td', 5)->plaintext, 'string'));
					 if(mb_stripos($level_str, 'Anonymous') !== false)
						 $level = 2;
					 $item['anonymous'] = $level;
					 $item['type_http'] = 1;
					 $result[] = $item;
					 $found = true;
				      }
				  }
			      }
			      if(!$found)
				  break;
			 }
			 usleep(100000 + mt_rand(0, 100000));
			 $p++;
		      }while((!$failure or $tryings++ < 3) and $p <= 30);
		     
		    $status['current_import_source'] = 'theproxisright.com';
		}
		else if($current_source == 'theproxisright.com'){
		    $options['html'] = 0;
                    $options['headers'] = array(
                       'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                       'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                       'Cache-Control' => 'max-age=0',
                       'Connection' => 'keep-alive',
                       'Host' => 'theproxisright.com'
                     );
		    
		     $key_data = $this->proxy_servers->get('https://theproxisright.com/apikey/js?t=6357781795243'.mt_rand(0, 1000000), $options);
		     $session_id = $ip = $user_agent = $key = "";
		     if(preg_match('/var sessionId =\'(\w+)\'/', $key_data, $pockets)){
			 $session_id = $pockets[1];
		     }
		     if(preg_match('/var ip =\'([\w\.]+)\'/', $key_data, $pockets)){
			 $ip = $pockets[1];
		     }
		     if(preg_match('/var userAgent =\'(.+?)\'/isu', $key_data, $pockets)){
			 $user_agent = $pockets[1];
		     }
		     if(preg_match('/var key =\'([\w-]+)\'/', $key_data, $pockets)){
			 $key = $pockets[1];
		     }
		     
		     if($session_id and $ip and $user_agent and $key){
			$options[CURLOPT_COOKIE] = 'ASP.NET_SessionId='.$session_id;
			$res = $this->proxy_servers->get('https://theproxisright.com/api/proxy/get?maxResults=1000&apiKey='.$key, $options); 
			@$res = json_decode($res, true);
			if($res and $res['list']){
			    foreach($res['list'] as $proxy){
				$item = array();
				@$item['ip'] = $proxy['ip_address'];
				@$item['port'] = $proxy['port'];
				@$item['country'] = (string) $proxy['country_code'];
				$level = 1;
				if (isset($item['anonymity']) and $item['anonymity'] !== false)
				    $level = 2;
				$item['anonymous'] = $level;
				$item['type_http'] = 1;
				
				if ($proxy['type'] == 'https')
				    $item['type_https'] = 1;
				if (mb_stripos($proxy['type'], 'socks') !== false) {
				    $item['type_socks4'] = 1;
				    $item['type_https'] = 1;
				}
				if (mb_stripos($proxy['type'], 'socks5') !== false)
				    $proxy['type_socks5'] = 1;
				$result[] = $item;
			    }
			}
		     }
		     if(!isset($status['free-proxy.cz_next_moment_time']) or $status['free-proxy.cz_next_moment_time'] - time() <= 0)
			 $status['current_import_source'] = 'free-proxy.cz';
		     else
			$status['current_import_source'] = 'foxtools.ru';
		}
		else if($current_source == 'free-proxy.cz'){
		    $options['html'] = 1;
                    $options['headers'] = array(
                       'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                       'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                       'Cache-Control' => 'max-age=0',
                       'Connection' => 'keep-alive',
                       'Host' => 'free-proxy.cz'
                     );
                    $options['referer'] = 'http://free-proxy.cz';
		    
		   $p = 1;
                     $table = array();
                     $tryings = 0;
                     do{
                        $res = $this->get('http://free-proxy.cz/en/proxylist/main/'.$p, $options); 
                        @$table = $res->find('#proxy_list', 0);
			$failure = !$table;
                       
                        if($table){
                             $rows = $table->find('tr');
			     $found = false;
                             if($rows){
                                 for($i = 1; $i < count($rows); $i++){
                                     $tr = $rows[$i];
				     if(@$tr->find('td', 0)->colspan)
					 continue;
                                     $item = array();
				     @$ip = $tr->find('td', 0)->innertext;
				     
				     if(preg_match('/Base64\.decode\("([\w=\/\+]+)"\)/is', $ip, $pockets) and $ip = base64_decode($pockets[1])){
					@$item['ip'] = $ip;
					@$item['port'] = $this->request->get_str($tr->find('td', 1)->find('span', 0)->plaintext, 'integer');
					@$item['country'] = $this->request->get_str($tr->find('td', 3)->find('a', 0)->plaintext, 'string');
					$level = 1;
					@$level_str = mb_strtolower($this->request->get_str($tr->find('td', 6)->find('small', 0)->plaintext, 'string'));
					if(mb_stripos($level_str, 'Anonymous') !== false)
						$level = 2;
					else if(mb_stripos($level_str, 'High anonymity') !== false)
						$level = 3;
					$item['anonymous'] = $level;
					$item['type_http'] = 1;
					@$type_str = mb_strtolower($this->request->get_str($tr->find('td', 2)->find('small', 0)->plaintext, 'string'));
					
					if(mb_stripos($type_str, 'https') !== false)
						 $item['type_https'] = 1;
					if(mb_stripos($type_str, 'socks') !== false){
						 $item['type_socks4'] = 1;
						 $item['type_https'] = 1;
					}
					if(mb_stripos($type_str, 'socks5') !== false)
						 $item['type_socks5'] = 1;
					$result[] = $item;
					$found = true;
				     }
                                 }
                             }
			     if(!$found)
				 break;
                        }
			usleep(100000 + mt_rand(0, 100000));
                        $p++;
                     }while((!$failure or $tryings++ < 3) and $p <= 300);
		     
		    /*if($p != 0)*/
			$status['free-proxy.cz_next_moment_time'] = time() + 25 * 60;
		    
		    $status['current_import_source'] = 'foxtools.ru';
		}
                else{
                     //$options['encoding'] = 'windows-1251';
                     $options['html'] = 1;
                     $options['headers'] = array(
                       'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                       'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                       'Cache-Control' => 'max-age=0',
                       'Connection' => 'keep-alive',
                       'Host' => 'foxtools.ru'
                     );
                     $options['referer'] = 'http://foxtools.ru/';
                     $options[CURLOPT_USERAGENT] = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.101 Safari/537.36';
                     $p = 1;
                     $table = array();
                     $tryings = 0;
                     do{
			 
                        $res = $this->get('http://foxtools.ru/Proxy?page='.$p, $options); 
                        @$table = $res->find('#theProxyList', 0);
			$failure = !$table;
                        
                        if($table){
                             $rows = $table->find('tr');
			     $found = false;
                             if($rows){
                                 for($i = 1; $i < count($rows); $i++){
                                     $tr = $rows[$i];
                                     $item = array();
                                     @$item['ip'] = $this->request->get_str($tr->find('td', 1)->plaintext, 'string');
                                     @$item['port'] = $this->request->get_str($tr->find('td', 2)->plaintext, 'integer');
                                     @$item['country'] = $this->request->get_str(str_replace('&nbsp;', ' ', $tr->find('td', 3)->plaintext), 'string');
                                     $level = 1;
                                     @$level_str = $this->request->get_str($tr->find('td', 4)->plaintext, 'string');
                                     if(mb_stripos($level_str, 'высокая') !== false)
                                             $level = 2;
                                     else if(mb_stripos($level_str, 'наивысшая') !== false)
                                             $level = 3;
                                     $item['anonymous'] = $level;
                                     $item['type_http'] = 1;
                                     @$type_str = $this->request->get_str($tr->find('td', 5)->plaintext, 'string');
                                     if(mb_stripos($type_str, 'https') !== false)
                                              $item['type_https'] = 1;
                                     if(mb_stripos($type_str, 'socks') !== false){
                                              $item['type_socks4'] = 1;
                                              $item['type_https'] = 1;
                                     }
                                     if(mb_stripos($type_str, 'socks5') !== false)
                                              $item['type_socks5'] = 1;
                                     $result[] = $item;
				     $found = true;
                                 }
                             }
			     
			     if(!$found)
				 break;
                        }
			usleep(100000 + mt_rand(0, 100000));
                        $p++;
                     }while((!$failure or $tryings++ < 3) and $p <= 21);
		     
                     $status['current_import_source'] = 'google-proxy.net'; //next /*IMPORTANT */
                }
                
                if($status['current_import_source'] == 'foxtools.ru'){
                    $status['importing_stopped'] = 1;
                    $status['next_query_time_for_moment'] = time() + $settings['import_interval'] * 60;
                }
                    
                $inserteded = 0;
                foreach($result as $item){
                    if($item['ip'] and $item['port'] and !$this->get_familiar_proxy($item)){
                        $this->add($item);
                        $inserteded++;
                    }
                }
                
                $status['last_found_for_importing'] = count($result);
                $status['last_found_for_inserting'] = $inserteded;
		$status['last_founded_proxies_in_source'][$current_source] = array('count' => count($result), 'imported' => $inserteded);
            }
	    
	    $this->settings->update_settings(array('proxy_searching_status' => serialize($status)));
	    F::mutex_lock("run_proxy_searching", true);
        }
	
	public function request($url, $options = array()) {
	    $result = array();
	    $cookieName = "";
	    if (isset($options['cookie'])) {
		$cookieName = strtr(ROOT_DIR_SERVICE . ("/cookies/{$options['cookie']}.txt"), "\\", "/");
		if (!isset($options['flush_cookie']))
		    $options[CURLOPT_COOKIEFILE] = $cookieName;
		else {
		    unset($options['flush_cookie']);
		    @unlink($cookieName);
		}

		$options[CURLOPT_COOKIEJAR] = $cookieName;
		if (!file_exists($cookieName))
		    fclose(fopen($cookieName, 'a+'));

		unset($options['cookie']);
	    }

	    if(!isset($options[CURLOPT_USERAGENT]))
		$options[CURLOPT_USERAGENT] = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:39.0) Gecko/20100101 Firefox/39.0';

	    $options[CURLOPT_SSL_VERIFYPEER] = false;
	    $options[CURLOPT_SSL_VERIFYHOST] = false;

	    if(!isset($options[CURLOPT_CONNECTTIMEOUT]))
		$options[CURLOPT_CONNECTTIMEOUT] = 30;
	    if(!isset($options[CURLOPT_TIMEOUT]))
		$options[CURLOPT_TIMEOUT] = 120;
	    
	    if (!isset($options['do_not_follow_location'])) {
		$options[CURLOPT_FOLLOWLOCATION] = true;
		$options[CURLOPT_AUTOREFERER] = true;
	    } else
		unset($options['do_not_follow_location']);

	    if (isset($options['referer'])) {
		$options[CURLOPT_REFERER] = $options['referer'];
		unset($options['referer']);
	    }

	    $encoding = "";
	    if (isset($options['encoding'])) {
		$encoding = $options['encoding'];
		unset($options['encoding']);
	    }
	    $method = "GET";
	    $post_data = null;
	    if(isset($options['post_data'])){
		    $method = "POST";
		    $post_data = $options['post_data'];
		    if($encoding){
			$new_post_data = array();
			foreach($post_data as $key => $val)
			    $new_post_data[iconv('utf-8', $encoding, $key)] = iconv('utf-8', $encoding, $val);
			$post_data = $new_post_data;
		    }
		    unset($options['post_data']);
	    }

	    $headers = array();
	    if(isset($options['headers'])){
		    $headers = $options['headers'];
		    foreach($headers as $name => &$val){
			$val = "$name: $val";
		    }
		    unset($options['headers']);
	    }
	    $headers[] = "Expect:  ";

	    $is_html_parse = false;
	    if(isset($options['html'])){
		$is_html_parse = $options['html'];
		unset($options['html']);
	    }

	    $this->curl->request($url, $method, $post_data, $headers, $options);
	    $response = $this->curl->execute();

	    if ($encoding) {
		$response = iconv($encoding, 'utf-8//IGNORE', $response);
	    }

	    if ($is_html_parse) {
		$response = str_get_html($response);
	    }

	    $result['response'] = $response;
	    return $result;
    }

    public function get($url, $options = array()) {
	if (isset($options['post_data']))
	    unset($options['post_data']);
	$res = $this->request($url, $options);
	return $res['response'];
    }

    public function post($url, $options = array()) {
	if (!isset($options['post_data']))
	    $options['post_data'] = array('fict' => 1);
	$res = $this->request($url, $options);
	return $res['response'];
    }
    
    public $destinations = array(
	0 => array('url' => 'http://vk.com', 'str' => '<meta name="description" content="ВКонтакте – универсальное средство'),
	1 => array('url' => 'http://yoip.ru', 'str' => '<link href="http://yoip.ru/css/style.css" rel="stylesheet">'),
	2 => array('url' => 'https://2ip.ru', 'str' => '<title>Узнать IP адрес</title>'),
	3 => array('url' => 'https://www.fl.ru', 'str' => '<meta name="description" lang="ru" content="FL.ru это профессиональный ресурс'),
	4 => array('url' => 'https://freelance.ru', 'str' => '<title>Удаленная работа. Фриланс вакансии, фрилансер, работа на дому'),
	5 => array('url' => 'http://www.adidas.ru', 'str' => '<meta property="og:site_name" content="adidas Россия" />'),
	6 => array('url' => 'http://www.proskater.ru', 'str' => '<meta property="og:site_name" content="Интернет-магазин http://www.proskater.ru">'),
	7 => array('url' => 'https://www.avito.ru', 'str' => '<meta name="description" content="Объявления на Avito'),
	8 => array('url' => 'http://www.kinopoisk.ru', 'str' => '<title>КиноПоиск. Все фильмы планеты</title>'),
	9 => array('url' => 'https://www.eff.org', 'str' => '<meta name="rights" content="https://www.eff.org/copyright" />'),
	10 => array('url' => 'http://www.imdb.com', 'str' => '<meta property="og:url" content="http://www.imdb.com/" />'),
	11 => array('url' => 'http://www.dailymail.co.uk/home/index.html', 'str' => '<meta name="description" content="MailOnline - all the latest news'),
	12 => array('url' => 'http://weas-robotics.ru', 'str' => '<link rel="canonical" href="http://weas-robotics.ru/" />'),
	13 => array('url' => 'http://grishinrobotics.com', 'str' => '<title>Grishin Robotics | Investment Company</title>'),
	14 => array('url' => 'http://www.ibm.com/en-us/homepage-a.html', 'str' => '<title>IBM - United States</title>'),
	15 => array('url' => 'http://www.intel.com/content/www/us/en/homepage.html', 'str' => '<meta name="description" content="Intel designs and builds the essential technologies'),
	16 => array('url' => 'http://www.intelsecurity.com', 'str' => '<meta name="keywords" content="Intel Security, McAfee'),
	17 => array('url' => 'https://www.quantcast.com', 'str' => 'Advertise | Quantcast'),
	18 => array('url' => 'http://corp.tlscontact.com', 'str' => '<meta name="keywords" content="TLScontact, visa application center')
    );
}
