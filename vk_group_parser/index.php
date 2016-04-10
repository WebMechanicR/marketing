<?php
error_reporting(E_ALL);
set_time_limit(0);

require_once dirname(dirname(__FILE__)).'/config/config.php';
require_once dirname(__FILE__).'/external/DB.php';
require_once dirname(__FILE__).'/external/RollingCurl.class.php';
require_once dirname(__FILE__).'/external/simpleHtml/simple_html_dom.php';

define("GROUP_LIST_SOURCE",  dirname(__FILE__).'/vk_group_list.txt');
define("COOKIE_FILE", strtr(dirname(__FILE__).("/cookie.txt"), "\\", "/"));

header("Content-type: text/html; charset=utf-8");

if(isset($_GET['strip_tags']) and $_GET['strip_tags']) {
    
    $result = preg_replace('/<.+?>/is', ' ', (isset($_POST['msg'])?$_POST['msg']:''));
    ?>
        <form action="index.php?strip_tags=1" method="post">
            <input type="submit" value="Вырезать теги"/><br/>
            <textarea cols="200" rows="100" name="msg"><?php echo $result; ?></textarea>
        </form>
    <?php
}
else{
    
$db = new DB();

$curl = new RollingCurl();

if(!file_exists(COOKIE_FILE))
      fclose(fopen(COOKIE_FILE, 'a+'));

$num_queries = 32;
$source = array();
$file = file(GROUP_LIST_SOURCE);

$info['rows_remaining_in_source'] = 0;
$info['queries'] = 0;
$info['inserted_groups'] = 0;
$info['inserted_clients'] = 0;
$info['updated_groups'] = 0;
$for_client_types_str = $db->selectCell("SELECT value FROM ?_settings WHERE name = 'vk_group_parser_org_types_for_importing'");
$for_client_types = explode(",", $for_client_types_str);
if($for_client_types)
    $info['for_client_types'] = $for_client_types_str;

if($file and is_array($file)){
    for($i = 0; $i < $num_queries; $i++){
        $source[] = array_shift($file);
        if(!$file)
            break;
    }
    
    $fp = fopen(GROUP_LIST_SOURCE, 'w');
    fwrite($fp, implode("\n", array_map("trim", $file)));
    fclose($fp);
    
    $info['rows_remaining_in_source'] = count($file);
    unset($file);
    
    if($source and is_array($source)){
        $options[CURLOPT_COOKIEFILE] = COOKIE_FILE;
        $options[CURLOPT_COOKIEJAR] = COOKIE_FILE;
        $options[CURLOPT_AUTOREFERER] = true;
        $options[CURLOPT_USERAGENT] = 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; GTB6; .NET CLR 2.0.50727; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729; AskTbBT5/5.14.1.20007)';
        $options[CURLOPT_TIMEOUT] = 7;
        $options[CURLOPT_CONNECTTIMEOUT] = 3;
        
        for($i = 0; $i < $num_queries; $i++){
            $url = trim(array_shift($source));
          
            if($url){
                if(!$existing_group = $db->selectRow("SELECT id, for_client_types FROM ?_vk_groups_list WHERE link = ?", $url)){
                    $curl->get($url, null, $options);
                    $response = iconv('windows-1251', 'utf-8', $curl->execute());
                    $html = str_get_html($response);
                    $new_club = array("link" => $url, "for_client_types" => $for_client_types?implode(",", $for_client_types):'');
                    
                    if($html)
                    {
                        $group_wide = $html->find("#group_wide", 0);
                        $client = array();
                        if($group_wide){
                            @$new_club['title'] = trim($group_wide->find('div.top_header', 0)->plaintext);
                            @$status = trim($group_wide->find('#page_current_info span', 0)->plaintext);
                            @$main_block = " ".$new_club['title']." ".$status. " ";
                            @$main_block .= ($group_wide->find('#wall_fixed', 0)->plaintext . " ");
                            @$main_block .= ($group_wide->find('div.group_info', 0)->plaintext . " ");
                            @$main_block .= ($group_wide->find('div.group_wiki_wrap', 0)->plaintext . " ");
                            @$new_club['followers'] = intval(preg_replace('/[^0-9]/is', '', $html->find('#group_followers div.p_header_bottom', 0)->innertext)); 
                            @$contacts = $html->find('#group_contacts', 0);
                            if($contacts){
                                @$new_club['num_contacts'] = intval(preg_replace('/[^0-9]/is', '', $contacts->find('div.p_header_bottom', 0)->innertext)); 
                                $contacts = $contacts->find('div.line_cell');
                                foreach($contacts as $contact){
                                    @$client['name'] = trim($contact->find('div.people_name a', 0)->plaintext);
                                    @$client['vk_link'] = 'http://vk.com'.$contact->find('div.people_name a', 0)->href;
                                    @$client['vk_desc'] = trim($contact->find('div.people_desc', 0)->plaintext);
                                    @$extra = $contact->find('div.people_extra', 0)->plaintext;
                                    $main_block .= (" ".$extra." ".$client['vk_desc']." ");
                                    $client['phone'] = implode(", ", extract_phones($extra));
                                    $client['email'] = implode(", ", extract_emails($extra));
                                }
                            }
                            
                            $new_club['found_sites'] = implode(", ", extract_domains($main_block));
                            $new_club['found_phones'] = implode(", ", extract_phones($main_block));
                            $new_club['found_emails'] = implode(", ", extract_emails($main_block));
                        }
                        
                        $group_id = $db->query("INSERT INTO ?_vk_groups_list (?#) VALUES (?a)", array_keys($new_club), array_values($new_club));
                        $info['inserted_groups']++;
                        
                        if((isset($client['vk_link']) and $client['vk_link'] and !$db->query("SELECT id FROM ?_client_base WHERE vk_link = ?", $client['vk_link']))){
                              $client['vk_group_id'] = $group_id;
                              $db->query("INSERT INTO ?_client_base (?#) VALUES (?a)", array_keys($client), array_values($client));
                              $info['inserted_clients']++;
                        }
                    } 
                }
                else if($for_client_types){
                    $old_types = explode(",", $existing_group['for_client_types']);
                    if($old_types != $for_client_types){
                        $for_client_types = array_merge($for_client_types, $old_types);
                        $for_client_types = array_unique($for_client_types);
                        $info['updated_groups']++;
                        $db->query("UPDATE ?_vk_groups_list SET for_client_types = ? WHERE id = ?d", implode(",", $for_client_types), $existing_group['id']);
                    }
                }
            }
            $info['queries']++;
            
            if(!$source)
                break;
        }
    }
    
    echo var_export($info, true);
}
else{
    echo 'empty!';
}
}

