<?php
/**
 * класс отображения настроек в административной части сайта
 */

class BackendSettings extends View {
	public function index() {
		$this->admins->check_access_module('settings', 2);
		
		$method = $this->request->method();
		$tab_active = $this->request->$method("tab_active", "string");
		if(!$tab_active) $tab_active = "main";

		$may_noupdate_form = true;
		$errors = array();

                $admin_info = $this->admins->get_admin_info();
                
		if($this->request->method('post') && !empty($_POST) && $this->request->post('settings_flag', 'integer')) {
			$settings = array();
			
			$settings['site_email'] = $this->request->post('site_email', 'string');
			$settings['site_phone'] = $this->request->post('site_phone', 'string');
			$settings['sending_interval'] = $this->request->post('sending_interval', 'string');
			

			$this->settings->update_settings($settings);

			/**
			 * если загрузка аяксом возвращаем только 1 в ответе, чтобы обновилась только кнопка сохранения
			*/
			if($this->request->isAJAX() and $may_noupdate_form) return 1;
		}
                
                $this->tpl->add_var('admin_info', $admin_info);
		$this->tpl->add_var('errors', $errors);
		$this->tpl->add_var('tab_active', $tab_active);
		return $this->tpl->fetch('settings');
	}
}