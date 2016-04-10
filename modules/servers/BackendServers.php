<?php
/**
 * класс отображения услуг в административной части сайта
 * @author riol
 *
 */

class BackendServers extends View {
	public function index() {
		$this->admins->check_access_module('servers');	
			
		/**
		 * действия с группами свойств
		 */
		$items = $this->request->post("check_item", "array");
		if(is_array($items) and count($items)>0 and $this->request->post("group_actions", "integer")) {
			$items = array_map("intval", $items);
			switch($this->request->post("do_active", "string")) {
				case "hide":
					$this->servers->update($items, array("enabled"=>0));
					break;
				case "show":
					$this->servers->update($items, array("enabled"=>1));
					break;
				case "delete":
					foreach($items as $id) {
						if($id>0) $this->servers->delete($id);
					}
					break;
			}
		}
                
		elseif($this->request->method('post') && !empty($_POST)) {
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
		

		$sort_by = $this->request->get("sort_by", "string");
		$sort_dir = $this->request->get("sort_dir", "string");

		if(!$sort_by or !in_array($sort_by, array("title", "date_add", "enabled")) ) $sort_by = "date_add";
		if(!$sort_dir or !in_array($sort_dir, array("asc", "desc")) ) $sort_dir = "desc";
                $login = $this->request->get('login', 'string');

		$paging_added_query = "&action=index&sort_by=".$sort_by."&sort_dir=".$sort_dir.'&login='.$login;
		$link_added_query = "&sort_by=".$sort_by."&sort_dir=".$sort_dir.'&login='.$login;

		// Постраничная навигация
		$limit = ($tmpVar = 30) ? $tmpVar : 10;
		// Текущая страница в постраничном выводе
		$p = $this->request->get('p', 'integer');
		// Если не задана, то равна 1
		$p = max(1, $p);
		$link_added_query .= "&p=".$p;

		$filter = array("sort"=> array($sort_by, $sort_dir));

		$filter["limit"] = array($p, $limit);
                if($login){
                    $filter['login'] = $login;
                }
                
		// Вычисляем количество страниц
		$servers_count = intval($this->servers->get_count_servers($filter));
		$total_pages_num = ceil($servers_count/$limit);

		$list_servers = $this->servers->get_list_servers( $filter  );
		
                $this->tpl->add_var('login', $login);
                $this->tpl->add_var('sort_by', $sort_by);
		$this->tpl->add_var('sort_dir', $sort_dir);
		$this->tpl->add_var('servers_count', $servers_count);
		$this->tpl->add_var('total_pages_num', $total_pages_num);
		$this->tpl->add_var('p', $p);
		$this->tpl->add_var('paging_added_query', $paging_added_query);
		$this->tpl->add_var('link_added_query', $link_added_query);
		$this->tpl->add_var('list_servers', $list_servers);
		
		return $this->tpl->fetch('servers');
	}

	/**
	 * редактирование/добавлние новости
	 */
	public function edit() {
		$this->admins->check_access_module('servers', 2);

		//возможность не перезагружать форму при запросах аяксом, но если это необходимо, например, загружена картинка - обновляем форму
		$may_noupdate_form = true;

		$method = $this->request->method();
		$server_id = $this->request->$method("id", "integer");
		$tab_active = $this->request->$method("tab_active", "string");
		if(!$tab_active) $tab_active = "main";
		$from_revision = $this->request->get("from_revision", "integer");
		

		/**
		 * ошибки при заполнении формы
		 */
		$errors = array();

		$server = array("id"=>$server_id, "enabled"=>1);

		if($this->request->method('post') && !empty($_POST)) {
			$server['host'] = $this->request->post('host', 'string');
			$server['sort'] = $this->request->post('sort', 'integer');
                        $server['login'] = $this->request->post('login', 'string');
                        $server['secure'] = mb_strtolower($this->request->post('secure', 'string'));
			$server['password'] = $this->request->post('password', 'string');
			$server['enabled'] = $this->request->post('enabled', 'integer');
			$server['port'] = $this->request->post('port', 'string');
			$server['alt_name'] = $this->request->post('alt_name', 'string');
                        $server['day_limit'] = $this->request->post('day_limit', 'integer');
                        if(!$server['day_limit'])
                            $server['day_limit'] = 300;
                        $server['spam_in_mail'] = $this->request->post('spam_in_mail', 'integer');
                        $server['spam_in_gmail'] = $this->request->post('spam_in_gmail', 'integer');
                        $server['spam_in_yandex'] = $this->request->post('spam_in_yandex', 'integer');
                        $server['spam_in_rambler'] = $this->request->post('spam_in_rambler', 'integer');
                        $server['spam_in_yahoo'] = $this->request->post('spam_in_yahoo', 'integer');
			$after_exit = $this->request->post('after_exit', "boolean");
			
			if(empty($server['host'])) {
				$errors['host'] = 'no_host';
				$tab_active = "main";
			}

			if(count($errors)==0) {

				if($server_id) {
					$this->servers->add_revision($server_id);
					$this->servers->update($server_id, $server);
				}
				else {
					$server_id = (int)$this->servers->add($server);
                                        $server['id'] = $server_id;
                                        $may_noupdate_form = false;
				}

				if($server_id) {
				//Загрузка изображений
					if($picture = $this->request->files('picture'))
					{
						if(isset($picture['error']) and $picture['error']!=0) {
							$errors['photo'] = 'error_size';
							$tab_active = "main";
						}
						else {
							if ($image_name = $this->image->upload_image($picture, $picture['name'], $this->servers->setting("dir_images")))
							{
								$image_id = $this->servers->add_image($server_id, $image_name);
								if(!$image_id) {
									$errors['photo'] = 'error_internal';
									$tab_active = "main";
								}
							}
							else
							{
								if($image_name===false) $errors['photo'] = 'error_type';
								else $errors['photo'] = 'error_upload';
								$tab_active = "main";
							}
						}
						$may_noupdate_form = false;
					}
									
				}

				/**
				 * если было нажата кнопка Сохранить и выйти, перекидываем на список страниц
				 */
				if($after_exit and count($errors)==0) {
					header("Location: ".DIR_ADMIN."?module=servers");
					exit();
				}
				/**
				 * если загрузка аяксом возвращаем только 1 в ответе, чтобы обновилась только кнопка сохранения
				 */
				elseif($this->request->isAJAX() and count($errors)==0 and $server['id'] and $may_noupdate_form) return 1;
			}
		}

		
		if($server_id) {
			if($from_revision) {
				$server = $this->servers->get_from_revision($from_revision, $server_id);
				if(isset($server['img']) and $server['img']!='') {
					if(!file_exists(ROOT_DIR_IMAGES.$this->servers->setting("dir_images")."normal/".$server['img'])) $server['img'] = "";
				}
			}
			else {
				$server = $this->servers->get_server($server_id);
			}
			if(count($server)==0) {
				header("Location: ".DIR_ADMIN."?module=servers");
				exit();
			}
			$list_revisions = $this->servers->get_list_revisions($server_id);
		}
		else {
			$server['sort'] = $this->servers->get_new_server_sort();
			$list_revisions = array();
		}
		
		$this->tpl->add_var('errors', $errors);
		$this->tpl->add_var('server', $server);
		$this->tpl->add_var('tab_active', $tab_active);
		$this->tpl->add_var('list_revisions', $list_revisions);
		$this->tpl->add_var('from_revision', $from_revision);
		$this->tpl->add_var('content_photos_for_id', $server_id);
		$this->tpl->add_var('content_photos_dir', SITE_URL.URL_IMAGES.$this->servers->setting("dir_images"));
		return $this->tpl->fetch('servers_add');
	}

	/**
	 * синоним для edit
	 */
	public function add() {
		return $this->edit();
	}
	
	/**
	 * удаление страницы
	 */
	public function delete() {
		$this->admins->check_access_module('servers', 2);
	
		$id = $this->request->get("id", "integer");
		if($id>0) $this->servers->delete($id);
		return $this->index();
	}
	
	/**
	 * создает дубликат страницы
	 * @return string
	 */
	public function duplicate() {
		$this->admins->check_access_module('servers', 2);
		$id = $this->request->get("id", "integer");
		if($id>0) $this->servers->duplicate($id);
		return $this->index();
	}
}