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
	 * This method is called to track an event "query" with Boxalino Analytics.
	 *
	 * @param $query query text
	 * @param $source optional source identifier
	 * @param $meta optional meta-data
	 */
	public function trackQuery($query, $source = '', $meta = '') {
		return $this->trackEvent(
			'query',
			sprintf('query:%s source:%s meta:%s', urlencode($query), urlencode($source), urlencode($meta))
		);
	}

	/**
	 * This method is called to track an event "refineQuery" with Boxalino Analytics.
	 *
	 * @param $term term text
	 * @param $property selected property
	 * @param $value selected value
	 * @param $meta optional meta-data
	 */
	public function trackRefineQuery($term, $property, $value, $meta = '') {
		return $this->trackEvent(
			'refineQuery',
			sprintf('term:%s property:%s value:%s meta:%s', urlencode($term), urlencode($property), urlencode($value), urlencode($meta))
		);
	}

	/**
	 * This method is called to track an event "setGuidance" with Boxalino Analytics.
	 *
	 * @param $property changed property
	 * @param $value changed value
	 * @param $meta optional meta-data
	 */
	public function trackSetGuidance($property, $value, $meta = '') {
		return $this->trackEvent(
			'setGuidance',
			sprintf('property:%s value:%s meta:%s', urlencode($property), urlencode($value), urlencode($meta))
		);
	}

	/**
	 * This method is called to track an event "removeGuidance" with Boxalino Analytics.
	 *
	 * @param $property removed property
	 * @param $meta optional meta-data
	 */
	public function trackRemoveGuidance($property, $meta = '') {
		return $this->trackEvent(
			'removeGuidance',
			sprintf('property:%s meta:%s', urlencode($property), urlencode($meta))
		);
	}

	/**
	 * This method is called to track an event "page" with Boxalino Analytics.
	 *
	 * @param $page page index
	 * @param $meta optional meta-data
	 */
	public function trackPage($page, $meta = '') {
		return $this->trackEvent(
			'page',
			sprintf('page:%d meta:%s', intval($page), urlencode($meta))
		);
	}

	/**
	 * This method is called to track an event "recommendationView" with Boxalino Analytics.
	 *
	 * @param $products product identifiers (productId => strategy)
	 * @param $meta optional meta-data
	 */
	public function trackRecommendationView($products) {
		$payload = array();
		foreach ($products as $productId => $strategy) {
			$payload[] = urlencode($productId).'='.urlencode($strategy);
		}
		return $this->trackEvent(
			'recommendationView',
			sprintf('products:%s meta:%s', implode(',', $payload), urlencode($meta))
		);
	}

	/**
	 * This method is called to track an event "recommendationClick" with Boxalino Analytics.
	 *
	 * @param $product product identifier
	 * @param $position product position
	 * @param $strategy recommendation strategy
	 * @param $meta optional meta-data
	 */
	public function trackRecommendationClick($product, $position, $strategy) {
		return $this->trackEvent(
			'recommendationClick',
			sprintf('product:%s position:%s strategy:%s', urlencode($product), urlencode($position), urlencode($strategy), urlencode($meta))
		);
	}

	/**
	 * This method is called to track an event "categoryView" with Boxalino Analytics.
	 *
	 * @param $id category identifier
	 * @param $name optional category name
	 * @param $meta optional meta-data
	 */
	public function trackCategoryView($id, $name = '', $meta = '') {
		return $this->trackEvent(
			'categoryView',
			sprintf('id:%s name:%s meta:%s', urlencode($id), urlencode($name), urlencode($meta))
		);
	}

	/**
	 * This method is called to track an event "productView" with Boxalino Analytics.
	 *
	 * @param $id product identifier
	 * @param $name optional product name
	 * @param $meta optional meta-data
	 */
	public function trackProductView($id, $name = '', $meta = '') {
		return $this->trackEvent(
			'productView',
			sprintf('id:%s name:%s meta:%s', urlencode($id), urlencode($name), urlencode($meta))
		);
	}

	/**
	 * This method is called to track an event "addToBasket" with Boxalino Analytics.
	 *
	 * @param $id product identifier
	 * @param $name optional product name
	 * @param $meta optional meta-data
	 */
	public function trackAddToBasket($id, $name = '', $meta = '') {
		return $this->trackEvent(
			'addToBasket',
			sprintf('id:%s name:%s meta:%s', urlencode($id), urlencode($name), urlencode($meta))
		);
	}

	/**
	 * This method is called to track an event "purchaseTry" with Boxalino Analytics.
	 *
	 * @param $amount total transaction amount
	 * @param $products product identifiers in the transaction
	 * @param $meta optional meta-data
	 */
	public function trackPurchaseTry($amount, $products = array(), $meta = '') {
		return $this->trackEvent(
			'purchaseTry',
			sprintf('amount:%f products:%s meta:%s', floatval($amount), urlencode(implode(',', $products)), urlencode($meta))
		);
	}

	/**
	 * This method is called to track an event "purchaseDone" with Boxalino Analytics.
	 *
	 * @param $status transaction status (TRUE = confirmed, FALSE = started)
	 * @param $amount total transaction amount
	 * @param $products product identifiers in the transaction
	 * @param $meta optional meta-data
	 */
	public function trackPurchase($status, $amount, $products = array(), $meta = '') {
		return $this->trackEvent(
			'purchaseDone',
			sprintf('status:%s amount:%f products:%s meta:%s', $status ? '1' : '0', floatval($amount), urlencode(implode(',', $products)), urlencode($meta))
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
		if (isset($_COOKIE['cemv'])) {
			$this->setCookie('cemv', $_COOKIE['cemv']);
		}

		// send request
		return ($this->postFields($this->url, $parameters) == 200);
	}
}

/**
 * @}
 */

?>