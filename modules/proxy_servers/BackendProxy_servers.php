<?php

class BackendProxy_servers extends View {
   
    public function index() {
	$this->admins->check_access_module('proxy_servers');

	$date_to = intval(strtotime($this->request->get('date_to', 'string')));
	$enabled = $this->request->get('enabled', 'integer');
	$levels = (array) $this->request->get('anonymous');
	$types = (array) $this->request->get('types');
	$country = $this->request->get('country', 'string');
	$date_from = intval(strtotime($this->request->get('date_from', 'string')));
	$uptime = $this->request->get('uptime', 'double');
	$private = $this->request->get('private', 'integer');
	
	$levels_str = "";
	if($levels){
	    foreach($levels as $level){
		$levels_str .= ('&anonymous[]='.$level);
	    }
	}
	$types_str = "";
	if($types){
	    foreach($types as $level){
		$types_str .= ('&types[]='.$level);
	    }
	}
	$sort_by = $this->request->get("sort_by", "string");
	$sort_dir = $this->request->get("sort_dir", "string");

	if($sort_by){
	    if(!$sort_by or !in_array($sort_by, array("last_checking", "uptime", "id")) ) $sort_by = "last_checking";
	    if(!$sort_dir or !in_array($sort_dir, array("asc", "desc")) ) $sort_dir = "desc";
	}
	
	
	$filter_query = "&date_to=" . $date_to . '&date_from=' . $date_from . '&enabled=' . $enabled . '&country=' . $country . $levels_str.'&uptime='.$uptime.'&private='.$private .$types_str;
	$paging_added_query = "&action=index".$filter_query."&sort_by=".$sort_by."&sort_dir=".$sort_dir;
	$link_added_query = "&action=index".$filter_query;

	/**
	 * действия с группами свойств
	 */
	$items = $this->request->post("check_item", "array");
	if (is_array($items) and count($items) > 0 and $this->request->post("group_actions", "integer")) {
	    $items = array_map("intval", $items);
	    switch ($this->request->post("do_active", "string")) {
		case "hide":
		    $this->proxy_servers->update($items, array("enabled" => 0));
		    break;
		case "show":
		    $this->proxy_servers->update($items, array("enabled" => 1));
		    break;
		case "delete":
		    foreach ($items as $id) {
			if ($id > 0)
			    $this->proxy_servers->delete($id);
		    }
		    break;
	    }
	}

	// Постраничная навигация
	$limit = ($tmpVar = 50) ? $tmpVar : 10;
	// Текущая страница в постраничном выводе
	$p = $this->request->get('p', 'integer');
	// Если не задана, то равна 1
	$p = max(1, $p);
	$link_added_query .= "&p=" . $p;

	$filter = array();
	if($sort_by)
	    $filter = array("sort"=> array($sort_by, $sort_dir));
	$filter["limit"] = array($p, $limit);

	if ($date_to or $date_from) {
	    $filter['date_to'] = $date_to;
	    $filter['date_from'] = $date_from;
	}
	if ($enabled) {
	    $filter['enabled'] = $enabled - 1;
	}
	if ($private) {
	    $filter['private'] = $private - 1;
	}
	if ($levels) {
	    $filter['levels'] = $levels;
	}
	if ($uptime) {
	    $filter['uptime'] = $uptime;
	}
	if ($types) {
	    $filter['levels'] = $types;
	}
	
	if ($country) {
	    $filter['countries'] = explode(",", $country);
	}

	//Вычисляем количество страниц
	$proxy_count = intval($this->proxy_servers->get_count_proxy_servers($filter));
	$total_pages_num = ceil($proxy_count / $limit);

	$list_proxies = $this->proxy_servers->get_list_proxy_servers($filter);

	$this->tpl->add_var('proxy_count', $proxy_count);
	$this->tpl->add_var('total_pages_num', $total_pages_num);
	$this->tpl->add_var('p', $p);
	$this->tpl->add_var('paging_added_query', $paging_added_query);
	$this->tpl->add_var('link_added_query', $link_added_query);
	$this->tpl->add_var('list_proxies', $list_proxies);
	$this->tpl->add_var('date_to', $date_to);
	$this->tpl->add_var('enabled', $enabled);
	$this->tpl->add_var('levels', $levels);
	$this->tpl->add_var('types', $types);
	$this->tpl->add_var('country', $country);
	$this->tpl->add_var('uptime', $uptime);
	$this->tpl->add_var('private', $private);
	$this->tpl->add_var('date_from', $date_from);
	$this->tpl->add_var('sort_by', $sort_by);
	$this->tpl->add_var('sort_dir', $sort_dir);
	$this->tpl->add_var('filter_query', $filter_query);
	
	return $this->tpl->fetch('proxy_servers');
    }

