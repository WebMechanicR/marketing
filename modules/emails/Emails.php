<?php
class Emails extends Module implements IElement {

	protected $module_name = "emails";
	private $module_table = "emails";
	private $module_nesting = true; //возможность вкладывать подстраницы в модуль
	private $cached_servers;
	private $module_table_statistics = 'statistics';
	private $module_table_cities = 'locations_cities';
	private $module_table_countries = 'locations_countries';
	private $module_table_org_types = 'org_types';
	private $module_table_mail = 'mails';
	private $module_table_mail_names = "mail_names";
	private $module_table_mail_accosts = "mail_accosts";
        private $module_table_temporary_emails = "temporary_emails";
	private $module_settings = array(
			"dir_images" => "img/",
			"image_sizes"=> array (
					"normal"=> array(280, 280, true, false),// ширина, высота, crop, watermark
					"small"=> array(50, 50, false, false)
			),
			"images_content_type" => "emails",
			"revisions_content_type" => "emails"

	);


	/**
	 * добавляет новый элемент в базу
	 */
	public function add($email) {
		//чистим кеш
		$this->cache->delete("list_emails");
		return $this->db->query("INSERT INTO ?_".$this->module_table." (?#) VALUES (?a)", array_keys($email), array_values($email));
	}

	/**
	 * обновляет элемент в базе
	 */
	public function update($id, $email) {

		//чистим кеш
		$this->cache->delete("list_emails");

		if($this->db->query("UPDATE ?_".$this->module_table." SET ?a WHERE id IN (?a)", $email, (array)$id))
			return $id;
		else
			return false;
	}

	/**
	 * удаляет элемент из базы
	 */
	public function delete($id) {
		if($email = $this->get_email($id)) {

			//if($email['img']!='') $this->delete_image($id);

			$this->db->query("DELETE FROM ?_".$this->module_table." WHERE id=?", $id);

			//чистим кеш
			$this->cache->delete("list_emails");

			$this->clear_revisions($id);
		}
	}

