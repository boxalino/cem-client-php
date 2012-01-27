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
 * Default CEM response handler for web-sites
 *
 * @author nitro@boxalino.com
 */
class CEM_WebResponseHandler extends CEM_AbstractWebHandler {
	/**
	 * Constructor
	 *
	 * @param &$crypto encryption facility
	 */
	public function __construct(&$crypto) {
		parent::__construct($crypto);
	}


	/**
	 * Called each client interaction to wrap the response
	 *
	 * @param &$state client state reference
	 * @param &$request client request reference
	 * @param &$response client response reference
	 * @param &$options options passed for interaction
	 */
	public function onInteraction(&$state, &$request, &$response, &$options) {
	}

	/**
	 * Called each client recommendation to wrap the response
	 *
	 * @param &$state client state reference
	 * @param &$request client request reference
	 * @param &$response client response reference
	 * @param &$options options passed for recommendation
	 */
	public function onRecommendation(&$state, &$request, &$response, &$options) {
	}

	/**
	 * Called if client interaction triggers an error
	 *
	 * @param &$state client state reference
	 * @param &$request client request reference
	 * @param &$options options passed for recommendation
	 */
	public function onError(&$state, &$request, &$options) {
	}
}

/**
 * @}
 */

?>