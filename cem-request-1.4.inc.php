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
 * P&R gateway request
 *
 * @package cem
 * @subpackage client
 */
class CEM_PR_MultiRequest extends CEM_GatewayRequest {
	/**
	 * Customer identifier
	 *
	 * @var string
	 */
	protected $customer;

	/**
	 * Requests
	 *
	 * @var array
	 */
	protected $requests;


	/**
	 * Constructor
	 *
	 * @param string $customer customer identifier
	 */
	public function __construct($customer) {
		parent::__construct();
		$this->customer = $customer;
		$this->requests = array();
	}


	/**
	 * Get customer identifier
	 *
	 * @return string customer identifier
	 */
	public function getCustomer() {
		return $this->customer;
	}

	/**
	 * Get recommendation requests
	 *
	 * @return array recommendation requests
	 */
	public function getRequests() {
		return $this->requests;
	}

	/**
	 * Add recommendation request
	 *
	 * @param CEM_PR_AbstractQuery $request recommendation request
	 */
	public function addRequest($request) {
		$this->requests[] = $request;
	}

	/**
	 * Clear recommendation requests
	 *
	 */
	public function clearRequests() {
		$this->requests = array();
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
		$root = array();
		$root['customer'] = $this->customer;
		$root['requests'] = array();
		foreach ($this->requests as $request) {
			$root['requests'][] = $request->type();
			$root['requests'][] = $request->build($state);
		}
		return json_encode($root);
	}
}

/**
 * Abstract recommendation query
 *
 * @package cem
 * @subpackage client
 */
abstract class CEM_PR_AbstractQuery {
	/**
	 * Strategy identifier
	 *
	 * @var string
	 */
	protected $strategy;

	/**
	 * Operation identifier
	 *
	 * @var string
	 */
	protected $operation;

	/**
	 * Strategy parameters
	 *
	 * @var array
	 */
	protected $parameters;


	/**
	 * Constructor
	 *
	 * @param string $strategy strategy identifier
	 * @param string $operation operation identifier
	 */
	public function __construct($strategy, $operation) {
		$this->strategy = $strategy;
		$this->operation = $operation;
		$this->parameters = array();
	}


	/**
	 * Get query type
	 *
	 * @return string query type
	 */
	public function type() {
		return "simple";
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
	 * Get operation identifier
	 *
	 * @return string operation identifier
	 */
	public function getOperation() {
		return $this->operation;
	}

	/**
	 * Get strategy parameter
	 *
	 * @param string $name parameter name
	 * @return string parameter value
	 */
	public function getParameter($name) {
		if (isset($this->parameters[$name])) {
			return $this->parameters[$name];
		}
		return FALSE;
	}

	/**
	 * Set strategy parameter
	 *
	 * @param string $name parameter name
	 * @param string $value parameter value
	 */
	public function setParameter($name, $value) {
		$this->parameters[$name] = $value;
	}

	/**
	 * Remove strategy parameter
	 *
	 * @param string $name parameter name
	 */
	public function removeParameter($name) {
		unset($this->parameters[$name]);
	}

	/**
	 * Clear strategy parameters
	 *
	 */
	public function clearParameters() {
		$this->parameters = array();
	}


	/**
	 * Called to build the query
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @return array query
	 */
	public function build(&$state) {
		$parameters = array();
		foreach ($this->parameters as $name => $value) {
			$parameters[] = array(
				"name" => $name,
				"value" => $value
			);
		}
		$indexPreferences = array();
/*		$profile = $state->get("ctx_profile");
		if ($profile) {
			$root['profile'] = json_decode($profile);
		}*/
		return array(
			"strategy" => $this->strategy,
			"operation" => $this->operation,
			"parameters" => $parameters,
			"indexPreferences" => $indexPreferences
		);
	}
}

/**
 * Kb query
 *
 * @package cem
 * @subpackage client
 */
class CEM_KB_Query {
	/**
	 * Index identifier
	 *
	 * @var string
	 */
	public $index;

	/**
	 * Flags
	 *
	 * @var array
	 */
	public $flags;

	/**
	 * Parameters
	 *
	 * @var array
	 */
	public $parameters;


	/**
	 * Constructor
	 *
	 * @param string $index index identifier
	 * @param string $flags query flags
	 * @param string $parameters query parameters
	 */
	public function __construct($index, $flags = array(), $parameters = array()) {
		$this->index = $index;
		$this->flags = $flags;
		$this->parameters = $parameters;
	}
}

/**
 * Query completion recommendation query
 *
 * @package cem
 * @subpackage client
 */
class CEM_PR_CompletionQuery extends CEM_PR_AbstractQuery {
	/**
	 * Index identifier
	 *
	 * @var string
	 */
	protected $index;

	/**
	 * Language identifier
	 *
	 * @var string
	 */
	protected $language;

	/**
	 * Base filter
	 *
	 * @var string
	 */
	protected $filter;

	/**
	 * Textual query
	 *
	 * @var string
	 */
	protected $queryText;

	/**
	 * Maximum amount of suggestions
	 *
	 * @var int
	 */
	protected $limit;

	/**
	 * Included properties
	 *
	 * @var array
	 */
	protected $includedProperties;

	/**
	 * Excluded properties
	 *
	 * @var array
	 */
	protected $excludedProperties;


	/**
	 * Constructor
	 *
	 * @param string $strategy strategy identifier
	 * @param string $operation operation identifier
	 */
	public function __construct($strategy, $operation, $index, $language, $filter, $queryText, $limit, $includedProperties = array(), $excludedProperties = array()) {
		parent::__construct($strategy, $operation);
		$this->index = $index;
		$this->language = $language;
		$this->filter = $filter;
		$this->queryText = $queryText;
		$this->limit = $limit;
		$this->includedProperties = $includedProperties;
		$this->excludedProperties = $excludedProperties;
	}


	/**
	 * Get query type
	 *
	 * @return string query type
	 */
	public function type() {
		return "completeQuery";
	}

	/**
	 * Called to build the query
	 *
	 * @param CEM_GatewayState &$state client state reference
	 * @return array query
	 */
	public function build(&$state) {
		$query = parent::build($state);
		$query["index"] = $this->index;
		$query["language"] = $this->language;
		$query["filter"] = $this->filter;
		$query["queryText"] = $this->queryText;
		$query["limit"] = $this->limit;
		$query["includedProperties"] = $this->includedProperties;
		$query["excludedProperties"] = $this->excludedProperties;
		return $query;
	}
}

?>