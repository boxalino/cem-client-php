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
 * Abstract recommendation query
 *
 * @author nitro@boxalino.com
 */
abstract class CEM_PR_AbstractQuery {
	/**
	 * Strategy identifier
	 */
	protected $strategy;

	/**
	 * Operation identifier
	 */
	protected $operation;

	/**
	 * Personalized flag
	 */
	protected $personalized;


	/**
	 * Constructor
	 *
	 * @param $strategy strategy identifier
	 * @param $operation operation identifier
	 * @param $personalized personalized flag
	 */
	public function __construct($strategy, $operation, $personalized = TRUE) {
		$this->strategy = $strategy;
		$this->operation = $operation;
		$this->personalized = $personalized;
	}


	/**
	 * Get query type
	 *
	 * @return query type
	 */
	public function type() {
		return "simple";
	}

	/**
	 * Get strategy identifier
	 *
	 * @return strategy identifier
	 */
	public function getStrategy() {
		return $this->strategy;
	}

	/**
	 * Get operation identifier
	 *
	 * @return operation identifier
	 */
	public function getOperation() {
		return $this->operation;
	}


	/**
	 * Called to build the query
	 *
	 * @param $state client state reference
	 * @return query
	 */
	public function build($state) {
		if ($this->personalized) {
			$indexPreferences = array();
			$profile = $state->getContextJson('profile');
			if (isset($profile->preferences)) {
				$indexPreferences = $profile->preferences;
			}
			return array(
				'strategy' => $this->strategy,
				'operation' => $this->operation,
				'indexPreferences' => $indexPreferences
			);
		}
		return array(
			'strategy' => $this->strategy,
			'operation' => $this->operation
		);
	}
}

/**
 * @}
 */

?>