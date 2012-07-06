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
 * Client-side data encryption
 *
 * @author nitro@boxalino.com
 */
class CEM_WebEncryption {
	/**
	 * Encryption key
	 */
	protected $key;

	/**
	 * Encryption IV
	 */
	protected $iv;

	/**
	 * Hash algorithm
	 */
	protected $hash = 'sha256';

	/**
	 * Encryption algorithm
	 */
	protected $algo = MCRYPT_RIJNDAEL_256;

	/**
	 * Encryption algorithm path
	 */
	protected $algoPath = '';

	/**
	 * Encryption mode
	 */
	protected $mode = MCRYPT_MODE_CBC;

	/**
	 * Encryption mode path
	 */
	protected $modePath = '';


	/**
	 * Constructor
	 *
	 * @param $secret server-side secret key used to encrypt data
	 * @param $iv initialization vector key
	 * @param $options encryption options
	 */
	public function __construct($secret, $iv, $options = array()) {
		foreach ($options as $key => $value) {
			if (property_exists($this, $key)) {
				$this->$key = $value;
			}
		}
		$this->key = hash($this->hash, $secret, TRUE);
		$this->iv = hash($this->hash, $iv, TRUE);
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


	/**
	 * Encrypt/deflate data with base64 encoding
	 *
	 * @param $data plain data
	 * @return encrypted data (or FALSE if none)
	 */
	public function encrypt64($data) {
		if (strlen($data) > 0) {
			$data = $this->encrypt('cem'.@gzdeflate($data, 9));
			if ($data) {
				return base64_encode($data);
			}
		}
		return FALSE;
	}

	/**
	 * Decrypt/inflate data with base64 encoding
	 *
	 * @param $data encrypted data
	 * @return plain data (or FALSE if none)
	 */
	public function decrypt64($data) {
		if (strlen($data) > 0) {
			$data = $this->decrypt(base64_decode($data));
			if ($data && strpos($data, 'cem') === 0) {
				return @gzinflate(substr($data, 3));
			}
		}
		return FALSE;
	}


	/**
	 * Escape value ('%' <> '%25', ';' <> '%3B', '=' <> '%3D')
	 *
	 * @param $value input value
	 * @return escaped value
	 */
	public function escapeValue($value) {
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
	public function unescapeValue($value) {
		return str_replace(
			array('%25', '%3B', '%3D'),
			array('%', ';', '='),
			$value
		);
	}
}

/**
 * @}
 */

?>