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
 * Boxalino Analytics tracker
 *
 * @author nitro@boxalino.com
 */
class CEM_Analytics extends CEM_HttpClient {
	/**
	 * @internal Tracker URL
	 */
	private $url;

	/**
	 * @internal Visitor id
	 */
	private $visitorId = '';

	/**
	 * @internal Visitor age
	 */
	private $visitorAge = 0;


	/**
	 * Constructor
	 *
	 * @param $url tracker url
	 * @param $username tracker username for authentication (optional)
	 * @param $password tracker password for authentication (optional)
	 * @param $connectionTimeout connect timeout ms (optional)
	 * @param $readTimeout read timeout ms (optional)
	 */
	public function __construct($url, $username = FALSE, $password = FALSE, $connectionTimeout = 1000, $readTimeout = 1000) {
		parent::__construct(FALSE, FALSE, $connectionTimeout, $readTimeout);
		$this->url = $url;
	}


	/**
	 * Set current visitor
	 *
	 * @param $visitorId visitor identifier
	 * @param $visitorAge visitor age
	 */
	public function setVisitor($visitorId, $visitorAge) {
		$this->visitorId = $visitorId;
		$this->visitorAge = $visitorAge;
	}


	/**
	 * This method is called to track an event "query" with Boxalino Analytics.
	 *
	 * @param $query query text
	 * @param $source optional source identifier
	 */
	public function trackQuery($query, $source = '') {
		return $this->trackEvent(
			'query',
			sprintf('query:%s source:%s', urlencode($query), urlencode($source))
		);
	}

	/**
	 * This method is called to track an event "refine-query" with Boxalino Analytics.
	 *
	 * @param $term term text
	 * @param $property selected property
	 * @param $value selected value
	 */
	public function trackRefineQuery($term, $property, $value) {
		return $this->trackEvent(
			'refineQuery',
			sprintf('term:%s property:%s value:%s', urlencode($term), urlencode($property), urlencode($value))
		);
	}

	/**
	 * This method is called to track an event "set-guidance" with Boxalino Analytics.
	 *
	 * @param $property changed property
	 * @param $value changed value
	 */
	public function trackSetGuidance($property, $value) {
		return $this->trackEvent(
			'setGuidance',
			sprintf('property:%s value:%s', urlencode($property), urlencode($value))
		);
	}

	/**
	 * This method is called to track an event "remove-guidance" with Boxalino Analytics.
	 *
	 * @param $property removed property
	 */
	public function trackRemoveGuidance($property) {
		return $this->trackEvent(
			'removeGuidance',
			sprintf('property:%s', urlencode($property))
		);
	}

	/**
	 * This method is called to track an event "recommendation-view" with Boxalino Analytics.
	 *
	 * @param $products product identifiers (productId => strategy)
	 */
	public function trackRecommendationView($products) {
		$payload = array();
		foreach ($products as $productId => $strategy) {
			$payload[] = urlencode($productId).'='.urlencode($strategy);
		}
		return $this->trackEvent(
			'recommendationView',
			implode(' ', $payload)
		);
	}

	/**
	 * This method is called to track an event "recommendation-click" with Boxalino Analytics.
	 *
	 * @param $product product identifier
	 * @param $position product position
	 * @param $strategy recommendation strategy
	 */
	public function trackRecommendationClick($product, $position, $strategy) {
		return $this->trackEvent(
			'recommendationClick',
			sprintf('product:%s position:%s strategy:%s', urlencode($product), urlencode($position), urlencode($strategy))
		);
	}

	/**
	 * This method is called to track an event "detail-view" with Boxalino Analytics.
	 *
	 * @param $product product identifier
	 * @param $source optional source identifier
	 */
	public function trackDetailView($product, $source = '') {
		return $this->trackEvent(
			'view',
			sprintf('product:%s source:%s', urlencode($product), urlencode($source))
		);
	}

	/**
	 * This method is called to track an event "add-to-basket" with Boxalino Analytics.
	 *
	 * @param $product product identifier
	 * @param $source optional source identifier
	 */
	public function trackAddToBasket($product, $source = '') {
		return $this->trackEvent(
			'addToBasket',
			sprintf('product:%s source:%s', urlencode($product), urlencode($source))
		);
	}

	/**
	 * This method is called to track an event "purchase" with Boxalino Analytics.
	 *
	 * @param $status transaction status (TRUE = confirmed, FALSE = started)
	 * @param $amount total transaction amount
	 * @param $count amount of products in the transaction
	 */
	public function trackPurchase($status, $amount, $count) {
		return $this->trackEvent(
			'purchase',
			sprintf('status:%s amount:%f products:%d', $status ? '1' : '0', floatval($amount), intval($count))
		);
	}


	/**
	 * Track an event with Boxalino Analytics.
	 *
	 * @param $name event name
	 * @param $description event description (optional)
	 */
	public function trackEvent($name, $description = '') {
		// extract analytics parameters
		$parameters = array(
			'connection' => 'http',
			'clientAddress' => '',
			'clientAgent' => '',
			'clientReferer' => '',
			'serverAddress' => '',
			'serverHost' => '',
			'visitorId' => $this->visitorId,
			'visitorAge' => $this->visitorAge,
			'eventName' => $name,
			'eventDescription' => $description
		);
		if (isset($_SERVER['HTTPS'])) {
			$parameters['connection'] = ($_SERVER['HTTPS'] == 'on' ? 'https' : 'http');
		}
		if (isset($_SERVER['REMOTE_ADDR'])) {
			$parameters['clientAddress'] = $_SERVER['REMOTE_ADDR'];
		}
		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$parameters['clientAgent'] = $_SERVER['HTTP_USER_AGENT'];
		}
		if (isset($_SERVER['HTTP_REFERER'])) {
			$parameters['clientReferer'] = $_SERVER['HTTP_REFERER'];
		}
		if (isset($_SERVER['SERVER_ADDR'])) {
			$parameters['serverAddress'] = $_SERVER['SERVER_ADDR'];
		}
		if (isset($_SERVER['HTTP_HOST'])) {
			$parameters['serverHost'] = $_SERVER['HTTP_HOST'];
		}

		// forward cookie(s)
		if (isset($_COOKIE['cemt'])) {
			$this->setCookie('cemt', $_COOKIE['cemt']);
		}

		// send request
		return ($this->postFields($this->url, $parameters) == 200);
	}
}

/**
 * @}
 */

?>