	/**
	 * создает копию элемента
	 */
	public function duplicate($id) {
		$new_id = null;
		if($email = $this->get_email($id)) {

			unset($email['id']);
			$email['title'] .= ' (копия)';
			$email['enabled'] = 0;

			$new_id = (int)$this->add($email);
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
		if($content = $this->get_email($for_id)) {
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
	public function get_email($id) {
		return $this->db->selectRow("SELECT * FROM ?_".$this->module_table." WHERE id=?d", $id);
	}

	public function get_email_by_criteriums($criteriums) {
		$condition = "";
		if(isset($criteriums['email']) and $criteriums['email']){
			$condition .= (empty($condition) ? " WHERE " : " AND ")." (email = '".$criteriums['email']."' OR email2 = '".$criteriums['email']."')";
		}
		if(isset($criteriums['email2']) and $criteriums['email2']){
			$condition .= (empty($condition) ? " WHERE " : " AND ")." (email = '".$criteriums['email2']."' OR email2 = '".$criteriums['email2']."')";
		}
		if(isset($criteriums['tel1']) and $criteriums['tel1']){
			$condition .= (empty($condition) ? " WHERE " : " AND ")." (tel1 LIKE '%".$criteriums['tel1']."%' OR tel2 LIKE '%".$criteriums['tel1']."%' OR tel3 LIKE '%".$criteriums['tel1']."%')";
		}
		if(isset($criteriums['tel2']) and $criteriums['tel2']){
			$condition .= (empty($condition) ? " WHERE " : " AND ")." (tel1 LIKE '%".$criteriums['tel2']."%' OR tel2 LIKE '%".$criteriums['tel2']."%' OR tel3 LIKE '%".$criteriums['tel2']."%')";
		}
		if(isset($criteriums['tel3']) and $criteriums['tel3']){
			$condition .= (empty($condition) ? " WHERE " : " AND ")." (tel1 LIKE '%".$criteriums['tel3']."%' OR tel2 LIKE '%".$criteriums['tel3']."%' OR tel3 LIKE '%".$criteriums['tel3']."%')";
		}
		if(isset($criteriums['vk_link']) and $criteriums['vk_link']){
			$condition .= (empty($condition) ? " WHERE " : " AND ")." (vk_link LIKE '%".$criteriums['vk_link']."%')";
		}
		if(isset($criteriums['site_url']) and $criteriums['site_url']){
			$condition .= (empty($condition) ? " WHERE " : " AND ")." (site_url LIKE '%".$criteriums['site_url']."%')";
		}
		if(isset($criteriums['name']) and $criteriums['name']){
			$condition .= (empty($condition) ? " WHERE " : " AND ")." (name LIKE '%".$criteriums['name']."%')";
		}
		if(isset($criteriums['city']) and $criteriums['city']){
			$condition .= (empty($condition) ? " WHERE " : " AND ")." (city=".intval($criteriums['city']).")";
		}
		 
		return $this->db->selectRow("SELECT * FROM ?_".$this->module_table." ".$condition);

	}

	/**
	 * возвращает новости удовлетворяющие фильтрам
	 * @param array $filter
	 */
	public function get_list_emails($filter=array()) {
		$limit = "";
		$where = "";
		$sort_by = " ORDER BY n.sort DESC";

		if(isset($filter['sort']) and count($filter['sort'])==2) {
			if($filter['sort'][0]=='find_in_set' and isset($filter['in_ids']) and is_array($filter['in_ids']) and count($filter['in_ids'])>0) {
				if(isset($filter['category_id']) and $filter['category_id']) {
					$sort_by = " ORDER BY FIND_IN_SET(n.id, '".implode(",", $filter['in_ids'])."')";
				}
				else {
					$new_in_ids = array();
					//выбираем id из списка только те, которые попадают на страницу, чтобы не сортировать при запросе лишнее
					for($i=($filter['limit'][0]-1)*$filter['limit'][1]; $i<(($filter['limit'][0]-1)*$filter['limit'][1]+$filter['limit'][1]); $i++) {
						if(!isset($filter['in_ids'][$i])) break;
						$new_in_ids[] = $filter['in_ids'][$i];
					}
					$filter['in_ids'] = $new_in_ids;
					$sort_by = " ORDER BY FIND_IN_SET(n.id, '".implode(",", $new_in_ids)."')";
					$filter['limit'] = array(1, $filter['limit'][1]);
				}
			}
			else {
				if(!in_array($filter['sort'][0], array("name", "org_type", "last_sending", "sending_count", "enabled"))) $filter['sort'][0] = "sort";
				if(!in_array($filter['sort'][1], array("asc", "desc")) ) $filter['sort'][1] = "asc";
				$sort_by = " ORDER BY n.".$filter['sort'][0]." ".$filter['sort'][1];
			}
		}

		if(isset($filter['limit']) and count($filter['limit'])==2) {
			$filter['limit'] = array_map("intval", $filter['limit']);
			$limit = " LIMIT ".($filter['limit'][0]-1)*$filter['limit'][1].", ".$filter['limit'][1];
		}

		if(isset($filter['enabled'])) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.enabled=".intval($filter['enabled']);
		}

		if(isset($filter['type'])) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.type=".intval($filter['type']);
		}
		
		if(isset($filter['template_type'])) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.template_type=".intval($filter['template_type']);
		}

		if(isset($filter['status'])) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.status=".intval($filter['status']);
		}
                
