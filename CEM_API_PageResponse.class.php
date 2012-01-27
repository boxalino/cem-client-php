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
	protected $responseSize;

	/**
	 * Context
	 */
	protected $context;

	/**
	 * Results offset
	 */
	protected $resultsOffset;

	/**
	 * Results total
	 */
	protected $resultsTotal;

	/**
	 * Results page index
	 */
	protected $resultsPageIndex;

	/**
	 * Results page count
	 */
	protected $resultsPageCount;

	/**
	 * Results page size
	 */
	protected $resultsPageSize;

	/**
	 * Results
	 */
	protected $results;

	/**
	 * Recommendations
	 */
	protected $recommendations;

	/**
	 * HTML blocks
	 */
	protected $blocks;


	/**
	 * Constructor
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->responseSize = 0;
		$this->context = NULL;
		$this->resultsOffset = 0;
		$this->resultsTotal = 0;
		$this->resultsPageIndex = 0;
		$this->resultsPageCount = 0;
		$this->resultsPageSize = 0;
		$this->results = array();
		$this->recommendations = array();
		$this->blocks = array();
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
		return (isset($this->context['value']) ? $this->context['value'] : NULL);
	}

	/**
	 * Get decoded cem context
	 *
	 * @return decoded cem context
	 */
	public function decodeContext() {
		$crypto = new CEM_WebEncryption(
			isset($this->context['key']) ? $this->context['key'] : '',
			isset($this->context['iv']) ? $this->context['iv'] : ''
		);
		$handler = new CEM_WebRequestHandler($crypto);
		return $handler->getSequentialContexts();
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
		$this->status = $node->getAttribute('status') == 'true';
		$this->time = $node->getAttribute('totalTime');

		// visit children
		$this->context = NULL;
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
					$this->context = array(
						'key' => $child->getAttribute('key'),
						'iv' => $child->getAttribute('iv'),
						'value' => $this->visitTexts($child)
					);
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