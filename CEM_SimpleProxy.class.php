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
	 * @internal Curl handle
	 */
	private $curl = NULL;

	/**
	 * @internal Curl info
	 */
	private $curlInfo = NULL;

	/**
	 * @internal Response status
	 */
	private $responseStatus = 'HTTP/1.0 500 Internal Server Error';

	/**
	 * @internal Response headers
	 */
	private $responseHeaders = array();

	/**
	 * @internal Response headers
	 */
	private $responseBody = '';
	

	/**
	 * Proxy current request to remote server
	 *
	 * @param $url destination url
	 * @param $allowedCookies allowed cookies
	 * @param $username destination username for authentication (optional)
	 * @param $password destination password for authentication (optional)
	 */
	public function __construct($url, $allowedCookies = array(), $username = FALSE, $password = FALSE) {
		try {
			// open curl
			$this->curl = curl_init($url);
			if (!$this->curl) {
				throw new Exception("Cannot initialize cURL");
			}
	
			// set connection properties
			if (!curl_setopt_array(
					$this->curl,
					array(
						CURLOPT_CONNECTTIMEOUT_MS => 5000,
						CURLOPT_TIMEOUT_MS => 15000,
						CURLOPT_ENCODING => 'identity',
						CURLOPT_HEADER => FALSE,
						CURLOPT_HEADERFUNCTION => array($this, 'parseProxyHeader'),
						CURLOPT_RETURNTRANSFER => TRUE,
						CURLINFO_HEADER_OUT => FALSE
					)
				)) {
				curl_close($this->curl);
				throw new Exception("Cannot configure cURL");
			}

			// set http authentication
			if ($username && $password) {
				if (!curl_setopt_array(
						$this->curl,
						array(
							CURLOPT_HTTPAUTH => CURLAUTH_ANY,
							CURLOPT_USERPWD => $username.':'.$password
						)
					)) {
					curl_close($this->curl);
					throw new Exception("Cannot configure cURL");
				}
			}
	
			// set http headers
			$requestHeaders = array();
			if (function_exists('apache_request_headers')) {
				foreach (apache_request_headers() as $name => $value) {
					if (!in_array(strtolower($name), self::$proxyHideHeaders)) {
						$requestHeaders[strtolower($name)] = array($name, $value);
					}
				}
			}
			$cookieHeader = '';
			foreach ($allowedCookies as $allowedCookie) {
				if (isset($_COOKIE[$allowedCookie])) {
					if (strlen($cookieHeader) > 0) {
						$cookieHeader .= '; ';
					}
					$cookieHeader .= urlencode($allowedCookie).'='.urlencode($_COOKIE[$allowedCookie]);
				}
			}
			if (strlen($cookieHeader) > 0) {
				$requestHeaders['cookie'] = array('Cookie', $cookieHeader);
			}
			$urlParts = parse_url($url);
			$requestHeaders['host'] = array('Host', $urlParts['host']);
			$requestHeaders['via'] = array('Via', '1.1 (Proxy)');

			$requestHeaderLines = array();
			foreach ($requestHeaders as $item) {
				$requestHeaderLines[] = $item[0].': '.$item[1];
			}
			if (!curl_setopt($this->curl, CURLOPT_HTTPHEADER, $requestHeaderLines)) {
				curl_close($this->curl);
				throw new Exception("Cannot configure cURL");
			}

			if (isset($_SERVER['REQUEST_METHOD'])) {
				// set http method
				if (!curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD'])) {
					curl_close($this->curl);
					throw new Exception("Cannot configure cURL");
				}

				// forward request body if any
				switch ($_SERVER['REQUEST_METHOD']) {
				case 'POST':
					if (sizeof($_POST) > 0) {
						if (!curl_setopt(
							$this->curl,
							CURLOPT_POSTFIELDS,
							$_POST)) {
							curl_close($this->curl);
							throw new Exception("Cannot configure cURL");
						}
						break;
					}
		
				case 'PUT':
					if (isset($requestHeaders['content-type'])) {
						if (!curl_setopt(
							$this->curl,
							CURLOPT_POSTFIELDS,
							file_get_contents("php://input"))) {
							curl_close($this->curl);
							throw new Exception("Cannot configure cURL");
						}
					}
					break;
				}
			}

			// execute request
			$this->responseBody = curl_exec($this->curl);
			$this->curlInfo = curl_getinfo($this->curl);

			// close curl
			curl_close($this->curl);

			// set final headers
			$responseHeaders = array();
			$cookieList = array();
			foreach ($this->responseHeaders as $entry) {
				$key = strtolower($entry[0]);
				switch ($key) {
				case 'content-length':
					$responseHeaders[] = array($entry[0], strlen($this->responseBody));
					break;

				case 'set-cookie':
				case 'set-cookie2':
					foreach ($this->parseCookie($entry[1]) as $cookie) {
						if (!in_array($cookie['name'], $allowedCookies)) {
							continue;
						}
						$cookieHeader = urlencode($cookie['name']).'='.urlencode($cookie['value']);
						if (isset($cookie['expires'])) {
							$cookieHeader .= '; expires='.$cookie['expires'];
						}
						$cookieHeader .= '; path=/';
						$responseHeaders[] = array('Set-Cookie', $cookieHeader);
					}
					break;

				default:
					if (!in_array($key, self::$proxyHideHeaders)) {
						$responseHeaders[] = array($entry[0], $entry[1]);
					}
					break;
				}
			}
			$this->responseHeaders = $responseHeaders;
		} catch (Exception $e) {
			$this->responseStatus = 'HTTP/1.0 500 Internal Server Error';
			$this->responseHeaders = array();
			$this->responseBody = '';
		}
		$this->responseHeaders[] = array('Via', '1.1 (Proxy)');
	}


	/**
	 * Write proxied content (status, headers, body)
	 *
	 * @return TRUE on success, FALSE on failure
	 */
	public function write() {
		// check that headers are not sent
		if (function_exists('header_remove')) {
			header_remove();
		}
		if (headers_sent()) {
			return FALSE;
		}

		// forward response status
		if ($this->curlInfo && isset($this->curlInfo['http_code'])) {
			header($this->responseStatus, TRUE, $this->curlInfo['http_code']);
		} else {
			header($this->responseStatus);
		}

		// forward response headers
		foreach ($this->responseHeaders as $entry) {
			header($entry[0].': '.$entry[1], FALSE);
		}

		// forward response body
		echo($this->responseBody);

		return ($this->curl && $this->curlInfo);
	}


	/**
	 * Get response code
	 *
	 * @return response code
	 */
	public function getCode() {
		if ($this->curlInfo && isset($this->curlInfo['http_code'])) {
			return intval($this->curlInfo['http_code']);
		}
		return 500;
	}

	/**
	 * Get response status
	 *
	 * @return response status
	 */
	public function getStatus() {
		return $this->responseStatus;
	}

	/**
	 * Get response headers
	 *
	 * @return response headers
	 */
	public function getHeaders() {
		return $this->responseHeaders;
	}

	/**
	 * Get response body
	 *
	 * @return response body
	 */
	public function getBody() {
		return $this->responseBody;
	}


	/**
	 * @internal Called to parse proxied response header.
	 *
	 * @param $h curl handle
	 * @param $data header data
	 * @return amount bytes read
	 */
	private function parseProxyHeader($h, $data) {
		$index = strpos($data, ':');
		if ($index > 0) {
			$this->responseHeaders[] = array(
				trim(substr($data, 0, $index)),
				trim(substr($data, $index + 1)),
				$data
			);
		} else {
			$this->responseStatus = $data;
		}
		return strlen($data);
	}

	/**
	 * @internal Parse cookie header
	 *
	 * @param $data header line
	 * @return cookie definition
	 */
	private function parseCookie($data) {
		$cookieList = array();

		// parse cookie
		$parts = explode(';', $data);
		if (count($parts) > 0) {
			$cookie = array();

			$value = explode('=', $parts[0]);
			for ($i = 1; $i < count($parts); $i++) {
				$parameter = explode('=', $parts[$i]);
				$cookie[trim($parameter[0])] = trim($parameter[1]);
			}
			$cookie['name'] = trim($value[0]);
			$cookie['value'] = trim($value[1]);

			$cookieList[] = $cookie;
		}
		return $cookieList;
	}
}

/**
 * @}
 */

?>