                if(isset($filter['org_type']) and $filter['org_type']) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.org_type LIKE '%|".$filter['org_type']."|%'";
		}

		if(isset($filter['city']) and $filter['city']) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.city=".intval($filter['city']);
		}

		if(isset($filter['admin']) and $filter['admin']) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.admin=".intval($filter['admin']);
		}

		if(isset($filter['site_link']) and $filter['site_link']) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.site_link LIKE '%".$filter['site_link']."%'";
		}
		
		if(isset($filter['send_group']) and $filter['send_group']) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.send_group LIKE '%".$filter['send_group']."%'";
		}

		if(isset($filter['date_from'])) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.last_sending >= ".$filter['date_from'];
		}

		if(isset($filter['date_to']) and $filter['date_to'] > 0){
			$where .= (empty($where) ? " WHERE " : " AND ")."n.last_sending <= ".$filter['date_to'];
		}

		if(isset($filter['name']) and $filter['name']) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.name LIKE '%".$filter['name']."%'";
		}

		if(isset($filter['org']) and $filter['org']) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.org LIKE '%".$filter['org']."%'";
		}

		if(isset($filter['email']) and $filter['email']) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.email LIKE '%".$filter['email']."%'";
		}

		return $this->db->select("SELECT n.*
				FROM ?_".$this->module_table." n
				".$where.$sort_by.$limit);
	}


	/**
	 * возвращает количество товаров удовлетворяющих фильтрам
	 * @param array $filter
	 */
	public function get_count_emails($filter=array()) {
		$where = "";

		if(isset($filter['enabled'])) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.enabled=".intval($filter['enabled']);
		}

		if(isset($filter['type'])) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.type=".intval($filter['type']);
		}

		if(isset($filter['status'])) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.status=".intval($filter['status']);
		}
                
                if(isset($filter['org_type']) and $filter['org_type']) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.org_type LIKE '%|".$filter['org_type']."|%'";
		}

		if(isset($filter['name']) and $filter['name']) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.name LIKE '%".$filter['name']."%'";
		}

		if(isset($filter['org']) and $filter['org']) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.org LIKE '%".$filter['org']."%'";
		}

		if(isset($filter['city']) and $filter['city']) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.city=".intval($filter['city']);
		}

		if(isset($filter['admin']) and $filter['admin']) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.admin=".intval($filter['admin']);
		}

		if(isset($filter['site_link']) and $filter['site_link']) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.site_link LIKE '%".$filter['site_link']."%'";
		}

		if(isset($filter['email']) and $filter['email']) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.email LIKE '%".$filter['email']."%'";
		}

		if(isset($filter['date_from'])) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.last_sending >= ".$filter['date_from'];
		}

		if(isset($filter['date_to']) and $filter['date_to'] > 0) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.last_sending <= ".$filter['date_to'];
		}
		
		if(isset($filter['template_type'])) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.template_type=".intval($filter['template_type']);
		}
		
		if(isset($filter['send_group']) and $filter['send_group']) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.send_group LIKE '%".$filter['send_group']."%'";
		}

		return $this->db->selectCell("SELECT count(distinct n.id) FROM ?_".$this->module_table." n"
				.$where);
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
	 * добавляет изображение
	 * @param int $email_id
	 * @param string $image
	 * @param string $name
	 * @param int $sort
	 * @return boolean
	 */
	public function add_image($email_id, $image) {
		$image_sizes = $this->setting("image_sizes");
		if(!$this->image->create_image(ROOT_DIR_IMAGES.$this->setting("dir_images")."original/".$image, ROOT_DIR_IMAGES.$this->setting("dir_images")."normal/".$image, $image_sizes["normal"])) return false;
		if(!$this->image->create_image(ROOT_DIR_IMAGES.$this->setting("dir_images")."original/".$image, ROOT_DIR_IMAGES.$this->setting("dir_images")."small/".$image, $image_sizes["small"])) return false;

		$this->update($email_id, array("img"=>$image));
		return $email_id;
	}


	public function delete_image($id) {
		$email = $this->get_email($id);
		if($email and $email['img']!="") {
			//проверяем, не используется ли это изображение где-то еще
			$count = $this->db->selectCell("SELECT count(*) FROM ?_".$this->module_table." WHERE img=?", $email['img']);
			if($count==1) {
				@unlink(ROOT_DIR_IMAGES.$this->setting("dir_images")."original/".$email['img']);
				@unlink(ROOT_DIR_IMAGES.$this->setting("dir_images")."normal/".$email['img']);
				@unlink(ROOT_DIR_IMAGES.$this->setting("dir_images")."small/".$email['img']);
			}
			$this->update($id, array("img"=>""));
		}
	}

	/**
	 * возвращает порядок сортировки для добавляемой страницы
	 */
	public function get_new_email_sort() {
		return $this->db->selectCell("SELECT MAX(sort) as sort FROM ?_".$this->module_table)+1;
	}

	/**
	 * @return boolean
	 */
	public function is_nesting() {
		return $this->module_nesting;
	}

	/**
	 * возвращает записи роутера для модуля
	 * {url_page} - подстановка адреса (full_link) страницы
	 */
	public function get_router_records() {
		return array(
				array('{url_page}(\/?)', 'module=emails&page_url={url_page}')
		);
	}

	public function get_emails_for_site(){
		$emails = array();
		if(($emails = $this->cache->get('list_emails')) === false){
			$emails= $this->get_list_emails(array('enabled' => 1));
			$this->cache->set($emails, 'list_emails', array(), false);
		}
		return $emails;
	}

	public function send($id, $subject, $handling, $body, $send_group=''){
		$data = $this->get_email(intval($id));
		
		$data['city'] =  isset($data['city'])?$this->get_city_by_id($data['city']):'';
		$data['country'] = isset($data['country'])?$this->get_country_by_id($data['country']):'';
	
		
		if(isset($data['org_type']) and $org_type_name = $this->get_org_type_by_id($data['org_type'])) {
			$data['org_type'] = $org_type_name['name'];
			$data['org_type_rod'] = $org_type_name['name_rod'];
		}
		
		$data['link'] = "";
		
		if($data['template_type'] and isset(System::$CONFIG['template_types_link'][$data['template_type']])) {
			$data['link'] = System::$CONFIG['template_types_link'][$data['template_type']].'?utm_term='.$data['id'];
		}
		
		if(!$this->cached_servers)
			$this->cached_servers = $this->servers->get_list_servers(array('enabled' => 1));
                
		if($data and $data['link'] and $this->cached_servers){
			$emails = array();
			if($data['main_e'] == 1)
				$emails[] = $data['email'];
			else if($data['main_e'] == 2 and $data['email2'])
				$emails[] = $data['email2'];
			else{
				$emails[] = $data['email'];
				if($data['email2'])
					$emails[] = $data['email2'];
			}

			$body = str_replace(
					array_map(function($arg){
								return "{".$arg."}";
							  }, 
							  array_keys($data)
					), array_values($data), $body);
			
			$subject = str_replace(
					array_map(function($arg){
						return "{".$arg."}";
					},
					array_keys($data)
					), array_values($data), $subject);

			$handling = str_replace(
					array_map(function($arg){
						return "{".$arg."}";
					},
					array_keys($data)
					), array_values($data), $handling);
			
				foreach($emails as $email){
					shuffle($this->cached_servers);
					$server = $this->cached_servers[0];
					$this->tpl->add_var('user', $data);
					$this->tpl->add_var('handling', $handling);
					$this->tpl->add_var('body', $body);
					$this->tpl->add_var('subject', $subject);
					$this->tpl->in_user();
					$html_mail_user = $this->tpl->fetch("mail/mail_template");
					$this->mail->send_mail(array($email, $email), $subject, $html_mail_user, false, $server, $id, $send_group);
					$this->tpl->in_admin();
				}
				return true;
		}
		return false;
	}

	/*
	 * END email сообщения
	*/

	/**
	 * подчистывает кол-во пользователей, заявок, поездок, добавленных после послеледнего запуска функции, разбивая по дням.
	 * и записывает все в таблицу статистики
	 */
	public function update_statistics($group_name) {
		//$this->settings->update_settings(array("statistics_last_date"=>mktime(0, 0, 0, 1, 1, 2014)));

		$date_add = mktime(0,0,0, date('m'), date('d'), date('Y'));
		$this->db->query("INSERT IGNORE INTO ?_".$this->module_table_statistics." SET group_name = ?, date_add = ?, mails_sent = 0", $group_name, $date_add);
		$this->db->query("UPDATE ?_".$this->module_table_statistics." SET mails_sent = mails_sent + 1 WHERE date_add = ? AND group_name = ?" ,$date_add, $group_name);
	}

	/**
	 * возвращает массив статистики
	 */
	public function get_statistics($filter=array()) {
		$sort_by = " ORDER BY st.date_add DESC";
		$limit = "";
		$where = "";

		if(isset($filter['sort']) and count($filter['sort'])==2) {
			if(!in_array($filter['sort'][0], array("date_add"))) $filter['sort'][0] = "date_add";
			if(!in_array($filter['sort'][1], array("asc", "desc")) ) $filter['sort'][1] = "desc";
			$sort_by = " ORDER BY st.".$filter['sort'][0]." ".$filter['sort'][1];
		}

		if(isset($filter['limit']) and count($filter['limit'])==2) {
			$filter['limit'] = array_map("intval", $filter['limit']);
			$limit = " LIMIT ".($filter['limit'][0]-1)*$filter['limit'][1].", ".$filter['limit'][1];
		}


		return $this->db->select("SELECT * FROM ?_".$this->module_table_statistics." st"
				.$where
				.$sort_by.$limit);
	}

	/**
	 * возвращает количество записей статистики удовлетворяющих фильтрам
	 * @param array $filter
	 */
	public function get_count_statistics($filter=array()) {
		$where = "";

		return $this->db->selectCell("SELECT count(*)
				FROM ?_".$this->module_table_statistics." st".$where);
	}

	public function get_total_stat_count(){
		return $this->db->selectCell("SELECT SUM(mails_sent)
				FROM ?_".$this->module_table_statistics." st");
	}
        
        public function get_total_opened_stat_count(){
		return $this->db->selectCell("SELECT SUM(opened_mails)
				FROM ?_".$this->module_table_statistics." st");
	}
        
        public function register_opening_email($group_name, $email = ""){
            $this->db->query("UPDATE ?_".$this->module_table_statistics." SET opened_mails = opened_mails + 1 WHERE group_name = ? ORDER BY date_add DESC LIMIT 1", $group_name);
        }
        
	public function get_city($city_name){
		return $this->db->query("SELECT id, name FROM ?_".$this->module_table_cities." WHERE name LIKE '%".$city_name."%' LIMIT 12");
	}

	public function get_city_by_id($id){
		return $this->db->selectCell('SELECT name FROM ?_'.$this->module_table_cities.' WHERE id = ?', $id);
	}

	public function get_city_by_name($name){
		return $this->db->selectCell('SELECT id FROM ?_'.$this->module_table_cities.' WHERE name = ?', $name);
	}

	public function add_city($city) {
		return $this->db->query("INSERT INTO ?_".$this->module_table_cities." (?#) VALUES (?a)", array_keys($city), array_values($city));
	}
	
	
	public function get_country($country_name){
		return $this->db->query("SELECT id, name FROM ?_".$this->module_table_countries." WHERE name LIKE '%".$country_name."%' LIMIT 12");
	}
	
	public function get_country_by_id($id){
		return $this->db->selectCell('SELECT name FROM ?_'.$this->module_table_countries.' WHERE id = ?', $id);
	}
	
	public function get_country_by_name($name){
		return $this->db->selectCell('SELECT id FROM ?_'.$this->module_table_countries.' WHERE name = ?', $name);
	}
	
	public function add_country($country) {
		return $this->db->query("INSERT INTO ?_".$this->module_table_countries." (?#) VALUES (?a)", array_keys($country), array_values($country));
	}
	
	public function get_org_types(){
		return $this->db->select('SELECT t.*, t.id AS ARRAY_KEY FROM ?_'.$this->module_table_org_types.' t ORDER BY name ASC');
	}
	
	public function get_org_type_by_id($id){
		return $this->db->selectRow('SELECT * FROM ?_'.$this->module_table_org_types.' WHERE id = ?', $id);
	}
	
	public function add_org_type($orgtype) {
		return $this->db->query("INSERT INTO ?_".$this->module_table_org_types." (?#) VALUES (?a)", array_keys($orgtype), array_values($orgtype));
	}
        
        public function update_org_type($id, $orgtype) {
		return $this->db->query("UPDATE ?_".$this->module_table_org_types." SET ?a WHERE id = ?d",  (array) $orgtype, (int) $id);
	}
        
        public function delete_org_type($id) {
		return $this->db->query("DELETE FROM ?_".$this->module_table_org_types." WHERE id=?", $id);
	}

	/**
	 * добавляет новый элемент в базу
	 */
	public function add_mail($email) {
		//чистим кеш
		$this->cache->delete("list_emails");
		return $this->db->query("INSERT INTO ?_".$this->module_table_mail." (?#) VALUES (?a)", array_keys($email), array_values($email));
	}

	/**
	 * обновляет элемент в базе
	 */
	public function update_mail($id, $email) {

		//чистим кеш
		$this->cache->delete("list_emails");
		if($this->db->query("UPDATE ?_".$this->module_table_mail." SET ?a WHERE id IN (?a)", $email, (array)$id))
			return $id;
		else
			return false;
	}

	/**
	 * удаляет элемент из базы
	 */
	public function delete_mail($id) {
		if($email = $this->get_email_mail($id)) {

				

			$this->db->query("DELETE FROM ?_".$this->module_table_mail." WHERE id=?", $id);

			//чистим кеш
			$this->cache->delete("list_emails");

			$this->clear_revisions($id);
		}
	}

	/**
	 * создает копию элемента
	 */
	public function duplicate_mail($id) {
		$new_id = null;
		if($email = $this->get_email_mail($id)) {

			unset($email['id']);
			$email['title'] .= ' (копия)';
			$email['enabled'] = 0;

			$new_id = (int)$this->add_mail($email);
		}
		return $new_id;
	}

	/**
	 * возвращает историю версий элемента
	 */
	public function get_list_revisions_mail($for_id) {
		return $this->revision->get_list_revisions($for_id, "email_mails");
	}

	/**
	 * добавляет версию элемента в историю
	 */
	public function add_revision_mail($for_id) {
		if($content = $this->get_email($for_id)) {
			return $this->revision->add_revision($for_id, "email_mails", $content);
		}
		return null;
	}

	/**
	 * возвращает данные элемента из определенной ревизии
	 */
	public function get_from_revision_mail($id, $for_id) {
		return $this->revision->get_from_revision($id, $for_id, "email_mails");
	}

	/**
	 * удаляет все ревизии элемента
	 */
	public function clear_revisions_mail($for_id) {
		return $this->revision->clear_revisions($for_id, "email_mails");
	}

	/**
	 * возвращает новость по id
	 * @param mixed $id
	 * @return array
	 */
	public function get_email_mail($id) {
		return $this->db->selectRow("SELECT * FROM ?_".$this->module_table_mail." WHERE id=?d", $id);
	}

	/**
	 * возвращает новости удовлетворяющие фильтрам
	 * @param array $filter
	 */
	public function get_list_emails_mail($filter=array()) {
		$limit = "";
		$where = "";

		if(isset($filter['limit']) and count($filter['limit'])==2) {
			$filter['limit'] = array_map("intval", $filter['limit']);
			$limit = " LIMIT ".($filter['limit'][0]-1)*$filter['limit'][1].", ".$filter['limit'][1];
		}

		if(isset($filter['enabled'])) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.enabled=".intval($filter['enabled']);
		}

		return $this->db->select("SELECT n.*
				FROM ?_".$this->module_table_mail." n".$where." ORDER BY n.sort ASC".$limit);
	}

	/**
	 * возвращает порядок сортировки для добавляемой страницы
	 */
	public function get_new_email_sort_mail() {
		return $this->db->selectCell("SELECT MAX(sort) as sort FROM ?_".$this->module_table_mail)+1;
	}
	
	
	/**
	 * возвращает массив названий писем
	 */
	public function get_list_mail_names() {
		$cache_key = "mail_names";
		if (false === ($mail_names = $this->cache->get($cache_key))) {
			$mail_names= $this->db->select("SELECT g.id AS ARRAY_KEY, g.* FROM ?_".$this->module_table_mail_names." g ORDER BY g.sort ASC");
			$this->cache->set($mail_names, $cache_key);
		}
		return $mail_names;
	}
	
	/**
	 * добавляет новое название письма в базу
	 */
	public function add_mail_name($mail_name) {
		//чистим кеш
		$this->cache->delete("mail_names");
		return $this->db->query("INSERT INTO ?_".$this->module_table_mail_names." (?#) VALUES (?a)", array_keys($mail_name), array_values($mail_name));
	}
	
	/**
	 * удаляет название письма из базы
	 */
	public function delete_mail_name($id) {
		//чистим кеш
		$this->cache->delete("mail_names");
		return $this->db->query("DELETE FROM ?_".$this->module_table_mail_names." WHERE id=?", $id);
	}
	
	/**
	 * обновляет название письма в базе
	 */
	public function update_mail_name($id, $mail_name) {
		//чистим кеш
		$this->cache->delete("mail_names");
		if($this->db->query("UPDATE ?_".$this->module_table_mail_names." SET ?a WHERE id=?", $mail_name, $id))
			return $id;
		else
			return false;
	}
	
	
	/**
	 * возвращает массив обращений писем
	 */
	public function get_list_mail_accosts() {
		$cache_key = "mail_accosts";
		if (false === ($mail_accosts = $this->cache->get($cache_key))) {
			$mail_accosts= $this->db->select("SELECT g.id AS ARRAY_KEY, g.* FROM ?_".$this->module_table_mail_accosts." g ORDER BY g.sort ASC");
			$this->cache->set($mail_accosts, $cache_key);
		}
		return $mail_accosts;
	}
	
	/**
	 * добавляет новое обращение письма в базу
	 */
	public function add_mail_accost($mail_accost) {
		//чистим кеш
		$this->cache->delete("mail_accosts");
		return $this->db->query("INSERT INTO ?_".$this->module_table_mail_accosts." (?#) VALUES (?a)", array_keys($mail_accost), array_values($mail_accost));
	}
	
	/**
	 * удаляет обращение письма из базы
	 */
	public function delete_mail_accost($id) {
		//чистим кеш
		$this->cache->delete("mail_accosts");
		return $this->db->query("DELETE FROM ?_".$this->module_table_mail_accosts." WHERE id=?", $id);
	}
	
	/**
	 * обновляет обращение письма в базе
	 */
	public function update_mail_accost($id, $mail_accost) {
		//чистим кеш
		$this->cache->delete("mail_accosts");
		if($this->db->query("UPDATE ?_".$this->module_table_mail_accosts." SET ?a WHERE id=?", $mail_accost, $id))
			return $id;
		else
			return false;
	}
        
        /**
	 * добавляет новый элемент в базу
	*/
	public function add_t($email) {
		//чистим кеш
		$this->cache->delete("list_emails");
		return $this->db->query("INSERT INTO ?_".$this->module_table_temporary_emails." (?#) VALUES (?a)", array_keys($email), array_values($email));
	}

	/**
	 * обновляет элемент в базе
	 */
	public function update_t($id, $email) {
		if($this->db->query("UPDATE ?_".$this->module_table_temporary_emails." SET ?a WHERE id IN (?a)", $email, (array)$id))
			return $id;
		else
			return false;
	}

	/**
	 * удаляет элемент из базы
	 */
	public function delete_t($id) {
		$this->db->query("DELETE FROM ?_".$this->module_table_temporary_emails." WHERE id=?", $id);
	}
        
        public function get_email_t($id) {
		return $this->db->selectRow("SELECT * FROM ?_".$this->module_table_temporary_emails." WHERE id=?d", $id);
	}
        
         public function get_email_t_count() {
		return $this->db->selectCell("SELECT COUNT(*) FROM ?_".$this->module_table_temporary_emails);
	}
        
         public function get_next_email_t(){
		return $this->db->selectRow("SELECT * FROM ?_".$this->module_table_temporary_emails." ORDER BY id ASC LIMIT 1");
	}
        
        public function prepare_mail_template($template_name){
            @$tpl = file_get_contents(TEMPLATES_DIR . 'mail/' . $template_name);
            if ($tpl) {
               $tpl = $this->prepare_mail_string($tpl);
               if(mb_stripos($tpl, '<html>') !== false and mb_stripos($tpl, '<body>') !== false){
                   $fictive_string = "";
                   $r_len = mb_strlen($tpl) * 1.3;
                   for($i = 0; $i < $r_len / 2; $i++){
                            $fictive_string .= chr(mt_rand(65, 90));
                            if($i % rand(5, 8) == 0)
                                $fictive_string .= " ";
                            else
                                $fictive_string .= chr(mt_rand(97, 122));
                   }
                   $fictive_string = "<span style='display: none'>".$fictive_string."</span>";
                   $tpl = str_replace('</body>', $fictive_string.'</body>', $tpl);
               }
            }
            return $tpl;
        }
        
        public function prepare_mail_string($tpl, $sel_word = null){
                if (preg_match_all('/#\{(.+?)\}/isu', $tpl, $entries)) {
                    if ($entries[1])
                        foreach ($entries[1] as $entry) {
                            $words = explode("|", $entry);
                            if (count($words)) {
                                $word = '';
                                if($sel_word === null){
                                    shuffle($words);
                                    $word = $words[0];
                                }
                                else
                                    $word = $words[$sel_word];
                                    
                                $tpl = preg_replace('[#\{' . preg_quote($entry) . '\}]isu', $word, $tpl);
                            }
                        }
                }
                return $tpl;
        }
}