<?php

/**
 * Boxalino CEM client library in PHP
 *
 * @package cem
 * @subpackage client
 * @author nitro@boxalino.com
 * @copyright 2009-2010 - Boxalino AG
 */


/** P&R parameter types: boolean */
define('CEM_PR_TYPE_BOOLEAN',  'boolean');

/** P&R parameter types: array of boolean */
define('CEM_PR_TYPE_BOOLEANS', 'boolean[]');

/** P&R parameter types: date */
define('CEM_PR_TYPE_DATE',     'date');

/** P&R parameter types: array of date */
define('CEM_PR_TYPE_DATES',    'date[]');

/** P&R parameter types: identifier */
define('CEM_PR_TYPE_ID',       'id');

/** P&R parameter types: array of identifier */
define('CEM_PR_TYPE_IDS',      'id[]');

/** P&R parameter types: matches */
define('CEM_PR_TYPE_MATCHES',  'matches');

/** P&R parameter types: number */
define('CEM_PR_TYPE_NUMBER',   'numeric');

/** P&R parameter types: array of number */
define('CEM_PR_TYPE_NUMBERS',  'numeric[]');

/** P&R parameter types: search */
define('CEM_PR_TYPE_SEARCH',   'search');

/** P&R parameter types: array of search */
define('CEM_PR_TYPE_SEARCHES', 'search[]');

/** P&R parameter types: string */
define('CEM_PR_TYPE_STRING',   'string');

/** P&R parameter types: array of string */
define('CEM_PR_TYPE_STRINGS',  'string[]');

/** P&R parameter types: localized text */
define('CEM_PR_TYPE_TEXT',     'text');

/** P&R parameter types: array of localized text */
define('CEM_PR_TYPE_TEXTS',    'text[]');


/**
 * P&R gateway request
 *
 * @package cem
 * @subpackage client
 */
class CEM_PR_SimpleRequest extends CEM_GatewayRequest {
	/**
	 * Strategy identifier
	 *
	 * @var string
	 */
	protected $strategy;

	/**
	 * Offset
	 *
	 * @var integer
	 */
	protected $offset;

	/**
	 * Size
	 *
	 * @var integer
	 */
	protected $size;

	/**
	 * Context
	 *
	 * @var array
	 */
	protected $context;


	/**
	 * Constructor
	 *
	 * @param string $strategy strategy identifier
	 * @param integer $offset offset
	 * @param integer $size size
	 */
	public function __construct($strategy, $offset, $size) {
		parent::__construct();
		$this->strategy = $strategy;
		$this->offset = $offset;
		$this->size = $size;
		$this->context = array();
	}


	/**
	 * Get strategy identifier
	 *
	 * @return string strategy identifier
	 */
	public function getStrategy() {
		return $this->strategy;
	}

	/**
	 * Get offset
	 *
	 * @return integer offset
	 */
	public function getOffset() {
		return $this->offset;
	}

	/**
	 * Get size
	 *
	 * @return integer size
	 */
	public function getSize() {
		return $this->size;
	}


	/**
	 * Get recommendation context
	 *
	 * @return array recommendation context
	 */
	public function getContext() {
		return $this->context;
	}

	/**
	 * Set recommendation context parameter
	 *
	 * @param string $key parameter key
	 * @param string $type parameter type (CEM_PR_TYPE_*)
	 * @param mixed $value parameter value (raw value depends of type)
	 */
	public function setContext($key, $type, $value) {
		$this->context[$key] = array(
			'type' => $type,
			'value' => $value
		);
	}

	/**
	 * Set recommendation context parameter (boolean)
	 *
	 * @param string $key parameter key
	 * @param boolean $value parameter value
	 */
	public function setBoolean($key, $value) {
		if (!is_bool($value)) {
			$value = $value ? TRUE : FALSE;
		}
		$this->setContext($key, CEM_PR_TYPE_BOOLEAN, $value);
	}

