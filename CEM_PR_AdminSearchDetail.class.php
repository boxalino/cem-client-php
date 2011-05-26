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
 * Search detail admin query
 *
 * @author nitro@boxalino.com
 */
class CEM_PR_AdminSearchDetail extends CEM_PR_AbstractQuery {
	/**
	 * Index identifier
	 */
	protected $index;

	/**
	 * Property values
	 */
	protected $properties;


	/**
	 * Constructor
	 *
	 * @param $index index identifier
	 * @param $properties property values
	 */
	public function __construct($index, $properties) {
		parent::__construct('kb/search', 'adminDetail', FALSE);
		$this->index = $index;
		$this->properties = $properties;
	}


	/**
	 * Get query type
	 *
	 * @return query type
	 */
	public function type() {
		return "adminDetail";
	}

	/**
	 * Called to build the query
	 *
	 * @param &$state client state reference
	 * @return query
	 */
	public function build(&$state) {
		$query = parent::build($state);
		$query["index"] = $this->index;
		$query["configuration"] = array('properties' => $this->properties);
		return $query;
	}
}

/**
 * @}
 */

?>