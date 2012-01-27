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
 * Query completion recommendation query
 *
 * @author nitro@boxalino.com
 */
class CEM_PR_CompletionQuery extends CEM_PR_AbstractQuery {
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
	 * Maximum amount of suggestions
	 */
	protected $suggestionLimit;
 
	/**
	 * Maximum amount of contextual recommendations
	 */
	protected $resultLimit;

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
	 * Scorer properties
	 */
	protected $scorerProperties;
 
	/**
	 * Disambiguation priorities
	 */
	protected $disambiguationPriorities;


	/**
	 * Constructor
	 *
	 * @param $index index identifier
	 * @param $language language identifier
	 * @param $filter query filter
	 * @param $queryText query text
	 * @param $suggestionLimit suggestion limit
	 * @param $resultLimit result limit
	 * @param $includedProperties included properties
	 * @param $excludedProperties excluded properties
	 * @param $filterProperties filter properties
	 * @param $scorerProperties scorer properties
	 * @param $disambiguationPriorities disambiguation priorities
	 */
	public function __construct($index, $language, $filter, $queryText, $suggestionLimit, $resultLimit, $includedProperties = array(), $excludedProperties = array(), $filterProperties = array(), $scorerProperties = array(), $disambiguationPriorities = array()) {
		parent::__construct('kb/query', 'complete');
		$this->index = $index;
		$this->language = $language;
		$this->filter = $filter;
		$this->queryText = $queryText;
		$this->suggestionLimit = $suggestionLimit;
		$this->resultLimit = $resultLimit;
		$this->includedProperties = $includedProperties;
		$this->excludedProperties = $excludedProperties;
		$this->filterProperties = $filterProperties;
		$this->scorerProperties = $scorerProperties;
		$this->disambiguationPriorities = $disambiguationPriorities;
	}


	/**
	 * Get query type
	 *
	 * @return query type
	 */
	public function type() {
		return "completeQuery";
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
		$query["language"] = $this->language;
		$query["filter"] = $this->filter;
		$query["queryText"] = $this->queryText;
		$query["suggestionLimit"] = $this->suggestionLimit;
		$query["resultPopulation"] = $this->resultLimit * 2;
		$query["resultLimit"] = $this->resultLimit;
		$query["termPopulation"] = 100;
		$query["includedProperties"] = $this->includedProperties;
		$query["excludedProperties"] = $this->excludedProperties;
		$query["filterProperties"] = $this->filterProperties;
		$query["scorerProperties"] = $this->scorerProperties;
		$query["disambiguationPriorities"] = $this->disambiguationPriorities;
		return $query;
	}
}

/**
 * @}
 */

?>