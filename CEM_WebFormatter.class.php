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
 * Data formatter for web-sites (CEM 1.4)
 *
 * @author nitro@boxalino.com
 */
class CEM_WebFormatter {
	/**
	 * Locale
	 */
	private $locale;

	/**
	 * Locale currency
	 */
	private $localeCurrency;

	/**
	 * Locale currency pattern
	 */
	private $localeCurrencyPattern;

	/**
	 * Date/time formatters (cache)
	 */
	private $_dateFormatters = array();

	/**
	 * Number formatters (cache)
	 */
	private $_numberFormatters = array();

	/**
	 * Price formatter (cache)
	 */
	private $_priceFormatter = NULL;

	/**
	 * Price code (cache)
	 */
	private $_priceCode = NULL;


	/**
	 * Constructor
	 *
	 * @param &$locale locale name
	 * @param &$localeCurrency locale currency code
	 * @param &$localeCurrencyPattern locale currency pattern
	 */
	public function __construct($locale, $localeCurrency, $localeCurrencyPattern) {
		$this->locale = $locale;
		$this->localeCurrency = $localeCurrency;
		$this->localeCurrencyPattern = $localeCurrencyPattern;
	}


	/**
	 * Format filter's label
	 *
	 * @param $property filter property
	 * @param $guidance filter descriptor
	 * @return filter's label
	 */
	public function formatFilterValue($property, $guidance) {
		switch ($property['dataType']) {
		case 'date':
			$dateFormat = IntlDateFormatter::SHORT;
			$timeFormat = IntlDateFormatter::SHORT;
			if ($guidance->data[0] != '*') {
				$from = $this->formatDateTime(self::parseDateISO8601($guidance->data[0]), $dateFormat, $timeFormat);
			} else {
				$from = NULL;
			}
			if ($guidance->data[1] != '*') {
				$to = $this->formatDateTime(self::parseDateISO8601($guidance->data[1]), $dateFormat, $timeFormat);
			} else {
				$to = NULL;
			}
			if ($from === NULL) {
				return "< $to";
			} else if ($to === NULL) {
				return ">= $from";
			}
			return "$from - $to";

		case 'number':
			if ($guidance->data[0] != '*') {
				if (stripos($property['id'], 'price') !== FALSE) {
					$from = $this->formatPrice($guidance->data[0]);
				} else if (round($guidance->data[0]) != $guidance->data[0]) {
					$from = $this->formatFloat($guidance->data[0]);
				} else {
					$from = $this->formatInteger($guidance->data[0]);
				}
			} else {
				$from = NULL;
			}
			if ($guidance->data[1] != '*') {
				if (stripos($property['id'], 'price') !== FALSE) {
					$to = $this->formatPrice($guidance->data[1]);
				} else if (round($guidance->data[1]) != $guidance->data[1]) {
					$to = $this->formatFloat($guidance->data[1]);
				} else {
					$to = $this->formatInteger($guidance->data[1]);
				}
			} else {
				$to = NULL;
			}
			if ($from === NULL) {
				return "< $to";
			} else if ($to === NULL) {
				return ">= $from";
			}
			return "$from - $to";
		}
		return end($guidance->data);
	}

	/**
	 * Format attribute value's label
	 *
	 * @param $attribute attribute descriptor
	 * @param $index value index
	 * @param $value value descriptor
	 * @return value's label
	 */
	public function formatAttributeValue($attribute, $index, $value) {
		switch ($attribute->type) {
		case 'dateRange':
			$dateFormat = IntlDateFormatter::SHORT;
			switch ($attribute->data[0]) {
			case 'year':
			case 'semester':
			case 'quarter':
			case 'month':
			case 'week':
			case 'day':
				$timeFormat = IntlDateFormatter::NONE;
				break;

			default:
				$timeFormat = IntlDateFormatter::SHORT;
				break;
			}
			if ($value->data[0] != '*') {
				$from = $this->formatDateTime(self::parseDateISO8601($value->data[0]), $dateFormat, $timeFormat);
			} else {
				$from = NULL;
			}
			if ($value->data[1] != '*') {
				$to = $this->formatDateTime(self::parseDateISO8601($value->data[1]), $dateFormat, $timeFormat);
			} else {
				$to = NULL;
			}
			if ($from === NULL || ($index == 0 && $to !== NULL)) {
				return "< $to";
			} else if ($to === NULL || ($index >= (sizeof($attribute->values) - 1) && $from !== NULL)) {
				return ">= $from";
			}
			return "$from - $to";

		case 'numberRange':
			$range = floatval($attribute->data[0]);
			if ($value->data[0] != '*') {
				if (stripos($attribute->property, 'price') !== FALSE) {
					$from = $this->formatPrice($value->data[0]);
				} else if (round($range) != $range) {
					$from = $this->formatFloat($value->data[0]);
				} else {
					$from = $this->formatInteger($value->data[0]);
				}
			} else {
				$from = NULL;
			}
			if ($value->data[1] != '*') {
				if (stripos($attribute->property, 'price') !== FALSE) {
					$to = $this->formatPrice($value->data[1]);
				} else if (round($range) != $range) {
					$to = $this->formatFloat($value->data[1]);
				} else {
					$to = $this->formatInteger($value->data[1]);
				}
			} else {
				$to = NULL;
			}
			if ($from === NULL || ($index == 0 && $to !== NULL)) {
				return "< $to";
			} else if ($to === NULL || ($index >= (sizeof($attribute->values) - 1) && $from !== NULL)) {
				return ">= $from";
			}
			return "$from - $to";
		}
		return implode(', ', $value->data);
	}


