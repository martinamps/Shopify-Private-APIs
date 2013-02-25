<?php

namespace Shopify;

class PrivateAPI {
	const _LOGIN_URL = 'auth/login';

	const _COOKIE_STORE = '/tmp/shopify_cookie.txt';
	const _USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17';
	
	protected $ch = null;
	protected $ci = null;
	
	private $inputs = false,
			$username = false,
			$password = false,
			$store = false,
			$token = false;
			
	public function __construct($user, $pass, $store) {	
		if (!filter_var($store, FILTER_VALIDATE_URL))
			throw new \Exception('Invalid store URL');
		
		$this->store = $store . (substr($store, -1) == '/' ? '' : '/');
		$this->username = $user;
		$this->password = $pass;

	}
	
	public function __destruct() {
		if (is_resource($this->ch))
			curl_close($this->ch);	
	}
		
	public function isLoggedIn() {
		return !is_array($this->getFields());
	}

	public function login() {
		$fields = $this->inputs ?: $this->getFields();

		$fields['login']  = $this->username;
		$fields['password'] = $this->password;

		$url = $this->store . self::_LOGIN_URL;
		
		$this->ch = curl_init($url);
		
		$this->setOpts([
			CURLOPT_POST => count($fields),
			CURLOPT_POSTFIELDS => http_build_query($fields),
			CURLOPT_HTTPHEADER => ['Shopify-Auth-Mechanisms:password']
		]);	
		
		$data = curl_exec($this->ch);
		$http_code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
		
		return ($http_code == 200 && $this->setToken($data));
	}
	
	public function doRequest($method, $function, $parameters) {
		$this->ch = curl_init();		

		$url = $this->store . $function;

		switch ($method) {
			case 'POST':
				$this->setOpts([
					CURLOPT_POST => true,
					CURLOPT_POSTFIELDS => json_encode($parameters),
					CURLOPT_URL => $url,
					CURLOPT_HTTPHEADER => [
						'X-Shopify-Api-Features: pagination-headers',
						'X-CSRF-Token: ' . $this->token,
						'X-Requested-With: XMLHttpRequest',
						'Content-Type: application/json',
						'Accept: application/json'
					]
				]);

				break;
			case 'GET':
			default:
				$this->setOpts([
					CURLOPT_HTTPGET => true,
					CURLOPT_URL => $url . (count($parameters) ? '?' . urldecode(http_build_query($parameters)) : '')
				]);
		}

		$response = curl_exec($this->ch);

		if (curl_errno($this->ch))
			throw new \Exception('Shopify Private API exception: ' . curl_error($this->ch));
		
		return json_decode($response);
	}

	public function setToken($input) {
		$data = filter_var($input, FILTER_VALIDATE_URL) ? $this->initGetData($input) : $input;
		
		if (preg_match('/<meta content="(.*)" name="csrf-token" \/>/i', $data, $token)) {
			$this->token = $token[1];
 			return true;
		}
		
		throw new \Exception('Failed to set token');
	}
		
	private function initGetData($url, $opts = []) {
		if (!filter_var($url, FILTER_VALIDATE_URL))
			throw new \Exception('Invalid URL: ' . $url);
		

		$this->ch = curl_init($url);
		$this->setOpts($opts);
		
		if (($http_code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE)) > 300)
			throw new \Exception('Failed to fetch ' . $url . ' (' . $http_code . ')');
					
		$data = curl_exec($this->ch);

		return $data;
	}
	
	private function setOpts($extra = []) {	
		
		$default = [
			CURLOPT_USERAGENT => self::_USER_AGENT,
			CURLOPT_COOKIEJAR => self::_COOKIE_STORE,
			CURLOPT_COOKIEFILE => self::_COOKIE_STORE,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true
		];
		
		$options = $default + array_filter($extra, function($v) {
			return !is_null($v);
		});
		
		curl_setopt_array($this->ch, $options);
	}
	
	private function getFields($data = false) {
		$data = $data ?: $this->initGetData($this->store);

	    if (preg_match('/(<form.*?.*?<\/form>)/is', $data, $matches))
	        $this->inputs = $this->getInputs($matches[1]);
	    
	    return is_array($this->inputs) ? $this->inputs : false;
	}
	
	private function getInputs($form, $inputs = []) {
		if (!($els = preg_match_all('/(<input[^>]+>)/is', $form, $matches)))
			return false;
			
		for ($i = 0; $i < $els; $i++) {
			$el = preg_replace('/\s{2,}/', ' ', $matches[1][$i]);
			
			if (preg_match('/name=(?:["\'])?([^"\'\s]*)/i', $el, $name) 
			 && preg_match('/value=(?:["\'])?([^"\'\s]*)/i', $el, $value))
				$inputs[$name[1]] = $value[1];
		
		}
		
		return $inputs;
	}
}