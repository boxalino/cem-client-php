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
 * Frontend API page response
 *
 * @author nitro@boxalino.com
 */
class CEM_API_PageResponse extends CEM_GatewayResponse {
	/**
	 * Response size
	 */
	protected $responseSize = 0;

	/**
	 * Context
	 */
	protected $context = NULL;

	/**
	 * Active query
	 */
	protected $query = '';

	/**
	 * Results offset
	 */
	protected $resultsOffset = 0;

	/**
	 * Results total
	 */
	protected $resultsTotal = 0;

	/**
	 * Results page index
	 */
	protected $resultsPageIndex = 0;

	/**
	 * Results page count
	 */
	protected $resultsPageCount = 0;

	/**
	 * Results page size
	 */
	protected $resultsPageSize = 0;

	/**
	 * Results
	 */
	protected $results = array();

	/**
	 * Recommendations
	 */
	protected $recommendations = array();

	/**
	 * HTML blocks
	 */
	protected $blocks = array();


	/**
	 * Constructor
	 *
	 */
	public function __construct() {
		parent::__construct();
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
		if (!@$doc->loadXML($data)) {
			return FALSE;
		}
		return $this->visitResponse($doc->documentElement);
	}


	/**
	 * Get cem context
	 *
	 * @return cem context
	 */
	public function getContext() {
		return $this->context;
	}

	/**
	 * Get decoded cem context
	 *
	 * @return decoded cem context
	 */
	public function decodeContext() {
		$handler = new CEM_WebRequestHandler($this->getCrypto());
		return $handler->getSequentialContexts();
	}

	/**
	 * Get cem query
	 *
	 * @return cem query
	 */
	public function getQuery() {
		return $this->query;
	}

	/**
	 * Get cem results
	 *
	 * @return cem results
	 */
	public function getResults() {
		return $this->results;
	}

	/**
	 * Get cem recommendations
	 *
	 * @return cem recommendations
	 */
	public function getRecommendations() {
		return $this->recommendations;
	}

	/**
	 * Check if html block exists
	 *
	 * @param $id block id
	 * @return TRUE if block exists, FALSE otherwise
	 */
	public function hasBlock($id) {
		return isset($this->blocks[$id]);
	}

	/**
	 * Get html block
	 *
	 * @param $id block id
	 * @return html block content or ''
	 */
	public function getBlock($id) {
		return (isset($this->blocks[$id]) ? $this->blocks[$id] : '');
	}

	/**
	 * Get html blocks
	 *
	 * @return html blocks
	 */
	public function getBlocks() {
		return $this->blocks;
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
		$this->status = $node->getAttribute('status') == 'true' || $node->getAttribute('success') == 'true';
		$this->time = $node->getAttribute('totalTime');
		$this->crypto['key'] = $node->getAttribute('cryptoKey');
		$this->crypto['iv'] = $node->getAttribute('cryptoIV');

		// visit children
		$this->context = NULL;
		$this->query = '';
		$this->resultsOffset = 0;
		$this->resultsTotal = 0;
		$this->resultsPageIndex = 0;
		$this->resultsPageCount = 0;
		$this->resultsPageSize = 0;
		$this->results = array();
		$this->recommendations = array();
		$this->blocks = array();
		for ($i = 0; $i < $node->childNodes->length; $i++) {
			$child = $node->childNodes->item($i);
			switch ($child->nodeType) {
			case XML_ELEMENT_NODE:
				switch ($child->tagName) {
				case 'context':
					$this->context = $this->visitTexts($child);
					break;

				case 'query':
					$this->query = $this->visitTexts($child);
					break;

				case 'results':
					$this->visitResults($child);
					break;

				case 'recommendations':
					$this->visitRecommendations($child);
					break;

				case 'blocks':
					$this->visitBlocks($child);
					break;
				}
				break;
			}
		}
		return TRUE;
	}

	/**
	 * Visit xml response
	 *
	 * @param &$node root element
	 */
	protected function visitResults(&$node) {
		$this->resultsOffset = $node->getAttribute('offset');
		$this->resultsTotal = $node->getAttribute('total');
		$this->resultsPageIndex = $node->getAttribute('pageIndex');
		$this->resultsPageCount = $node->getAttribute('pageCount');
		$this->resultsPageSize = $node->getAttribute('pageSize');
		for ($i = 0; $i < $node->childNodes->length; $i++) {
			$child = $node->childNodes->item($i);
			switch ($child->nodeType) {
			case XML_ELEMENT_NODE:
				switch ($child->tagName) {
				case 'result':
					$this->results[] = $child->getAttribute('id');
					break;
				}
				break;
			}
		}
	}

	/**
	 * Visit xml response
	 *
	 * @param &$node root element
	 */
	protected function visitRecommendations(&$node) {
		for ($i = 0; $i < $node->childNodes->length; $i++) {
			$child = $node->childNodes->item($i);
			switch ($child->nodeType) {
			case XML_ELEMENT_NODE:
				switch ($child->tagName) {
				case 'recommendation':
					$this->recommendations[] = $child->getAttribute('id');
					break;
				}
				break;
			}
		}
	}

	/**
	 * Visit xml response
	 *
	 * @param &$node root element
	 */
	protected function visitBlocks(&$node) {
		for ($i = 0; $i < $node->childNodes->length; $i++) {
			$child = $node->childNodes->item($i);
			switch ($child->nodeType) {
			case XML_ELEMENT_NODE:
				switch ($child->tagName) {
				case 'block':
					$this->blocks[$child->getAttribute('id')] = $this->visitTexts($child);
					break;
				}
				break;
			}
		}
	}

	/**
	 * Visit xml text nodes
	 *
	 * @param &$node xml element
	 * @return text content
	 */
	protected function visitTexts(&$node) {
		$text = array();
		for ($i = 0; $i < $node->childNodes->length; $i++) {
			$child = $node->childNodes->item($i);
			switch ($child->nodeType) {
			case XML_TEXT_NODE:
			case XML_CDATA_SECTION_NODE:
				$text[] = $child->data;
				break;
			}
		}
		return implode(' ', $text);
	}
}

/**
 * @}
 */

?>