	/**
	 * Format url with parameters
	 *
	 * @param $uri base uri
	 * @param $parameters query parameters
	 * @param $hash url hash
	 * @return full uri
	 */
	public function formatUrl($uri, $parameters = array(), $hash = NULL) {
		if (sizeof($parameters) > 0) {
			$uri .= '?';
			$i = 0;
			foreach ($parameters as $key => $value) {
				if ($i++ > 0) {
					$uri .= '&';
				}
				if (is_string($key)) {
					if (is_array($value)) {
						foreach ($value as $index => $item) {
							if ($index > 0) {
								$uri .= '&';
							}
							$uri .= urlencode($key) . '[]=' . urlencode($item);
						}
					} else {
						$uri .= urlencode($key) . '=' . urlencode($value);
					}
				} else {
					$uri .= urlencode($value) . '=' . urlencode($this->requestString($value));
				}
			}
		}
		if (strlen($hash) > 0) {
			$uri .= $hash;
		}
		return $uri;
	}


	/**
	 * Format date value
	 *
	 * @param $value date in ISO8601 format
	 * @param $dateFormat date format
	 * @return formatted date
	 */
	public function formatDate($value, $dateFormat = IntlDateFormatter::LONG) {
		return $this->formatDateTime($value, $dateFormat, IntlDateFormatter::NONE);
	}

	/**
	 * Format date/time value
	 *
	 * @param $value date/time in ISO8601 format
	 * @param $dateFormat date format
	 * @param $timeFormat time format
	 * @return formatted date/time
	 */
	public function formatDateTime($value, $dateFormat = IntlDateFormatter::LONG, $timeFormat = IntlDateFormatter::SHORT) {
		if (!isset($this->_dateFormatters[$dateFormat]) ||
			!isset($this->_dateFormatters[$dateFormat][$timeFormat])) {
			$this->_dateFormatters[$dateFormat][$timeFormat] = IntlDateFormatter::create($this->locale, $dateFormat, $timeFormat);
		}
		return $this->_dateFormatters[$dateFormat][$timeFormat]->format(self::parseDateISO8601($value));
	}


	/**
	 * Format number value as integer
	 *
	 * @param $value number
	 * @return formatted number
	 */
	public function formatInteger($value) {
		return $this->formatNumber($value, NumberFormatter::DECIMAL, NumberFormatter::TYPE_INT64);
	}

	/**
	 * Format number value as float
	 *
	 * @param $value number
	 * @return formatted number
	 */
	public function formatFloat($value) {
		return $this->formatNumber($value, NumberFormatter::DECIMAL, NumberFormatter::TYPE_DOUBLE);
	}

	/**
	 * Format number value as percents
	 *
	 * @param $value number
	 * @return formatted number
	 */
	public function formatPercent($value) {
		return $this->formatNumber($value, NumberFormatter::PERCENT, NumberFormatter::TYPE_DOUBLE);
	}

	/**
	 * Format number value
	 *
	 * @param $value number
	 * @param $format number format
	 * @param $type number type
	 * @return formatted number
	 */
	public function formatNumber($value, $format = NumberFormatter::DECIMAL, $type = NumberFormatter::TYPE_DOUBLE) {
		if (!isset($this->_numberFormatters[$format])) {
			$this->_numberFormatters[$format] = NumberFormatter::create($this->locale, $format);
		}
		return $this->_numberFormatters[$format]->format($value, $type);
	}


	/**
	 * Format price value
	 *
	 * @param $value price
	 * @param $rounding round value
	 * @return formatted price
	 */
	public function formatPrice($value, $rounding = 0) {
		if ($this->_priceFormatter == NULL) {
			$this->_priceFormatter = NumberFormatter::create($this->locale, NumberFormatter::CURRENCY);
			if ($this->localeCurrency) {
				$this->_priceCode = $this->localeCurrency;
			} else {
				$this->_priceCode = $this->_priceFormatter->getTextAttribute(NumberFormatter::CURRENCY_CODE);
			}
			if ($this->localeCurrencyPattern) {
				$this->_priceFormatter->setPattern($this->localeCurrencyPattern);
			}
		}
		if ($rounding > 0) {
			$rounding = 1.0 / $rounding;
			$value = round($value * $rounding) / $rounding;
		}
		return $this->_priceFormatter->formatCurrency($value, $this->_priceCode);
	}


	/**
	 * Parse ISO8601 date format
	 *
	 * @param $value date string
	 * @return gmt timestamp
	 */
	public static function parseDateISO8601($value) {
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2}).(\d{3})([+\-])(\d{2})(\d{2})$/', $value, $matches)) {
			$delta = intval($matches[9]) * 60 * 60 + intval($matches[10]) * 60;
			if ($matches[8] == '-') {
				$delta = -$delta;
			}
			return (mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]) + $delta);
		}
		return NULL;
	}
}

/**
 * @}
 */

?>