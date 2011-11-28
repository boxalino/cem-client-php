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
 * (C) 2009-2011 - Boxalino AG
 */


/**
 * Simple proxy
 *
 * @author nitro@boxalino.com
 */
class CEM_SimpleProxy {
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
	 * @internal Http client
	 */
	private $client = NULL;


	/**
	 * Proxy current request to remote server
	 *
	 * @param $url destination url
	 * @param $username destination username for authentication (optional)
	 * @param $password destination password for authentication (optional)
	 * @param $connectionTimeout connect timeout ms (optional)
	 * @param $readTimeout read timeout ms (optional)
	 * @param $allowedCookies allowed cookies (optional)
	 */
	public function __construct($url, $username = FALSE, $password = FALSE, $connectionTimeout = 1000, $readTimeout = 15000, $allowedCookies = array()) {
		try {
			// build client
			$this->client = new CEM_HttpClient($username, $password, $connectionTimeout, $readTimeout);

			// forward http headers
			$requestContentType = FALSE;
			$urlParts = parse_url($url);
			$requestHeaders = array();
			$requestHeaders[] = array('Host', $urlParts['host']);
			$requestHeaders[] = array('Via', '1.1 (Proxy)');
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

			// forward http cookies
			foreach ($allowedCookies as $allowedCookie) {
				if (isset($_COOKIE[$allowedCookie])) {
					$this->client->setCookie($allowedCookie, $_COOKIE[$allowedCookie]);
				}
			}

			// forward http request
			if (isset($_SERVER['REQUEST_METHOD'])) {
				switch ($_SERVER['REQUEST_METHOD']) {
				case 'GET':
					$this->client->get($url, array(), FALSE, $requestHeaders);
					break;

				case 'POST':
					if (sizeof($_POST) > 0) {
						$this->client->postFields($url, $_POST, FALSE, $requestHeaders);
						break;
					}
					if (!$requestContentType) {
						throw new Exception("Invalid content-type");
					}
					$this->client->post($url, $requestContentType, file_get_contents("php://input"), FALSE, $requestHeaders);
					break;

				case 'PUT':
					if (!$requestContentType) {
						throw new Exception("Invalid content-type");
					}
					$this->client->put($url, $requestContentType, file_get_contents("php://input"), FALSE, $requestHeaders);
					break;

				default:
					throw new Exception("Unsupported http method: ".$_SERVER['REQUEST_METHOD']);
				}
			} else {
				$this->client->get($url, array(), FALSE, $requestHeaders);
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
		$code = $this->client->getCode();
		return ($code > 0 ? $code : 500);
			
	}

	/**
	 * Get response status
	 *
	 * @return response status
	 */
	public function getStatus() {
		$status = $this->client->getStatus();
		return ($status ? $status : 'HTTP/1.0 500 Internal Server Error');
	}

	/**
	 * Get response cookies
	 *
	 * @param $allowedCookies allowed cookies (optional)
	 * @return response cookies
	 */
	public function getCookies($allowedCookies = array()) {
		$cookies = array();
		foreach ($this->client->getCookies() as $name => $cookie) {
			if (!in_array($name, $allowedCookies)) {
				continue;
			}
			$cookies[$name] = $cookie;
		}
		return $cookies;
	}

	/**
	 * Get response headers
	 *
	 * @param $allowedCookies allowed cookies (optional)
	 * @param $cookiePath cookie path (optional)
	 * @return response headers
	 */
	public function getHeaders($allowedCookies = array(), $cookiePath = '/') {
		// rewrite headers
		$headers = array();
		foreach ($this->client->getHeaders() as $entry) {
			switch ($entry['key']) {
			case 'content-length':
				$headers[] = array(
					'key' => $entry['key'],
					'name' => $entry['name'],
					'value' => strlen($this->getBody())
				);
				break;

			default:
				if (!in_array($entry['key'], self::$proxyHideHeaders)) {
					$headers[] = $entry;
				}
				break;
			}
		}

		// append cookies
		foreach ($this->getCookies($allowedCookies) as $cookie) {
			$header = urlencode($cookie['name']).'='.urlencode($cookie['value']);
			if (isset($cookie['expires'])) {
				$header .= '; expires='.$cookie['expires'];
			}
			$header .= '; path='.$cookiePath;
			$header[] = array(
				'key' => 'set-cookie',
				'name' => 'Set-Cookie',
				'value' => $header
			);
		}
		return $headers;
	}

	/**
	 * Get response body
	 *
	 * @return response body
	 */
	public function getBody() {
		return $this->client->getBody();
	}


	/**
	 * Write proxied content (status, headers, body)
	 *
	 * @param $allowedCookies allowed cookies (optional)
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
		if ($this->getCode() > 0) {
			header($this->getStatus(), TRUE, $this->getCode());
		} else {
			header($this->getStatus());
		}

		// forward response headers
		foreach ($this->getHeaders($allowedCookies, $cookiePath) as $entry) {
			header($entry['name'].': '.$entry['value'], FALSE);
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