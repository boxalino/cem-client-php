<?php

/**
 * Boxalino CEM client library in PHP
 *
 * @package cem
 * @subpackage client
 * @author nitro@boxalino.com
 * @copyright 2009-2010 - Boxalino AG
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
	 * @param string $raw optional encoded format
	 */
	public function __construct($raw = FALSE) {
		$this->code = 0;
		$this->message = "";
		$this->cookies = array();
		$this->data = array();

		// decode state
		if ($raw) {
			list($cookies, $context, $others) = explode('&', $raw);

			if (strlen($cookies) > 0) {
				foreach (explode(';', $cookies) as $item) {
					list($name, $value) = explode('=', $item);

					$name = urldecode($name);
					if (strlen($name) > 0) {
						$this->cookies[$name] = array('value' => urldecode($value));
					}
				}
			}
			if (strlen($context) > 0) {
				$value = array();
				foreach (explode(';', $context) as $item) {
					list($name, $level, $data) = explode('=', $item);

					$name = urldecode($name);
					$level = urldecode($level);
					$data = urldecode($data);
					if (strlen($name) > 0) {
						$value[$name] = array('level' => $level, 'data' => $data);
					}
				}
				$this->data['context'] = $value;
			}
			if (strlen($others) > 0) {
				foreach (explode(';', $others) as $item) {
					list($name, $data) = explode('=', $item);

					$name = urldecode($name);
					if (strlen($name) > 0) {
						$this->data[$name] = json_decode(urldecode($data));
					}
				}
			}
		}
	}


	/**
	 * Encode this object into a compressed format
	 *
	 * @return string encoded format
	 */
	public function encode() {
		$text = $this->getCookies();
		$text .= '&';
		if (isset($this->data['context'])) {
			$i = 0;
			foreach ($this->data['context'] as $key => $value) {
				if ($i > 0) {
					$text .= ';';
				}
				$text .= urlencode($key) . '=' . urlencode($value['level']) . '=' . urlencode($value['data']);
				$i++;
			}
		}
		$text .= '&';
		$i = 0;
		foreach ($this->data as $key => $value) {
			if ($key != 'context') {
				if ($i > 0) {
					$text .= ';';
				}
				$text .= urlencode($key) . '=' . urlencode(json_encode($value['data']));
				$i++;
			}
		}
		return $text;
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
	 * Get current session identifier
	 *
	 * @return string session identifier or NULL if none
	 */
	public function getSessionId() {
		if (isset($this->cookies['JSESSIONID'])) {
			return $this->cookies['JSESSIONID']['value'];
		}
		return NULL;
	}

	/**
	 * Set current session identifier
	 *
	 * @param string $id session identifier (or NULL to remove)
	 */
	public function setSessionId($id) {
		if ($id !== NULL) {
			$this->cookies['JSESSIONID'] = array(
				'value' => $id
			);
		} else if (isset($this->cookies['JSESSIONID'])) {
			unset($this->cookies['JSESSIONID']);
		}
	}


	/**
	 * Get active cookies for http header
	 *
	 * @return string active cookies
	 */
	public function getCookies() {
		$text = '';
		foreach ($this->cookies as $name => $cookie) {
			if (strlen($text) > 0) {
				$text .= ';';
			}
			$text .= urlencode($name) . '=' . urlencode($cookie['value']);
		}
		return $text;
	}

	/**
	 * Set an active cookie
	 *
	 * @param string $name cookie name
	 * @param array $cookie cookie value
	 */
	public function setCookie($name, $cookie) {
		$this->cookies[$name] = $cookie;
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