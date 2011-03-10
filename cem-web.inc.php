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
 * Client-side data encryption
 *
 * @package cem
 * @subpackage web
 */
class CEM_WebEncryption {
	/**
	 * Hash algorithm
	 *
	 * @var string
	 */
	protected $hash;

	/**
	 * Encryption key
	 *
	 * @var string
	 */
	protected $key;

	/**
	 * Encryption IV
	 *
	 * @var string
	 */
	protected $iv;

	/**
	 * Encryption algorithm
	 *
	 * @var string
	 */
	protected $algo;

	/**
	 * Encryption algorithm path
	 *
	 * @var string
	 */
	protected $algoPath;

	/**
	 * Encryption mode
	 *
	 * @var string
	 */
	protected $mode;

	/**
	 * Encryption mode path
	 *
	 * @var string
	 */
	protected $modePath;


	/**
	 * Constructor
	 *
	 * @param string $secret server-side secret key used to encrypt data
	 * @param string $iv initialization vector key
	 * @param string $options encryption options
	 */
	public function __construct($secret, $iv, $options = array()) {
		$this->hash = isset($options['hash']) ? $options['hash'] : 'sha256';
		$this->key = hash($this->hash, $secret, TRUE);
		$this->iv = hash($this->hash, $iv, TRUE);
		$this->algo = isset($options['algo']) ? $options['algo'] : MCRYPT_RIJNDAEL_256;
		$this->algoPath = isset($options['algoPath']) ? $options['algoPath'] : '';
		$this->mode = isset($options['mode']) ? $options['mode'] : MCRYPT_MODE_CBC;
		$this->modePath = isset($options['modePath']) ? $options['modePath'] : '';
	}


	/**
	 * Encrypt data
	 *
	 * @param string $data plain data
	 * @return string encrypted data
	 */
	public function encrypt($data) {
		// open algorithm
		$td = mcrypt_module_open($this->algo, $this->algoPath, $this->mode, $this->modePath);
		if (!$td) {
			return FALSE;
		}

		// build key
		$ks = mcrypt_enc_get_key_size($td);
		if (strlen($this->key) < $ks) {
			return FALSE;
		}
		$key = substr($this->key, 0, $ks);

		// build iv
		$is = mcrypt_enc_get_iv_size($td);
		if (strlen($this->iv) < $is) {
			return FALSE;
		}
		$iv = substr($this->iv, 0, $is);

		// encrypt
		if (mcrypt_generic_init($td, $key, $iv) === FALSE) {
			return FALSE;
		}
		$data = mcrypt_generic($td, $data);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		return $data;
	}

	/**
	 * Decrypt data
	 *
	 * @param string $data encrypted data
	 * @return string plain data
	 */
	public function decrypt($data) {
		// check input data
		if (strlen($data) == 0) {
			return FALSE;
		}

		// open algorithm
		$td = mcrypt_module_open($this->algo, $this->algoPath, $this->mode, $this->modePath);
		if (!$td) {
			return FALSE;
		}

		// build key
		$ks = mcrypt_enc_get_key_size($td);
		if (strlen($this->key) < $ks) {
			return FALSE;
		}
		$key = substr($this->key, 0, $ks);

		// build iv
		$is = mcrypt_enc_get_iv_size($td);
		if (strlen($this->iv) < $is) {
			return FALSE;
		}
		$iv = substr($this->iv, 0, $is);

		// decrypt
		if (mcrypt_generic_init($td, $key, $iv) === FALSE) {
			return FALSE;
		}
		$data = mdecrypt_generic($td, $data);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		return $data;
	}
}

/**
 * Abstract CEM handler for web-sites
 *
 * @package cem
 * @subpackage web
 */
abstract class CEM_AbstractWebHandler {
	/**
	 * Encryption facility
	 *
	 * @var CEM_WebEncryption
	 */
	protected $crypto;

	/**
	 * Context variable keys
	 *
	 * @var array
	 */
	protected $keys;


