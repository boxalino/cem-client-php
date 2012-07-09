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
	 * State context scopes
	 */
	protected $context = array();

	/**
	 * Json decoded context scopes (cache)
	 */
	protected $_jsonContext = array();


	/**
	 * Constructor
	 *
	 */
	public function __construct() {
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
	 * Reset changed cookies
	 *
	 */
	public function resetChangedCookies() {
		$this->changedCookies = array();
	}


	/**
	 * Get context scopes
	 *
	 * @return context scopes
	 */
	public function getContext() {
		return $this->context;
	}

	/**
	 * Get context scope data
	 *
	 * @param $name context scope name
	 * @return context scope data or '' if none
	 */
	public function getContextData($name) {
		if (isset($this->context[$name])) {
			return $this->context[$name]['data'];
		}
		return '';
	}

	/**
	 * Get context scope data as json
	 *
	 * @param $name context scope name
	 * @return context scope data (decoded)
	 */
	public function getContextJson($name) {
		if (!isset($this->_jsonContext[$name])) {
			$this->_jsonContext[$name] = @json_decode($this->getContextData($name));
		}
		return $this->_jsonContext[$name];
	}

	/**
	 * Set context scope data
	 *
	 * @param $name context scope name
	 * @param $data context scope data
	 */
	public function setContextData($name, $data) {
		if (isset($this->context[$name])) {
			$this->context[$name]['data'] = $data;
		} else {
			$this->context[$name] = array(
				'level' => 'search',
				'mode' => 'sequential',
				'data' => $data
			);
		}
		if (isset($this->_jsonContext[$name])) {
			unset($this->_jsonContext[$name]);
		}
	}

	/**
	 * Set context scopes
	 *
	 * @param $context context scopes
	 */
	public function setContext($context) {
		$this->context = $context;
		$this->_jsonContext = array();
	}
}

/**
 * @}
 */

?>