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
 * Client-side data encryption
 *
 * @author nitro@boxalino.com
 */
class CEM_WebEncryption {
	/**
	 * Hash algorithm
	 */
	protected $hash;

	/**
	 * Encryption key
	 */
	protected $key;

	/**
	 * Encryption IV
	 */
	protected $iv;

	/**
	 * Encryption algorithm
	 */
	protected $algo;

	/**
	 * Encryption algorithm path
	 */
	protected $algoPath;

	/**
	 * Encryption mode
	 */
	protected $mode;

	/**
	 * Encryption mode path
	 */
	protected $modePath;


	/**
	 * Constructor
	 *
	 * @param $secret server-side secret key used to encrypt data
	 * @param $iv initialization vector key
	 * @param $options encryption options
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
	 * @param $data plain data
	 * @return encrypted data
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
	 * @param $data encrypted data
	 * @return plain data
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
 * Default CEM state handler for web-sites
 *
 * @author nitro@boxalino.com
 */
class CEM_WebStateHandler extends CEM_AbstractWebHandler {
	/**
	 * Cached state
	 */
	protected $state;


	/**
	 * Constructor
	 *
	 * @param &$crypto encryption facility
	 */
	public function __construct(&$crypto) {
		parent::__construct($crypto);
		$this->state = NULL;
	}


	/**
	 * Create client state
	 *
	 * @return client state
	 */
	public function create() {
		return new CEM_GatewayState();
	}

	/**
	 * Read client state from storage
	 *
	 * @return client state or NULL if none
	 */
	public function read() {
		return $this->state;
	}

	/**
	 * Write client state to storage
	 *
	 * @param &$state client state
	 */
	public function write(&$state) {
		$this->state = $state;
	}

	/**
	 * Remove client state from storage
	 *
	 * @param &$state client state
	 */
	public function remove(&$state) {
		$this->state = NULL;
	}
}

/**
 * Session-based CEM state handler for web-sites
 *
 * @author nitro@boxalino.com
 */
class CEM_WebStateSessionHandler extends CEM_WebStateHandler {
	/**
	 * State variable key
	 */
	protected $name;


	/**
	 * Constructor
	 *
	 * @param &$crypto encryption facility
	 * @param $name variable name (defaults to 'cem')
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
	 * @param &$state client state
	 */
	public function write(&$state) {
		$_SESSION[$this->name] = $state;

		parent::write($state);
	}

	/**
	 * Remove client state from storage
	 *
	 * @param &$state client state
	 */
	public function remove(&$state) {
		if (isset($_SESSION[$this->name])) {
			unset($_SESSION[$this->name]);
		}

		parent::remove($state);
	}
}

/**
 * @}
 */

?>