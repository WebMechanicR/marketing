<?php
/**
 * класс отображения услуг в административной части сайта
 * @author riol
 *
 */

class BackendEmails extends View {
	public function index() {
		$this->admins->check_access_module('emails');

		$sort_by = $this->request->get("sort_by", "string");
		$sort_dir = $this->request->get("sort_dir", "string");
		$site_link = $this->request->get("site_link", "string");
		$city = $this->request->get("city", "string");
		$send_group = $this->request->get("send_group", "string");
		$date_to = intval(strtotime($this->request->get('date_to', 'string')));
		$date_from = intval(strtotime($this->request->get('date_from', 'string')));
		$template_type = $this->request->get("org_type", "integer");

		if(!$sort_by or !in_array($sort_by, array("name", "org_type", "last_sending", "sending_count", "enabled"))) $sort_by = "sort";
		if(!$sort_dir or !in_array($sort_dir, array("asc", "desc")) ) $sort_dir = "desc";

		$link_added_query = "&sort_by=".$sort_by."&sort_dir=".$sort_dir;
		$filtres_query = "&site_link=".$site_link.'&city='.($city).'&date_to='.$date_to.'&date_from='.$date_from.'&org_type='.$template_type.'&send_group='.$send_group;
		$paging_added_query = "&action=".$this->request->get('action', 'string')."&sort_by=".$sort_by."&sort_dir=".$sort_dir.$filtres_query;

		/**
		 * действия с группами свойств
		 */
		$items = $this->request->post("check_item", "array");
		if(is_array($items) and count($items)>0 and $this->request->post("group_actions", "integer")){
			$items = array_map("intval", $items);
			switch($this->request->post("do_active", "string")) {
				case "hide":
					$this->emails->update($items, array("enabled"=>0));
					break;
				case "show":
					$this->emails->update($items, array("enabled"=>1));
					break;
				case "delete":
					foreach($items as $id) {
						if($id>0) $this->emails->delete($id);
					}
					break;

			}
		}
		elseif($this->request->method('post') && !empty($_POST)) {
			$email_name = $this->request->post('email_name', "array");

			if(is_array($email_name) and count($email_name)>0) {
				/**
				 * обновляем список
				 */
				$i=1;
				foreach($email_name as $up_email_id=>$up_email_name) {
					$up_email_id = intval($up_email_id);
					if($up_email_id>0 ) {
						$this->emails->update($up_email_id, array("sort"=>$i));
					}
					$i++;
				}
			}

			/**
			 * если загрузка аяксом и не было добавления возвращаем только 1 в ответе, чтобы обновилась только кнопка сохранения
			 */
			//if($this->request->isAJAX()) return 1;
		}

		// Постраничная навигация
		$limit = intval($this->settings->limit_admin_num);
		// Текущая страница в постраничном выводе
		$p = $this->request->get('p', 'integer');
		// Если не задана, то равна 1
		$p = max(1, $p);
		$link_added_query .= "&p=".$p;

		$admin_info = $this->admins->get_admin_info();
		$filter = array("sort"=> array($sort_by, $sort_dir));

		$filter["limit"] = array($p, $limit);
		if($date_to or $date_from)
		{
			$filter['date_to'] = $date_to;
			$filter['date_from'] = $date_from;
		}
		if($site_link!='') $filter["site_link"] = $site_link;
		if($city and $city_id = $this->emails->get_city_by_name($city)) $filter["city"] = $city_id;
		if($template_type) $filter["org_type"] = $template_type;
		if($send_group) $filter["send_group"] = $send_group;
		
		if($admin_info['access_class'] > 2)
			$filter['admin'] = $admin_info['id'];

		//Вычисляем количество страниц
		$emails_count = intval($this->emails->get_count_emails($filter));
		$total_pages_num = ceil($emails_count/$limit);

		$list_emails = $this->emails->get_list_emails( $filter );

		if($this->request->get('send_all')){
			unset($filter['limit']);
			$list_emails2 = $this->emails->get_list_emails( $filter );
			foreach($list_emails2 as $item)
				$this->emails->send($item['id']);
			$this->tpl->add_var('count_of_sent', count($list_emails2));
		}

                $this->tpl->add_var('org_types', $this->emails->get_org_types());
		$this->tpl->add_var('list_emails', $list_emails);
		$this->tpl->add_var('date_to', $date_to);
		$this->tpl->add_var('date_from', $date_from);
		$this->tpl->add_var('site_link', $site_link);
		$this->tpl->add_var('city', $city);
		$this->tpl->add_var('sort_by', $sort_by);
		$this->tpl->add_var('sort_dir', $sort_dir);
		$this->tpl->add_var('emails_count', $emails_count);
		$this->tpl->add_var('total_pages_num', $total_pages_num);
		$this->tpl->add_var('p', $p);
		$this->tpl->add_var('paging_added_query', $paging_added_query);
		$this->tpl->add_var('link_added_query', $link_added_query);
		$this->tpl->add_var('filtres_query', $filtres_query);
		$this->tpl->add_var('org_type', $template_type);
		$this->tpl->add_var('send_group', $send_group);

		return $this->tpl->fetch('emails');
	}

