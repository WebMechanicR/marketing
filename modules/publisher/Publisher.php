<?php

require_once dirname(dirname(dirname(__FILE__))).'/classes/external/simpleHtml/simple_html_dom.php';
require_once(dirname(dirname(dirname(__FILE__)))."/classes/external/AngryCurl/RollingCurl.class.php");
class Publisher extends Module {

	protected $module_name = "publisher";
	private $module_table = "publisher";
        private $module_table_proxies = "proxies";
        
	private $module_nesting = false; //возможность вкладывать подстраницы в модуль

	private $module_settings = array(
			"dir_images" => "img/",
			"image_sizes"=> array (
					"normal"=> array(280, 280, true, false),// ширина, высота, crop, watermark
					"small"=> array(50, 50, false, false)
			),
			"images_content_type" => "publisher",
			"revisions_content_type" => "publisher"

	);
        
        private $curl = false;
        
        public function __construct(){
            parent::__construct();
            if(!$this->curl)
                $this->curl = new RollingCurl();
        }


        public function request($url, $options = array()){
            $result = array();
            $cookieName = "";
            if(isset($options['cookie'])){
                $cookieName = strtr(ROOT_DIR_SERVICE.("cookies/{$options['cookie']}.txt"), "\\", "/");
                if(!isset($options['flush_cookie']))
                    $options[CURLOPT_COOKIEFILE] = $cookieName;
                else{
                    unset($options['flush_cookie']);
                    @unlink($cookieName);
                }
                
                $options[CURLOPT_COOKIEJAR] = $cookieName;
                if(!file_exists($cookieName))
                    fclose(fopen($cookieName, 'a+'));
                
                unset($options['cookie']);
            }
            
            if(!isset($options[CURLOPT_USERAGENT]))
                $options[CURLOPT_USERAGENT] = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:36.0) Gecko/20100101 Firefox/36.0';
            $options[CURLOPT_SSL_VERIFYPEER] = false;
            $options[CURLOPT_SSL_VERIFYHOST] = false;
            
            if(!isset($options['do_not_follow_location'])){
                $options[CURLOPT_FOLLOWLOCATION] = true;
                $options[CURLOPT_AUTOREFERER] = true;
            }
            else
                unset($options['do_not_follow_location']);
            
            if(isset($options['referer'])){
                $options[CURLOPT_REFERER] = $options['referer'];
                unset($options['referer']);
            }
            
            $encoding = "";
            if(isset($options['encoding'])){
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
            
            $headers = null;
            if(isset($options['headers'])){
                $headers = $options['headers'];
                unset($options['headers']);
            }
            
            $is_html_parse = false;
            if(isset($options['html'])){
                $is_html_parse = $options['html'];
                unset($options['html']);
            }
           
            $this->curl->request($url, $method, $post_data, $headers, $options);
            $response = $this->curl->execute();
            
            if($encoding){
                $response = iconv($encoding, 'utf-8//IGNORE', $response);
            }
            if($is_html_parse){
                $response = str_get_html($response);
            }
            
            $result['response'] = $response;
            return $result;
        }
        
        public function get($url, $options = array()){
            if(isset($options['post_data']))
                unset($options['post_data']);
            $res = $this->request($url, $options);
            return $res['response'];
        }
        
        public function post($url, $options = array()){
            if(!isset($options['post_data']))
                $options['post_data'] = array('fict' => 1);
            $res = $this->request($url, $options);
            return $res['response'];
        }
        
