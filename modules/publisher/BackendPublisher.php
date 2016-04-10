<?php

class BackendPublisher extends View {
	public function index() {
            
            return $this->fl_auto_reply();
	}

	public function fl_auto_reply($parsing = false){
            $this->admins->check_access_module('publisher');
            
            /* PARSING PROCEDURE */
            
            if($parsing){
               return $this->publisher->fl_auto_reply_parser();
            }
            
            /* END PARSING PROCEDURE */
            
            if($this->request->get('flag')){
               $disabled = !(bool) $this->request->get('enabled', 'integer');
               $this->settings->update_settings(array("is_fl_auto_reply_disabled" => $disabled));
               $this->settings->update_settings(array("is_fl_auto_reply_status" => ''));
               $this->settings->update_settings(array("is_fl_auto_reply_recommended_next_query_time" => ''));
            }
            
            if($this->request->post('post_flag')){
                $settings['login'] = $this->request->post('fl_login', 'string');
                $settings['pass'] = $this->request->post('fl_pass');
                $settings['parsing_deep'] = $this->request->post('parsing_deep', 'integer');
                $settings['updating_interval'] = $this->request->post('updating_interval', 'integer');
                $titles = (array) $this->request->post('title');
                $templates = (array) $this->request->post('template');
                $regexps = (array) $this->request->post('regexp');
                $a_regexps =  (array) $this->request->post('a_regexp');
                $specs = (array) $this->request->post('spec');
                $test_modes = (array) $this->request->post('test_mode');
                $n_template = $this->request->post('n_template');
                $n_title = $this->request->post('n_title');
                $n_regexp = $this->request->post('n_regexp');
                $n_a_regexp = $this->request->post('n_a_regexp');
                $n_spec = $this->request->post('n_spec');
                $n_test_mode = $this->request->post('n_test_mode', 'integer');
                
                $settings['answers'] = array();
                if($templates)
                    foreach($templates as $key => $template){
                        if(!trim($template) or !trim($titles[$key]))
                            continue;
                        $answer['template'] = $template;
                        $answer['title'] = $titles[$key];
                        $answer['regexp'] = $regexps[$key];
                        $answer['a_regexp'] = $a_regexps[$key];
                        $answer['spec'] = isset($specs[$key])?$specs[$key]:array();
                        $answer['test_mode'] = isset($test_modes[$key])?$test_modes[$key]:false;
                        $settings['answers'][] = $answer;
                    }
               
                if(trim($n_title) and trim($n_template)){
                    $answer['template'] = $n_template;
                    $answer['title'] = $n_title;
                    $answer['regexp'] = $n_regexp;
                    $answer['a_regexp'] = $n_a_regexp;
                    $answer['spec'] = $n_spec;
                    $answer['test_mode'] = $n_test_mode;
                    $settings['answers'][] = $answer;
                    
                }
                
                $this->settings->update_settings(array('fl_auto_reply_settings' => serialize($settings)));
                $this->settings->update_settings(array("is_fl_auto_reply_status" => ''));
                $this->settings->update_settings(array("is_fl_auto_reply_recommended_next_query_time" => ''));
            }
            
            @$settings = unserialize($this->settings->fl_auto_reply_settings);
            $specializations = array(
                1 => "Менеджмент",
                2 => "Разработка сайтов",
                3 => "Дизайн и Арт",
                5 => "Программирование",
                6 => "Оптимизация (SEO)",
                7 => "Переводы",
                8 => "Тексты",
                9 => "3D Графика",
                10 => "Фотография",
                11 => "Аудио/Видео",
                12 => "Реклама и Маркетинг",
                13 => "Аутсорсинг и консалтинг",
                14 => "Архитектура/Интерьер",
                16 => "Разработка игр",
                17 => "Полиграфия",
                19 => "Анимация и флеш",
                20 => "Инжиниринг",
                22 => "Обучение и консультации",
                23 => "Мобильные приложения",
                24 => "Сети и информационные системы",
            );
            
            $this->tpl->add_var('enabled', !$this->settings->is_fl_auto_reply_disabled);
            $this->tpl->add_var('status', @unserialize($this->settings->is_fl_auto_reply_status));
            $this->tpl->add_var('settings', $settings);
            $this->tpl->add_var('specializations', $specializations);
            return $this->tpl->fetch('publisher_fl_auto_reply');
        }
        