    public function edit_proxy() {
		$this->admins->check_access_module('proxy_servers', 2);

		//возможность не перезагружать форму при запросах аяксом, но если это необходимо, например, загружена картинка - обновляем форму
		$may_noupdate_form = true;

		$method = $this->request->method();
		$server_id = $this->request->$method("id", "integer");
		$tab_active = $this->request->$method("tab_active", "string");
		if(!$tab_active) $tab_active = "main";
		
		/**
		 * ошибки при заполнении формы
		 */
		$errors = array();

		$server = array("id"=>$server_id, "enabled"=>1);

		if($this->request->method('post') && !empty($_POST)) {
			$server['alt_name'] = $this->request->post('alt_name', 'string');
                        $server['ip'] = $this->request->post('ip', 'string');
                        $server['port'] = $this->request->post('port', 'string');
                        $server['type_http'] = $this->request->post('type_http', 'integer');
                        $server['type_https'] = $this->request->post('type_https', 'integer');
                        $server['type_socks4'] = $this->request->post('type_socks4', 'integer');
                        $server['type_socks5'] = $this->request->post('type_socks5', 'integer');
                        $server['anonymous'] = $this->request->post('anonymous', 'integer');
                        $server['login'] = $this->request->post('login', 'string');
                        $server['password'] = $this->request->post('password', 'string');
                        $server['enabled'] = $this->request->post('enabled', 'string');
                        $server['country'] = $this->request->post('country', 'string');
                        $server['blocked_in_mail'] = $this->request->post('blocked_in_mail', 'integer');
                        $server['blocked_in_gmail'] = $this->request->post('blocked_in_gmail', 'integer');
                        $server['blocked_in_yandex'] = $this->request->post('blocked_in_yandex', 'integer');
                        $server['blocked_in_rambler'] = $this->request->post('blocked_in_rambler', 'integer');
                        $server['blocked_in_yahoo'] = $this->request->post('blocked_in_yahoo', 'integer');
                        $server['do_not_delete'] = $this->request->post('do_not_delete', 'integer');
                        
                        if($server['type_https'])
                            $server['type_http'] = 1;
                        if($server['type_socks4']){
                            $server['type_http'] = 1;
                            $server['type_https'] = 1;
                        }
                        if($server['type_socks5']){
                            $server['type_http'] = 1;
                            $server['type_https'] = 1;
                            $server['type_socks4'] = 1;
                        }
                        
			$after_exit = $this->request->post('after_exit', "boolean");
			
			if(empty($server['ip'])) {
				$errors['ip'] = 'no_ip';
				$tab_active = "main";
			}

			if(count($errors)==0) {
				if($server_id) {
					$this->proxy_servers->update($server_id, $server);
				}
				else {
					$server_id = (int)$this->proxy_servers->add($server);
                                        $server['id'] = $server_id;
                                        $may_noupdate_form = false;
				}

				/**
				 * если было нажата кнопка Сохранить и выйти, перекидываем на список страниц
				 */
				if($after_exit and count($errors)==0) {
					header("Location: ".DIR_ADMIN."?module=proxy_servers&action=index");
					exit();
				}
				/**
				 * если загрузка аяксом возвращаем только 1 в ответе, чтобы обновилась только кнопка сохранения
				 */
				elseif($this->request->isAJAX() and count($errors)==0 and $server['id'] and $may_noupdate_form) return 1;
			}
		}
		else
		if($server_id) {
			$server = $this->proxy_servers->get_proxy_server($server_id);
			
			if(count($server)==0) {
				header("Location: ".DIR_ADMIN."?module=proxy_servers&action=index");
				exit();
			}
		}
		
		$this->tpl->add_var('errors', $errors);
		$this->tpl->add_var('server', $server);
		$this->tpl->add_var('tab_active', $tab_active);
		return $this->tpl->fetch('proxy_servers_add');
	}