	/**
	 * Constructor
	 *
	 * @param CEM_WebEncryption &$crypto encryption facility
	 * @param array $keys request parameter mapping
	 */
	public function __construct(&$crypto, $keys = array()) {
		$this->crypto = $crypto;
		$this->keys = $keys;
	}


	/**
	 * Escape value ('%' <> '%25', ';' <> '%3B', '=' <> '%3D')
	 *
	 * @param string $value input value
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
	 * @param string $value input value
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
	 * @param string $data plain data
	 * @return string encrypted data (or FALSE if none)
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
	 * @param string $data encrypted data
	 * @return string plain data (or FALSE if none)
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
	 * @param string $key parameter key
	 * @return string mapped key
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
	 * @param string $key parameter key
	 * @return boolean TRUE if exists FALSE otherwise
	 */
	protected function requestExists($key) {
		return isset($_REQUEST[$this->requestKey($key)]);
	}

	/**
	 * Get request parameter as boolean
	 *
	 * @param string $key parameter key
	 * @param boolean $default default value
	 * @return boolean parameter value or default value if doesn't exist
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
	 * @param string $key parameter key
	 * @param float $default default value
	 * @return float parameter value or default value if doesn't exist
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
	 * @param string $key parameter key
	 * @param string $default default value
	 * @return string parameter value or default value if doesn't exist
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
	 * @param string $key parameter key
	 * @param string $default default value
	 * @return array parameter value or default value if doesn't exist
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
	 * @param string $value raw string value
	 * @return string formatted string value
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
 * Default CEM state handler for web-sites
 *
 * @package cem
 * @subpackage web
 */
class CEM_WebStateHandler extends CEM_AbstractWebHandler {
	/**
	 * Cached state
	 *
	 * @var CEM_GatewayState
	 */
	protected $state;


	/**
	 * Constructor
	 *
	 * @param CEM_WebEncryption &$crypto encryption facility
	 */
	public function __construct(&$crypto) {
		parent::__construct($crypto);
		$this->state = NULL;
	}


	/**
	 * Create client state
	 *
	 * @return CEM_GatewayState client state
	 */
	public function create() {
		return new CEM_GatewayState();
	}

	/**
	 * Read client state from storage
	 *
	 * @return CEM_GatewayState client state or NULL if none
	 */
	public function read() {
		return $this->state;
	}

	/**
	 * Write client state to storage
	 *
	 * @param CEM_GatewayState &$state client state
	 */
	public function write(&$state) {
		$this->state = $state;
	}

	/**
	 * Remove client state from storage
	 *
	 * @param CEM_GatewayState &$state client state
	 */
	public function remove(&$state) {
		$this->state = NULL;
	}
}

/**
 * Session-based CEM state handler for web-sites
 *
 * @package cem
 * @subpackage web
 */
class CEM_WebStateSessionHandler extends CEM_WebStateHandler {
	/**
	 * State variable key
	 *
	 * @var string
	 */
	protected $name;


	/**
	 * Constructor
	 *
	 * @param CEM_WebEncryption &$crypto encryption facility
	 * @param string $name variable name (defaults to 'cem')
	 */
	public function __construct(&$crypto, $name = 'cem') {
		parent::__construct($crypto);
		$this->name = $name;

		// start session if necessary
		if (strlen(session_id()) == 0) {
			session_start();
		}
		if (isset($_SESSION[$this->name])) {
			$this->state = $_SESSION[$this->name];
		}
	}


	/**
	 * Write client state to storage
	 *
	 * @param CEM_GatewayState &$state client state
	 */
	public function write(&$state) {
		$_SESSION[$this->name] = $state;

		parent::write($state);
	}

	/**
	 * Remove client state from storage
	 *
	 * @param CEM_GatewayState &$state client state
	 */
	public function remove(&$state) {
		if (isset($_SESSION[$this->name])) {
			unset($_SESSION[$this->name]);
		}

		parent::remove($state);
	}
}

?>