	/**
	 * редактирование/добавлние новости
	 */
	public function edit(){
		$this->admins->check_access_module('emails', 2);

		//возможность не перезагружать форму при запросах аяксом, но если это необходимо, например, загружена картинка - обновляем форму
		$may_noupdate_form = false;

		$method = $this->request->method();
		$email_id = $this->request->$method("id", "integer");
		$tab_active = $this->request->$method("tab_active", "string");
		if(!$tab_active) $tab_active = "main";
		$from_revision = $this->request->get("from_revision", "integer");
		$admin_info = $this->admins->get_admin_info();
		if($email_id)
		{
			$emailO = $this->emails->get_email(intval($email_id));
			if($admin_info['access_class'] > 2 and $emailO and $emailO['admin'] != $admin_info['access_class'])
				return false;
		}

		/**
		 * ошибки при заполнении формы
		 */
		$errors = array();

		$email = array("id"=>$email_id, "enabled"=>1);

		if(isset($_SESSION['last_fields'])) {
			foreach($_SESSION['last_fields'] as $last_key=>$last_val) {
				$email[$last_key] = $last_val;
			}
		}
                
		if($this->request->method('post') && !empty($_POST)) {
			$email['type'] = $this->request->post('type', 'integer');
			$email['template_type'] = $this->request->post('template_type', 'integer');
			$email['email'] = $this->request->post('email', 'string');
			$email['main_e'] = $this->request->post('main_e', 'integer');
			$email['email2'] = $this->request->post('email2', 'string');
			$email['name'] = $this->request->post('name', 'string');
			$org_types = (array) $this->request->post('org_type', 'array');
			$email['city'] = $this->emails->get_city_by_name($this->request->post('city', 'string'));
			$email['country'] = $this->emails->get_country_by_name($this->request->post('country', 'string'));
			$email['address'] = $this->request->post('address', 'string');
			$email['site_url'] = $this->request->post('site_url', 'string');
			$email['vk_link'] = $this->request->post('vk_link', 'string');
			$email['site_quality'] = $this->request->post('site_quality', 'string');
			$email['tel1'] = $this->request->post('tel1', 'string');
			$email['main_t'] = $this->request->post('main_t', 'integer');
			$email['tel2'] = $this->request->post('tel2', 'string');
			$email['tel3'] = preg_replace('/[^0-9]/', '', $this->request->post('tel3', 'string'));
			$email['tel4'] = preg_replace('/[^0-9]/', '', $this->request->post('tel4', 'string'));
			$email['chief_name'] = $this->request->post('chief_name', 'string');
			$email['org_type_rod'] = $this->request->post('org_type_rod', 'string');
			$email['source'] = $this->request->post('source', 'string');
                        $email['unsubscribed'] = $this->request->post('unsubscribed', 'integer');

			if($admin_info['access_class'] <= 2)
				$email['admin'] = $this->request->post('admin', 'integer');
			else {
				$email['admin'] = $admin_info['access_class'];
			}
			$email['comment'] = $this->request->post('comment', 'string');

			$email['sort'] = $this->request->post('sort', 'integer');

			$after_exit = $this->request->post('after_exit', "boolean");
			/*
			 if(!$email['email'] and !$email['email2']) {
			$errors['email'] = 'no_email';
			}
			*/
			if(!$email['name']) {
				$errors['name'] = 'no_name';
			}
                        
			if(!$org_types) {
				$errors['org_type'] = 'no_org_type';
			}
                        else
                            $email['org_type'] = '|'.implode('|', $org_types).'|';

			if($email['tel3'] and mb_strlen($email['tel3'])!=10) {
				$errors['tel3'] = 'err';
			}
			else {
				$email['tel3'] = F::phoneBlocks($email['tel3']);
			}
			if($email['tel4'] and mb_strlen($email['tel4'])!=10) {
				$errors['tel4'] = 'err';
			}
			else {
				$email['tel4'] = F::phoneBlocks($email['tel4']);
			}

			if(count($errors)==0) {

				if(!$email['country'] and $this->request->post('country', 'string')) {
					//добавляем новую страну
					$email['country'] = (string)  $this->emails->add_country(array('name'=>$this->request->post('country', 'string')));
				}

				if(!$email['city'] and $this->request->post('city', 'string')) {
					//добавляем новый город
					$email['city'] = (string) $this->emails->add_city(array('name'=>$this->request->post('city', 'string'), 'country_id'=>$email['country']));
				}


				//cчитаем степень готовности
				/*
				1-я  степень -  наличие названия, типа предприятия, сайта или соцсети, Е-мэйла, мобильного телефона.
				2-я  степень -  наличие названия, типа предприятия, сайта или соцсети, Е-мэйла или мобильного телефона.
				3-я степень -  наличие названия, типа предприятия, Е-мэйла, мобильного телефона.
				4-я  степень  -  неполные данные
				*/
				$email['enabled'] = 4;
				if($email['name'] and $email['org_type']) {
					if($email['email'] and $email['tel1'] and ($email['site_url'] or $email['vk_link']))
						$email['enabled'] = 1;
					elseif(($email['email'] or $email['tel3']) and ($email['site_url'] or $email['vk_link']))
					$email['enabled'] = 2;
					elseif($email['email'] and $email['tel3'])
					$email['enabled'] = 3;
				}

				if($email_id) {
					$this->emails->add_revision($email_id);
					$this->emails->update($email_id, $email);
				}
				else {
					$email_id = (int)$this->emails->add($email);
					$may_noupdate_form = false;
                                        $email['id'] = $email_id;
				}

				$_SESSION['last_fields']['template_type'] = $email['template_type'];
				$_SESSION['last_fields']['city'] = $email['city'];
				$_SESSION['last_fields']['country'] = $email['country'];

				/**
				 * если было нажата кнопка Сохранить и выйти, перекидываем на список страниц
				 */
				if($after_exit and count($errors)==0) {
					header("Location: ".DIR_ADMIN."?module=emails&action=edit");
					exit();
				}
				/**
				 * если загрузка аяксом возвращаем только 1 в ответе, чтобы обновилась только кнопка сохранения
				 */
				elseif($this->request->isAJAX() and count($errors)==0 and $email['id'] and $may_noupdate_form) return 1;
			}
		}

		else {
			if($email_id) {
				$email = $this->emails->get_email($email_id);
				if(count($email)==0) {
					header("Location: ".DIR_ADMIN."?module=emails&action=edit");
					exit();
				}
			}
			else {
				$email['sort'] = $this->emails->get_new_email_sort();
			}
		}

		$this->tpl->add_var('city_name', isset($email['city'])?$this->emails->get_city_by_id($email['city']):'');
		$this->tpl->add_var('country_name', isset($email['country'])?$this->emails->get_country_by_id($email['country']):'');
		$this->tpl->add_var('admins', $this->admins->get_admins());
		$this->tpl->add_var('org_types', $this->emails->get_org_types());
		$this->tpl->add_var('errors', $errors);
		$this->tpl->add_var('email', $email);
		$this->tpl->add_var('tab_active', $tab_active);
		$this->tpl->add_var('content_photos_for_id', $email_id);
		$this->tpl->add_var('content_photos_dir', SITE_URL.URL_IMAGES.$this->emails->setting("dir_images"));
		return $this->tpl->fetch('emails_add');
	}
        
        /**
	 * управление видами доставки
	 */
	public function org_types() {
		$this->admins->check_access_module('emails', 2);

		$del_id = $this->request->get("del_id", "integer");
		if($del_id>0) $this->emails->delete_org_type($del_id);

		if($this->request->method('post') && !empty($_POST)) {
			$new_org_type_name = $this->request->post('new_org_type_name', 'string');
			$new_org_type_sort = $this->request->post('new_org_type_sort', 'integer');

			$org_type_name = $this->request->post('org_type_name', "array");

			if(is_array($org_type_name) and count($org_type_name)>0) {
				/**
				 * обновляем список доставок
				 */
				$i=1;
				foreach($org_type_name as $up_org_type_id=>$up_org_type_name) {
					$up_org_type_name = F::clean($up_org_type_name);
					$up_org_type_id = intval($up_org_type_id);
					if($up_org_type_id>0 and !empty($up_org_type_name)) {
						$this->emails->update_org_type($up_org_type_id, array("name"=>$up_org_type_name, "sort"=>$i));
					}
					$i++;
				}
			}

			if(!empty($new_org_type_name)) {
				/**
				 * добавляем новую доставку
				 */
				$add_org_type = array("name"=>$new_org_type_name, "sort"=>$new_org_type_sort);
				$this->emails->add_org_type($add_org_type);
			}
			/**
			 * если загрузка аяксом и не было добавления возвращаем только 1 в ответе, чтобы обновилась только кнопка сохранения
			 */
			elseif($this->request->isAJAX()) return 1;
		}

		$org_types = $this->emails->get_org_types();

		$this->tpl->add_var('org_types', $org_types);
		return $this->tpl->fetch('emails_org_types');
	}

