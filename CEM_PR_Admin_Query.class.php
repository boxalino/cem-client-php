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
 * Query admin query
 *
 * @author nitro@boxalino.com
 */
class CEM_PR_Admin_Query extends CEM_PR_AbstractAdminQuery {
	/**
	 * Index identifier
	 */
	protected $index;

	/**
	 * Language identifier
	 */
	protected $language;

	/**
	 * Base filter
	 */
	protected $filter;

	/**
	 * Textual query
	 */
	protected $queryText;

	/**
	 * Included properties
	 */
	protected $includedProperties;

	/**
	 * Excluded properties
	 */
	protected $excludedProperties;

	/**
	 * Filter properties
	 */
	protected $filterProperties;

	/**
	 * Terms
	 */
	protected $queryTerms;


	/**
	 * Constructor
	 *
	 * @param $mode admin mode
	 * @param $index index identifier
	 * @param $language language identifier
	 * @param $filter query filter
	 * @param $queryText query text
	 * @param $includedProperties included properties
	 * @param $excludedProperties excluded properties
	 * @param $filterProperties filter properties
	 * @param $queryTerms terms to update
	 */
	public function __construct($mode, $index, $language, $filter, $queryText, $includedProperties = array(), $excludedProperties = array(), $filterProperties = array(), $queryTerms = array()) {
		parent::__construct('kb/query', 'admin', $mode);
		$this->index = $index;
		$this->language = $language;
		$this->filter = $filter;
		$this->queryText = $queryText;
		$this->includedProperties = $includedProperties;
		$this->excludedProperties = $excludedProperties;
		$this->filterProperties = $filterProperties;
		$this->queryTerms = $queryTerms;
	}


	/**
	 * Get query type
	 *
	 * @return query type
	 */
	public function type() {
		return "adminQuery";
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
		$query["language"] = $this->language;
		$query["filter"] = $this->filter;
		$query["queryText"] = $this->queryText;
		$query["termPopulation"] = 100;
		$query["includedProperties"] = $this->includedProperties;
		$query["excludedProperties"] = $this->excludedProperties;
		$query["filterProperties"] = $this->filterProperties;
		$query["queryTerms"] = $this->queryTerms;
		return $query;
	}
}

/**
 * @}
 */

?>