<?php
/**
 * класс для отправки почты
 * @author riol
 *
 */
class Mail extends System {
	private $pathToMailQueue;
	private $prevId;

	public function __construct() {
		$this->prevId = 0;
		parent::__construct();
		/**
		 * подключаем необходимые файлы
		*/
		$this->pathToMailQueue =  strtr(ROOT_DIR_SERVICE.'mailQueue/', '\\', '/');
		require_once dirname(__FILE__).'/external/PHPMailer/class.phpmailer.php';
	}

	/**
	 * отправляет письмо
	 * @param array $to = array(email, имя)
	 * @param string $subject
	 * @param string $html_mail
	 */
	public function send_mail($to, $subject, $html_mail, $outQueue = false, $smtpParams = array(), $id = 0, $send_group = '', $debug = false) {
		if(!$outQueue or ($smtpParams and isset($smtpParams['fictive_server']) and $smtpParams['fictive_server'])){
			$this->addToQueue(array($to, $subject, $html_mail, $smtpParams, $id, $send_group, $debug));
			return;
		}
                
		$mail = new PHPMailer(); // defaults to using php "mail()"
		$from_mail = ($smtpParams)?$smtpParams['login']:$this->settings->site_email;
                
		if($smtpParams){
                        if(!$debug and isset($smtpParams['debug_mode']) and $smtpParams['debug_mode'])
                            $debug = true;
                    
			$mail->IsSMTP();
			$mail->CharSet = 'UTF-8';
                        $mail->Hostname = 'localhost.localdomain';
			$mail->Host       = $smtpParams['host'];                    // SMTP server example
			$mail->SMTPDebug  = $debug?3:0;                             // enables SMTP debug information (for testing)
                        $mail->Debugoutput  = 'into_global_variable';
                        $mail->XMailer = 'Microsoft Office Outlook, Build 12.0.4210X-MimeOLE: Produced By Microsoft MimeOLE V6.00.2800.1165';
			$mail->SMTPAuth   = true;                                   // enable SMTP authentication
			$mail->Port       = $smtpParams['port'];                    // set the SMTP port for the GMAIL server
			$mail->Username   = $smtpParams['login'];                   // SMTP account username example
			$mail->Password   = $smtpParams['password'];                // SMTP account password example
			$mail->SMTPSecure = $smtpParams['secure'];
		}

		$mail->SetFrom($from_mail, $to[1]);
		$mail->AddReplyTo($from_mail, $from_mail);
		$mail->AddAddress($to[0], $to[0]);
		$mail->Subject = $subject;
		$mail->MsgHTML($html_mail);

		$errorInfo = $mail->Send();
                
                if($smtpParams){
                    $old = $this->servers->get_server(intval($smtpParams['id']));
                    if($old)
                       $this->servers->update($old['id'], array('sending_count' => $old['sending_count'] + 1, 
                           'enabled' => (!$errorInfo and ($debug or rand(1, 3) == 3))?0:1, 
                           'sent_for_the_day' => $old['sent_for_the_day'] + 1,
                           'last_sending_moment' => time()
                            ));
                    
                    if($debug and isset($GLOBALS['phpmailer_debug_info'])){
                        $di = $GLOBALS['phpmailer_debug_info'];
                        if($old){
                            $this->servers->update($old['id'], array('debug_info' => $di));
                        }
                    }
                }
                
		if($id and $this->prevId != $id){
			$old = $this->emails->get_email(intval($id));
			if($old)
				$this->emails->update($id, array('last_sending' => time(), 'send_group' => $send_group, 'sending_count' => $old['sending_count'] + 1));
			$this->prevId = $id;
		}
                
                if($send_group)
                    $this->emails->update_statistics($send_group);
	}
        
	/**
	 * Status of sending
	 * @param array $params
	 * @return string
	 */
	public function status($params = false){
		$old_status = $this->settings->mail_auto_sender_status;
		if($params !== false){
			$this->settings->update_settings(array('mail_auto_sender_status' => serialize($params)));
		}
		else{
			if($old_status and ($old_status = unserialize($old_status))){
				$result = "Сообщений в очереди: ".$old_status['current_num']."<br/>";
                                @$rem_mails = glob(ROOT_DIR_SERVICE.'mailQueue/*');
                                $rem_mails = (count($rem_mails) + 1) * ($this->settings->sending_interval + 5);
                                $result .= ("Прибл. время до завершения: ".$rem_mails."сек.<br/>");
				$result .= ("Отправляет на: ".$old_status['current_email']."<br/>");
                                $result .= ("Рассылка: ".$old_status['current_group'].' - '.$old_status['current_topic']."<br/>");
				return $result;
			}
			else{
				return "Ожидание отправки ...";
			}
		}
	}

	public function addToQueue($data){
		$tempName = tempnam($this->pathToMailQueue, 'ml');
		$fp = fopen($tempName, "w");
		if($fp){
			flock($fp, LOCK_EX);
			fwrite($fp, serialize($data));
			flock($fp, LOCK_UN);
			fclose($fp);
		}
		else{
			$this->errorHandlerObject->Push(ERROR_HANDLER_WARNING, 34, 'Ошибка добавления в очередь email сообщений');
		}
	}

	public function sendFromQueue($num = 1){
		$sentCount = 0;
		$dir = opendir($this->pathToMailQueue);
		if($dir){
			$result = array();
			while($name = readdir($dir)){
				if(($name != '.') and ($name != '..')){
					$result[] = $name;
				}
			}
			closedir($dir);
			$currentNum = count($result);
			if($currentNum){
				$currentEmail = $currentTopic = "";

				foreach($result as $name){
					$path = $this->pathToMailQueue.$name;
					@$data = file_get_contents($path);
					@$data = unserialize($data);
					if($data){
                                                //server distributing
                                                if($data[3]){
                                                    $data[3] = $this->servers->distribute_for($data[0][0]);
                                                    if(isset($data[3]['fictive_server']) and $data[3]['fictive_server'])
                                                        continue;
                                                }
						$currentEmail = $data[0][0];
						$currentTopic = $data[1];
						$this->send_mail($data[0],$data[1],$data[2], true, $data[3], $data[4], $data[5], $data[6]);
						if(!$sentCount)
							$this->status(array('current_num' => $currentNum, 'current_email' => $currentEmail, 'current_topic' => $currentTopic, 'current_group' => $data[5]?$data[5]:'Неизвестно'));
						$sentCount++;
					}
					@unlink($path);
					if($sentCount >= $num)
						break;
				}

				 
			}
			else
				$this->status(array());

			return array($currentNum, $sentCount);
		}
		else{
			$this->errorHandlerObject->Push(ERROR_HANDLER_WARNING, 34, 'Не найден каталог очереди email');
		}
	}
}