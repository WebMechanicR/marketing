<?php
class Servers extends Module implements IElement {

	protected $module_name = "servers";
	private $module_table = "servers";
	private $module_nesting = true; //возможность вкладывать подстраницы в модуль
        private $cached = null;
        
	private $module_settings = array(
			"dir_images" => "img/",
			"image_sizes"=> array (
					"normal"=> array(280, 280, true, false),// ширина, высота, crop, watermark
					"small"=> array(50, 50, false, false)
			),
			"images_content_type" => "servers",
			"revisions_content_type" => "servers"

	);


	/**
	 * добавляет новый элемент в базу
	*/
	public function add($server) {
		//чистим кеш
		$this->cache->delete("list_servers");
		return $this->db->query("INSERT INTO ?_".$this->module_table." (?#) VALUES (?a)", array_keys($server), array_values($server));
	}

	/**
	 * обновляет элемент в базе
	 */
	public function update($id, $server) {

		//чистим кеш
		$this->cache->delete("list_servers");
		
		if($this->db->query("UPDATE ?_".$this->module_table." SET ?a WHERE id IN (?a)", $server, (array)$id))
			return $id;
		else
			return false;
	}

	/**
	 * удаляет элемент из базы
	 */
	public function delete($id) {
		if($server = $this->get_server($id)) {


			$this->db->query("DELETE FROM ?_".$this->module_table." WHERE id=?", $id);

			//чистим кеш
			$this->cache->delete("list_servers");

			$this->clear_revisions($id);
		}
	}

