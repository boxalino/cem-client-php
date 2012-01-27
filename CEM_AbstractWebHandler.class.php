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
 * Abstract CEM handler for web-sites
 *
 * @author nitro@boxalino.com
 */
abstract class CEM_AbstractWebHandler {
	/**
	 * Encryption facility
	 */
	protected $crypto;

	/**
	 * Context key mapping
	 */
	protected $keys;


	/**
	 * Constructor
	 *
	 * @param &$crypto encryption facility
	 * @param $keys request parameter mapping
	 */
	public function __construct(&$crypto, $keys = array()) {
		$this->crypto = $crypto;
		$this->keys = $keys;
	}


	/**
	 * Set key mapping
	 *
	 * @param $keys keys
	 */
	public function setKeys($keys) {
		foreach ($keys as $src => $dst) {
			$this->keys[$src] = $dst;
		}
	}


	/**
	 * Escape value ('%' <> '%25', ';' <> '%3B', '=' <> '%3D')
	 *
	 * @param $value input value
	 * @return escaped value
	 */
	protected function escapeValue($value) {
		return str_replace(
			array('%', ';', '='),
			array('%25', '%3B', '%3D'),
			$value
		);
	}

	/**
	 * Unescape value ('%' <> '%25', ';' <> '%3B', '=' <> '%3D')
	 *
	 * @param $value input value
	 * @return escaped value
	 */
	protected function unescapeValue($value) {
		return str_replace(
			array('%25', '%3B', '%3D'),
			array('%', ';', '='),
			$value
		);
	}


	/**
	 * Encrypt/deflate data
	 *
	 * @param $data plain data
	 * @return encrypted data (or FALSE if none)
	 */
	protected function encrypt($data) {
		if (strlen($data) > 0) {
			$data = $this->crypto->encrypt('cem'.@gzdeflate($data, 9));
			if ($data) {
				return base64_encode($data);
			}
		}
		return FALSE;
	}

	/**
	 * Decrypt/inflate data
	 *
	 * @param $data encrypted data
	 * @return plain data (or FALSE if none)
	 */
	protected function decrypt($data) {
		if (strlen($data) > 0) {
			$data = $this->crypto->decrypt(base64_decode($data));
			if ($data && strpos($data, 'cem') === 0) {
				return @gzinflate(substr($data, 3));
			}
		}
		return FALSE;
	}


	/**
	 * Map parameter key
	 *
	 * @param $key parameter key
	 * @return mapped key
	 */
	protected function requestKey($key) {
		if (isset($this->keys[$key])) {
			return $this->keys[$key];
		}
		return $key;
	}

	/**
	 * Check if parameter exists
	 *
	 * @param $key parameter key
	 * @return TRUE if exists FALSE otherwise
	 */
	protected function requestExists($key) {
		return isset($_REQUEST[$this->requestKey($key)]);
	}

	/**
	 * Get request parameter as boolean
	 *
	 * @param $key parameter key
	 * @param $default default value
	 * @return parameter value or default value if doesn't exist
	 */
	protected function requestBoolean($key, $default = FALSE) {
		if (isset($_REQUEST[$this->requestKey($key)])) {
			$value = strtolower($this->requestString($key));
			return ($value == 'true' || $value == 'on' || floatval($value) > 0);
		}
		return $default;
	}

	/**
	 * Get request parameter as number
	 *
	 * @param $key parameter key
	 * @param $default default value
	 * @return parameter value or default value if doesn't exist
	 */
	protected function requestNumber($key, $default = 0) {
		if (isset($_REQUEST[$this->requestKey($key)])) {
			$value = $this->requestString($key);
			if (is_numeric($value)) {
				return floatval($value);
			}
			return 0;
		}
		return floatval($default);
	}

	/**
	 * Get request parameter as string
	 *
	 * @param $key parameter key
	 * @param $default default value
	 * @return parameter value or default value if doesn't exist
	 */
	protected function requestString($key, $default = "") {
		if (isset($_REQUEST[$this->requestKey($key)])) {
			return $this->filterRawString($_REQUEST[$this->requestKey($key)]);
		}
		return strval($default);
	}

	/**
	 * Get request parameter as string array
	 *
	 * @param $key parameter key
	 * @param $default default value
	 * @return parameter value or default value if doesn't exist
	 */
	protected function requestStringArray($key, $default = array()) {
		$array = array();
		if (isset($_REQUEST[$this->requestKey($key)])) {
			$value = $_REQUEST[$this->requestKey($key)];
			if (is_array($value)) {
				foreach ($value as $item) {
					$array[] = $this->filterRawString($item);
				}
			} else {
				$array[] = $this->filterRawString($value);
			}
		} else {
			foreach ($default as $value) {
				$array[] = strval($value);
			}
		}
		return $array;
	}

	/**
	 * Convert raw string
	 *
	 * @param $value raw string value
	 * @return formatted string value
	 */
	protected function filterRawString($value) {
		$value = strval($value);
		if (mb_detect_encoding($value) != 'UTF-8') {
			$value = mb_convert_encoding($value, 'UTF-8');
		}
		if (get_magic_quotes_gpc()) {
			return stripslashes($value);
		}
		return $value;
	}
}

/**
 * @}
 */

?>