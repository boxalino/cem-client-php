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
 * Abstract gateway response
 *
 * @author nitro@boxalino.com
 */
abstract class CEM_GatewayResponse14 extends CEM_GatewayResponse {
	/**
	 * Server version
	 */
	protected $version;

	/**
	 * Response status
	 */
	protected $status;

	/**
	 * Response message
	 */
	protected $message;

	/**
	 * Response time
	 */
	protected $time;


	/**
	 * Constructor
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->version = NULL;
		$this->status = FALSE;
		$this->message = NULL;
		$this->time = 0;
	}


	/**
	 * Get server version
	 *
	 * @return server version
	 */
	public function getVersion() {
		return $this->version;
	}

	/**
	 * Get status
	 *
	 * @return status
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * Get response message
	 *
	 * @return response message
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * Get time
	 *
	 * @return time (in seconds)
	 */
	public function getTime() {
		return ($this->time / 1000.0);
	}
}


/**
 * PR gateway response
 *
 * @author nitro@boxalino.com
 */
class CEM_PR_GatewayResponse14 extends CEM_GatewayResponse14 {
	/**
	 * Customer identifier
	 */
	protected $customer;

	/**
	 * Response format
	 */
	protected $responseFormat;

	/**
	 * Response scope
	 */
	protected $responses;

	/**
	 * Response size
	 */
	protected $responseSize;


	/**
	 * Constructor
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->customer = NULL;
		$this->responseFormat = NULL;
		$this->responseSize = 0;
		$this->responses = array();
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
	 * Get response format
	 *
	 * @return response format
	 */
	public function getResponseFormat() {
		return $this->responseFormat;
	}

	/**
	 * Get response size
	 *
	 * @return response size
	 */
	public function getResponseSize() {
		return $this->responseSize;
	}

	/**
	 * Get response scopes
	 *
	 * @return response scopes
	 */
	public function getResponses() {
		return $this->responses;
	}


	/**
	 * Called to read the response
	 *
	 * @param &$state client state reference
	 * @param &$data response raw body
	 * @return TRUE on success, FALSE otherwise
	 */
	public function read(&$state, &$data) {
		$this->responseSize = strlen($data);
		$doc = new DOMDocument("1.0", 'UTF-8');
		$doc->loadXML($data);
		return $this->visitResponse($doc->documentElement);
	}


	/**
	 * Visit xml response
	 *
	 * @param &$node root element
	 * @return TRUE on success, FALSE otherwise
	 */
	protected function visitResponse(&$node) {
		// check root element
		if ($node->tagName != 'cem') {
			return FALSE;
		}

		// get attributes
		$this->version = $node->getAttribute('version');
		$this->status = $node->getAttribute('status') == 'true';
		$this->time = $node->getAttribute('time');
		$this->customer = $node->getAttribute('customer');
		$this->responseFormat = $node->getAttribute('responseFormat');

		// visit children
		$this->message = NULL;
		$this->responses = array();
		$this->context = array();
		for ($i = 0; $i < $node->childNodes->length; $i++) {
			$child = $node->childNodes->item($i);
			switch ($child->nodeType) {
			case XML_ELEMENT_NODE:
				switch ($child->tagName) {
				case 'message':
					$this->message = $this->visitTexts($child);
					break;

				case 'response':
					if ($this->responseFormat == CEM_PR_FORMAT_JSON) {
						$scope = json_decode($this->visitTexts($child));
						if (!$scope) {
							return FALSE;
						}
						$this->responses[] = $scope;
					} else {
						return FALSE;
					}
					break;
				}
				break;
			}
		}
		return TRUE;
	}

	/**
	 * Visit xml text nodes
	 *
	 * @param &$node xml element
	 * @return text content
	 */
	protected function visitTexts(&$node) {
		$text = '';
		for ($i = 0; $i < $node->childNodes->length; $i++) {
			$child = $node->childNodes->item($i);
			switch ($child->nodeType) {
			case XML_TEXT_NODE:
			case XML_CDATA_SECTION_NODE:
				$text .= $child->data;
				break;
			}
		}
		return $text;
	}
}


