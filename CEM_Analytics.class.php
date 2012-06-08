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
	 * This method is called to track an event "client request" with Boxalino Analytics.
	 *
	 * @param $trackIdle optional track-idle flag
	 */
	public function trackClientRequest($trackIdle = FALSE) {
		return $this->trackEvent(
			'request',
			array(
				'idle' => $trackIdle ? '1' : '0'
			)
		);
	}

	/**
	 * This method is called to track an event "client idle" with Boxalino Analytics.
	 *
	 * @param $time idle time in seconds
	 */
	public function trackClientIdle($time) {
		return $this->trackEvent(
			'idle',
			array(
				'time' => intval($time)
			)
		);
	}


	/**
	 * This method is called to track an event "query" with Boxalino Analytics.
	 *
	 * @param $query query text
	 * @param $source optional source identifier
	 * @param $widget optional widget identifier
	 */
	public function trackQuery($query, $source = '', $widget = '') {
		return $this->trackEvent(
			'query',
			array(
				'query' => $query,
				'source' => $source,
				'widget' => $widget
			)
		);
	}

	/**
	 * This method is called to track an event "refineQuery" with Boxalino Analytics.
	 *
	 * @param $term term text
	 * @param $property selected property
	 * @param $value selected value
	 * @param $widget optional widget identifier
	 */
	public function trackRefineQuery($term, $property, $value, $widget = '') {
		return $this->trackEvent(
			'refineQuery',
			array(
				'term' => $term,
				'property' => $property,
				'value' => $value,
				'widget' => $widget
			)
		);
	}

	/**
	 * This method is called to track an event "setGuidance" with Boxalino Analytics.
	 *
	 * @param $property changed property
	 * @param $value changed value
	 * @param $widget optional widget identifier
	 */
	public function trackSetGuidance($property, $value, $widget = '') {
		return $this->trackEvent(
			'setGuidance',
			array(
				'property' => $property,
				'value' => $value,
				'widget' => $widget
			)
		);
	}

	/**
	 * This method is called to track an event "removeGuidance" with Boxalino Analytics.
	 *
	 * @param $property removed property
	 * @param $widget optional widget identifier
	 */
	public function trackRemoveGuidance($property, $widget = '') {
		return $this->trackEvent(
			'removeGuidance',
			array(
				'property' => $property,
				'widget' => $widget
			)
		);
	}

	/**
	 * This method is called to track an event "page" with Boxalino Analytics.
	 *
	 * @param $page page index
	 * @param $widget optional widget identifier
	 */
	public function trackPage($page, $widget = '') {
		return $this->trackEvent(
			'page',
			array(
				'page' => intval($page),
				'widget' => $widget
			)
		);
	}

	/**
	 * This method is called to track an event "recommendationView" with Boxalino Analytics.
	 *
	 * @param $products product identifiers (productId => strategy)
	 * @param $widget optional widget identifier
	 */
	public function trackRecommendationView($products, $widget = '') {
		$payload = array();
		foreach ($products as $productId => $strategy) {
			$payload[] = urlencode($productId).'='.urlencode($strategy);
		}
		return $this->trackEvent(
			'recommendationView',
			array(
				'products' => implode(',', $payload),
				'widget' => $widget
			)
		);
	}

	/**
	 * This method is called to track an event "recommendationClick" with Boxalino Analytics.
	 *
	 * @param $product product identifier
	 * @param $position product position
	 * @param $strategy recommendation strategy
	 * @param $widget optional widget identifier
	 */
	public function trackRecommendationClick($product, $position, $strategy, $widget = '') {
		return $this->trackEvent(
			'recommendationClick',
			array(
				'product' => $product,
				'position' => $position,
				'strategy' => $strategy,
				'widget' => $widget
			)
		);
	}

	/**
	 * This method is called to track an event "categoryView" with Boxalino Analytics.
	 *
	 * @param $id category identifier
	 * @param $name optional category name
	 * @param $widget optional widget identifier
	 */
	public function trackCategoryView($id, $name = '', $widget = '') {
		return $this->trackEvent(
			'categoryView',
			array(
				'id' => $id,
				'name' => $name,
				'widget' => $widget
			)
		);
	}

	/**
	 * This method is called to track an event "productView" with Boxalino Analytics.
	 *
	 * @param $id product identifier
	 * @param $name optional product name
	 * @param $widget optional widget identifier
	 */
	public function trackProductView($id, $name = '', $widget = '') {
		return $this->trackEvent(
			'productView',
			array(
				'id' => $id,
				'name' => $name,
				'widget' => $widget
			)
		);
	}

	/**
	 * This method is called to track an event "addToBasket" with Boxalino Analytics.
	 *
	 * @param $item product info {id:..., name:..., quantity:..., price:...}
	 * @param $widget optional widget identifier
	 */
	public function trackAddToBasket($item, $widget = '') {
		return $this->trackEvent(
			'addToBasket',
			array(
				'item' => @json_encode($item),
				'widget' => $widget
			)
		);
	}

	/**
	 * This method is called to track an event "purchaseTry" with Boxalino Analytics.
	 *
	 * @param $amount total transaction amount
	 * @param $items products in the transaction ([ {id:..., name:..., quantity:..., price:..., widget:...} ])
	 */
	public function trackPurchaseTry($amount, $items = array()) {
		return $this->trackEvent(
			'purchaseTry',
			array(
				'amount' => floatval($amount),
				'items' => @json_encode($items)
			)
		);
	}

	/**
	 * This method is called to track an event "purchaseDone" with Boxalino Analytics.
	 *
	 * @param $status transaction status (TRUE = confirmed, FALSE = started)
	 * @param $amount total transaction amount
	 * @param $items products in the transaction ([ {id:..., name:..., quantity:..., price:..., widget:...} ])
	 */
	public function trackPurchase($status, $amount, $items = array()) {
		return $this->trackEvent(
			'purchaseDone',
			array(
				'status' => $status ? '1' : '0',
				'amount' => floatval($amount),
				'items' => @json_encode($items)
			)
		);
	}


	/**
	 * Track an event with Boxalino Analytics.
	 *
	 * @param $name event name
	 * @param $description event parameters or description (optional)
	 */
	public function trackEvent($name, $description = array()) {
		if (is_array($description)) {
			$parameters = array();
			foreach ($description as $k => $v) {
				if (strlen($v) > 0) {
					$parameters[] = urlencode($k).':'.urlencode($v);
				}
			}
			$description = implode(' ', $parameters);
		}

		// forward cookie(s)
		foreach ($_COOKIE as $key => $value) {
			if (strpos($key, 'cem') === 0) {
				$this->setCookie($key, $value);
			}
		}

		// send request
		$parameters = $this->getParameters();
		$parameters['eventName'] = $name;
		$parameters['eventDescription'] = $description;
		return ($this->postFields($this->url, $parameters) == 200);
	}


	/**
	 * Detect and return client infos and context
	 *
	 * @return analytics parameters
	 */
	public static function getParameters() {
		// extract analytics parameters
		$parameters = array(
			'connection' => 'http',
			'clientAddress' => '',
			'clientAgent' => '',
			'clientReferer' => '',
			'serverAddress' => '',
			'serverHost' => '',
			'serverUri' => ''
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
		if (isset($_SERVER['REQUEST_URI'])) {
			$parameters['serverUri'] = $_SERVER['REQUEST_URI'];
		}
		return $parameters;
	}
}

/**
 * @}
 */

?>