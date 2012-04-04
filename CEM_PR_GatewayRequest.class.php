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


/** PR request/response format: json */
define('CEM_PR_FORMAT_JSON', 'JSON');

/** PR request/response format: xml */
define('CEM_PR_FORMAT_XML', 'XML');


/**
 * P&R gateway request
 *
 * @author nitro@boxalino.com
 */
class CEM_PR_GatewayRequest extends CEM_GatewayRequest {
	/**
	 * Customer identifier
	 */
	protected $customer;

	/**
	 * Request format
	 */
	protected $requestFormat = CEM_PR_FORMAT_JSON;

	/**
	 * Requests
	 */
	protected $requests = array();

	/**
	 * Response format
	 */
	protected $responseFormat = CEM_PR_FORMAT_JSON;


	/**
	 * Constructor
	 *
	 * @param $customer customer identifier
	 */
	public function __construct($customer) {
		parent::__construct();
		$this->customer = $customer;
	}


	/**
	 * Get customer identifier
	 *
	 * @return customer identifier
	 */
	public function getCustomer() {
		return $this->customer;
	}

	/**
	 * Get recommendation requests
	 *
	 * @return recommendation requests
	 */
	public function getRequests() {
		return $this->requests;
	}

	/**
	 * Add recommendation request
	 *
	 * @param $request recommendation request
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
	 * @return request body content-type
	 */
	public function getContentType() {
		return "text/xml; charset=UTF-8";
	}

	/**
	 * Called to write the request
	 *
	 * @param $state client state reference
	 * @return request raw body
	 */
	public function write($state) {
		$doc = new DOMDocument("1.0", 'UTF-8');

		$root = $doc->createElement('cem');
		$root->setAttribute('customer', $this->customer);
		$root->setAttribute('requestFormat', $this->requestFormat);
		$root->setAttribute('responseFormat', $this->responseFormat);

		foreach ($this->requests as $request) {
			$el = $doc->createElement('request');
			if ($this->requestFormat == CEM_PR_FORMAT_JSON) {
				$el->setAttribute('type', $request->type());
				$el->appendChild($doc->createCDATASection(json_encode($request->build($state))));
			} else {
				return FALSE;
			}
			$root->appendChild($el);
		}

		$doc->appendChild($root);

		return $doc->saveXML();
	}
}

/**
 * @}
 */

?>