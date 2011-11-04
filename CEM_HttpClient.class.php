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
 * Boxalino CEM Http client class
 *
 * @author nitro@boxalino.com
 */
class CEM_HttpClient {
	/**
	 * @internal cURL handle
	 */
	private $curl;

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
		// open curl
		$this->curl = curl_init();
		if (!$this->curl) {
			throw new Exception("Cannot initialize cURL");
		}

		// set base options
		if (!curl_setopt_array(
			$this->curl,
			array(
				CURLOPT_CONNECTTIMEOUT_MS => $connectionTimeout,
				CURLOPT_TIMEOUT_MS => $readTimeout,
				CURLOPT_SSL_VERIFYPEER => FALSE,
				CURLOPT_RETURNTRANSFER => TRUE,
				CURLOPT_HEADER => FALSE,
				CURLOPT_HEADERFUNCTION => array($this, 'parseHeader')
			)
		)) {
			throw new Exception("Cannot configure cURL (base)");
		}

		// set http authentication
		if ($username && $password && !curl_setopt_array(
			$this->curl,
			array(
				CURLOPT_HTTPAUTH => CURLAUTH_ANY,
				CURLOPT_USERPWD => $username.':'.$password
			)
		)) {
			throw new Exception("Cannot configure cURL (http-auth)");
		}
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
		// build url with parameters
		if (sizeof($parameters) > 0) {
			$urlInfo = parse_url($url);
			$url = $urlInfo['scheme'].'://';
			if (isset($urlInfo['user']) && isset($urlInfo['pass'])) {
				$url .= $urlInfo['user'].':'.$urlInfo['pass'].'@';
			}
			$url .= $urlInfo['host'];
			if (isset($urlInfo['port'])) {
				$url .= ':'.$urlInfo['port'];
			}
			if (isset($urlInfo['query']) && strlen($urlInfo['query']) > 0) {
				$url .= '?'.$urlInfo['query'].'&';
			} else {
				$url .= '?';
			}
			if (isset($urlInfo['fragment'])) {
				$url .= '#'.$urlInfo['fragment'];
			}
		}
		return $this->process('GET', $url, $referer, $headers);
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
	 * @param $parameters request parameters map (optional)
	 * @param $referer http referer url (optional)
	 * @param $headers http headers pairs (optional)
	 * @return http code
	 */
	public function postFields($url, $parameters = array(), $referer = FALSE, $headers = array()) {
		$headers[] = array('Content-Type', 'multipart/form-data');
		return $this->process('POST', $url, $referer, $headers, $parameters);
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
		// check curl
		if ($this->curl == NULL) {
			throw new Exception("cURL handle closed");
		}

		// set url
		if (!curl_setopt($this->curl, CURLOPT_URL, $url)) {
			throw new Exception("Cannot configure cURL (url)");
		}

		// set method
		if (!curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method)) {
			throw new Exception("Cannot configure cURL (method)");
		}

		// set headers
		$headerLines = array();
		foreach ($headers as $header) {
			$headerLines[] = $header[0].': '.$header[1];
		}
		if (sizeof($headerLines) > 0 && !curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headerLines)) {
			throw new Exception("Cannot configure cURL (headers)");
		}

		// set post fields
		if ($postData !== FALSE && !curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postData)) {
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
		if (strlen($cookieHeader) > 0 && !curl_setopt($this->curl, CURLOPT_COOKIE, $cookieHeader)) {
			throw new Exception("Cannot configure cURL (cookies)");
		}

		// set referer
		if (strlen($referer) > 0 && !curl_setopt($this->curl, CURLOPT_REFERER, $referer)) {
			throw new Exception("Cannot configure cURL (referer)");
		}

		// execute curl request
		$this->responseStatus = NULL;
		$this->responseHeaders = array();
		$time = microtime(TRUE);
		$this->responseBody = curl_exec($this->curl);
		$this->curlInfo = curl_getinfo($this->curl);
		$this->curlError = curl_error($this->curl);
		$this->time = microtime(TRUE) - $time;

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
					$cookie['name'] = trim($value[0]);
					$cookie['value'] = trim($value[1]);
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

		// follow redirect
		if ($redirectUrl) {
			return $this->process('GET', $redirectUrl, $referer, $headers, array());
		}
		return $this->curlInfo['http_code'];
	}

	/**
	 * Close http client
	 *
	 */
	public function close() {
		if ($this->curl != NULL) {
			curl_close($this->curl);
			$this->curl = NULL;
		}
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