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
 * Boxalino CEM Http client class
 *
 * @author nitro@boxalino.com
 */
class CEM_HttpClient {
	/** Allowed encoding */
	private static $allowedEncodings = NULL;


	/**
	 * Convert string encoding
	 *
	 * @param $value input value
	 * @param $charset target charset
	 * @return encoded value
	 */
	public static function convertEncoding($value, $charset = 'UTF-8') {
		if (self::$allowedEncodings == NULL) {
			self::$allowedEncodings = array(mb_internal_encoding());
			foreach (array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 13, 14, 15) as $i) {
				self::$allowedEncodings[] = sprintf('ISO-8859-%d', $i);
			}
			self::$allowedEncodings = array_unique(self::$allowedEncodings);
		}
		if (is_array($value)) {
			foreach ($value as $key => $item) {
				$value[$key] = self::convertEncoding($item, $charset);
			}
		} else if (strcasecmp(mb_detect_encoding($value, array_unique(array_merge(array($charset), self::$allowedEncodings))), $charset) != 0) {
			$value = mb_convert_encoding($value, $charset, mb_internal_encoding());
		}
		return $value;
	}

	/**
	 * Convert parameters encoding
	 *
	 * @param $parameters input parameters
	 * @param $charset target charset
	 * @return encoded parameters
	 */
	public static function convertParametersEncoding($parameters, $charset = 'UTF-8') {
		$list = array();
		foreach ($parameters as $key => $value) {
			$list[$key] = self::convertEncoding($value, $charset);
		}
		return $list;
	}

	/**
	 * Build url-encoded key/value list
	 *
	 * @param $parameters parameters
	 * @return key/value list
	 */
	public static function buildKVList($parameters) {
		$list = array();
		foreach ($parameters as $k => $v) {
			$k = urlencode($k);
			if (is_array($v)) {
				foreach ($v as $i => $vi) {
					$list[] = $k.'['.urlencode($i).']='.urlencode($vi);
				}
			} else {
				$list[] = $k.'='.urlencode($v);
			}
		}
		return implode('&', $list);
	}

	/**
	 * Build full url
	 *
	 * @param $url http url
	 * @param $parameters request parameters map (optional)
	 * @param $fragment new fragment (optional)
	 * @return full url
	 */
	public static function buildUrl($url, $parameters = array(), $fragment = NULL) {
		// build url with parameters
		if (strlen($url) > 0) {
			$urlInfo = parse_url($url);
			$url = array();
			if (isset($urlInfo['scheme'])) {
				$url[] = $urlInfo['scheme'].'://';
				if (isset($urlInfo['user']) && isset($urlInfo['pass'])) {
					$url[] = $urlInfo['user'].':'.$urlInfo['pass'].'@';
				}
				if (isset($urlInfo['host'])) {
					$url[] = $urlInfo['host'];
					if (isset($urlInfo['port'])) {
						$url[] = ':'.$urlInfo['port'];
					}
				}
				if (isset($urlInfo['path']) && strlen($urlInfo['path']) > 0) {
					$url[] = $urlInfo['path'];
				} else {
					$url[] = '/';
				}
			} else if (isset($urlInfo['path'])) {
				$url[] = $urlInfo['path'];
			}
			if (isset($urlInfo['query']) && strlen($urlInfo['query']) > 0) {
				$url[] = '?'.$urlInfo['query'];
				if (sizeof($parameters) > 0) {
					$url[] = '&';
				}
			} else if (sizeof($parameters) > 0) {
				$url[] = '?';
			}
			$url[] = self::buildKVList(self::convertParametersEncoding($parameters));
			if (strlen($fragment) > 0) {
				$url[] = '#'.urlencode($fragment);
			} else if (isset($urlInfo['fragment'])) {
				$url[] = '#'.urlencode($urlInfo['fragment']);
			}
			return implode('', $url);
		}
		$url = '';
		if (sizeof($parameters) > 0) {
			$url .= '?'.self::buildKVList(self::convertParametersEncoding($parameters));
		}
		if (strlen($fragment) > 0) {
			$url .= '#'.urlencode($fragment);
		}
		return $url;
	}


	/**
	 * @internal cURL username
	 */
	private $username;

	/**
	 * @internal cURL password
	 */
	private $password;

	/**
	 * @internal cURL connection timeout
	 */
	private $connectionTimeout;

	/**
	 * @internal cURL read timeout
	 */
	private $readTimeout;

	/**
	 * @internal Last cURL info
	 */
	private $curlInfo = NULL;

	/**
	 * @internal Last cURL error
	 */
	private $curlError = NULL;

	/**
	 * @internal Last request time
	 */
	private $time = 0;

	/**
	 * @internal Last response status line
	 */
	private $responseStatus = NULL;

	/**
	 * @internal Last response headers
	 */
	private $responseHeaders = array();

	/**
	 * @internal Last response body
	 */
	private $responseBody = NULL;

	/**
	 * @internal Session cookies
	 */
	private $cookies = array();


	/**
	 * Constructor
	 *
	 * @param $username tracker username for authentication (optional)
	 * @param $password tracker password for authentication (optional)
	 * @param $connectionTimeout connect timeout in ms (optional)
	 * @param $readTimeout read timeout in ms (optional)
	 */
	public function __construct($username = FALSE, $password = FALSE, $connectionTimeout = 1000, $readTimeout = 15000) {
		$this->username = $username;
		$this->password = $password;
		$this->connectionTimeout = $connectionTimeout;
		$this->readTimeout = $readTimeout;
	}


	/**
	 * Get last error
	 *
	 * @return last error
	 */
	public function getError() {
		return ($this->curlError ? $this->curlError : '');
	}

	/**
	 * Get last http request time
	 *
	 * @return last http request time
	 */
	public function getTime() {
		return $this->time;
	}

	/**
	 * Get last http code
	 *
	 * @return last http code
	 */
	public function getCode() {
		return ($this->curlInfo ? $this->curlInfo['http_code'] : 0);
	}

	/**
	 * Get last http response status line
	 *
	 * @return last http response status line
	 */
	public function getStatus() {
		return $this->responseStatus;
	}

	/**
	 * Get cookies (remote)
	 *
	 * @return cookies (remote)
	 */
	public function getCookies() {
		$cookies = array();
		foreach ($this->cookies as $name => $cookie) {
			if (!$cookie['remote']) {
				continue;
			}
			$cookies[$name] = $cookie;
		}
		return $cookies;
	}

	/**
	 * Get last http response headers
	 *
	 * @return last http response headers
	 */
	public function getHeaders() {
		return $this->responseHeaders;
	}

	/**
	 * Get last http response body
	 *
	 * @return last http response body
	 */
	public function getBody() {
		return $this->responseBody;
	}


	/**
	 * Get cookie
	 *
	 * @param $name cookie name
	 * @return cookie object or NULL if none
	 */
	public function getCookie($name) {
		return (isset($this->cookies[$name]) ? $this->cookies[$name] : NULL);
	}

	/**
	 * Set cookie
	 *
	 * @param $name cookie name
	 * @param $value cookie value
	 */
	public function setCookie($name, $value) {
		if (!isset($this->cookies[$name])) {
			$this->cookies[$name] = array('name' => $name, 'value' => '', 'remote' => FALSE, 'expiresTime' => 0);
		}
		$this->cookies[$name]['value'] = $value;
	}

	/**
	 * Remove cookie
	 *
	 * @param $name cookie name
	 */
	public function removeCookie($name) {
		if (isset($this->cookies[$name])) {
			unset($this->cookies[$name]);
		}
	}


	/**
	 * Process GET request
	 *
	 * @param $url http url
	 * @param $parameters request parameters map (optional)
	 * @param $referer http referer url (optional)
	 * @param $headers http headers pairs (optional)
	 * @return http code
	 */
	public function get($url, $parameters = array(), $referer = FALSE, $headers = array()) {
		return $this->process('GET', CEM_HttpClient::buildUrl($url, $parameters), $referer, $headers);
	}

	/**
	 * Process PUT request (raw data)
	 *
	 * @param $url http url
	 * @param $contentType request content-type
	 * @param $requestBody request body
	 * @param $referer http referer url (optional)
	 * @param $headers http headers pairs (optional)
	 * @return http code
	 */
	public function put($url, $contentType, $requestBody, $referer = FALSE, $headers = array()) {
		$headers[] = array('Content-Type', $contentType);
		return $this->process('PUT', $url, $referer, $headers, $requestBody);
	}

	/**
	 * Process POST request (raw data)
	 *
	 * @param $url http url
	 * @param $contentType request content-type
	 * @param $requestBody request body
	 * @param $referer http referer url (optional)
	 * @param $headers http headers pairs (optional)
	 * @return http code
	 */
	public function post($url, $contentType, $requestBody, $referer = FALSE, $headers = array()) {
		$headers[] = array('Content-Type', $contentType);
		return $this->process('POST', $url, $referer, $headers, $requestBody);
	}

	/**
	 * Process POST request (fields)
	 *
	 * @param $url http url
	 * @param $parameters request parameters map
	 * @param $charset request charset (optional)
	 * @param $referer http referer url (optional)
	 * @param $headers http headers pairs (optional)
	 * @return http code
	 */
	public function postFields($url, $parameters, $charset = 'UTF-8', $referer = FALSE, $headers = array()) {
		return $this->post(
			$url,
			'application/x-www-form-urlencoded; charset='.$charset,
			self::buildKVList(self::convertParametersEncoding($parameters, $charset)),
			$referer,
			$headers
		);
	}

	/**
	 * Process any type of request
	 *
	 * @param $method http method
	 * @param $url http url
	 * @param $referer http referer url (optional)
	 * @param $headers http headers pairs (optional)
	 * @param $postData http post data (optional)
	 * @return http code
	 */
	public function process($method, $url, $referer = FALSE, $headers = array(), $postData = FALSE) {
		$beginTime = microtime(TRUE);

		// open curl
		$curl = curl_init();
		if (!$curl) {
			throw new Exception("Cannot initialize cURL");
		}

		// set base options
		if (!curl_setopt_array(
			$curl,
			array(
				CURLOPT_SSL_VERIFYPEER => FALSE,
				CURLOPT_RETURNTRANSFER => TRUE,
				CURLOPT_ENCODING => 'identity',
				CURLOPT_HEADER => FALSE,
				CURLOPT_HEADERFUNCTION => array($this, 'parseHeader')
			)
		)) {
			throw new Exception("Cannot configure cURL (base)");
		}

		// set timeout if supported
		if (defined('CURLOPT_CONNECTTIMEOUT_MS') && defined('CURLOPT_TIMEOUT_MS')) {
			if (!curl_setopt_array(
				$curl,
				array(
					CURLOPT_CONNECTTIMEOUT_MS => $this->connectionTimeout,
					CURLOPT_TIMEOUT_MS => $this->readTimeout
				)
			)) {
				throw new Exception("Cannot configure cURL (base)");
			}
		}

		// set http authentication
		if ($this->username && $this->password && !curl_setopt_array(
			$curl,
			array(
				CURLOPT_HTTPAUTH => CURLAUTH_ANY,
				CURLOPT_USERPWD => $this->username.':'.$this->password
			)
		)) {
			throw new Exception("Cannot configure cURL (http-auth)");
		}

		// set url
		if (!curl_setopt($curl, CURLOPT_URL, $url)) {
			throw new Exception("Cannot configure cURL (url)");
		}

		// set method
		if (!curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method)) {
			throw new Exception("Cannot configure cURL (method)");
		}

		// set headers
		$headerLines = array();
		foreach ($headers as $header) {
			$headerLines[] = $header[0].': '.$header[1];
		}
		if (sizeof($headerLines) > 0 && !curl_setopt($curl, CURLOPT_HTTPHEADER, $headerLines)) {
			throw new Exception("Cannot configure cURL (headers)");
		}

		// set post fields
		if ($postData !== FALSE && !curl_setopt($curl, CURLOPT_POSTFIELDS, $postData)) {
			throw new Exception("Cannot configure cURL (post fields)");
		}

		// set cookies
		$cookieHeader = '';
		foreach ($this->cookies as $cookie) {
			if (strlen($cookieHeader) > 0) {
				$cookieHeader .= '; ';
			}
			$cookieHeader .= urlencode($cookie['name']).'='.urlencode($cookie['value']);
		}
		if (strlen($cookieHeader) > 0 && !curl_setopt($curl, CURLOPT_COOKIE, $cookieHeader)) {
			throw new Exception("Cannot configure cURL (cookies)");
		}

		// set referer
		if (strlen($referer) > 0 && !curl_setopt($curl, CURLOPT_REFERER, $referer)) {
			throw new Exception("Cannot configure cURL (referer)");
		}

		// execute curl request
		$this->responseStatus = NULL;
		$this->responseHeaders = array();
		$this->responseBody = curl_exec($curl);
		$this->curlInfo = curl_getinfo($curl);
		$this->curlError = curl_error($curl);

		// close curl
		curl_close($curl);

		// parse response headers
		$redirectUrl = NULL;
		foreach ($this->responseHeaders as $header) {
			switch ($header['key']) {
			case 'location':
				if (strpos($header['value'], '/') === 0) {
					$urlInfo = parse_url($url);
					$redirectUrl = $urlInfo['scheme'].'://';
					if (isset($urlInfo['user']) && isset($urlInfo['pass'])) {
						$redirectUrl .= $urlInfo['user'].':'.$urlInfo['pass'].'@';
					}
					$redirectUrl .= $urlInfo['host'];
					if (isset($urlInfo['port'])) {
						$redirectUrl .= ':'.$urlInfo['port'];
					}
					$redirectUrl .= $header['value'];
				} else {
					$redirectUrl = $header['value'];
				}
				break;

			case 'set-cookie':
			case 'set-cookie2':
				$parts = explode(';', $header['value']);
				if (count($parts) > 0) {
					$value = explode('=', $parts[0]);
					$cookie = array();
					for ($i = 1; $i < count($parts); $i++) {
						$parameter = explode('=', $parts[$i]);
						$cookie[trim($parameter[0])] = trim($parameter[1]);
					}
					$cookie['name'] = urldecode(trim($value[0]));
					$cookie['value'] = urldecode(trim($value[1]));
					$cookie['remote'] = TRUE;
					if (isset($cookie['expires'])) {
						$time = strptime($cookie['expires'], '%a, %d-%b-%Y %H:%M:%S GMT');
						$cookie['expiresTime'] = gmmktime($time['tm_hour'], $time['tm_min'], $time['tm_sec'], $time['tm_mon'] + 1, $time['tm_mday'], $time['tm_year'] + 1900);
					} else {
						$cookie['expiresTime'] = 0;
					}
					$this->cookies[$cookie['name']] = $cookie;
				}
				break;
			}
		}

		// fetch time
		$this->time += microtime(TRUE) - $beginTime;

		// follow redirect
		if ($redirectUrl) {
			return $this->process('GET', $redirectUrl, $referer, $headers, array());
		}
		return $this->curlInfo['http_code'];
	}


	/**
	 * @internal Called by CURL to parse http header
	 *
	 * @param $h CURL handle
	 * @param $data header line
	 * @return line size
	 */
	private function parseHeader($h, $data) {
		$index = strpos($data, ':');
		if ($index > 0) {
			$this->responseHeaders[] = array(
				'key' => strtolower(trim(substr($data, 0, $index))),
				'name' => trim(substr($data, 0, $index)),
				'value' => trim(substr($data, $index + 1)),
				'line' => $data
			);
		} else {
			$this->responseStatus = $data;
		}
		return strlen($data);
	}
}

/**
 * @}
 */

?>