	/**
	 * создает копию элемента
	 */
	public function duplicate($id) {
		$new_id = null;
		if($server = $this->get_server($id)) {
				
			unset($server['id']);
			
			$server['enabled'] = 0;
				
			$new_id = (int)$this->add($server);
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
		if($content = $this->get_server($for_id)) {
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
	public function get_server($id) {
		return $this->db->selectRow("SELECT * FROM ?_".$this->module_table." WHERE id=?d", $id);
	}

	/**
	 * возвращает новости удовлетворяющие фильтрам
	 * @param array $filter
	 */
	public function get_list_servers($filter=array()) {
		$limit = "";
		$where = "";

		if(isset($filter['limit']) and count($filter['limit'])==2) {
			$filter['limit'] = array_map("intval", $filter['limit']);
			$limit = " LIMIT ".($filter['limit'][0]-1)*$filter['limit'][1].", ".$filter['limit'][1];
		}

		if(isset($filter['enabled'])) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.enabled=".intval($filter['enabled']);
		}
                
                if(isset($filter['login'])) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.login LIKE '%".$filter['login']."%'";
		}
		
		return $this->db->select("SELECT n.*
				FROM ?_".$this->module_table." n".$where." ORDER BY n.sort DESC".$limit);
	}
        
        public function get_count_servers($filter=array()) {
		$where = "";

		if(isset($filter['enabled'])) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.enabled=".intval($filter['enabled']);
		}

                if(isset($filter['login'])) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.login LIKE '%".$filter['login']."%'";
		}
		

		return $this->db->selectCell("SELECT count(n.id)
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
	 * добавляет изображение
	 * @param int $server_id
	 * @param string $image
	 * @param string $name
	 * @param int $sort
	 * @return boolean
	 */
	public function add_image($server_id, $image) {
		$image_sizes = $this->setting("image_sizes");
		if(!$this->image->create_image(ROOT_DIR_IMAGES.$this->setting("dir_images")."original/".$image, ROOT_DIR_IMAGES.$this->setting("dir_images")."normal/".$image, $image_sizes["normal"])) return false;
		if(!$this->image->create_image(ROOT_DIR_IMAGES.$this->setting("dir_images")."original/".$image, ROOT_DIR_IMAGES.$this->setting("dir_images")."small/".$image, $image_sizes["small"])) return false;

		$this->update($server_id, array("img"=>$image));
		return $server_id;
	}


	public function delete_image($id) {
		$server = $this->get_server($id);
		if($server and $server['img']!="") {
			//проверяем, не используется ли это изображение где-то еще
			$count = $this->db->selectCell("SELECT count(*) FROM ?_".$this->module_table." WHERE img=?", $server['img']);
			if($count==1) {
				@unlink(ROOT_DIR_IMAGES.$this->setting("dir_images")."original/".$server['img']);
				@unlink(ROOT_DIR_IMAGES.$this->setting("dir_images")."normal/".$server['img']);
				@unlink(ROOT_DIR_IMAGES.$this->setting("dir_images")."small/".$server['img']);
			}
			$this->update($id, array("img"=>""));
		}
	}

	/**
	 * возвращает порядок сортировки для добавляемой страницы
	 */
	public function get_new_server_sort() {
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
				array('{url_page}(\/?)', 'module=servers&page_url={url_page}')
		);
	}
	
	public function get_servers_for_site(){
		$servers = array();
		if(($servers = $this->cache->get('list_servers')) === false){
			$servers= $this->get_list_servers(array('enabled' => 1));
			$this->cache->set($servers, 'list_servers', array(), false);
		}
		return $servers;
	}
        
        public function distribute_for($email_addr){
            $fict = array('fictive_server' => 1);
            if(!$this->cached)
                $this->cached = $this->get_servers_for_site();
            if($this->cached){
                //1)extracts these which have exceeded limit and blocks them if it is necessary
                //2)redistribute between according
                $ready_servers = array();
                foreach($this->cached as $server){
                    if(time() - $server['start_of_the_day'] > 86400){
                        $this->update($server['id'], array('start_of_the_day' => time(), 'sent_for_the_day' => 0));
                        $server['sent_for_the_day'] = 0;
                    }
                    if($server['sending_count'] > 990){
                        $this->update($server['id'], array('enabled' => 0));
                        continue;
                    }
                    if($server['day_limit'] <= $server['sent_for_the_day'])
                        continue;
                    if(preg_match('/yandex\.ru$/is', $server['login']) and time() - $server['last_sending_moment'] < 61)
                        continue;
                    $ready_servers[] = $server;
                }
                
                if($ready_servers){
                    $required_pattern = "";
                    if(preg_match('/mail\.ru$|bk\.ru$|inbox\.ru$|list\.ru$/is', $email_addr)){
                        if(!$server['spam_in_mail'])
                            $required_pattern = "/bk\.ru$|inbox\.ru$|mail\.ru$|list\.ru$/is";
                        else 
                           $required_pattern = "/[^(bk\.ru)(inbox\.ru)(mail\.ru)(list\.ru)]$/is"; 
                    }
                    else if(preg_match('/gmail\.com$/is', $email_addr)){
                         if(!$server['spam_in_gmail'])
                            $required_pattern = "/gmail\.com$/is";
                        else 
                           $required_pattern = "/[^(gmail\.com)]$/is"; 
                    }
                    else if(preg_match('/yandex\.ru/is', $email_addr)){
                         if(!$server['spam_in_yandex'])
                            $required_pattern = "/yandex\.ru$/is";
                        else 
                           $required_pattern = "/[^(yandex\.ru)]$/is"; 
                    }
                    else if(preg_match('/rambler\.ru/is', $email_addr)){
                         if(!$server['spam_in_rambler'])
                            $required_pattern = "/rambler\.ru$/is";
                        else 
                           $required_pattern = "/[^(rambler\.ru)]$/is"; 
                    }

                    if($required_pattern){
                        $t_ready_servers = array();
                        $count = 0;
                        foreach($ready_servers as $server){
                            if(preg_match($required_pattern, $server['login']))
                                    $t_ready_servers[] = $server;
                            if($count++ > 20)
                                break;
                        }
                        if($t_ready_servers){
                            shuffle($t_ready_servers);
                            return $t_ready_servers[0];
                        }
                    }

                    if($ready_servers){
                        shuffle($ready_servers);
                        return $ready_servers[0];
                    }
                }
            }
            
            return $fict;
        }
}