	public function preparing(){
		$this->admins->check_access_module('emails', 2);
		$method = $this->request->method();
		$tab_active = $this->request->$method("tab_active", "string");
		if(!$tab_active) $tab_active = "main";
		$errors = array();

		$id = $this->request->get('id', 'integer');
		
		$send_group = "";
		if(isset($_SESSION['last_send_group']))
			$send_group = $_SESSION['last_send_group'];

		$email = array("id"=>$id);
		if($this->request->method('post') && !empty($_POST)) {
			$email['id'] = $this->request->post('id', 'integer');
			$email['title'] = $this->request->post('title', 'string');
			$email['description'] = $this->request->post('desc');
			$email['accost'] = $this->request->post('accost', 'string');
			
			$send_group = $this->request->post('send_group', 'string');

			$after_exit = $this->request->post('after_exit', "boolean");

			if(empty($email['title'])) {
				$errors['title'] = 'no_title';
			}
			if(empty($email['accost'])) {
				$errors['accost'] = 'no_accost';
			}
			if(empty($email['description'])) {
				$errors['description'] = 'no_description';
			}

			if(count($errors)==0) {
				$this->emails->send($email['id'], $email['title'], $email['accost'], $email['description'], $send_group);
				$_SESSION['last_send_group'] = $send_group;
				return 1;
			}
		}
		
		$mail_accosts = $this->emails->get_list_mail_accosts();
		$mail_names = $this->emails->get_list_mail_names();

		$this->tpl->add_var('templates', $this->emails->get_list_emails_mail(array('enabled'=>1)));
		$this->tpl->add_var('errors', $errors);
		$this->tpl->add_var('email', $email);
		$this->tpl->add_var('mail_accosts', $mail_accosts);
		$this->tpl->add_var('mail_names', $mail_names);
		$this->tpl->add_var('tab_active', $tab_active);
		$this->tpl->add_var('send_group', $send_group);
		return $this->tpl->fetch('emails_preparing');
	}

	public function checking_in_base(){
		$this->admins->check_access_module('emails', 2);

		if($this->request->get('check', 'boolean')) {
                        $id = $this->request->get('id', 'integer');
			$criteriums['name'] = $this->request->get('name', 'string');
			$criteriums['city'] = $this->emails->get_city_by_name($this->request->get('city', 'string'));
			$criteriums['site_url'] = $this->request->get('site_url');
			if($criteriums['site_url'])
				$criteriums['site_url'] = F::url($criteriums['site_url']);
			$criteriums['vk_link'] = $this->request->get('vk_link');
			if($criteriums['vk_link'])
				$criteriums['vk_link'] = F::url($criteriums['vk_link']);
			$criteriums['tel1'] = $this->request->get('tel1', 'string');
			$criteriums['tel2'] = $this->request->get('tel2', 'string');
			$criteriums['tel3'] = $this->request->get('tel3', 'string');
			$criteriums['email'] = $this->request->get('email', 'string');
			$criteriums['email2'] = $this->request->get('email2', 'string');

			$email = $this->emails->get_email_by_criteriums($criteriums);

			$this->tpl->add_var('city_name', isset($email['city'])?$this->emails->get_city_by_id($email['city']):'');
			$this->tpl->add_var('admins', $this->admins->get_admins());
			$this->tpl->add_var('errors', array());
			$this->tpl->add_var('not_found', !$email?true:false);
                        $this->tpl->add_var('email_id', !$email?true:false);
			$this->tpl->add_var('email', $email);
			$this->tpl->add_var('tab_active', 'main');
			$this->tpl->add_var('list_revisions', array());
                        $this->tpl->add_var('org_types', $this->emails->get_org_types());
			$this->tpl->add_var('from_revision', false);
                        if($email and $email['id'] != $id){
                            $res = "";
                            foreach($email as $key=>$val)
                                $res .= ($key.': '.$val."\n");
                            return "Похожая запись \n".$res;
                        }
                        else {
                            return "Нет похожих записей! \n";
                        }
                        return $this->tpl->fetch('emails_add');
		}
		else return "&nbsp;";
	}

	public function analise(){
		$this->admins->check_access_module('emails', 2);

		$analise_id = $this->request->get('analise_id', 'integer');

		if(($managers = $this->cache->get('managers_for_adding')) === false){
			@$managers = unserialize(file_get_contents(COMMERCIAL_DB_SERVER_ADDRESS.'ajax/get_list_managers.php'));
			if(!$managers)
				$managers = array();
			$this->cache->set($managers, 'managers_for_adding', array(), 3*60*60);
		}

		if($this->request->method('post') && !empty($_POST)) {
			$email = $this->emails->get_email($this->request->post('id', 'integer'));
			$manager_id = $this->request->post('manager', 'integer');

			if($email and $manager_id){
				$url = COMMERCIAL_DB_SERVER_ADDRESS.'ajax/add_new_site.php?simple_key=87odhSA';
				$url .= ('&title='.urlencode('Сайт - '.$email['name']));
				if($email['main_e'] == 2)
					$url .= ('&email='.urlencode($email['email2']));
				else
					$url .= ('&email='.urlencode($email['email']));
					
				$url .= ('&host='.urlencode($email['site_url']));
				$url .= ('&cname='.urlencode($email['chief_name']));
				$url .= ('&org='.urlencode($email['name']));
				$url .= ('&type='.$email['type']);
				$url .= ('&template='.$email['template_type']);
				$url .= ('&manager_id='.$manager_id);
				$contacts = "";
				if($email['city'])
					$contacts .= ($this->emails->get_city_by_id($email['city']));
				if($contacts and $email['address'])
					$contacts .= (', '.$email['address']);
				else if($email['address'])
					$contacts = $email['address'];

				$url .= ('&contacts='.urlencode($contacts));
					
					
				if(file_get_contents($url) == "1")
					return 1;
				else{
					$this->tpl->add_var('error', true);
				}
			}
		}

		$list_opinions = $this->ordercall->get_list_calls( array("user_id"=>$analise_id) );
		
		$this->tpl->add_var('managers', $managers);
		$this->tpl->add_var('list_opinions', $list_opinions);
		$this->tpl->add_var('analise_id', $analise_id);
		return $this->tpl->fetch('emails_analise');
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
		$this->admins->check_access_module('emails', 2);

		$id = $this->request->get("id", "integer");
		if($id>0) $this->emails->delete($id);
		return $this->index();
	}

	/**
	 * создает дубликат страницы
	 * @return string
	 */
	public function duplicate(){
		$this->admins->check_access_module('emails', 2);
		$id = $this->request->get("id", "integer");
		if($id>0) $this->emails->duplicate($id);
		return $this->index();
	}

	public function export() {
		$this->admins->check_access_module('emails');

		$list_emails = $this->emails->get_list_emails();
		if(true)
		{
			$errorExp = false;
			//File CSV formation
			$csvArray = array();
			$fileName = tempnam(ROOT_DIR_FILES, "csv_");
			if(!($fp = fopen($fileName, "w+")))
			{
				$errorExp = true;
				$fileName = false;
			}
			else{
				$separator = ';';
				$ci = 0;
				$csvArray=array("Email", "Имя", "Организация","Статус","Тип");
				fputcsv($fp, $csvArray, $separator);

				foreach($list_emails as $email)
				{
					$csvArray = array($email['email'], $email['name'], $email['org'], System::$CONFIG['statuses'][$email['status']], System::$CONFIG['site_types'][$email['type']]);
					fputcsv($fp, $csvArray, $separator);
				}
				fclose($fp);

				define("FLASH_OFF_DEBUG", true);

				$this->wraps_off();
				$this->add_header("Content-Description: File Transfer\r\n");
				$this->add_header("Pragma: public\r\n");
				$this->add_header("Expires: 0\r\n");
				$this->add_header("Cache-Control: must-revalidate, post-check=0, pre-check=0\r\n");
				$this->add_header("Cache-Control: public\r\n");
				$this->add_header("Content-Type: text/comma-separated-values;\r\n");
				$this->add_header("Content-Disposition: attachment; filename=\"emails.csv\"\r\n");

				$file_contents = file_get_contents($fileName);
				$file_contents = iconv("utf-8", "windows-1251", $file_contents);
				@unlink($fileName);

				return $file_contents;
			}
		}
	}

