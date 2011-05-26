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
 * @}
 */

?>