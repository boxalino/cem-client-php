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
 * Guidance previews query
 *
 * @author nitro@boxalino.com
 */
class CEM_PR_GuidancePreviews extends CEM_PR_AbstractQuery {
	/**
	 * Base query
	 */
	protected $query;

	/**
	 * Textual query
	 */
	protected $queryText;

	/**
	 * Terms
	 */
	protected $queryTerms;

	/**
	 * Guidances
	 */
	protected $guidances;

	/**
	 * Filter properties
	 */
	protected $filterProperties;

	/**
	 * Scorer properties
	 */
	protected $scorerProperties;

	/**
	 * Refinements
	 */
	protected $refinements;

	/**
	 * Attributes
	 */
	protected $attributes;

	/**
	 * Maximum recommendations
	 */
	protected $maximumRecommendations;

	/**
	 * Alternatives flag
	 */
	protected $alternatives;


	/**
	 * Constructor
	 *
	 * @param $query base query
	 * @param $queryText query text
	 * @param $queryTerms query terms
	 * @param $guidances guidances
	 * @param $filterProperties filter properties
	 * @param $scorerProperties scorer properties
	 * @param $refinements refinements
	 * @param $attributes attributes
	 * @param $maximumRecommendations maximum recommendations
	 * @param $alternatives alternatives flag
	 */
	public function __construct($query, $queryText, $queryTerms, $guidances, $filterProperties, $scorerProperties, $refinements, $attributes, $maximumRecommendations, $alternatives) {
		parent::__construct('kb/guidance', 'previews', FALSE);
		$this->query = $query;
		$this->queryText = $queryText;
		$this->queryTerms = $queryTerms;
		$this->guidances = $guidances;
		$this->filterProperties = $filterProperties;
		$this->scorerProperties = $scorerProperties;
		$this->refinements = $refinements;
		$this->attributes = $attributes;
		$this->maximumRecommendations = $maximumRecommendations;
		$this->alternatives = $alternatives;
	}


	/**
	 * Get query type
	 *
	 * @return query type
	 */
	public function type() {
		return "guidancePreviews";
	}

	/**
	 * Called to build the query
	 *
	 * @param $state client state reference
	 * @return query
	 */
	public function build($state) {
		$query = parent::build($state);
		$query["query"] = $this->query;
		$query["queryText"] = $this->queryText;
		$query["queryTerms"] = $this->queryTerms;
		$query["guidances"] = $this->guidances;
		$query["filterProperties"] = $this->filterProperties;
		$query["scorerProperties"] = $this->scorerProperties;
		$query["refinements"] = $this->refinements;
		$query["attributes"] = $this->attributes;
		$query["maximumRecommendations"] = $this->maximumRecommendations;
		$query["alternatives"] = $this->alternatives;
		return $query;
	}
}

/**
 * @}
 */

?>