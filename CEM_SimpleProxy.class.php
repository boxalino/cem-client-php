<?php

/** @addtogroup cem
 *
 * @{
 */

/**
 * @internal
 *
 * Boxalino CEM client library in PHP.
 *
 * (C) 2009-2012 - Boxalino AG
 */


/**
 * Simple proxy
 *
 * @author nitro@boxalino.com
 */
class CEM_SimpleProxy extends CEM_HttpClient {
	/**
	 * @internal Proxy headers to drop
	 */
	private static $proxyHideHeaders = array(
		'authenticate',
		'connection',
		'content-encoding',
		'cookie',
		'keep-alive',
		'host',
		'proxy-authenticate',
		'proxy-authorization',
		'proxy-connection',
		'set-cookie',
		'set-cookie2',
		'te',
		'trailer',
		'transfer-encoding',
		'upgrade',
		'www-authenticate',
		'x-powered-by'
	);


	/**
	 * Proxy current request to remote server
	 *
	 * @param $url destination url
	 * @param $username destination username for authentication (optional)
	 * @param $password destination password for authentication (optional)
	 * @param $connectionTimeout connect timeout ms (optional)
	 * @param $readTimeout read timeout ms (optional)
	 * @param $allowedCookies allowed cookies (optional, regexp if '/.../')
	 * @param $headers additional headers (optional)
	 */
	public function __construct($url, $username = FALSE, $password = FALSE, $connectionTimeout = 1000, $readTimeout = 15000, $allowedCookies = array(), $headers = array()) {
		parent::__construct($username, $password, $connectionTimeout, $readTimeout);
		try {
			// forward http headers
			$requestContentType = FALSE;
			$requestHeaders = array();
			if (function_exists('apache_request_headers')) {
				foreach (apache_request_headers() as $name => $value) {
					$key = strtolower($name);
					switch ($key) {
					case 'content-type':
						$requestContentType = $value;
						break;

					default:
						if (!in_array($key, self::$proxyHideHeaders)) {
							$requestHeaders[] = array($name, $value);
						}
						break;
					}
				}
			}
			foreach ($headers as $key => $value) {
				$requestHeaders[] = array($key, $value);
			}
			$urlParts = parse_url($url);
			$requestHeaders[] = array('Host', $urlParts['host']);
			$requestHeaders[] = array('Via', '1.1 (Proxy)');

			// forward http cookies
			foreach ($_COOKIE as $name => $value) {
				foreach ($allowedCookies as $allowedCookie) {
					if ($name == $allowedCookie ||
						(strpos($allowedCookie, '/') === 0 && preg_match($allowedCookie, $name))) {
						$this->setCookie($name, $value);
						break;
					}
				}
			}

			// forward http request
			if (isset($_SERVER['REQUEST_METHOD'])) {
				switch ($_SERVER['REQUEST_METHOD']) {
				case 'GET':
					$this->get($url, $_GET, FALSE, $requestHeaders);
					break;

				case 'POST':
					if (sizeof($_POST) > 0) {
						$this->postFields($url, $_POST, 'UTF-8', FALSE, $requestHeaders);
						break;
					}
					if (!$requestContentType) {
						throw new Exception("Invalid content-type");
					}
					$this->post($url, $requestContentType, file_get_contents("php://input"), FALSE, $requestHeaders);
					break;

				case 'PUT':
					if (!$requestContentType) {
						throw new Exception("Invalid content-type");
					}
					$this->put($url, $requestContentType, file_get_contents("php://input"), FALSE, $requestHeaders);
					break;

				default:
					throw new Exception("Unsupported http method: ".$_SERVER['REQUEST_METHOD']);
				}
			} else {
				$this->get($url, array(), FALSE, $requestHeaders);
			}
		} catch (Exception $e) {
		}
	}


	/**
	 * Get response code
	 *
	 * @return response code
	 */
	public function getCode() {
		$code = parent::getCode();
		return ($code > 0 ? $code : 500);
	}

	/**
	 * Get response status
	 *
	 * @return response status
	 */
	public function getStatus() {
		$status = parent::getStatus();
		return ($status ? $status : 'HTTP/1.0 500 Internal Server Error');
	}


	/**
	 * Write proxied content (status, headers, body)
	 *
	 * @param $allowedCookies allowed cookies (optional, regexp if '/.../')
	 * @param $cookiePath cookie path (optional)
	 * @return TRUE on success, FALSE on failure
	 */
	public function write($allowedCookies = array(), $cookiePath = '/') {
		// check that headers are not sent
		if (function_exists('header_remove')) {
			header_remove();
		}
		if (headers_sent()) {
			return FALSE;
		}

		// forward response status
		header($this->getStatus(), TRUE, $this->getCode());

		// forward response headers with rewrite
		foreach ($this->getHeaders() as $entry) {
			switch ($entry['key']) {
			case 'content-length':
				header($entry['name'].': '.$this->getSize(), FALSE);
				break;

			default:
				if (!in_array($entry['key'], self::$proxyHideHeaders)) {
					header($entry['name'].': '.$entry['value'], FALSE);
				}
				break;
			}
		}

		// forward response cookies
		foreach ($this->getCookies() as $name => $cookie) {
			foreach ($allowedCookies as $allowedCookie) {
				if ($name == $allowedCookie ||
					(strpos($allowedCookie, '/') === 0 && preg_match($allowedCookie, $name))) {
					$header = urlencode($cookie['name']).'='.urlencode($cookie['value']);
					if (isset($cookie['expires'])) {
						$header .= '; expires='.$cookie['expires'];
					}
					$header .= '; path='.$cookiePath;
					header('Set-Cookie: '.$header, FALSE);
				}
			}
		}

		// forward response body
		echo($this->getBody());
		return TRUE;
	}
}

/**
 * @}
 */

?>