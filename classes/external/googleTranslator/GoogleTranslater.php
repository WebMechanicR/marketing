<?php
/**
 * GoogleTranslater is a PHP interface for http://translate.google.com/
 * It sends a request to the google translate service, gets a response and
 * provides the translated text.
 *
 * @author Andrew Kulakov <avk@8xx8.ru>
 * @version 1.0.0
 * @license https://github.com/Andrew8xx8/GoogleTranslater/blob/master/MIT-LICENSE.txt
 * @copyright Andrew Kulakov (c) 2011
 */

require_once(dirname(__FILE__)."/RollingCurl.class.php");
require_once(dirname(__FILE__)."/AngryCurl.class.php");

define('AC_DIR', dirname(__FILE__));

class GoogleTranslater
{
	/**
	 * @var string Some errors
	 */
	private $_errors = "";

	private $AC;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->AC = new AngryCurl(null);

		$this->AC->load_proxy_list(
				AC_DIR . DIRECTORY_SEPARATOR . 'proxy_list.txt',
				# optional: number of threads
				200,
				# optional: proxy type
				'http',
				# optional: target url to check
				'http://google.com',
				# optional: target regexp to check
				'title>G[o]{2}gle'
		);
		$this->AC->load_useragent_list( AC_DIR . DIRECTORY_SEPARATOR . 'useragent_list.txt');

		if (!function_exists('curl_init'))
			$this->_errors = "No CURL support";
	}
	

	/**
	 * Translate text.
	 * @param  string $text          Source text to translate
	 * @param  string $fromLanguage  Source language
	 * @param  string $toLanguage    Destenation language
	 * @param  bool   $translit      If true function return transliteration of source text
	 * @return string|bool           Translated text or false if exists errors
	 */
	public function translateText($text, $fromLanguage = "en", $toLanguage = "ru", $translit = false)
	{
		if (empty($this->_errors)) {
			$result = "";
			$i = 0;
			while($i < mb_strlen($text))
			{
				if(mb_strlen($text)<600) $subText = $text;
				else {
					$pos = mb_strrpos(mb_substr($text, $i, 600), ' ');
					if($pos) $subText = mb_substr($text, $i, $pos);
					else $subText = mb_substr($text, $i, 600);
				}
				$i += mb_strlen($subText);

				if(trim($subText)!='') {
					$response = $this->_curlToGoogle("http://translate.google.com/translate_a/t?client=te&text=".urlencode($subText)."&hl=$toLanguage&sl=$fromLanguage&tl=i$toLanguage&multires=1&otf=1&ssel=0&tsel=0&uptl=ru&sc=1");
					$result .= $this->_parceGoogleResponse($response, $translit);
				}
				//                sleep(1);
			}
			return $result;
		} else
			return false;
	}

	/**
	 * Translate array.
	 * @param  array  $array         Array with source text to translate
	 * @param  string $fromLanguage  Source language
	 * @param  string $toLanguage    Destenation language
	 * @param  bool   $translit      If true function return transliteration of source text
	 * @return array|bool            Array  with translated text or false if exists errors
	 */
	public function translateArray($array, $fromLanguage = "en", $toLanguage = "ru", $translit = false)
	{
		if (empty($this->_errors)) {
			$text = implode(" [<#>] ", $array);
			$response = $this->translateText($text, $fromLanguage, $toLanguage, $translit);
			return $this->_explode($response);
		} else
			return false;
	}

	public function getLanguages()
	{
		if (empty($this->_errors)) {
			$page = $this->_curlToGoogle('http://translate.google.com/');
			preg_match('%<select[^<]*?tl[^<]*?>(.*?)</select>%is', $page, $match);
			preg_match_all("%<option.*?value=\"(.*?)\">(.*?)</option>%is", $match[0], $languages);
			$result = Array();
			for($i = 0; $i < count($languages[0]); $i++){
				$result[$languages[1][$i]] = $languages[2][$i];
			}
			return $result;
		} else
			return false;
	}

	public function getLanguagesHTML()
	{
		if (empty($this->_errors)) {
			$page = $this->_curlToGoogle('http://translate.google.com/');
			preg_match('%<select[^<]*?tl[^<]*?>(.*?)</select>%is', $page, $match);
			return $match[1];
		} else
			return false;
	}

	public function getErrors()
	{
		return $this->_errors;
	}

	private function _explode($text)
	{
		$text = preg_replace("%\[\s*<\s*#\s*>\s*\]%", "[<#>]", $text);
		return array_map('trim', explode('[<#>]', $text));
	}

	private function _curlToGoogle($url)
	{

		$this->AC->get($url);
		$response = $this->AC->execute();


		/*$curl = curl_init();
		 curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		if (isset($_SERVER['HTTP_REFERER'])) {
		curl_setopt($curl, CURLOPT_REFERER, $_SERVER['HTTP_REFERER']);
		}
		curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/534.24 (KHTML, like Gecko) Chrome/11.0.696.71 Safari/534.24");
		$response = curl_exec($curl);
		// Check if any error occured
		if(curl_errno($curl))
		{
		$this->_errors .=  "Curl Error: ".curl_error($curl);
		return false;
		}
		curl_close($curl);*/
		return $response;
	}

	private function _parceGoogleResponse($response, $translit = false)
	{
		if (empty($this->_errors)) {
			$result = "";
			$json = json_decode($response);
			if($json and isset($json->sentences)) {
				foreach ($json->sentences as $sentence) {
					$result .= $translit ? $sentence->translit : $sentence->trans;
				}
			}
			return $result;
		}  else {
			return false;
		}
	}
}
?>