        public function fl_auto_reply_parser(){
             if(!$this->settings->is_fl_auto_reply_disabled){
                    $su = 'https://www.fl.ru/';
                    $allowed = true;
                    @$status = unserialize($this->settings->is_fl_auto_reply_status);
                    @$action_info = unserialize($this->settings->is_fl_auto_reply_action_info);
                    if(!$status){
                        $action_info = array();
                        $status = array('action' => 'Авторизация ...');
                    }
                    @$settings = unserialize($this->settings->fl_auto_reply_settings);
                    
                    $options['encoding'] = 'windows-1251';
                    $options['cookie'] = 'fl.ru';
                    $options['html'] = true;
                    $options['headers'] = array(
                        'Host' => 'www.fl.ru'            
                    );
                    if(!$settings or !$settings['answers'] or !$settings['login'])
                    {
                        $allowed = false;
                        $status['error'] = 'Выполните настройки для автоответов!';
                    } 
                    
                    $next_moment = 0;
                    if($allowed and $this->settings->is_fl_auto_reply_recommended_next_query_time - time() <= 0){
                        
                        if(!isset($action_info['url'])){
                            $action_info['url'] = $su;
                            $options['flush_cookie'] = true;
                        }
                       
                        $res = $this->publisher->get($action_info['url'], $options);
                        if($res){
                            $action_info['failed_requests'] = 0;
                            $status['error'] = '';
                            if(isset($options['flush_cookie']))
                                unset($options['flush_cookie']);
                            
                            if($lfrm = $res->find('#lfrm') and $res->find('#lfrm', 0)->find('input[name=login]')){
                                //login
                                $u_token_key = '';
                                if(preg_match('/var _TOKEN_KEY = \'(\w+)\'/isu', $res, $u_token_key)){
                                    $u_token_key = trim($u_token_key[1]);
                                }
                                $options['headers']['Referer'] = 'https://www.fl.ru/';
                                
                                $options['post_data'] = array(
                                    'action' => 'login',
                                    'autologin' => 1,
                                    'login' => $settings['login'],
                                    'passwd' => $settings['pass'],
                                    'u_token_key' => $u_token_key
                                );
                                
                                $res = $this->publisher->post($su, $options);
                                if($res and $user_menu = $res->find('ul.b-user-employee-menu', 0)){
                                    $action_info = array('url' => $su.'projects/?kind=1');
                                }
                                else{
                                    if(isset($action_info['failed_login_requests']) and $action_info['failed_login_requests'] > 5){
                                        $this->errorHandlerObject->Push(ERROR_HANDLER_WARNING, 1, "Cannot login on fl.ru");
                                        $status['error'] = 'Ошибка входа. Проверьте правильность логина и пароля';
                                        $next_moment = time() + 60 * 7;
                                    }
                                    if(isset($action_info['failed_login_requests']))
                                        $action_info['failed_login_requests']++;
                                    else
                                        $action_info['failed_login_requests'] = 1;
                                }
                            }
                            else{
                                //logged in
                                $continue = true;
                                if(!isset($action_info['answer_info']))
                                    $action_info['answer_info'] = array('i' => 0, 'p' => 1, 'filter_applied' => false);
                               
                                $answer = isset($settings['answers'][$action_info['answer_info']['i']])?$settings['answers'][$action_info['answer_info']['i']]:false;
                               
                                if(!$answer){
                                    $action_info = null;
                                }
                                else{
                                    if($answer['spec'] and !$action_info['answer_info']['filter_applied']){
                                        $status['action'] = 'Применение фильтров по ответу "'.$answer['title'].'"';
                                        //applying filters
                                        $action_info['answer_info']['p'] = 1;
                                        
                                        $u_token_key = '';
                                        if(preg_match('/var _TOKEN_KEY = \'(\w+)\'/isu', $res, $u_token_key)){
                                            $u_token_key = trim($u_token_key[1]);
                                        }
                                        
                                        $options['post_data'] = array(
                                          'action' => 'postfilter',
                                          'kind' => 1,
                                          'pf_subcategory' => "",
                                          'comboe_columns[1]' => 0,
                                          'comboe_columns[0]' => 0,
                                          'comboe_column_id' => 0,
                                          'comboe_db_id' => 0,
                                          'comboe' => "Все специализации",
                                          'pf_cost_from' => "",
                                          'currency_text_columns[1]' => 0,
                                          'currency_text_columns[0]' => 2,
                                          'currency_text_column_id' => 0,
                                          'currency_text_db_id' => 2,
                                          'pf_currency' => 2,
                                          'currency_text' => 'Руб',
                                          'pf_keywords' => '',
                                          'u_token_key' => $u_token_key
                                        );
                                        
                                        $i = 0;
                                        foreach($answer['spec'] as $spec){
                                            $options['post_data']['pf_categofy[0]['.$spec.']'] = 0;
                                        }
                                        $options['headers']['Referer'] = $su.'projects';
                                        
                                        $res = $this->publisher->post($su.'projects/', $options);
                                        if($res){
                                            $action_info['answer_info']['filter_applied'] = true;
                                            $action_info['answer_info']['p'] = 1;
                                        }
                                        else
                                            $continue = false;
                                    }
                                   
                                    if($continue){
                                        if(!isset($action_info['do_answer'])){
                                            //analizing rows
                                            $stopped = false;
                                            $status['action'] = 'Поиск подходящих проектов по ответу "'.$answer['title'].'". Страница '.$action_info['answer_info']['p'].' ...';
                                            if($action_info['answer_info']['p'] == 1 and isset($action_info['last_answered'][$action_info['answer_info']['i']])){
                                                $t = $action_info['last_answered'][$action_info['answer_info']['i']]['prev'];
                                                $action_info['last_answered'][$action_info['answer_info']['i']]['prev'] = $action_info['last_answered'][$action_info['answer_info']['i']]['new'];
                                                $action_info['last_answered'][$action_info['answer_info']['i']]['new'] = $t;
                                            }
                                            
                                            $rows_found = false;
                                            $rows = $res->find('#projects-list', 0);
                                            if($rows)
                                                $rows = $rows->find('div.b-post');
                                            
                                            if($rows){
                                                foreach($rows as $row){
                                                    if($title = $row->find('h2.b-post__title', 0) and $title = $title->find('a', 0)){
                                                        $rows_found = true;
                                                        $pinned = $row->find('h2.b-post__pin', 0)?true:false;
                                                        $item['title'] = trim($title->innertext);
                                                        $item['link'] = $su.trim($title->href, '/');
                                                        $item['id'] = 0;
                                                        if(preg_match('[^'.$su.'projects/(\d+)(.*)]isu', $item['link'], $pockets)){
                                                            $item['id'] = intval($pockets[1]);
                                                        }
                                                        
                                                        if(!$item['title'] or !$item['id'])
                                                            continue;
                                                       
                                                        $item['desc'] = "";
                                                        $item['moment'] = 0;
                                                        $item['status'] = 0; //0 - i can answer; 1 - my answer have been made; 2 - employee defined; 3 - answering forbidden
                                                        $other = $row->find('script', 1);
                                                        if($other and $other = $other->innertext){
                                                              if(preg_match('/<div class="b-post__txt ">(.*?)<\/div>/is', $other, $pockets)){
                                                                  $item['desc'] = $pockets[1];
                                                              }
                                                        }
                                                        
                                                        $other = $row->find('script', 2);
                                                        $other = str_get_html(preg_replace('/<script type="text\/javascript">document\.write\(\'(.*?)\'\);<\/script>/is', '$1', $other));
                                                       
                                                        if($other){
                                                            $stat = $other->find('a.b-post__link');
                                                            if($stat){
                                                                foreach($stat as $st){
                                                                    if(mb_stripos($st, 'исполнитель') !== false)
                                                                        $item['status'] = 2;
                                                                    if(mb_stripos($st, 'ваш ответ') !== false)
                                                                        $item['status'] = 1;
                                                                }
                                                            }
                                                            
                                                            if(preg_match('/&nbsp;&nbsp;([\w\s,:]+)&nbsp;&nbsp;/isu', $other, $pockets)){
                                                                $date = $pockets[1];
                                                                
                                                                if(mb_stripos($date, 'что') !== false)
                                                                       $item['moment'] = time();
                                                                   
                                                                if(preg_match('#(?:(\d+)\s*(?:часов|часа|час))?(?:\s*(\d+)\s*(?:минут|минуты|минуту|минута))#isu', $date, $pockets)){
                                                                    $hours = isset($pockets[1])?intval($pockets[1]):0;
                                                                    $minutes = isset($pockets[2])?intval($pockets[2]):0;
                                                                    $item['moment'] = time() - $hours * 3600 - $minutes * 60;
                                                                }
                                                                else if(preg_match('#(\d+)\s*(?:часов|часа|час)#isu', $date, $pockets)){
                                                                    $hours = isset($pockets[1])?intval($pockets[1]):0;
                                                                    $item['moment'] = time() - $hours * 3600;
                                                                }
                                                                else{
                                                                    $date = trim(str_replace(array(
                                                                        ',',
                                                                        'января',
                                                                        'февраля',
                                                                        'марта',
                                                                        "апреля",
                                                                        'мая',
                                                                        "июня",
                                                                        "июля",
                                                                        "августа",
                                                                        "сентября",
                                                                        "октября",
                                                                        "ноября",
                                                                        "декабря"
                                                                    ), array(
                                                                       '',
                                                                       '1',
                                                                       '2',
                                                                       '3',
                                                                       '4',
                                                                       '5',
                                                                       '6',
                                                                       '7',
                                                                       '8',
                                                                       '9',
                                                                       '10',
                                                                       '11',
                                                                       '12'
                                                                    ), $date));
                                                                    @list($day, $month, $time) = explode(" ", $date);
                                                                    @list($hours, $minutes) = explode(":", $time);
                                                                    @$item['moment'] = mktime(trim($hours), trim($minutes), 0, trim($month), trim($day), date('Y') - ((($month - date('m') >= 10))?1:0));
                                                                    
                                                                }
                                                            }
                                                        }
                                                        
                                                        if(!$item['moment']){
                                                            
                                                            $status['error'] = 'Не могу получить момент публикации у проекта '. mb_substr($item['title'], 0, 100);
                                                            //$this->errorHandlerObject->Push(ERROR_HANDLER_WARNING, 1, "Не могу получить момент публикации у проекта " . mb_substr($item['title'], 0, 100));
                                                        }
                                                        
                                                        $item['according_to_answer'] = false;
                                                        if(!$item['status'] and $item['title'] and $item['id']){
                                                            $regexps = explode("\n", $answer['regexp']);
                                                            $a_regexps = explode("\n", $answer['a_regexp']);
                                                            $new_a_regexps = array();
                                                           
                                                            
                                                            if($regexps)
                                                                foreach($regexps as $regexp){
                                                                    $regexp = trim($regexp);
                                                                    if($regexp){
                                                                        if(@preg_match('/'.$regexp.'/isu', $item['title'])){
                                                                            $item['according_to_answer'] = array('anw' => $answer['title'], 'acc_by' => 'title');
                                                                            break;
                                                                        }
                                                                    }
                                                                }
                                                                
                                                           if(!$item['according_to_answer'] and trim($item['desc']) and $a_regexps)
                                                                foreach($a_regexps as $regexp){
                                                                    $regexp = trim($regexp);
                                                                    if($regexp){
                                                                        if(@preg_match('/'.$regexp.'/isu', $item['desc'])){
                                                                            $item['according_to_answer'] = array('anw' => $answer['title'], 'acc_by' => 'desc');
                                                                            break;
                                                                        }
                                                                    }
                                                                } 
                                                           
                                                            if($item['according_to_answer']){
                                                                if(!isset($action_info['do_answer']))
                                                                    $action_info['do_answer'] = array();
                                                                $action_info['do_answer'][] = array('item' => $item, 'tpl' => $answer['template'], 'test_mode' => $answer['test_mode']?true:false);
                                                            }
                                                        }
                                                        
                                                        if($item['id']){
                                                            //stat
                                                            if(!isset($status['statistics']))
                                                                $status['statistics'] = array();
                                                            if(!isset($status['statistics']['viewed_rows'])){
                                                                $status['statistics']['viewed_rows'] = array();
                                                            }
                                                            if(!isset($status['statistics']['moment_of_last_veiwed_row']))
                                                                $status['statistics']['moment_of_last_row'] = time();

                                                            if($item['moment'] and $item['moment'] < $status['statistics']['moment_of_last_row'] and !$pinned)
                                                                $status['statistics']['moment_of_last_row'] = $item['moment'];
                                                            
                                                            $found = false;
                                                            foreach($status['statistics']['viewed_rows'] as $val){
                                                                if($val['id'] == $item['id']){
                                                                    $found = true;
                                                                    break;
                                                                }
                                                            }
                                                            
                                                            if(!$found)
                                                                array_unshift($status['statistics']['viewed_rows'], $item);
                                                            
                                                            if(count($status['statistics']['viewed_rows']) > 5000){
                                                                $status['statistics']['viewed_rows'] = array_slice($status['statistics']['viewed_rows'], 0, 1000);
                                                                $status['statistics']['moment_of_last_row'] = $status['statistics']['viewed_rows'][count($status['statistics']['viewed_rows']) - 1]['moment'];
                                                            }
                                                            
                                                            if(!isset($action_info['last_answered']))
                                                                $action_info['last_answered'] = array();
                                                            if(!isset($action_info['last_answered'][$action_info['answer_info']['i']])){
                                                                    $action_info['last_answered'][$action_info['answer_info']['i']] = array('prev' => 0, 'new' => 0);  
                                                            }
                                                            if($action_info['last_answered'][$action_info['answer_info']['i']]['new'] < $item['id'])
                                                                $action_info['last_answered'][$action_info['answer_info']['i']]['new'] = $item['id'];
                                                           
                                                            if(($item['moment'] and !$pinned and (time() - $item['moment'] > $settings['parsing_deep'] * 3600)) or $action_info['answer_info']['p'] > 300 or 
                                                                    (!$pinned and $action_info['last_answered'][$action_info['answer_info']['i']]['prev'] and $action_info['last_answered'][$action_info['answer_info']['i']]['prev'] >= $item['id'])){
            
                                                                $stopped = true;
                                                                break;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                            
                                            if(!$rows_found){
                                                $stopped = true;
                                            }
                                           
                                            if(!$stopped){
                                                $action_info['answer_info']['p']++;
                                                $action_info['url'] = $su.'projects/?kind=1&page='.$action_info['answer_info']['p'];
                                            }
                                            else
                                            {
                                                $a_i = $action_info['answer_info']['i'] + 1;
                                                if(count($settings['answers'])<= $a_i){
                                                    $do_answer = false;
                                                    if(isset($action_info['do_answer']))
                                                        $do_answer = $action_info['do_answer'];
                                                    
                                                    $last_answered = array();
                                                    if(isset($action_info['last_answered']))
                                                        $last_answered = $action_info['last_answered'];
                                                    
                                                    $action_info = array('url' => $su.'projects/?kind=1');
                                                    
                                                    if($last_answered)
                                                        $action_info['last_answered'] = $last_answered;
                                                    
                                                    if($do_answer){
                                                        $action_info['do_answer'] = $do_answer;
                                                        $action_info['wait_after_answering'] = $settings['updating_interval'] * 60 + time();
                                                    }
                                                    else
                                                        $next_moment = $settings['updating_interval'] * 60 + time();
                                                    $status['action'] = 'Ожидание обновления...';
                                                }
                                                else
                                                {
                                                    $action_info['answer_info'] = array('i' => $a_i, 'p' => 1, 'filter_applied' => false);
                                                    $action_info['url'] = $su.'projects/?kind=1';
                                                }
                                            }
                                            
                                            if(isset($action_info['do_answer'])){
                                                $action_info['url_after_answering'] = $action_info['url'];
                                                $a = current($action_info['do_answer']);
                                                $action_info['url'] = $a['item']['link'];
                                            }
                                        }
                                        else{
                                            //do answer
                                            $requested = false;
                                            $data = current($action_info['do_answer']);
                                            $item = $data['item'];
                                            $status['action'] = 'Ответ на проект "'.mb_substr($item['title'], 0, 100).'" ...';
                                            $form = $res->find('#form_add_offer', 0);
                                            if(!$form){
                                                $res = $this->publisher->get(trim($action_info['url']), $options);
                                                if($res)
                                                    $form = $res->find('#form_add_offer', 0);
                                            }
                                            
                                            if($form){
                                                //adding offer
                                                if($data['test_mode']){
                                                    $item['status'] = 1;
                                                    $item['answered_with_testing'] = 1;
                                                    $requested = true;
                                                }
                                                else{
                                                    $u_token_key = '';
                                                    if(preg_match('/var _TOKEN_KEY = \'(\w+)\'/isu', $res, $u_token_key)){
                                                        $u_token_key = trim($u_token_key[1]);
                                                    }
                                                    
                                                    $options['post_data'] = array(
                                                      'action' => 'add',
                                                      'edit' => 0,
                                                      'hash' => $form->find('input[name=hash]', 0)->value,
                                                      'pid' => $form->find('input[name=pid]', 0)->value,
                                                      'f' => '',
                                                      'u' => '',
                                                      'ps_cost_from' => '',
                                                      'ps_cost_type' => 2,
                                                      'ps_for_customer_only' => 1,
                                                      'ps_is_color' => '',
                                                      'ps_payed_items' => '',
                                                      'ps_time_from' => '',
                                                      'ps_time_time' => 0,
                                                      'u_token_key' => $u_token_key,
                                                      'ps_text' => $data['tpl']
                                                    );
                                                    
                                                   
                                                    if(preg_match('/var works_ids = new Array\(\)(.+?)function submitAddFileForm\(\) \{/is', $res, $pockets)){
                                                        $works = $pockets[1];
                                                        if(preg_match_all('/works_ids\[(\d+)\] = \'\d+\'/is', $works, $pockets)){
                                                            if(!is_array($pockets[1]))
                                                                $pockets[1] = array($pockets[1]);
                                                            $i = 1;
                                                            foreach($pockets[1] as $v){
                                                                $options['post_data']['ps_portfolio_work_'.$v] = $v;
                                                                $options['post_data']['ps_work_'.$i.'_id'] = $v;
                                                                $i++;
                                                                if($i > 3)
                                                                    break;
                                                            }
                                                        }
                                                        if(preg_match_all('/works_names\[\d+\] = \'(.+?)\'/isu', $works, $pockets)){
                                                            if(!is_array($pockets[1]))
                                                                $pockets[1] = array($pockets[1]);
                                                            $i = 1;
                                                            foreach($pockets[1] as $v){
                                                                $options['post_data']['ps_work_'.$i.'_name'] = $v;
                                                                $i++;
                                                                if($i > 3)
                                                                    break;
                                                            }
                                                        }
                                                        if(preg_match_all('/works_prevs\[\d+\] = \'(.*?)\'/isu', $works, $pockets)){
                                                            if(!is_array($pockets[1]))
                                                                $pockets[1] = array($pockets[1]);
                                                            $i = 1;
                                                            foreach($pockets[1] as $v){
                                                                $options['post_data']['ps_work_'.$i.'_prev_pict'] = $v;
                                                                $i++;
                                                                if($i > 3)
                                                                    break;
                                                            }
                                                        }
                                                        if(preg_match_all('/works_picts\[\d+\] = \'(.*?)\'/isu', $works, $pockets)){
                                                            if(!is_array($pockets[1]))
                                                                $pockets[1] = array($pockets[1]);
                                                            $i = 1;
                                                            foreach($pockets[1] as $v){
                                                                $options['post_data']['ps_work_'.$i.'_pict'] = $v;
                                                                $i++;
                                                                if($i > 3)
                                                                    break;
                                                            }
                                                        }
                                                        if(preg_match_all('/works_links\[\d+\] = \'(.*?)\'/isu', $works, $pockets)){
                                                            if(!is_array($pockets[1]))
                                                                $pockets[1] = array($pockets[1]);
                                                            $i = 1;
                                                            foreach($pockets[1] as $v){
                                                                $options['post_data']['ps_work_'.$i.'_link'] = $v;
                                                                $i++;
                                                                if($i > 3)
                                                                    break;
                                                            }
                                                        }
                                                    }
                                                    
                                                    $options['headers']['Referer'] = $item['link'];
                                                    
                                                    $res = $this->publisher->post($item['link'], $options);
                                                    if($res){
                                                        $action_info['failed_answers'] = 0;
                                                        $requested = true;
                                                        $item['status'] = 1;
                                                    }
                                                    else{
                                                        if(!isset($action_info['failed_answers']))
                                                            $action_info['failed_answers'] = 1;
                                                        else
                                                            $action_info['failed_answers']++;
                                                        $status['error'] = 'Ошибка при ответе...';
                                                        if($action_info['failed_answers'] > 7){
                                                            $action_info['failed_answers'] = 0;
                                                            $requested = true;
                                                        }
                                                    }
                                                }
                                            }
                                            else{
                                                 
                                                 $requested = true;
                                                 $item['status'] = 3;
                                                 
                                                 //if is not pro
                                                 $not_pro = true;
                                                 $pro = $res->find('.b-user-employee-menu', 0);
                                                 if($pro)
                                                     $pro = $pro->find('.b-user-menu-pro-clause', 0);
                                                
                                                 if($pro and mb_stripos($pro, 'Активен до') !== false)
                                                         $not_pro = false;
                                                 if($not_pro){
                                                     
                                                     $status['error'] = 'Невозможно делать ответы. Необходим аккаунт PRO';
                                                 }
                                            }
                                           
                                            
                                            if($requested){
                                                array_shift($action_info['do_answer']);
                                            }
                                            
                                            {
                                                //stat
                                                if(isset($status['statistics']['viewed_rows'])){
                                                    foreach($status['statistics']['viewed_rows'] as &$row){
                                                        if($item['id'] == $row['id']){
                                                            $row = $item;
                                                            break;
                                                        }
                                                    }
                                                }
                                            }
                                            
                                            if(!$action_info['do_answer']){
                                                $action_info['url'] = $action_info['url_after_answering'];
                                                if(isset($action_info['wait_after_answering'])){
                                                    $next_moment = $action_info['wait_after_answering'];
                                                    unset($action_info['wait_after_answering']);
                                                    $status['action'] = 'Ожидание обновления...';
                                                }
                                                unset($action_info['url_after_answering']);
                                                unset($action_info['do_answer']);
                                            }
                                            else{
                                                $a=current($action_info['do_answer']);
                                                $action_info['url'] = $a['item']['link'];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        else{
                            if(isset($action_info['failed_requests']) and $action_info['failed_requests'] > 7){
                                $this->errorHandlerObject->Push(ERROR_HANDLER_WARNING, 1, "Cannot make request to fl.ru");
                                $next_moment = time() + 60 * 5;
                                $status['error'] = 'Ошибка запроса. Сайт недоступен';
                            }
                            if(isset($action_info['failed_requests']))
                                $action_info['failed_requests']++;
                            else
                                 $action_info['failed_requests'] = 1;
                        }
                        
                      
                           
                        
                       
                        $this->settings->update_settings(array("is_fl_auto_reply_action_info" => serialize($action_info)));
                        $this->settings->update_settings(array("is_fl_auto_reply_recommended_next_query_time" => $next_moment));
                        $this->settings->update_settings(array("is_fl_auto_reply_status" => serialize($status)));
                       
                    }
                }
            return isset($status['action'])?$status['action']:1; 
        }
        
       /**
	 * добавляет новый элемент в базу
	*/
	public function add_proxy($server) {
		//чистим кеш
		$this->cache->delete("list_proxies");
		return $this->db->query("INSERT INTO ?_".$this->module_table_proxies." (?#) VALUES (?a)", array_keys($server), array_values($server));
	}

	/**
	 * обновляет элемент в базе
	 */
	public function update_proxy($id, $server) {

		//чистим кеш
		$this->cache->delete("list_proxies");
		
		if($this->db->query("UPDATE ?_".$this->module_table_proxies." SET ?a WHERE id IN (?a)", $server, (array)$id))
			return $id;
		else
			return false;
	}

	/**
	 * удаляет элемент из базы
	 */
	public function delete_proxy($id) {
		if($server = $this->get_proxy($id)) {


			$this->db->query("DELETE FROM ?_".$this->module_table_proxies." WHERE id=?", $id);

			//чистим кеш
			$this->cache->delete("list_proxies");

		}
	}

	/**
	 * возвращает новость по id
	 * @param mixed $id
	 * @return array
	 */
	public function get_proxy($id) {
		return $this->db->selectRow("SELECT * FROM ?_".$this->module_table_proxies." WHERE id=?d", $id);
	}

	/**
	 * возвращает новости удовлетворяющие фильтрам
	 * @param array $filter
	 */
	public function get_list_proxies($filter=array()) {
		$limit = "";
		$where = "";

		if(isset($filter['limit']) and count($filter['limit'])==2) {
			$filter['limit'] = array_map("intval", $filter['limit']);
			$limit = " LIMIT ".($filter['limit'][0]-1)*$filter['limit'][1].", ".$filter['limit'][1];
		}

		if(isset($filter['enabled'])) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.enabled=".intval($filter['enabled']);
		}
                
                if(isset($filter['level']) and $filter['level']) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.anonymous=".intval($filter['level']);
		}
                
                if(isset($filter['country']) and $filter['country']) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.country LIKE '%".$filter['country']."%'";
		}
                
                if(isset($filter['levels']) and $filter['levels']) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.anonymous IN(".implode(",", $filter['levels']).")";
		}
                
                if(isset($filter['countries']) and $filter['countries']) {
                        foreach($filter['countries'] as &$country)
                            $country = "n.country LIKE '%".$country."%'";
			$where .= (empty($where) ? " WHERE " : " AND ")."(".implode(" OR ", $filter['countries']).")";
		}
                
                if(isset($filter['date_from'])) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.last_checking >= ".$filter['date_from'];
		}

		if(isset($filter['date_to']) and $filter['date_to'] > 0){
			$where .= (empty($where) ? " WHERE " : " AND ")."n.last_checking <= ".$filter['date_to'];
		}
		
		return $this->db->select("SELECT n.*
				FROM ?_".$this->module_table_proxies." n".$where." ORDER BY n.last_checking DESC".$limit);
	}
        
        public function get_count_proxies($filter=array()) {
		$where = "";

		if(isset($filter['enabled'])) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.enabled=".intval($filter['enabled']);
		}

                if(isset($filter['date_from'])) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.last_checking >= ".$filter['date_from'];
		}

		if(isset($filter['date_to']) and $filter['date_to'] > 0){
			$where .= (empty($where) ? " WHERE " : " AND ")."n.last_checking <= ".$filter['date_to'];
		}
                
                 if(isset($filter['level']) and $filter['level']) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.anonymous=".intval($filter['level']);
		}
                
                if(isset($filter['country']) and $filter['country']) {
			$where .= (empty($where) ? " WHERE " : " AND ")."n.country LIKE '%".$filter['country']."%'";
		}
                
                
		return $this->db->selectCell("SELECT count(n.id)
				FROM ?_".$this->module_table_proxies." n".$where);
	}
        
        public function get_new_proxy_sort() {
		return $this->db->selectCell("SELECT MAX(sort) as sort FROM ?_".$this->module_table_proxies)+1;
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
                return $this->db->selectCell("SELECT count(n.id)
				FROM ?_".$this->module_table_proxies." n".$where);
	
        }
        
        public function check_proxy(){
            return;
	    
            if(!F::mutex_lock("for_proxy_checking"))
                return;
	    
            if(!function_exists('for_proxy_checking_request_end')){
                $GLOBALS['m_system'] = $this;
                $GLOBALS['m_scanned_successful'] = 0;
                
                function for_proxy_checking_request_end($response, $info, $request){
                     global $m_system;
                     if(preg_match('/^id:(\d+)/is', $response, $pockets)){
                        $id = intval($pockets[1]);
                        echo $response,'<br/>';
                        if(preg_match('/ok_checking_proxy,(\d+)/is', $response, $pockets)){
                            $level = intval($pockets[1]);
                            $m_system->update_proxy($id, array('enabled' => 1, 'anonymous' => $level, 'last_checking' => time(), 'checking_count' => 0));
                            $GLOBALS['m_scanned_successful']++;
                        }
                        else{
                            $old = $m_system->get_proxy($id);
                            if($old){
                                if($old['checking_count'] >= 7 and !$old['do_not_delete']){
                                    $m_system->delete_proxy($id);
                                }
                                else{
                                    $m_system->update_proxy($id, array('enabled' => 0, 'last_checking' => time(), 'checking_count' => $old['checking_count'] + 1));
                                }
                            }
                        }
                    }
                }
            }
            
            $threads = 64;
            @$status = unserialize($this->settings->proxy_scaner_status);
            if(!$status){
               $status['scanned'] = 0;
               $status['scanned_successful'] = 0;
               $status['scanning_of_enabled'] = 0;
               $status['stopped'] = 0;
            }
            $proxies = $this->db->select("SELECT * FROM ?_".$this->module_table_proxies." WHERE enabled = {$status['scanning_of_enabled']} ORDER BY last_checking ASC, checking_count ASC, anonymous DESC LIMIT ".$threads);
            
            @$settings = unserialize($this->settings->proxy_scaner_settings);
            if(!$proxies or (time() - $proxies[0]['last_checking'] <= (isset($settings['interval'])?$settings['interval']*2*60:4000))){
                  if($status['scanning_of_enabled']){
                    $this->settings->update_settings(array("proxy_scaner_recommended_next_query_time" => time() + $settings['interval']*60));
                    $status['stopped'] = 1;
                    $status['scanned_successful'] = 0;
                    $status['scanned'] = 0;
                  }
                 
                  $status['scanning_of_enabled'] = (int) !$status['scanning_of_enabled'];
                  $proxies = array();
            }
            
            if($proxies){
                $status['stopped'] = 0;
                $curl = new AngryCurl('for_proxy_checking_request_end');
                foreach($proxies as $proxy){
                    $type = $proxy['type_http'].','.$proxy['type_https'].','.$proxy['type_socks4'].','.$proxy['type_socks5'];
                    $curl->get(SITE_URL.'ajax/check_proxy.php?ip='.$proxy['ip'].'&id='.$proxy['id'].'&username='.$proxy['login'].'&pass='.$proxy['password'].'&type='.$type.'&port='.$proxy['port']);
                }
                  
                $curl->execute(count($proxies));
                
                $status['scanned'] +=count($proxies);
                $status['scanned_successful'] += $GLOBALS['m_scanned_successful'];
            }
            
            $this->search_proxies($status, $settings);
            
            $this->settings->update_settings(array("proxy_scaner_status" => serialize($status)));
            
            F::mutex_lock("for_proxy_checking", true);
        }
        
        public function search_proxies(&$status, $settings){
            if(!isset($status['next_query_time_for_moment']))
                $status['next_query_time_for_moment'] = 0;
            $options[CURLOPT_CONNECTTIMEOUT] = 10;
            $options[CURLOPT_TIMEOUT] = 21;
            
            if($status['next_query_time_for_moment'] - time() <= 0){
                $status['importing_stopped'] = 0;
                if(!isset($status['current_import_source']))
                    $status['current_import_source'] = 'foxtools.ru';

                $result = array();
                
                if($status['current_import_source'] == 'xroxy.com'){
                     $options['encoding'] = 'iso-8859-1';
                     $options['html'] = 1;
                     $options['headers'] = array(
                       'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                       'Accept-Encoding' => 'gzip, deflate, sdch',
                       'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
                       'Cache-Control' => 'max-age=0',
                       'Connection' => 'keep-alive',
                       'Host' => '/www.xroxy.com'
                     );
                     $options['referer'] = 'http://www.xroxy.com/proxylist.php';
                     $options[CURLOPT_USERAGENT] = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.101 Safari/537.36';
                     $p = isset($status['current_import_source_xroxy.com_p'])?$status['current_import_source_xroxy.com_p']:0;
                     
                     $table = array();
                     $rows = array();
                     $tryings = 0;
                     do{
                        $table = array();
                        $res = $this->publisher->get('http://www.xroxy.com/proxylist.php?desc=true&pnum='.$p, $options); 
                        if(!$res and $tryings++ > 3)
                            continue;
                        
                        $rows = array();
                        if($res and @$table = $res->find('#content table', 1)){
                             $rows = $table->find('tr.row1');
                             $rows2 = $table->find('tr.row0');
                             if($rows2)
                                 foreach($rows2 as $row)
                                     $rows[] = $row;
                             if($rows){
                                 for($i = 1; $i < count($rows); $i++){
                                     $tr = $rows[$i];
                                     $item = array();
                                     @$item['ip'] = $this->request->get_str($tr->find('td', 1)->find('a', 0)->plaintext, 'string');
                                     @$item['port'] = $this->request->get_str($tr->find('td', 2)->find('a', 0)->plaintext, 'integer');
                                     @$item['country'] = $this->request->get_str(str_replace('&nbsp;', ' ', $tr->find('td', 5)->find('a', 0)->plaintext), 'string');
                                     $item['anonymous'] = 1;
                                     $item['type_http'] = 1;
                                     @$type_str = $this->request->get_str($tr->find('td', 3)->find('a', 0)->plaintext, 'string');
                                     if(mb_stripos($type_str, 'https') !== false)
                                              $item['type_https'] = 1;
                                     if(mb_stripos($type_str, 'socks') !== false){
                                              $item['type_socks4'] = 1;
                                              $item['type_https'] = 1;
                                     }
                                     if(mb_stripos($type_str, 'socks5') !== false)
                                              $item['type_socks5'] = 1;
                                     $result[] = $item;
                                 }
                             }
                        }
                        
                        $p++;
                     }while($table and $rows and $p % 10 != 0 and $p <= 300);
                     
                     if($rows and $p <= 300){
                         $status['current_import_source_xroxy.com_p'] = $p;
                     }
                     else{
                         if(isset($status['current_import_source_xroxy.com_p']))
                             unset($status['current_import_source_xroxy.com_p']);
                         $status['current_import_source'] = 'google-proxy.net';
                     }
                }
                else if($status['current_import_source'] == 'google-proxy.net'){
                    //$options['encoding'] = 'windows-1251';
                     $options['html'] = 1;
                     $options['headers'] = array(
                       'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                       'Accept-Encoding' => 'gzip, deflate, sdch',
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
                     $res = $this->publisher->get('http://www.google-proxy.net/', $options); 
                     
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
                    $status['current_import_source'] = 'foxtools.ru';
                }
                else{
                     //$options['encoding'] = 'windows-1251';
                     $options['html'] = 1;
                     $options['headers'] = array(
                       'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                       'Accept-Encoding' => 'gzip, deflate, sdch',
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
                        $table = array();
                        $res = $this->get('http://foxtools.ru/Proxy?page='.$p, $options); 
                        if(!$res and $tryings++ > 3)
                            continue;
                        
                        if($res and @$table = $res->find('#theProxyList', 0)){
                             $rows = $table->find('tr');
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
                                 }
                             }
                        }
                        $p++;
                     }while($table and $p <= 21);
                     $status['current_import_source'] = 'xroxy.com'; //next /*IMPORTANT */
                }
                
                if($status['current_import_source'] == 'foxtools.ru'){
                    $status['importing_stopped'] = 1;
                    $status['next_query_time_for_moment'] = time() + $settings['import_interval'] * 60;
                }
                    
                $inserteded = 0;
                foreach($result as $item){
                    if($item['ip'] and $item['port'] and !$this->get_familiar_proxy($item)){
                        $this->add_proxy($item);
                        $inserteded++;
                    }
                }
                
                $status['last_found_for_importing'] = count($result);
                $status['last_found_for_inserting'] = $inserteded;
            }
        }
}