/**
 * Guided-Search gateway response
 *
 * @author nitro@boxalino.com
 */
class CEM_GS_GatewayResponse14 extends CEM_GatewayResponse14 {
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
	 * Response format
	 */
	protected $responseFormat;

	/**
	 * Response scope
	 */
	protected $responses;

	/**
	 * Response size
	 */
	protected $responseSize;

	/**
	 * Context scopes
	 */
	protected $context;


	/**
	 * Constructor
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->customer = NULL;
		$this->dialog = NULL;
		$this->language = NULL;
		$this->responseFormat = NULL;
		$this->responseSize = 0;
		$this->responses = array();
		$this->context = array();
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
	 * Get response format
	 *
	 * @return response format
	 */
	public function getResponseFormat() {
		return $this->responseFormat;
	}

	/**
	 * Get response size
	 *
	 * @return response size
	 */
	public function getResponseSize() {
		return $this->responseSize;
	}

	/**
	 * Get response scopes
	 *
	 * @return response scopes
	 */
	public function getResponses() {
		return $this->responses;
	}

	/**
	 * Get context scopes
	 *
	 * @return context scopes 
	 */
	public function getContext() {
		return $this->context;
	}


	/**
	 * Called to read the response
	 *
	 * @param &$state client state reference
	 * @param &$data response raw body
	 * @return TRUE on success, FALSE otherwise
	 */
	public function read(&$state, &$data) {
		$this->responseSize = strlen($data);
		$doc = new DOMDocument("1.0", 'UTF-8');
		$doc->loadXML($data);
		if ($this->visitResponse($doc->documentElement)) {
			$state->set('context', $this->context);
			return TRUE;
		}
		return FALSE;
	}


	/**
	 * Visit xml response
	 *
	 * @param &$node root element
	 * @return TRUE on success, FALSE otherwise
	 */
	protected function visitResponse(&$node) {
		// check root element
		if ($node->tagName != 'cem') {
			return FALSE;
		}

		// get attributes
		$this->version = $node->getAttribute('version');
		$this->status = $node->getAttribute('status') == 'true';
		$this->time = $node->getAttribute('time');
		$this->customer = $node->getAttribute('customer');
		$this->dialog = $node->getAttribute('dialog');
		$this->language = $node->getAttribute('language');
		$this->responseFormat = $node->getAttribute('responseFormat');

		// visit children
		$this->message = NULL;
		$this->responses = array();
		$this->context = array();
		for ($i = 0; $i < $node->childNodes->length; $i++) {
			$child = $node->childNodes->item($i);
			switch ($child->nodeType) {
			case XML_ELEMENT_NODE:
				switch ($child->tagName) {
				case 'message':
					$this->message = $this->visitTexts($child);
					break;

				case 'context':
					$this->context[$child->getAttribute('id')] = array(
						'level' => $child->getAttribute('level'),
						'mode' => $child->getAttribute('mode'),
						'data' => $this->visitTexts($child)
					);
					break;

				case 'response':
					if ($this->responseFormat == CEM_GS_FORMAT_JSON) {
						$scope = json_decode($this->visitTexts($child));
						if (!$scope) {
							return FALSE;
						}
						$this->responses[$scope->id] = $scope->response;
					} else {
						return FALSE;
					}
					break;
				}
				break;
			}
		}
		return TRUE;
	}

	/**
	 * Visit xml text nodes
	 *
	 * @param &$node xml element
	 * @return text content
	 */
	protected function visitTexts(&$node) {
		$text = '';
		for ($i = 0; $i < $node->childNodes->length; $i++) {
			$child = $node->childNodes->item($i);
			switch ($child->nodeType) {
			case XML_TEXT_NODE:
			case XML_CDATA_SECTION_NODE:
				$text .= $child->data;
				break;
			}
		}
		return $text;
	}
}

/**
 * @}
 */

?>