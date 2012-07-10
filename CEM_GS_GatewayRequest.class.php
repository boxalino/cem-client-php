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


/** GS request/response format: none */
define('CEM_GS_FORMAT_NONE', 'NONE');

/** GS request/response format: json */
define('CEM_GS_FORMAT_JSON', 'JSON');

/** GS request/response format: xml */
define('CEM_GS_FORMAT_XML', 'XML');


/**
 * Guided-Search gateway request
 *
 * @author nitro@boxalino.com
 */
class CEM_GS_GatewayRequest extends CEM_GatewayRequest {
	/**
	 * Customer identifier
	 */
	protected $customer;

	/**
	 * Dialog identifier
	 */
	protected $dialog;

	/**
	 * Language identifier
	 */
	protected $language;

	/**
	 * Request format
	 */
	protected $requestFormat = CEM_GS_FORMAT_JSON;

	/**
	 * Requests array
	 */
	protected $requests = array();

	/**
	 * Response format
	 */
	protected $responseFormat = CEM_GS_FORMAT_JSON;


	/**
	 * Constructor
	 *
	 * @param $customer customer identifier
	 * @param $dialog dialog identifier
	 * @param $language language identifier
	 */
	public function __construct($customer, $dialog, $language) {
		parent::__construct();
		$this->customer = $customer;
		$this->dialog = $dialog;
		$this->language = $language;
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
	 * Get dialog identifier
	 *
	 * @return dialog identifier
	 */
	public function getDialog() {
		return $this->dialog;
	}

	/**
	 * Get language identifier
	 *
	 * @return language identifier
	 */
	public function getLanguage() {
		return $this->language;
	}

	/**
	 * Get request format
	 *
	 * @return request format
	 */
	public function getRequestFormat() {
		return $this->requestFormat;
	}

	/**
	 * Get request batch
	 *
	 * @return request batch
	 */
	public function getRequests() {
		return $this->requests;
	}

	/**
	 * Insert a new request at the begining of the batch
	 *
	 * @param $action request action (or NULL if none)
	 * @param $variables request variables hashmap
	 */
	public function insertRequest($action, $variables = array()) {
		array_unshift($this->requests, array('type' => 'action', 'action' => $action, 'variables' => $variables));
	}

	/**
	 * Insert a new init request at the begining of the batch
	 *
	 */
	public function insertInitRequest() {
		array_unshift($this->requests, array('type' => 'init'));
	}

	/**
	 * Insert a new free request at the begining of the batch
	 *
	 */
	public function insertFreeRequest() {
		array_unshift($this->requests, array('type' => 'free'));
	}

	/**
	 * Insert a new request at the end of the batch
	 *
	 * @param $action request action (or NULL if none)
	 * @param $variables request variables hashmap
	 */
	public function appendRequest($action, $variables = array()) {
		$this->requests[] = array('type' => 'action', 'action' => $action, 'variables' => $variables);
	}

	/**
	 * Insert a new init request at the begining of the batch
	 *
	 */
	public function appendInitRequest() {
		$this->requests[] = array('type' => 'init');
	}

	/**
	 * Insert a new free request at the begining of the batch
	 *
	 */
	public function appendFreeRequest() {
		$this->requests[] = array('type' => 'free');
	}

	/**
	 * Clear request batch
	 *
	 */
	public function clearRequests() {
		$this->requests = array();
	}

	/**
	 * Get response format
	 *
	 * @return response format
	 */
	public function getResponseFormat() {
		return $this->responseFormat;
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
		$root->setAttribute('dialog', $this->dialog);
		$root->setAttribute('language', $this->language);
		$root->setAttribute('requestFormat', $this->requestFormat);
		$root->setAttribute('responseFormat', $this->responseFormat);

		if (sizeof($this->requests) > 0) {
			foreach ($this->requests as $request) {
				$el = $doc->createElement($request['type']);
				if (isset($request['action']) && strlen($request['action']) > 0) {
					$el->setAttribute('id', $request['action']);
				}
				if (isset($request['variables'])) {
					if ($this->requestFormat == CEM_GS_FORMAT_JSON) {
						if (sizeof($request['variables']) > 0) {
							$el->appendChild($doc->createCDATASection(json_encode($request['variables'])));
						} else {
							$el->appendChild($doc->createCDATASection("{}"));
						}
					} else {
						return FALSE;
					}
				}
				$root->appendChild($el);
			}
		}

		foreach ($state->getContext() as $id => $item) {
			$context = $doc->createElement('context');
			$context->setAttribute('id', $id);
			$context->setAttribute('level', $item['level']);
			$context->setAttribute('mode', $item['mode']);
			$context->appendChild($doc->createCDATASection($item['data']));
			$root->appendChild($context);
		}

		$doc->appendChild($root);

		return $doc->saveXML();
	}
}

/**
 * @}
 */

?>