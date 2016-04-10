<?php
/**
 * класс отображения текстовых страниц в пользовательской части сайта
 */

class FrontedEmails extends View {
	public function index() {
		return false;
	}
        
        public function register_opening_email() {
		$email = $this->request->get('e', 'string');
                $delivery_id = $this->request->get('ti', 'integer');
                if($delivery_id and $delivery = $this->emails->get_email_mail(intval($delivery_id))){
                    $this->emails->register_opening_email($this->emails->prepare_mail_string($delivery['title'], 0));
                }
                $img = imagecreatefrompng (TEMPLATES_DIR.'img/transparent.png');
                header("Content-type: image/png");
                imagepng($img);
                exit;
	}
}