	public function import(){
		$this->admins->check_access_module('emails');

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
						$listFields = array('email' => 'Email', 
                                                                    'email2' => 'Email', 
                                                                    'name' => "Клиент", 
                                                                    "tel3" => 'Мобильный 1',
                                                                    "country" => "Страна", 
                                                                    "address" => "Адрес", 
                                                                    'site_url' => "Сайт", 
                                                                    'vk_link' => "Ссылка на соц.сеть Вконтакте",    
                                                                    "tel1" => "Телефон 1",
                                                                    "chief_name" => "Имя директора"
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
									if (($key = array_search('name', $oldAssociation)) !== false)
										$product = array_merge($product, array('name' => $this->request->get_str($uploadProduct[$key], 'string')));
									if (($key = array_search('tel1', $oldAssociation)) !== false)
										$product = array_merge($product, array('tel1' => preg_replace('/[^0-9]/', '', $this->request->get_str($uploadProduct[$key], 'string'))));
                                                                        if (($key = array_search('tel3', $oldAssociation)) !== false)
										$product = array_merge($product, array('tel3' => preg_replace('/[^0-9]/', '', $this->request->get_str($uploadProduct[$key], 'string'))));
									if (($key = array_search('email', $oldAssociation)) !== false)
										$product = array_merge($product, array('email' => $this->request->get_str($uploadProduct[$key], 'string')));
									if (($key = array_search('site_quality', $oldAssociation)) !== false)
										$product = array_merge($product, array('site_quality' => $this->request->get_str($uploadProduct[$key], 'string')));
									if (($key = array_search('country', $oldAssociation)) !== false){
                                                                            $country = $this->request->get_str($uploadProduct[$key], 'string');
                                                                            if($country){
                                                                                $country = $this->emails->get_country_by_name($country);
                                                                                if(!$country)
                                                                                    $country = $this->emails->add_country(array('name'=>$country));
                                                                                $product = array_merge($product, array('country' => $country));
                                                                            }
                                                                        }
										
                                                                        if (($key = array_search('address', $oldAssociation)) !== false) {
                                                                            $city_name = substr($this->request->get_str($uploadProduct[$key], 'string'), 0, stripos($this->request->get_str($uploadProduct[$key], 'string'), ","));
                                                                            $id = $this->emails->get_city_by_name($city_name);
                                                                            
                                                                            if($id)
                                                                                $product = array_merge($product, array('city' => $id));
                                                                            $product = array_merge($product, array('address' => $this->request->get_str($uploadProduct[$key], 'string')));
                                                                        }
                                                                        if(($key = array_search('site_url', $oldAssociation)) !== false)
										$product = array_merge($product, array('site_url' => $this->request->get_str($uploadProduct[$key], 'url')));
                                                                      
									if($this->emails->get_email_by_criteriums(array('email' => $product['email'])) or (isset($product['email2'])?$this->emails->get_email_by_criteriums(array('email' => $product['email2'])):false));
                                                                        else{
                                                                            if(!isset($product['name']) or !$product['name'])
                                                                                $product['name'] = 'Неизвестно '.rand(1, 100000000);
                                                                            if(!isset($product['org_type']) or !$product['org_type'])
                                                                                $product['org_type'] = 1;
                                                                            
                                                                            $this->emails->add($product);
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
                $this->tpl->add_var('rows_count', $this->emails->get_email_t_count());
		$this->tpl->add_var('errorMessage', $errorMessage);
		$this->tpl->add_var('post_flag', $post_flag);
		$this->tpl->add_var('operation', $operation);
		$this->tpl->add_var('post_continue', $post_continue);
		return $this->tpl->fetch('emails_import');
	}

	public function status(){
		$this->admins->check_access_module('emails');
		$this->tpl->add_var('status', $this->mail->status());
		return $this->tpl->fetch('emails_sending');
	}

	public function statistics() {
		$this->admins->check_access_module('emails');

		$paging_added_query = "&action=statistics";


		// Постраничная навигация
		$limit = intval($this->settings->limit_admin_num);
		// Текущая страница в постраничном выводе
		$p = $this->request->get('p', 'integer');
		// Если не задана, то равна 1
		$p = max(1, $p);

		$filter = array("limit"=> array($p, $limit));

		// Вычисляем количество страниц
		$statistics_count = intval($this->emails->get_count_statistics($filter));
		$total_pages_num = ceil($statistics_count/$limit);

		$statistics = $this->emails->get_statistics($filter);

		$this->tpl->add_var('statistics', $statistics);
		$this->tpl->add_var('statistics_count', $statistics_count);
		$this->tpl->add_var('total_count', $this->emails->get_total_stat_count());
                $this->tpl->add_var('total_opened_count', $this->emails->get_total_opened_stat_count());
		$this->tpl->add_var('total_pages_num', $total_pages_num);
		$this->tpl->add_var('p', $p);
		$this->tpl->add_var('paging_added_query', $paging_added_query);
		return $this->tpl->fetch('emails_statistics');
	}

	public function export_statistics(){
		$this->admins->check_access_module('emails');

		$file_contents = $errorExp = false;

		$list_statistics = $this->emails->get_statistics();

		$csvFields = array(
				"date_add"=>"Дата",
                                "group_name"=>"Рассылка",
				"mails_sent"=>"Отправлено писем",
                                "opened_mails"=>"Открыто писем"
		);

		$errorExp = false;
		//File CSV formation
		$fileName = tempnam(ROOT_DIR_FILES, "csv_");
		if(!($fp = fopen($fileName, "w+")))
		{
			$errorExp = true;
			$fileName = false;
		}
		else{
			$separator = ';';
			$ci = 0;

			fputcsv($fp, $csvFields, $separator);

			foreach($list_statistics as $item)
			{
				$preparingStatistics = array();
				foreach($csvFields as $mKey => $val)
				{
					switch($mKey) {
						case "date_add":
							$preparingStatistics[] = date('d.m.y', $item[$mKey]);
							break;

						default: $preparingStatistics[] = $item[$mKey];
					}

				}

				fputcsv($fp, $preparingStatistics, $separator);
			}
			fclose($fp);

			define("FLASH_OFF_DEBUG", true);

			$this->wraps_off();
			$this->add_header("Content-Description: File Transfer\r\n");
			$this->add_header("Pragma: public\r\n");
			$this->add_header("Expires: 0\r\n");
			$this->add_header("Cache-Control: must-revalidate, post-check=0, pre-check=0\r\n");
			$this->add_header("Cache-Control: public\r\n");
			$this->add_header("Content-Type: text/comma-separated-values;\r\n");
			$this->add_header("Content-Disposition: attachment; filename=\"statistics.csv\"\r\n");

			$file_contents = file_get_contents($fileName);
			$file_contents = @iconv("utf-8", "windows-1251//TRANSLIT", $file_contents);
			@unlink($fileName);

			return $file_contents;
		}

		return $this->statistics();
	}

	public function mails() {
		$this->admins->check_access_module('emails');
			
		/**
		 * действия с группами свойств
		*/
		$items = $this->request->post("check_item", "array");
		if(is_array($items) and count($items)>0 and $this->request->post("group_actions", "integer")) {
			$items = array_map("intval", $items);
			switch($this->request->post("do_active", "string")) {
				case "hide":
					$this->emails->update_mail($items, array("enabled"=>0));
					break;
				case "show":
					$this->emails->update_mail($items, array("enabled"=>1));
					break;
				case "delete":
					foreach($items as $id) {
						if($id>0) $this->emails->delete_mail($id);
					}
					break;
			}
		}

		elseif($this->request->method('post') && !empty($_POST)) {
			$email_name = $this->request->post('email_name', "array");

			if(is_array($email_name) and count($email_name)>0) {
				/**
				 * обновляем список
				 */
				$i=1;
				foreach($email_name as $up_email_id=>$up_email_name) {
					$up_email_id = intval($up_email_id);
					if($up_email_id>0 ) {
						$this->emails->update_mail($up_email_id, array("sort"=>$i));
					}
					$i++;
				}
			}

			/**
			 * если загрузка аяксом и не было добавления возвращаем только 1 в ответе, чтобы обновилась только кнопка сохранения
			 */
			if($this->request->isAJAX()) return 1;
		}

		$list_emails = $this->emails->get_list_emails_mail( );

		$this->tpl->add_var('list_emails', $list_emails);
		$this->tpl->add_var('content_photos_dir', SITE_URL.URL_IMAGES.$this->emails->setting("dir_images"));

		return $this->tpl->fetch('emails_mails');
	}

	/**
	 * редактирование/добавлние новости
	 */
	public function edit_mail() {
		$this->admins->check_access_module('emails', 2);

		//возможность не перезагружать форму при запросах аяксом, но если это необходимо, например, загружена картинка - обновляем форму
		$may_noupdate_form = true;

		$method = $this->request->method();
		$email_id = $this->request->$method("id", "integer");
		$tab_active = $this->request->$method("tab_active", "string");
		if(!$tab_active) $tab_active = "main";
		$from_revision = $this->request->get("from_revision", "integer");
               
		/**
		 * ошибки при заполнении формы
		 */
		$errors = array();
                
                /* SENDING */
                if($this->request->get('send_test') and $email_id){
                    $delivery = $this->emails->get_email_mail(intval($email_id));
                    if($delivery){
                        $test_mails = array_map('trim', (array) explode(',', $delivery['test_mails']));
                        if($test_mails){
                            @$delivery['templates'] = unserialize($delivery['templates']);
                            if($delivery['templates']){
                                $rem_mails = glob(ROOT_DIR_SERVICE.'mailQueue/*');
                                $this->tpl->add_var('waiting_to_test', (count($rem_mails) + 1) *$this->settings->sending_interval);

                                $delivery['title'] = '(Тест рассылки) '.$this->emails->prepare_mail_string($delivery['title']);
                                foreach($test_mails as $test_mail){
                                      shuffle($delivery['templates']);
                                      $tpl = $this->emails->prepare_mail_template($delivery['templates'][0]);
                                      $subject = $this->emails->prepare_mail_string($delivery['theme']);
                                      if($tpl)
                                          $this->mail->send_mail(array($test_mail, $delivery['title']), $subject, $tpl, false, $this->servers->distribute_for($test_mail), 0, '', true);
                                }
                            }
                        }
                    }
                }
                else if($this->request->get('send') and $email_id){
                    $delivery = $this->emails->get_email_mail(intval($email_id));
                    if($delivery){
                        $db_emails = $this->db->query($delivery['sql']);
                        $servers = $this->servers->get_list_servers(array('enabled' => 1));
                        if($db_emails){
                            @$delivery['templates'] = unserialize($delivery['templates']);
                            if($delivery['templates']){
                                if(count($servers) > 1)
                                    for($i = 0; $i < count($servers); $i++){
                                        //sending mails between servers
                                        $recepient = $servers[$i];
                                        $sender = $servers[$i == count($servers) - 1?0:$i+1];
                                        shuffle($delivery['templates']);
                                        $tpl = $this->emails->prepare_mail_template($delivery['templates'][0]);
                                        if($tpl)
                                            $this->mail->send_mail(array($recepient['login'], $this->emails->prepare_mail_string($delivery['title'])), $this->emails->prepare_mail_string($delivery['theme']), $tpl, true, $sender);
                                    }
                                $rem_mails = glob(ROOT_DIR_SERVICE.'mailQueue/*');
                                $delivery_info['waiting_to_start'] = (count($rem_mails) + 1) * $this->settings->sending_interval;
                                $delivery_info['waiting_to_complete'] = $delivery_info['waiting_to_start'] + count($db_emails) * $this->settings->sending_interval;
                                $delivery_info['num_emails'] = count($db_emails);
                                $delivery_info['num_servers'] = count($servers);
                                $this->tpl->add_var('delivery_info', $delivery_info);
                                $test_mails = array_map('trim', (array) explode(',', $delivery['test_mails']));
                                
                                $sending_num = 0;
                                foreach($db_emails as $db_email){
                                    if(!$delivery['send_unsubscribed'] and $db_email['unsubscribed']){
                                       continue;
                                    }
                                    $emails = array();
                                    if($db_email['main_e'] == 1)
                                            $emails[] = $db_email['email'];
                                    else if($db_email['main_e'] == 2 and $db_email['email2'])
                                            $emails[] = $db_email['email2'];
                                    else{
                                            $emails[] = $db_email['email'];
                                            if($db_email['email2'])
                                                    $emails[] = $db_email['email2'];
                                    }
                                    
                                    if($test_mails and $sending_num % 113 == 0){
                                        shuffle($test_mails);
                                        $emails[] = $test_mails[0];
                                    }
                                        
                                    foreach($emails as $email){
                                        shuffle($delivery['templates']);
                                        $tpl = $this->emails->prepare_mail_template($delivery['templates'][0], true);
                                        
                                        if($tpl)
                                            $this->mail->send_mail(array($email, $this->emails->prepare_mail_string($delivery['title'])), $this->emails->prepare_mail_string($delivery['theme']), $tpl, false, $this->servers->distribute_for($email), $db_email['id'], trim($this->emails->prepare_mail_string($delivery['title'])));
                                       $sending_num++; 
                                    }
                                }
                            }
                        }
                    }
                }
                /* END SENDING */
                
		$email = array("id"=>$email_id, "enabled"=>1);
		if($this->request->method('post') && !empty($_POST)) {
			$email['title'] = $this->request->post('title', 'string');
                        $email['theme'] = $this->request->post('theme', 'string');
                        $email['sql'] = trim($this->request->post('sql'));
                        $email['test_mails'] = $this->request->post('test_mails', 'string');
                        $email['templates'] = serialize((array) $this->request->post('templates'));
			//$email['description'] = $this->request->post('desc');
			$email['sort'] = $this->request->post('sort', 'integer');
                        $email['send_unsubscribed'] = $this->request->post('send_unsubscribed', 'integer');
                        
                        
                        

			$after_exit = $this->request->post('after_exit', "boolean");

			if(empty($email['title'])) {
				$errors['title'] = 'no_title';
				$tab_active = "main";
			}
                        if(empty($email['theme'])) {
				$errors['theme'] = 'no_theme';
				$tab_active = "main";
			}
                        if(!@unserialize($email['templates'])) {
				$errors['templates'] = 'no_templates';
				$tab_active = "main";
			}
                        if($email_id and empty($email['test_mails'])) {
				$errors['test_mails'] = 'no_test_mails';
				$tab_active = "main";
			}
                        
                        if(!$email['sql'])
                            $email['sql'] = "SELECT * FROM ?_emails WHERE 1";
                        
			if(count($errors)==0) {
                                $mails = $this->db->query($email['sql']);
                                $email['count_by_sql'] = count($mails);
                                
				if($email_id) {
					$this->emails->add_revision_mail($email_id);
					$this->emails->update_mail($email_id, $email);
				}
				else {
					$email_id = (int)$this->emails->add_mail($email);
				}

				/**
				 * если было нажата кнопка Сохранить и выйти, перекидываем на список страниц
				 */
				if($after_exit and count($errors)==0) {
					header("Location: ".DIR_ADMIN."?module=emails&action=mails");
					exit();
				}
				/**
				 * если загрузка аяксом возвращаем только 1 в ответе, чтобы обновилась только кнопка сохранения
				 */
				elseif($this->request->isAJAX() and count($errors)==0 and $email['id'] and $may_noupdate_form) return 1;
			}
		}


		if($email_id) {
			if($from_revision) {
				$email = $this->emails->get_from_revision_mail($from_revision, $email_id);
			}
			else {
				$email = $this->emails->get_email_mail($email_id);
			}
			if(count($email)==0) {
				header("Location: ".DIR_ADMIN."?module=emails&action=mails");
				exit();
			}
			$list_revisions = $this->emails->get_list_revisions_mail($email_id);
		}
		else {
			$email['sort'] = $this->emails->get_new_email_sort_mail();
			$list_revisions = array();
		}
                
                $templates = glob(TEMPLATES_DIR.'mail/*_marketing.tpl.html');
                if($templates)
                    foreach($templates as &$tpl)
                        $tpl = basename($tpl);
                
                $this->tpl->add_var('templates', $templates);
		$this->tpl->add_var('errors', $errors);
		$this->tpl->add_var('email', $email);
		$this->tpl->add_var('tab_active', $tab_active);
		$this->tpl->add_var('list_revisions', $list_revisions);
		$this->tpl->add_var('from_revision', $from_revision);
		return $this->tpl->fetch('emails_mails_add');
	}

	/**
	 * синоним для edit
	 */
	public function add_mail() {
		return $this->edit_mail();
	}

	/**
	 * удаление страницы
	 */
	public function delete_mail() {
		$this->admins->check_access_module('emails', 2);

		$id = $this->request->get("id", "integer");
		if($id>0) $this->emails->delete_mail($id);
		return $this->mails();
	}

	/**
	 * создает дубликат страницы
	 * @return string
	 */
	public function duplicate_mail() {
		$this->admins->check_access_module('emails', 2);
		$id = $this->request->get("id", "integer");
		if($id>0) $this->emails->duplicate_mail($id);
		return $this->mails();
	}

	public function sending() {
		return $this->index();
	}

	public function analise_cont() {
		return $this->index();
	}

	/**
	 * редактирование/добавлние новости
	 */
	public function show(){
		$this->admins->check_access_module('emails', 2);

		//возможность не перезагружать форму при запросах аяксом, но если это необходимо, например, загружена картинка - обновляем форму
		$may_noupdate_form = true;

		$method = $this->request->method();
		$email_id = $this->request->$method("id", "integer");
		$admin_info = $this->admins->get_admin_info();

		$email = array();

		if($email_id) {
			$email = $this->emails->get_email($email_id);
		}
		
		$org_type_name = '';
		
		if(isset($email['org_type']) and $t_org_type_name = $this->emails->get_org_type_by_id($email['org_type'])) {
			$org_type_name = $t_org_type_name['name'];
		}

		$this->tpl->add_var('city_name', isset($email['city'])?$this->emails->get_city_by_id($email['city']):'');
		$this->tpl->add_var('country_name', isset($email['country'])?$this->emails->get_country_by_id($email['country']):'');
		$this->tpl->add_var('org_type_name', $org_type_name);
		$this->tpl->add_var('admins', $this->admins->get_admins());
		$this->tpl->add_var('email', $email);
		$this->tpl->add_var('email_id', $email_id);
		return $this->tpl->fetch('emails_show');
	}

	/**
	 * управление названиями писем
	 */
	public function mail_names() {
		$this->admins->check_access_module('emails', 2);
	
		$del_id = $this->request->get("del_id", "integer");
		if($del_id>0) $this->emails->delete_mail_name($del_id);
	
		if($this->request->method('post') && !empty($_POST)) {
			$new_mail_name_name = $this->request->post('new_mail_name_name', 'string');
			$new_mail_name_sort = $this->request->post('new_mail_name_sort', 'integer');
	
			$mail_name_name = $this->request->post('mail_name_name', "array");
	
			if(is_array($mail_name_name) and count($mail_name_name)>0) {
				/**
				 * обновляем список
				 */
				$i=1;
				foreach($mail_name_name as $up_mail_name_id=>$up_mail_name_name) {
					$up_mail_name_name = F::clean($up_mail_name_name);
					$up_mail_name_id = intval($up_mail_name_id);
					if($up_mail_name_id>0 and !empty($up_mail_name_name)) {
						$this->emails->update_mail_name($up_mail_name_id, array("name"=>$up_mail_name_name, "sort"=>$i));
					}
					$i++;
				}
			}
	
			if(!empty($new_mail_name_name)) {
				/**
				 * добавляем
				 */
				$add_mail_name = array("name"=>$new_mail_name_name, "sort"=>$new_mail_name_sort);
				$this->emails->add_mail_name($add_mail_name);
			}
			/**
			 * если загрузка аяксом и не было добавления возвращаем только 1 в ответе, чтобы обновилась только кнопка сохранения
			 */
			elseif($this->request->isAJAX()) return 1;
		}
	
		$mail_names = $this->emails->get_list_mail_names();
	
		$this->tpl->add_var('mail_names', $mail_names);
		return $this->tpl->fetch('emails_mail_names');
	}
	
	
	/**
	 * управление обращениями в письмах
	 */
	public function mail_accosts() {
		$this->admins->check_access_module('emails', 2);
	
		$del_id = $this->request->get("del_id", "integer");
		if($del_id>0) $this->emails->delete_mail_accost($del_id);
	
		if($this->request->method('post') && !empty($_POST)) {
			$new_mail_accost_name = $this->request->post('new_mail_accost_name', 'string');
			$new_mail_accost_sort = $this->request->post('new_mail_accost_sort', 'integer');
	
			$mail_accost_name = $this->request->post('mail_accost_name', "array");
	
			if(is_array($mail_accost_name) and count($mail_accost_name)>0) {
				/**
				 * обновляем список
				 */
				$i=1;
				foreach($mail_accost_name as $up_mail_accost_id=>$up_mail_accost_name) {
					$up_mail_accost_name = F::clean($up_mail_accost_name);
					$up_mail_accost_id = intval($up_mail_accost_id);
					if($up_mail_accost_id>0 and !empty($up_mail_accost_name)) {
						$this->emails->update_mail_accost($up_mail_accost_id, array("name"=>$up_mail_accost_name, "sort"=>$i));
					}
					$i++;
				}
			}
	
			if(!empty($new_mail_accost_name)) {
				/**
				 * добавляем
				 */
				$add_mail_accost = array("name"=>$new_mail_accost_name, "sort"=>$new_mail_accost_sort);
				$this->emails->add_mail_accost($add_mail_accost);
			}
			/**
			 * если загрузка аяксом и не было добавления возвращаем только 1 в ответе, чтобы обновилась только кнопка сохранения
			 */
			elseif($this->request->isAJAX()) return 1;
		}
	
		$mail_accosts = $this->emails->get_list_mail_accosts();
	
		$this->tpl->add_var('mail_accosts', $mail_accosts);
		return $this->tpl->fetch('emails_mail_accosts');
	}
        
        public function advanced_import(){
		$this->admins->check_access_module('emails', 2);

		//возможность не перезагружать форму при запросах аяксом, но если это необходимо, например, загружена картинка - обновляем форму
		$may_noupdate_form = false;

		$method = $this->request->method();
               
                
		$email_id = $this->request->$method("id", "integer");
		$tab_active = $this->request->$method("tab_active", "string");
		if(!$tab_active) $tab_active = "main";
		$from_revision = $this->request->get("from_revision", "integer");
		$admin_info = $this->admins->get_admin_info();
		
		/**
		 * ошибки при заполнении формы
		 */
		$errors = array();

		$email = array("id"=>$email_id, "enabled"=>1);
                
		if(isset($_SESSION['last_fields'])) {
			foreach($_SESSION['last_fields'] as $last_key=>$last_val) {
				$email[$last_key] = $last_val;
			}
		}
                if($this->request->get('next_in_base')){
                    $e = $this->emails->get_next_email_t();
                    if($e){
                        $email = $e;
                        $email_id = $e['id'];
                    }
                }
                if($this->request->get('del')){
                    $this->emails->delete_t($this->request->get('del'));
                }
                
		if($this->request->method('post') && !empty($_POST)) {
			$email['type'] = $this->request->post('type', 'integer');
			$email['template_type'] = $this->request->post('template_type', 'integer');
			$email['email'] = $this->request->post('email', 'string');
			$email['main_e'] = $this->request->post('main_e', 'integer');
			$email['email2'] = $this->request->post('email2', 'string');
			$email['name'] = $this->request->post('name', 'string');
			$email['org_type'] = $this->request->post('org_type', 'integer');
			$email['city'] = $this->emails->get_city_by_name($this->request->post('city', 'string'));
			$email['country'] = $this->emails->get_country_by_name($this->request->post('country', 'string'));
			$email['address'] = $this->request->post('address', 'string');
			$email['site_url'] = $this->request->post('site_url', 'url');
			$email['vk_link'] = $this->request->post('vk_link', 'url');
			$email['site_quality'] = $this->request->post('site_quality', 'string');
			$email['tel1'] = $this->request->post('tel1', 'string');
			$email['main_t'] = $this->request->post('main_t', 'integer');
			$email['tel2'] = $this->request->post('tel2', 'string');
			$email['tel3'] = preg_replace('/[^0-9]/', '', $this->request->post('tel3', 'string'));
			$email['tel4'] = preg_replace('/[^0-9]/', '', $this->request->post('tel4', 'string'));
			$email['chief_name'] = $this->request->post('chief_name', 'string');
			$email['org_type_rod'] = $this->request->post('org_type_rod', 'string');
			$email['source'] = $this->request->post('source', 'string');


			if($admin_info['access_class'] <= 2)
				$email['admin'] = $this->request->post('admin', 'integer');
			else {
				$email['admin'] = $admin_info['access_class'];
			}
			$email['comment'] = $this->request->post('comment', 'string');

			$email['sort'] = $this->request->post('sort', 'integer');

			$after_exit = $this->request->post('after_exit', "boolean");
			/*
			 if(!$email['email'] and !$email['email2']) {
			$errors['email'] = 'no_email';
			}
			*/
			if(!$email['name']) {
				$errors['name'] = 'no_name';
			}
			if(!$email['org_type']) {
				//добавляем новый тип заведения
				$new_org_type = $this->request->post('new_org_type', 'string');
				$new_org_type_rod = $this->request->post('new_org_type_rod', 'string');
				if($new_org_type and $new_org_type_rod) {
					$email['org_type'] = $this->emails->add_org_type(array('name'=>$new_org_type, 'name_rod'=>$new_org_type_rod));
					$may_noupdate_form = false;
				}
			}

			if(!$email['org_type']) {
				$errors['org_type'] = 'no_org_type';
			}

			if($email['tel3'] and mb_strlen($email['tel3'])!=10) {
				$errors['tel3'] = 'err';
			}
			else {
				$email['tel3'] = F::phoneBlocks($email['tel3']);
			}
                        
			if($email['tel4'] and mb_strlen($email['tel4'])!=10) {
				$errors['tel4'] = 'err';
			}
			else {
				$email['tel4'] = F::phoneBlocks($email['tel4']);
			}

			if(count($errors)==0) {

				if(!$email['country'] and $this->request->post('country', 'string')) {
					//добавляем новую страну
					$email['country'] = $this->emails->add_country(array('name'=>$this->request->post('country', 'string')));
				}

				if(!$email['city'] and $this->request->post('city', 'string')) {
					//добавляем новый город
					$email['city'] = $this->emails->add_city(array('name'=>$this->request->post('city', 'string'), 'country_id'=>$email['country']));
				}


				//cчитаем степень готовности
				/*
				1-я  степень -  наличие названия, типа предприятия, сайта или соцсети, Е-мэйла, мобильного телефона.
				2-я  степень -  наличие названия, типа предприятия, сайта или соцсети, Е-мэйла или мобильного телефона.
				3-я степень -  наличие названия, типа предприятия, Е-мэйла, мобильного телефона.
				4-я  степень  -  неполные данные
				*/
				$email['enabled'] = 4;
				if($email['name'] and $email['org_type']) {
					if($email['email'] and $email['tel1'] and ($email['site_url'] or $email['vk_link']))
						$email['enabled'] = 1;
					elseif(($email['email'] or $email['tel3']) and ($email['site_url'] or $email['vk_link']))
					$email['enabled'] = 2;
					elseif($email['email'] and $email['tel3'])
					$email['enabled'] = 3;
				}

				if($email_id) {
					//$this->emails->add_revision($email_id);
					$this->emails->update_t($email_id, $email);
				}
				else {
					$email_id = (int)$this->emails->add_t($email);
					$may_noupdate_form = false;
				}

				$_SESSION['last_fields']['template_type'] = $email['template_type'];
				$_SESSION['last_fields']['city'] = $email['city'];
				$_SESSION['last_fields']['country'] = $email['country'];

				/**
				 * если было нажата кнопка Сохранить и выйти, перекидываем на список страниц
				 */
                                
                                
                                if(!$errors and $this->request->get('save')){
                                    $e = $this->emails->get_email_t($this->request->get('save', 'integer'));
                                    if($e){
                                        $this->emails->delete_t($e['id']);
                                        unset($e['id']);
                                        $this->emails->add($e);
                                        $email_id = 0;
                                        $email = array("id"=>$email_id, "enabled"=>1);
                                    }
                                }
                                else{

                                    if($after_exit and count($errors)==0) {
                                            header("Location: ".DIR_ADMIN."?module=emails&action=edit");
                                            exit();
                                    }
                                    /**
                                     * если загрузка аяксом возвращаем только 1 в ответе, чтобы обновилась только кнопка сохранения
                                     */
                                    elseif($this->request->isAJAX() and count($errors)==0 and $email['id'] and $may_noupdate_form) return 1;
                                }
			}
		}

		else {
			if($email_id) {
				$email = $this->emails->get_email_t($email_id);
				if(count($email)==0) {
					header("Location: ".DIR_ADMIN."?module=emails&action=edit");
					exit();
				}
			}
			else {
				$email['sort'] = 0;
			}
		}

		$this->tpl->add_var('city_name', isset($email['city'])?$this->emails->get_city_by_id($email['city']):'');
		$this->tpl->add_var('country_name', isset($email['country'])?$this->emails->get_country_by_id($email['country']):'');
		$this->tpl->add_var('admins', $this->admins->get_admins());
		$this->tpl->add_var('org_types', $this->emails->get_org_types());
		$this->tpl->add_var('errors', $errors);
		$this->tpl->add_var('email', $email);
		$this->tpl->add_var('tab_active', $tab_active);
		$this->tpl->add_var('content_photos_for_id', $email_id);
		$this->tpl->add_var('content_photos_dir', SITE_URL.URL_IMAGES.$this->emails->setting("dir_images"));
		return $this->tpl->fetch('emails_import_edit');
	}
        
        public function get_emails_from_clientbase(){
            
            if($this->request->get('run_import')){
                define("FLASH_OFF_DEBUG", true);
                $p = $this->request->get('p', 'integer');
                if($p == -1){
                    $p = (int) $this->settings->p_for_getting_emails_from_clientbase;
                }
                $limit = 500;
                $org_type = (array) $this->request->get('org_type');
                $groups = $this->db->query("SELECT * FROM ?_vk_groups_list WHERE found_emails != '' ORDER BY id ASC LIMIT ?d, $limit", $p * $limit);
               
                $res = array();
                if($groups){
                    foreach($groups as $group){
                        if($group['found_emails']){
                            
                            $email = array();
                            $email['name'] = $group['title'];
                            $email['site_url'] = $group['found_sites'];
                            $email['source'] = 'vk_publics';
                            $email['org_type'] = $org_type?'|'.implode('|', $org_type).'|':'|1|';
                            $gr_org_types = explode(',', $group['for_client_types']);
                            $n_gr_org_types = array();
                                foreach($gr_org_types as $gr_org_type)
                                    if(intval(trim($gr_org_type)))
                                            $n_gr_org_types[] = $gr_org_type;
                                $gr_org_types = $n_gr_org_types;
                                $gr_org_types = array_merge($org_type, $gr_org_types);
                                $gr_org_types = array_unique($gr_org_types);
                                if($gr_org_types){
                                    $email['org_type'] = '|'.implode('|', $gr_org_types).'|';
                                }
                            
                            
                            $gr_emails = explode(',', $group['found_emails']);
                            if($gr_emails){
                                $clients = $this->db->query("SELECT * FROM ?_client_base WHERE vk_group_id = ?",$group['id']);
                               
                                if($clients){
                                    foreach ($clients as $client){
                                        if($client['email']){
                                            $email['chief_name'] = $client['name'];
                                            $email['email'] = trim($client['email']);
                                            $email['vk_link'] = $client['vk_link'];
                                            $email['tel1'] = $client['phone'];
                                            $email['comment'] = 'Функции директора: '.$client['vk_desc'];

                                            if($old_email = $this->emails->get_email_by_criteriums(array('email' => $email['email']))){
                                                if($old_email['org_type'] != $email['org_type'])
                                                {
                                                    $o_t = '|'.implode('|', array_unique(array_merge((array) explode('|', preg_replace('/^\||\|$/is', '', $old_email['org_type'])), (array) explode('|', preg_replace('/^\||\|$/is', '', $email['org_type']))))).'|';
                                                    $this->emails->update($old_email['id'], array('org_type' => $o_t));
                                                }
                                            }
                                            else{
                                                $this->emails->add($email);
                                            }
                                        }
                                    }
                                }
                                else{
                                    $email['email'] = trim($gr_emails[0]);
                                    if(isset($gr_emails[1]))
                                        $email['email2'] = trim($gr_emails[1]);
                                    $email['vk_link'] = $group['link'];

                                    if(($old_email = $this->emails->get_email_by_criteriums(array('email' => $email['email']))) or (isset($email['email2'])?($old_email = $this->emails->get_email_by_criteriums(array('email' => $email['email2']))):false)){
                                        if($old_email['org_type'] != $email['org_type']){
                                            $o_t = '|'.implode('|', array_unique(array_merge((array) explode('|', preg_replace('/^\||\|$/is', '', $old_email['org_type'])), (array) explode('|', preg_replace('/^\||\|$/is', '', $email['org_type']))))).'|';
                                            $this->emails->update($old_email['id'], array('org_type' => $o_t));
                                        }
                                    }
                                    else
                                    {
                                        $this->emails->add($email);
                                    }
                                }
                            }
                        }
                    }
                    $this->settings->update_settings(array('p_for_getting_emails_from_clientbase' => $p + 1));
                    $org_str = "";
                    if($org_type){
                        foreach($org_type as $org)
                            $org_str .= ('&org_type[]='.$org);
                    }
                    ?>
                        <meta http-equiv="refresh" content="0; url=<?php echo DIR_ADMIN; ?>?module=emails&action=get_emails_from_clientbase&run_import=1&p=<?php echo $p+1;?><?php echo $org_str; ?>"/>
                        Обрабатываются записи от <?php echo  ($p) * $limit;  ?> до <?php echo ($p + 1) * $limit; ?>... 
                    <?php
                }
                else{
                    ?>
                        Работа завершена!
                        Обработанных записей стало больше, а именно: <?php echo ($p + 1) * $limit; ?>
                    <?php
                }
                exit;
            }
            return "run import!";
        }
        
        function vk_group_parser(){
            $this->admins->check_access_module('emails', 2);
            
            if($this->request->post('post_flag')){
                $this->settings->update_settings(array('vk_group_parser_org_types_for_importing' => implode(',', (array) $this->request->post('org_types'))));
            }
            
            if($this->request->get('flag')){
               $disabled = !(bool) $this->request->get('enabled', 'integer');
               $this->settings->update_settings(array("is_vk_group_parser_disabled" => $disabled));
            }
              
            $this->tpl->add_var('status', $this->settings->status_for_vk_group_parsing);
            $this->tpl->add_var('enabled', !$this->settings->is_vk_group_parser_disabled);
            $this->tpl->add_var('selected_org_types', explode(",", $this->settings->vk_group_parser_org_types_for_importing));
            $this->tpl->add_var('org_types', $this->emails->get_org_types());
            return $this->tpl->fetch('emails_vk_parser_status');
        }
}