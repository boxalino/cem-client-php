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
 * Gateway state
 *
 * @author nitro@boxalino.com
 */
class CEM_GatewayState {
	/**
	 * Http status code
	 */
	protected $code;

	/**
	 * Http status message
	 */
	protected $message;

	/**
	 * Active cookies
	 */
	protected $cookies;

	/**
	 * State data
	 */
	protected $data;
	

	/**
	 * Constructor
	 *
	 */
	public function __construct() {
		$this->code = 0;
		$this->message = "";
		$this->cookies = array();
		$this->data = array();
	}


	/**
	 * Get last http status code
	 *
	 * @return last http status code
	 */
	public function getHttpCode() {
		return $this->code;
	}

	/**
	 * Get last http status message
	 *
	 * @return last http status message (or "" if none)
	 */
	public function getMessage() {
		switch ($this->code) {
		case 401:
			return "Unauthorized (wrong username or password)";

		case 403:
			return "Forbidden";

		case 404:
			return "Not found";

		case 500:
			return "Server error";
		}
		return $this->message;
	}

	/**
	 * Set status
	 *
	 * @param code last http status code
	 * @param message last http status message
	 */
	public function setStatus($code, $message) {
		$this->code = $code;
		$this->message = $message;
	}


	/**
	 * Get cookie header
	 *
	 * @return cookie header (or FALSE if none)
	 */
	public function getCookieHeader() {
		if (sizeof($this->cookies) > 0) {
			$text = '';
			foreach ($this->cookies as $name => $cookie) {
				if (strlen($text) > 0) {
					$text .= ';';
				}
				$text .= urlencode($name) . '=' . urlencode($cookie['value']);
			}
			return $text;
		}
		return FALSE;
	}

	/**
	 * Get cookie value
	 *
	 * @param $name cookie name
	 * @return cookie value (or FALSE if none)
	 */
	public function getCookie($name) {
		if (isset($this->cookies[$name])) {
			return $this->cookies[$name]['value'];
		}
		return FALSE;
	}

	/**
	 * Set an active cookie
	 *
	 * @param $name cookie name
	 * @param $value cookie value
	 * @param $parameters cookie parameters
	 */
	public function setCookie($name, $value, $parameters = array()) {
		$this->cookies[$name] = array(
			'value' => $value,
			'parameters' => $parameters
		);
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
	 * List state data
	 *
	 * @return data list
	 */
	public function getAll() {
		return $this->data;
	}

	/**
	 * Get state data
	 *
	 * @param $key data key
	 * @param $default default value
	 * @return data value
	 */
	public function get($key, $default = FALSE) {
		if (isset($this->data[$key])) {
			return $this->data[$key];
		}
		return $default;
	}

	/**
	 * Find state data
	 *
	 * @param $keyPrefix data key prefix
	 * @return map of data value
	 */
	public function find($keyPrefix) {
		$map = array();
		foreach ($this->data as $key => $value) {
			if (strpos($key, $keyPrefix) === 0) {
				$map[substr($key, strlen($keyPrefix))] = $value;
			}
		}
		return $map;
	}

	/**
	 * Set state data
	 *
	 * @param $key data key
	 * @param $value data value
	 */
	public function set($key, $value) {
		$this->data[$key] = $value;
	}

	/**
	 * Remove state data
	 *
	 * @param $key data key
	 */
	public function remove($key) {
		unset($this->data[$key]);
	}

	/**
	 * Remove state data
	 *
	 * @param $keyPrefix data key prefix
	 */
	public function removeAll($keyPrefix) {
		$map = array();
		foreach ($this->data as $key => $value) {
			if (strpos($key, $keyPrefix) < 0) {
				$map[$key] = $value;
			}
		}
		$this->data = $map;
	}
}

/**
 * @}
 */

?>