	/**
	 * синоним для edit
	 */
	public function add_proxy() {
		return $this->edit_proxy();
	}
	
	/**
	 * удаление страницы
	 */
	public function delete_proxy() {
		$this->admins->check_access_module('proxy_servers', 2);
	
		$id = $this->request->get("id", "integer");
		if($id>0) $this->proxy_servers->delete($id);
		return $this->index();
	}
        
        public function proxy_import(){
		$this->admins->check_access_module('proxy_servers');

                
		$post_flag = $this->request->method('post');
		$post_continue = $this->request->post('post_continue', 'integer');
		$separator = $this->request->post('separator', 'string');
		$operation = $this->request->post('operation', 'string');
		$flag_of_complete = $this->request->post('flag_of_complete', 'integer');

		$fileName = $this->request->files('file');
		$errorMessage = "";

		if ($post_flag) {
			$separator = substr($separator, 0, 1);
			if (!$post_continue) {
				if (!$fileName = $this->file->upload_file($fileName, "csv_import.csv", null, array("csv")))
					$errorMessage = "Ошибка загрузки файла.";
			}
			else if (!$fileName = $this->request->post('file_name'))
				$errorMessage = "Внутрення ошибка";

			if (!$errorMessage) {
				$newFileName = ROOT_DIR_FILES . $fileName;
				if (!$fp = fopen($newFileName, "r"))
					$errorMessage = "Ошибка чтения файла.";
				else {
					$csvArray = array();
					while ($readData = fgetcsv($fp, 0, $separator)) {
						foreach ($readData as $key => $val)
							$readData[$key] = iconv('windows-1251', 'utf-8', $readData[$key]);

						$csvArray[] = $readData;
					}
					fclose($fp);

					$countCsvArray = count($csvArray) - 1;

					if ($countCsvArray) {
						$uploadedFields = $csvArray[0];
						$listFields = array(
                                                                    'ip' => 'IP', 
                                                                    'port' => "Порт", 
                                                                    "type_http" => 'HTTP',
                                                                    "type_https" => "HTTPS", 
                                                                    "type_socks4" => "SOCKS4", 
                                                                    'type_socks5' => "SOCKS5", 
                                                                    'anonymous' => "Уровень",    
                                                                    "login" => "Логин",
                                                                    "password" => "Пароль",
                                                                    "country" => "Страна"
                                                    );

                                                
						if (!$post_continue) {
							$this->tpl->add_var('listFields', $listFields);
							$this->tpl->add_var('uploadedFields', $uploadedFields);
							$this->tpl->add_var('fileName', $fileName);
							$this->tpl->add_var('separator', $separator);
						} else {
							@unlink($newFileName);

							$countListFields = count($listFields);
							$oldAssociation = array();

							foreach ($uploadedFields as $key => $val) {
								$association = $this->request->post("fieldAssoc_" . $key);
								if (array_search($association, $oldAssociation) === false) {

									$convertedAssoc = array_search($association, $listFields);
									if ($convertedAssoc == 'ignore')
										continue;
									if (!$convertedAssoc)
										continue;

									$oldAssociation[$key] = $association;
								} else
									continue;
							}

							//field transformation
							foreach ($oldAssociation as $key => $val) {
								if (($newVal = array_search($val, $listFields)) !== false)
									$oldAssociation[$key] = $newVal;
							}


							if ($operation != 'add')
								$errorMessage = "Выбрана недопустимая операция";
							else {

								function assoc($field, &$oldAssociation, &$uploadProduct) {
									if (($key = array_search($field, $oldAssociation)) === false)
										return null;
									else
										return $uploadProduct[$key];
								}

								$successWritings = 0;
								//Добавление/обновление
								for ($count = 1; $count <= $countCsvArray; $count++) {
									$uploadProduct = $csvArray[$count];
									$product = array();
                                                                        
									if (($key = array_search('alt_name', $oldAssociation)) !== false)
										$product = array_merge($product, array('alt_name' => $this->request->get_str($uploadProduct[$key], 'string')));
									if (($key = array_search('ip', $oldAssociation)) !== false)
										$product = array_merge($product, array('ip' => $this->request->get_str($uploadProduct[$key], 'string')));
                                                                        if (($key = array_search('port', $oldAssociation)) !== false)
										$product = array_merge($product, array('port' => $this->request->get_str($uploadProduct[$key], 'string')));
                                                                        if (($key = array_search('type_http', $oldAssociation)) !== false)
										$product = array_merge($product, array('type_http' => $this->request->get_str($uploadProduct[$key], 'integer')));
                                                                        if (($key = array_search('type_https', $oldAssociation)) !== false)
										$product = array_merge($product, array('type_https' => $this->request->get_str($uploadProduct[$key], 'integer')));
                                                                        if (($key = array_search('type_socks4', $oldAssociation)) !== false)
										$product = array_merge($product, array('type_socks4' => $this->request->get_str($uploadProduct[$key], 'integer')));
                                                                        if (($key = array_search('anonymous', $oldAssociation)) !== false)
										$product = array_merge($product, array('anonymous' => $this->request->get_str($uploadProduct[$key], 'integer')));
                                                                        if (($key = array_search('login', $oldAssociation)) !== false)
										$product = array_merge($product, array('login' => $this->request->get_str($uploadProduct[$key], 'string')));
                                                                        if (($key = array_search('password', $oldAssociation)) !== false)
										$product = array_merge($product, array('password' => $this->request->get_str($uploadProduct[$key], 'string')));
                                                                        if (($key = array_search('country', $oldAssociation)) !== false)
										$product = array_merge($product, array('country' => $this->request->get_str($uploadProduct[$key], 'string')));
                                                                      
									if($this->proxy_servers->get_familiar_proxy($product));
                                                                        
                                                                        else{
                                                                           
                                                                            $this->proxy_servers->add($product);
                                                                            $successWritings++;
                                                                        }
								}
								$ignoredWritings = $countCsvArray - $successWritings;
								$this->tpl->add_var('successWritings', $successWritings);
								$this->tpl->add_var('ignoredWritings', $ignoredWritings);
							}
						}
					}
				}

				if ($flag_of_complete)
					$errorMessage = $post_flag = false;

		
			}
		}
                
		$this->tpl->add_var('errorMessage', $errorMessage);
		$this->tpl->add_var('post_flag', $post_flag);
		$this->tpl->add_var('operation', $operation);
		$this->tpl->add_var('post_continue', $post_continue);
		return $this->tpl->fetch('proxy_servers_import');
	}
	
