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


/** PR admin query mode: read */
define('CEM_PR_ADMIN_READ', 'read');

/** PR admin query mode: read/write */
define('CEM_PR_ADMIN_READ_WRITE', 'readWrite');

/** PR admin query mode: write */
define('CEM_PR_ADMIN_WRITE', 'write');

/** PR admin query mode: clear */
define('CEM_PR_ADMIN_CLEAR', 'clear');


/**
 * Abstract recommendation admin query
 *
 * @author nitro@boxalino.com
 */
abstract class CEM_PR_AbstractAdminQuery extends CEM_PR_AbstractQuery {
	/**
	 * Mode
	 */
	protected $mode;


	/**
	 * Constructor
	 *
	 * @param $mode admin mode
	 * @param $strategy strategy identifier
	 * @param $operation operation identifier
	 */
	public function __construct($strategy, $operation, $mode) {
		parent::__construct($strategy, $operation, FALSE);
		$this->mode = $mode;
	}


	/**
	 * Called to build the query
	 *
	 * @param &$state client state reference
	 * @return query
	 */
	public function build(&$state) {
		$query = parent::build($state);
		$query["mode"] = $this->mode;
		return $query;
	}
}

/**
 * @}
 */

?>