function extract_domains($str){
    $str = " ".$str." ";
    $res = array();
    if(preg_match_all('/([\w^_]([\w\-^_]{0,61}[\w^_])?\.)+[\w^_]{2,6}/isu', $str, $matches)){
        $matches = array_map(function($arg){ return str_replace('www.', '', $arg); }, $matches[0]);
        foreach($matches as $domain){
            $domain = mb_strtolower(trim($domain));
            if(!$domain or preg_match('/\.jpg$|\.gif$|\.jpeg$|\.png$|^vk\.|^vkontakte\.|\.vk\.|\.vkontakte\.|^this\.|\.php|^wall\.|^event\.|groups\.|^2f|\.$|mail\.|yandex\.|google\.|gmail\.|rambler\.|[0-9]$/isu', $domain) or
                    (preg_match('/[а-яА-ЯЁё]/isu', $domain) and !preg_match('/\.рф$/isu', $domain)))
                        continue;
            
            $res[] = $domain;
        }
        $res = array_unique($res);
    }
    return $res;
}

function extract_phones($str){
    $str = " ".$str." ";
    $res = array();
    if(preg_match_all('/\+?[\d\-\)\(\s]+/is', $str, $matches)){
        foreach($matches[0] as $phone){
            $phone = trim($phone);
            if(mb_strlen($phone) > 6 and !preg_match('/\-{2,}|[\)\(]{2,}/is', $phone))
                $res[] = $phone;
        }
        $res = array_unique($res);
    }
    return $res;
}

function extract_emails($str){
    $str = " ".$str." ";
    $res = array();
    if(preg_match_all('/[a-z0-9_\-\+]+@[a-z0-9\-]+\.([a-z]{2,3})(?:\.[a-z]{2})?/is', $str, $matches)){
        foreach($matches[0] as $mail){
            $mail = mb_strtolower(trim($mail));
            $res[] = $mail;
        }
        $res = array_unique($res);
    }
    return $res;
}

?>