        public function proxy_scaner(){
            $this->admins->check_access_module('proxy_servers');
            
            if($this->request->get('flag')){
	       $settings = array();
               $disabled = !(bool) $this->request->get('enabled', 'integer');
	       $settings['proxy_scaner_disabled'] = $disabled;
	       $settings['proxy_scaner_status'] = '';
	       $settings['proxy_searching_status'] = '';
	       $settings['proxy_scaner_recommended_next_query_time'] = 0;
	       $this->settings->update_settings($settings);
            }
            
            if($this->request->post('post_flag')){
		$settings = array();
                $s['interval'] = $this->request->post('interval', 'integer');
                $s['import_interval'] = $this->request->post('import_interval', 'integer');
		$s['proxy_scaner_threads'] = $this->request->post('proxy_scaner_threads', 'integer');
		$settings['proxy_scaner_settings'] = serialize($s);
		$settings['proxy_searching_status'] = '';
		$settings['proxy_scaner_status'] = '';
		$settings['proxy_scaner_recommended_next_query_time'] = 0;
	        $this->settings->update_settings($settings);
            }
            
            @$settings = unserialize($this->settings->proxy_scaner_settings);
            
            $this->tpl->add_var('enabled', !$this->settings->proxy_scaner_disabled);
            $this->tpl->add_var('scaner_status', @unserialize($this->settings->proxy_scaner_status));
	    $this->tpl->add_var('searching_status', @unserialize($this->settings->proxy_searching_status));
            $this->tpl->add_var('settings', $settings);
            return $this->tpl->fetch('proxy_servers_scaner');
        }
	
	public function test_proxy_source(){
	    $source = 'cool-proxy.net';
	    $this->proxy_servers->search_proxies($source);
	    echo 'ok';
	    exit;
	}
	
	public function test_proxy_checking(){
	    $this->proxy_servers->check_proxy_now(null, null, false);
	    exit;
	}
}