	/**
	 * Set recommendation context parameter (boolean[])
	 *
	 * @param string $key parameter key
	 * @param array $value parameter value
	 */
	public function setBooleans($key, $value) {
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				if (!is_bool($value[$k])) {
					$value[$k] = $value[$k] ? TRUE : FALSE;
				}
			}
		} else {
			$value = array();
		}
		$this->setContext($key, CEM_PR_TYPE_BOOLEANS, $value);
	}

	/**
	 * Set recommendation context parameter (date)
	 *
	 * @param string $key parameter key
	 * @param date $value parameter value
	 */
	public function setDate($key, $value) {
		if (!is_string($value)) {
			$value = strval($value);
		}
		$this->setContext($key, CEM_PR_TYPE_DATE, $value);
	}

	/**
	 * Set recommendation context parameter (date[])
	 *
	 * @param string $key parameter key
	 * @param array $value parameter value
	 */
	public function setDates($key, $value) {
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				if (!is_string($value[$k])) {
					$value[$k] = strval($value[$k]);
				}
			}
		} else {
			$value = array();
		}
		$this->setContext($key, CEM_PR_TYPE_DATES, $value);
	}

	/**
	 * Set recommendation context parameter (id)
	 *
	 * @param string $key parameter key
	 * @param string $value parameter value
	 */
	public function setId($key, $value) {
		if (!is_string($value)) {
			$value = strval($value);
		}
		$this->setContext($key, CEM_PR_TYPE_ID, $value);
	}

	/**
	 * Set recommendation context parameter (id[])
	 *
	 * @param string $key parameter key
	 * @param array $value parameter value
	 */
	public function setIds($key, $value) {
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				if (!is_string($value[$k])) {
					$value[$k] = strval($value[$k]);
				}
			}
		} else {
			$value = array();
		}
		$this->setContext($key, CEM_PR_TYPE_IDS, $value);
	}

	/**
	 * Set recommendation context parameter (matches)
	 *
	 * @param string $key parameter key
	 * @param object $value parameter value
	 */
	public function setMatches($key, $value) {
		// TODO: type checks
		$this->setContext($key, CEM_PR_TYPE_MATCHES, $value);
	}

	/**
	 * Set recommendation context parameter (numeric)
	 *
	 * @param string $key parameter key
	 * @param float $value parameter value
	 */
	public function setNumber($key, $value) {
		if (!is_numeric($value)) {
			$value = floatval($value);
		}
		$this->setContext($key, CEM_PR_TYPE_NUMBER, $value);
	}

	/**
	 * Set recommendation context parameter (numeric[])
	 *
	 * @param string $key parameter key
	 * @param array $value parameter value
	 */
	public function setNumbers($key, $value) {
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				if (!is_numeric($value[$k])) {
					$value[$k] = floatval($value[$k]);
				}
			}
		} else {
			$value = array();
		}
		$this->setContext($key, CEM_PR_TYPE_NUMBERS, $value);
	}

	/**
	 * Set recommendation context parameter (search)
	 *
	 * @param string $key parameter key
	 * @param object $value parameter value
	 */
	public function setSearch($key, $value) {
		// TODO: type checks
		$this->setContext($key, CEM_PR_TYPE_SEARCH, $value);
	}

	/**
	 * Set recommendation context parameter (search[])
	 *
	 * @param string $key parameter key
	 * @param array $value parameter value
	 */
	public function setSearches($key, $value) {
		if (is_array($value)) {
			// TODO: type checks
		} else {
			$value = array();
		}
		$this->setContext($key, CEM_PR_TYPE_SEARCHES, $value);
	}

	/**
	 * Set recommendation context parameter (string)
	 *
	 * @param string $key parameter key
	 * @param string $value parameter value
	 */
	public function setString($key, $value) {
		if (!is_string($value)) {
			$value = strval($value);
		}
		$this->setContext($key, CEM_PR_TYPE_STRING, $value);
	}

	/**
	 * Set recommendation context parameter (string[])
	 *
	 * @param string $key parameter key
	 * @param array $value parameter value
	 */
	public function setStrings($key, $value) {
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				if (!is_string($value[$k])) {
					$value[$k] = strval($value[$k]);
				}
			}
		} else {
			$value = array();
		}
		$this->setContext($key, CEM_PR_TYPE_STRINGS, $value);
	}

	/**
	 * Set recommendation context parameter (text)
	 *
	 * @param string $key parameter key
	 * @param array $value parameter value
	 */
	public function setText($key, $value) {
		if (is_array($value)) {
			// TODO: type checks
		} else {
			$value = array();
		}
		$this->setContext($key, CEM_PR_TYPE_TEXT, $value);
	}

	/**
	 * Set recommendation context parameter (text[])
	 *
	 * @param string $key parameter key
	 * @param array $value parameter value
	 */
	public function setTexts($key, $value) {
		if (is_array($value)) {
			// TODO: type checks
		} else {
			$value = array();
		}
		$this->setContext($key, CEM_PR_TYPE_TEXTS, $value);
	}

	/**
	 * Clear recommendation context
	 *
	 */
	public function clearContext() {
		$this->context = array();
	}


	/**
	 * Get request body content-type
	 *
	 * @return string request body content-type
	 */
	public function getContentType() {
		return "text/plain; charset=UTF-8";
	}

	/**
	 * Called to write the request
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @return string request raw body
	 */
	public function write(&$state) {
		$profile = $state->get("ctx_profile");

		$root = array();
		$root['strategy'] = $this->strategy;
		$root['offset'] = $this->offset;
		$root['size'] = $this->size;
		$root['context'] = $this->context;
		if ($profile) {
			$root['profile'] = json_decode($profile);
		}
		return json_encode($root);
	}
}

?>