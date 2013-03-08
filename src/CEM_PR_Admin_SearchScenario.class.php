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
 * Scenario admin query
 *
 * @author nitro@boxalino.com
 */
class CEM_PR_Admin_SearchScenario extends CEM_PR_AbstractAdminQuery {
	/**
	 * Index identifier
	 */
	protected $index;

	/**
	 * Scenario configurations
	 */
	protected $configurations;


	/**
	 * Constructor
	 *
	 * @param $mode admin mode
	 * @param $index index identifier
	 * @param $configurations configurations to update
	 */
	public function __construct($mode, $index, $configurations = array()) {
		parent::__construct('kb/search', 'adminScenario', $mode);
		$this->index = $index;
		$this->configurations = $configurations;
	}


	/**
	 * Get query type
	 *
	 * @return query type
	 */
	public function type() {
		return "adminScenario";
	}

	/**
	 * Called to build the query
	 *
	 * @param $state client state reference
	 * @return query
	 */
	public function build($state) {
		$query = parent::build($state);
		$query["index"] = $this->index;
		$query["configurations"] = $this->configurations;
		return $query;
	}
}

/**
 * @}
 */

?>