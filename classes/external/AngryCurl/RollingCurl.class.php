<?php
/*
Authored by Josh Fraser (www.joshfraser.com)
Released under Apache License 2.0
 * Modified by Tim Jackson (fixing bug of timeout when using laid down proxy)

Maintained by Alexander Makarov, http://rmcreative.ru/

$Id$
 * 
*/

/**
 * Class that represent a single curl request
 */
class RollingCurlRequest {
    public $url = false;
    public $method = 'GET';
    public $post_data = null;
    public $headers = null;
    public $options = null;

    /**
     * @param string $url
     * @param string $method
     * @param  $post_data
     * @param  $headers
     * @param  $options
     * @return void
     */
    function __construct($url, $method = "GET", $post_data = null, $headers = null, $options = null) {
        $this->url = $url;
        $this->method = $method;
        $this->post_data = $post_data;
        $this->headers = $headers;
        $this->options = $options;
    }

    /**
     * @return void
     */
    public function __destruct() {
        unset($this->url, $this->method, $this->post_data, $this->headers, $this->options);
    }
}

/**
 * RollingCurl custom exception
 */
class RollingCurlException extends Exception {
}

/**
 * Class that holds a rolling queue of curl requests.
 *
 * @throws RollingCurlException
 */
class RollingCurl {
    /**
     * @var int
     *
     * Window size is the max number of simultaneous connections allowed.
     *
     * REMEMBER TO RESPECT THE SERVERS:
     * Sending too many requests at one time can easily be perceived
     * as a DOS attack. Increase this window_size if you are making requests
     * to multiple servers or have permission from the receving server admins.
     */
    private $window_size = 5;

    /**
     * @var float
     *
     * Timeout is the timeout used for curl_multi_select.
     */
    private $timeout = 1.0;

    /**
     * @var string|array
     *
     * Callback function to be applied to each result.
     */
    private $callback;

    
    /**
     * @var array
     *
     * Set your base options that you want to be used with EVERY request.
     */
    protected $options = array(
        CURLOPT_SSL_VERIFYPEER => 0,
	CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_TIMEOUT => 30
    );

    /**
     * @var array
     */
    private $headers = array();

    /**
     * @var Request[]
     *
     * The request queue
     */
    private $requests = array();

    /**
     * @var RequestMap[]
     *
     * Maps handles to request indexes
     */
    private $requestMap = array();

    /**
     * @param  $callback
     * Callback function to be applied to each result.
     *
     * Can be specified as 'my_callback_function'
     * or array($object, 'my_callback_method').
     *
     * Function should take three parameters: $response, $info, $request.
     * $response is response body, $info is additional curl info.
     * $request is the original request
     *
     * @return void
     */
    
    private $control_timeout = false;
    
