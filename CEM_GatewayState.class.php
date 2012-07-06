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
 * Gateway state
 *
 * @author nitro@boxalino.com
 */
class CEM_GatewayState {
	/**
	 * Active cookies
	 */
	protected $cookies = array();

	/**
	 * Changed cookies
	 */
	protected $changedCookies = array();

	/**
	 * State data
	 */
	protected $data = array();

	/**
	 * Json decoded contexts (cache)
	 */
	private $_jsonContexts = array();


	/**
	 * Constructor
	 *
	 */
	public function __construct() {
	}


	/**
	 * Reset changed cookies
	 *
	 */
	public function resetChangedCookies() {
		$this->changedCookies = array();
	}

	/**
	 * Get cookies
	 *
	 * @param $changedOnly only return changed cookies
	 * @return cookies
	 */
	public function getCookies($changedOnly = FALSE) {
		if ($changedOnly) {
			$cookies = array();
			foreach ($this->changedCookies as $name) {
				$cookies[$name] = $this->cookies[$name];
			}
			return $cookies;
		}
		return $this->cookies;
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
	 * Get a cookie
	 *
	 * @param $name cookie name
	 * @return cookie value (or FALSE if none)
	 */
	public function getCookie($name) {
		if (isset($this->cookies[$name])) {
			return $this->cookies[$name];
		}
		return FALSE;
	}

	/**
	 * Set a cookie
	 *
	 * @param $name cookie name
	 * @param $value cookie value
	 */
	public function setCookie($name, $value) {
		if (is_array($value)) {
			$this->cookies[$name] = $value;
			if (!in_array($name, $this->changedCookies)) {
				$this->changedCookies[] = $name;
			}
		} else {
			if (!isset($this->cookies[$name])) {
				$this->cookies[$name] = array('name' => $name, 'value' => '', 'remote' => FALSE, 'expiresTime' => 0);
			}
			$this->cookies[$name]['value'] = $value;
		}
	}

	/**
	 * Remove a cookie
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


	/**
	 * Get context scopes
	 *
	 * @return context scopes
	 */
	public function getContexts() {
		return $this->get('context', array());
	}

	/**
	 * Get context data
	 *
	 * @param $name context name
	 * @return context data
	 */
	public function getContextData($name) {
		$scopes = $this->getContexts();
		if (isset($scopes[$name])) {
			return $scopes[$name]['data'];
		}
		return '';
	}

	/**
	 * Get context data from json
	 *
	 * @param $name context name
	 * @return context data (decoded)
	 */
	public function getContextJson($name) {
		if (!isset($this->_jsonContexts[$name])) {
			$this->_jsonContexts[$name] = json_decode($this->getContextData($name));
		}
		return $this->_jsonContexts[$name];
	}

	/**
	 * Set context scopes
	 *
	 * @param $contexts context scopes
	 */
	public function setContexts($contexts) {
		$this->set('context', $contexts);
		$this->_jsonContexts = array();
	}
}

/**
 * @}
 */

?>