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
class CEM_Analytics {
	/**
	 * @internal Tracker URL
	 */
	private $url;

	/**
	 * @internal Http client
	 */
	protected $client;


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
		$this->url = $url;
		$this->client = new CEM_HttpClient($username, $password, $connectionTimeout, $readTimeout);
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

		// forward cookie
		if (isset($_COOKIE['cemt'])) {
			$this->client->setCookie('cemt', $_COOKIE['cemt']);
		}

		// send request
		return ($this->client->postFields($this->url, $parameters) == 200);
	}

	/**
	 * This method is called to track an event "add-to-basket" with Boxalino Analytics.
	 *
	 * @param $product product identifier
	 */
	public function trackAddToBasket($product) {
		return $this->trackEvent(
			'addToBasket',
			sprintf('product:%s', $product)
		);
	}

	/**
	 * This method is called to track an event "purchase" with Boxalino Analytics.
	 *
	 * @param $status transaction status (TRUE = confirmed, FALSE = started)
	 * @param $amount total transaction amount
	 * @param $products amount of products in the transaction
	 */
	public function trackPurchase($status, $amount, $products) {
		return $this->trackEvent(
			'purchase',
			sprintf('status:%s amount:%f products:%d', $status ? '1' : '0', $amount, $products)
		);
	}
}

/**
 * @}
 */

?>