    function __construct($callback = null) {
        $this->callback = $callback;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        return (isset($this->{$name})) ? $this->{$name} : null;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return bool
     */
    public function __set($name, $value) {
        // append the base options & headers
        if ($name == "options" || $name == "headers") {
            $this->{$name} = $value + $this->{$name};
        } else {
            $this->{$name} = $value;
        }
        return true;
    }

    /**
     * Add a request to the request queue
     *
     * @param Request $request
     * @return bool
     */
    public function add($request) {
        $this->requests[] = $request;
        return true;
    }

    /**
     * Create new Request and add it to the request queue
     *
     * @param string $url
     * @param string $method
     * @param  $post_data
     * @param  $headers
     * @param  $options
     * @return bool
     */
    public function request($url, $method = "GET", $post_data = null, $headers = null, $options = null) {
        
        $this->requests[] = new RollingCurlRequest($url, $method, $post_data, $headers, $options);
        return true;
    }

    /**
     * Perform GET request
     *
     * @param string $url
     * @param  $headers
     * @param  $options
     * @return bool
     */
    public function get($url, $headers = null, $options = null) {
        return $this->request($url, "GET", null, $headers, $options);
    }

    /**
     * Perform POST request
     *
     * @param string $url
     * @param  $post_data
     * @param  $headers
     * @param  $options
     * @return bool
     */
    public function post($url, $post_data = null, $headers = null, $options = null) {
        return $this->request($url, "POST", $post_data, $headers, $options);
    }

    /**
     * Execute processing
     *
     * @param int $window_size Max number of simultaneous connections
     * @return string|bool
     */
    public function execute($window_size = null) {
        // rolling curl window must always be greater than 1
	
	$control_timeout = false;
	@$request = $this->requests[0];
	if($options = $this->get_options($request) and isset($options[CURLOPT_PROXY]))
	    $control_timeout = true;
	$this->control_timeout = $control_timeout;
	
	if($control_timeout){  
	    if((sizeof($this->requests) == 1)){
		$GLOBALS['RollingCurl_returned_data'] = false;
		if(!function_exists("RollingCurl_for_control_timeout")){
		    function RollingCurl_for_control_timeout($response, $info, $request){
			global $RollingCurl_returned_data;
			$RollingCurl_returned_data = $response;
		    }
		}
		$this->callback = "RollingCurl_for_control_timeout";
		
		$this->rolling_curl($window_size);
		$response = $GLOBALS['RollingCurl_returned_data'];
		return $response;
	    }
	    else
		return $this->rolling_curl($window_size);
	}
        else if ((sizeof($this->requests) == 1)) {
            return $this->single_curl();
        } else {
            // start the rolling curl. window_size is the max number of simultaneous connections
            return $this->rolling_curl($window_size);
        }
    }

    /**
     * Performs a single curl request
     *
     * @access private
     * @return string
     */
    private function single_curl() {
        $ch = curl_init();
        $request = array_shift($this->requests);
        $options = $this->get_options($request);
        
        curl_setopt_array($ch, $options);
        $output = curl_exec($ch);
        $info = curl_getinfo($ch);

        // it's not neccesary to set a callback for one-off requests
        if ($this->callback) {
            $callback = $this->callback;
            if (is_callable($this->callback)) {
                call_user_func($callback, $output, $info, $request);
            }
        }
        else
            return $output;
        return true;
    }

    /**
     * Performs multiple curl requests
     *
     * @access private
     * @throws RollingCurlException
     * @param int $window_size Max number of simultaneous connections
     * @return bool
     */
    private function rolling_curl($window_size = null) {
        if ($window_size)
            $this->window_size = $window_size;

        // make sure the rolling window isn't greater than the # of urls
        if (sizeof($this->requests) < $this->window_size)
            $this->window_size = sizeof($this->requests);

        if ($this->window_size < 2 and !$this->control_timeout) {
            throw new RollingCurlException("Window size must be greater than 1");
        }

        $master = curl_multi_init();
	$requestOptions = array();
	$requestStartmoments = array();
	$requestHandlers = array();
        // start the first batch of requests
        for ($i = 0; $i < $this->window_size; $i++) {
            $ch = curl_init();
	    // Add to our request Maps
            $key = (string) $ch;
            $this->requestMap[$key] = $i;
	    
            $options = $this->get_options($this->requests[$i]);
	    if($this->control_timeout){
		if(isset($options[CURLOPT_CONNECTTIMEOUT]))
		    unset($options[CURLOPT_CONNECTTIMEOUT]);
		$requestOptions[$key] = $options;
		$requestStartmoments[$key] = time();
		$requestHandlers[$key] = $ch;
	    }
	  
            curl_setopt_array($ch, $options);
            curl_multi_add_handle($master, $ch);
        }

        do {
            while (($execrun = curl_multi_exec($master, $running)) == CURLM_CALL_MULTI_PERFORM) ;
            if ($execrun != CURLM_OK)
                break;
	    
            // a request was just completed -- find out which one
            while ($done = curl_multi_info_read($master)) {

                // get the info and content returned on the request
                $info = curl_getinfo($done['handle']);
                $output = curl_multi_getcontent($done['handle']);

                // send the return values to the callback function.
                $callback = $this->callback;
                if (is_callable($callback)) {
                    $key = (string) $done['handle'];
                    $request = $this->requests[$this->requestMap[$key]];
                    unset($this->requestMap[$key]);
                    call_user_func($callback, $output, $info, $request);
                }

                // start a new request (it's important to do this before removing the old one)
                if ($i < sizeof($this->requests) && isset($this->requests[$i]) && $i < count($this->requests)) {
                    $ch = curl_init();
		    
		    // Add to our request Maps
                    $key = (string) $ch;
                    $this->requestMap[$key] = $i;
                    $options = $this->get_options($this->requests[$i]);
		    if($this->control_timeout){
			if(isset($options[CURLOPT_CONNECTTIMEOUT]))
			    unset($options[CURLOPT_CONNECTTIMEOUT]);
			$requestOptions[$key] = $options;
			$requestStartmoments[$key] = time();
			$requestHandlers[$key] = $ch;
		    }
                    curl_setopt_array($ch, $options);
                    curl_multi_add_handle($master, $ch);

                    
                    $i++;
                }
		$key = (string) $done['handle'];
		if($this->control_timeout){
			unset($requestOptions[$key]);
			unset($requestStartmoments[$key]);
			unset($requestHandlers[$key]);
		}
		
                // remove the curl handle that just completed
                curl_multi_remove_handle($master, $done['handle']);
            }
	    
            // Block for data in / output; error handling is done by curl_multi_exec
            if ($running)
                curl_multi_select($master, $this->timeout);
	    
	    if($this->control_timeout and $running and $requestOptions)
		foreach($requestOptions as $key => $opts){
		    if(isset($opts[CURLOPT_TIMEOUT]))
		    {
			if(time() - $requestStartmoments[$key] > $opts[CURLOPT_TIMEOUT])
			{   
			    curl_close($requestHandlers[$key]);
			    curl_multi_remove_handle($master, $requestHandlers[$key]);
			    unset($requestOptions[$key]);
			    unset($requestStartmoments[$key]);
			    unset($requestHandlers[$key]);
			}
		    }
		}
		
        } while ($running);
        curl_multi_close($master);
        return true;
    }


    /**
     * Helper function to set up a new request by setting the appropriate options
     *
     * @access private
     * @param Request $request
     * @return array
     */
    private function get_options($request) {
        // options for this entire curl object
        $options = $request->options;
        if (ini_get('safe_mode') == 'Off' || !ini_get('safe_mode')) {
            $options[CURLOPT_FOLLOWLOCATION] = 1;
	    $options[CURLOPT_AUTOREFERER] = 1;
            $options[CURLOPT_MAXREDIRS] = 5;
        }
        $options[CURLOPT_RETURNTRANSFER] = 1;
        $options[CURLOPT_SSL_VERIFYPEER] = false;
	$options[CURLOPT_SSL_VERIFYHOST] = false;
	
        $headers = $request->headers;
        // append custom options for this specific request
        if ($request->options) {
            $options = $request->options + $options;
        }

        // set the request URL
        $options[CURLOPT_URL] = $request->url;

        // posting data w/ this request?
        if ($request->post_data) {
            $options[CURLOPT_POST] = 1;
            $options[CURLOPT_POSTFIELDS] = $request->post_data;
        }
        if ($headers) {
            $options[CURLOPT_HEADER] = 0;
            $options[CURLOPT_HTTPHEADER] = $headers;
            
        }
        return $options;
    }

    /**
     * @return void
     */
    public function __destruct() {
        unset($this->window_size, $this->callback, $this->options, $this->headers, $this->requests);
    }
}
