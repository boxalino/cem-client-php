<?php

/**
 * Boxalino CEM client library in PHP
 *
 * @package cem
 * @subpackage client
 * @author nitro@boxalino.com
 * @copyright 2009-2011 - Boxalino AG
 */


/**
 * Gateway state
 *
 * @package cem
 * @subpackage client
 */
class CEM_GatewayState {
	/**
	 * Http status code
	 *
	 * @var integer
	 */
	protected $code;

	/**
	 * Http status message
	 *
	 * @var string
	 */
	protected $message;

	/**
	 * Active cookies
	 *
	 * @var array
	 */
	protected $cookies;

	/**
	 * State data
	 *
	 * @var array
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
	 * @return integer last http status code
	 */
	public function getHttpCode() {
		return $this->code;
	}

	/**
	 * Get last http status message
	 *
	 * @return string last http status message (or "" if none)
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
	 * @param integer code last http status code
	 * @param string message last http status message
	 */
	public function setStatus($code, $message) {
		$this->code = $code;
		$this->message = $message;
	}


	/**
	 * Get cookie header
	 *
	 * @return string cookie header (or FALSE if none)
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
	 * @param string $name cookie name
	 * @return string cookie value (or FALSE if none)
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
	 * @param string $name cookie name
	 * @param string $value cookie value
	 * @param array $parameters cookie parameters
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
	 * @param string $name cookie name
	 */
	public function removeCookie($name) {
		if (isset($this->cookies[$name])) {
			unset($this->cookies[$name]);
		}
	}


	/**
	 * List state data
	 *
	 * @return array data list
	 */
	public function getAll() {
		return $this->data;
	}

	/**
	 * Get state data
	 *
	 * @param string $key data key
	 * @param mixed $default default value
	 * @return mixed data value
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
	 * @param string $keyPrefix data key prefix
	 * @return array map of data value
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
	 * @param string $key data key
	 * @param mixed $value data value
	 */
	public function set($key, $value) {
		$this->data[$key] = $value;
	}

	/**
	 * Remove state data
	 *
	 * @param string $key data key
	 */
	public function remove($key) {
		unset($this->data[$key]);
	}

	/**
	 * Remove state data
	 *
	 * @param string $keyPrefix data key prefix
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

?>