        public function proxy_list(){
             $this->admins->check_access_module('publisher');
            
             if($this->request->method('post') && !empty($_POST)) {
			$slide_name = $this->request->post('server_name', "array");
		
			if(is_array($slide_name) and count($slide_name)>0) {
				/**
				 * обновляем список
				 */
				$i=1;
				foreach($slide_name as $up_slide_id=>$up_slide_name) {
					$up_slide_id = intval($up_slide_id);
					if($up_slide_id>0 ) {
						$this->servers->update($up_slide_id, array("sort"=>$i));
					}
					$i++;
				}
			}
		
			/**
			 * если загрузка аяксом и не было добавления возвращаем только 1 в ответе, чтобы обновилась только кнопка сохранения
			 */
			if($this->request->isAJAX()) return 1;
		}
             
             $date_to = intval(strtotime($this->request->get('date_to', 'string')));
             $enabled = $this->request->get('enabled', 'integer');
             $level = $this->request->get('anonymous', 'integer');
             $country = $this->request->get('country', 'string');
	     $date_from = intval(strtotime($this->request->get('date_from', 'string')));
             $paging_added_query = "&action=proxy_list&date_to=".$date_to.'&date_from='.$date_from.'&enabled='.$enabled.'&country='.$country.'&level='.$level;
	     $link_added_query = "&date_to=".$date_to.'&date_from='.$date_from.'&enabled='.$enabled;
             
             // Постраничная навигация
	     $limit = ($tmpVar = 50) ? $tmpVar : 10;
	     // Текущая страница в постраничном выводе
		$p = $this->request->get('p', 'integer');
		// Если не задана, то равна 1
		$p = max(1, $p);
		$link_added_query .= "&p=".$p;

		$filter["limit"] = array($p, $limit);
                
                if($date_to or $date_from)
		{
			$filter['date_to'] = $date_to;
			$filter['date_from'] = $date_from;
		}
                
                if($enabled){
                    $filter['enabled'] = $enabled - 1;
                }
                
                if($level){
                    $filter['level'] = $level;
                }
                
                if($country){
                    $filter['country'] = $country;
                }
                
		//Вычисляем количество страниц
		$proxy_count = intval($this->publisher->get_count_proxies($filter));
		$total_pages_num = ceil($proxy_count/$limit);

		$list_proxies = $this->publisher->get_list_proxies( $filter  );
		
		$this->tpl->add_var('proxy_count', $proxy_count);
		$this->tpl->add_var('total_pages_num', $total_pages_num);
		$this->tpl->add_var('p', $p);
		$this->tpl->add_var('paging_added_query', $paging_added_query);
                $this->tpl->add_var('link_added_query', $link_added_query);
		$this->tpl->add_var('list_proxies', $list_proxies);
                $this->tpl->add_var('date_to', $date_to);
                $this->tpl->add_var('enabled', $enabled);
                $this->tpl->add_var('level', $level);
                $this->tpl->add_var('country', $country);
		$this->tpl->add_var('date_from', $date_from);
		
		return $this->tpl->fetch('publisher_proxy_list'); 
        }
        
	public function edit_proxy() {
		$this->admins->check_access_module('publisher', 2);

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
                        $server['sort'] = $this->request->post('sort', 'integer');
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
					$this->publisher->update_proxy($server_id, $server);
				}
				else {
					$server_id = (int)$this->publisher->add_proxy($server);
                                        $server['id'] = $server_id;
                                        $may_noupdate_form = false;
				}

				/**
				 * если было нажата кнопка Сохранить и выйти, перекидываем на список страниц
				 */
				if($after_exit and count($errors)==0) {
					header("Location: ".DIR_ADMIN."?module=publisher&action=proxy_list");
					exit();
				}
				/**
				 * если загрузка аяксом возвращаем только 1 в ответе, чтобы обновилась только кнопка сохранения
				 */
				elseif($this->request->isAJAX() and count($errors)==0 and $server['id'] and $may_noupdate_form) return 1;
			}
		}

		
		if($server_id) {
			$server = $this->publisher->get_proxy($server_id);
			
			if(count($server)==0) {
				header("Location: ".DIR_ADMIN."?module=publisher&action=proxy_list");
				exit();
			}
		}
		else {
			$server['sort'] = $this->publisher->get_new_proxy_sort();
		}
		
		$this->tpl->add_var('errors', $errors);
		$this->tpl->add_var('server', $server);
		$this->tpl->add_var('tab_active', $tab_active);
		return $this->tpl->fetch('publisher_proxy_add');
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
		$this->admins->check_access_module('publisher', 2);
	
		$id = $this->request->get("id", "integer");
		if($id>0) $this->publisher->delete_proxy($id);
		return $this->proxy_list();
	}
        
        public function proxy_import(){
		$this->admins->check_access_module('publisher');

                
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
                                                                      
									if($this->publisher->get_familiar_proxy($product));
                                                                        
                                                                        else{
                                                                           
                                                                            $this->publisher->add_proxy($product);
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
		return $this->tpl->fetch('publisher_proxy_import');
	}
	
        public function proxy_scaner(){
            $this->admins->check_access_module('publisher');
            
            if($this->request->get('flag')){
               $disabled = !(bool) $this->request->get('enabled', 'integer');
               $this->settings->update_settings(array("proxy_scaner_disabled" => $disabled));
               $this->settings->update_settings(array("proxy_scaner_status" => ''));
               $this->settings->update_settings(array("proxy_scaner_recommended_next_query_time" => ''));
            }
            
            if($this->request->post('post_flag')){
                $settings['interval'] = $this->request->post('interval', 'integer');
                $settings['import_interval'] = $this->request->post('import_interval', 'integer');
                $this->settings->update_settings(array('proxy_scaner_settings' => serialize($settings)));
                $this->settings->update_settings(array("proxy_scaner_status" => ''));
                $this->settings->update_settings(array("proxy_scaner_recommended_next_query_time" => ''));
            }
            
            @$settings = unserialize($this->settings->proxy_scaner_settings);
            
            $this->tpl->add_var('enabled', !$this->settings->proxy_scaner_disabled);
            $this->tpl->add_var('status', @unserialize($this->settings->proxy_scaner_status));
            $this->tpl->add_var('settings', $settings);
            return $this->tpl->fetch('publisher_proxy_scaner');
        }
}