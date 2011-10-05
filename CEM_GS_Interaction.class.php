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
 * GS interaction for web-sites (CEM 1.4)
 *
 * @author nitro@boxalino.com
 */
class CEM_GS_Interaction extends CEM_AbstractWebHandler {
	/**
	 * Current request
	 */
	protected $request;

	/**
	 * Current response
	 */
	protected $response;

	/**
	 * User-defined options
	 */
	protected $options;

	/**
	 * Value formatter
	 */
	protected $formatter;

	/**
	 * Json decoded contexts (cache)
	 */
	private $_jsonContexts = array();

	/**
	 * Sequential context (cache)
	 */
	private $_sequentialContexts = NULL;

	/**
	 * Current ambiguities (cache)
	 */
	private $_ambiguities = NULL;

	/**
	 * Current filters (cache)
	 */
	private $_filters = NULL;

	/**
	 * Current properties (cache)
	 */
	private $_properties = array();

	/**
	 * Current refinements (cache)
	 */
	private $_refinements = array();

	/**
	 * Current alternatives (cache)
	 */
	private $_alternatives = array();

	/**
	 * Current scenarios (cache)
	 */
	private $_scenarios = array();

	/**
	 * Current results (cache)
	 */
	private $_results = array();

	/**
	 * Current recommendations (cache)
	 */
	private $_recommendations = array();


	/**
	 * Constructor
	 *
	 * @param &$crypto encryption facility
	 * @param &$request client request reference
	 * @param &$response client response reference
	 * @param &$options user-defined options
	 * @param &$formatter value formatter
	 */
	public function __construct(&$crypto, &$request, &$response, &$options, &$formatter) {
		parent::__construct($crypto);
		$this->request = $request;
		$this->response = $response;
		$this->options = $options;
		$this->formatter = $formatter;
	}


	/**
	 * Get underlying request
	 *
	 * @return underlying request
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * Get underlying response
	 *
	 * @return underlying response
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * Get user-defined options
	 *
	 * @return user-defined options
	 */
	public function getOptions() {
		return $this->options;
	}

	/**
	 * Get value formatter
	 *
	 * @return value formatter
	 */
	public function getFormatter() {
		return $this->formatter;
	}


	/**
	 * Get server version
	 *
	 * @return server version
	 */
	public function getVersion() {
		return $this->response->getVersion();
	}

	/**
	 * Get status
	 *
	 * @return status
	 */
	public function getStatus() {
		return $this->response->getStatus();
	}

	/**
	 * Get response message
	 *
	 * @return response message
	 */
	public function getMessage() {
		return $this->response->getMessage();
	}

	/**
	 * Get remote time
	 *
	 * @return remote time (in seconds)
	 */
	public function getTime() {
		return $this->response->getTime();
	}

	/**
	 * Get processing time
	 *
	 * @return processing time (in seconds)
	 */
	public function getTotalTime() {
		return $this->response->getTotalTime();
	}


	/**
	 * Get context scopes
	 *
	 * @return context scopes
	 */
	public function getContext() {
		return $this->response->getContext();
	}

	/**
	 * Get context data
	 *
	 * @param $name context name
	 * @return context data
	 */
	public function getContextData($name) {
		$scopes = $this->getContext();
		if (isset($scopes[$name])) {
			return $scopes[$name]['data'];
		}
		return '';
	}

	/**
	 * Get context data from json
	 *
	 * @param $name context name
	 * @return context data (decoded)
	 */
	public function getContextJson($name) {
		if (!isset($this->_jsonContexts[$name])) {
			$this->_jsonContexts[$name] = @json_decode($this->getContextData($name));
		}
		return $this->_jsonContexts[$name];
	}


	/**
	 * Encode sequential contexts
	 *
	 * @param $contexts optional custom contexts
	 * @return encoded sequential contexts
	 */
	public function encodeSequentialContexts($contexts = NULL) {
		if ($this->_sequentialContexts == NULL) {
			$data = '';
			if ($contexts == NULL) {
				$contexts = $this->response->getContext();
			}
			foreach ($contexts as $name => $scope) {
				if ($scope['mode'] == 'sequential') {
					switch ($scope['level']) {
					case 'visitor':
					case 'session':
					case 'search':
						if (strlen($data) > 0) {
							$data .= ';';
						}
						$data .= $this->escapeValue($name) . '=' . $this->escapeValue($scope['level']) . '=' . $this->escapeValue($scope['data']);
						break;
					}
				}
			}
			$this->_sequentialContexts = $this->encrypt($data);
		}
		return $this->_sequentialContexts;
	}

	/**
	 * Encode parameters and state into url
	 *
	 * @param $parameters additional query parameters
	 * @return encoded url query
	 */
	public function encodeQuery($parameters = array()) {
		$context = $this->encodeSequentialContexts();
		if ($context) {
			$parameters['context'] = $context;
		}
		return $this->formatter->formatUrl('', $parameters);
	}


	/**
	 * Get a pseudo-unique host identifier
	 *
	 * @return pseudo-unique host identifier
	 */
	public function getHostIdentifier() {
		return sprintf('%u', crc32($_SERVER['REMOTE_ADDR']));
	}

	/**
	 * Get a pseudo-unique visitor identifier
	 *
	 * @return pseudo-unique visitor identifier
	 */
	public function getVisitorIdentifier() {
		$profile = $this->getContextJson('profile');
		if (isset($profile->id)) {
			return $profile->id;
		}
		return '-';
	}

	/**
	 * Get visitor age
	 *
	 * @return visitor age
	 */
	public function getVisitorAge() {
		$profile = $this->getContextJson('profile');
		if (isset($profile->age)) {
			return $profile->age;
		}
		return 0;
	}


	/**
	 * Check if group exists
	 *
	 * @param $id group identifier
	 * @return TRUE if group exists or FALSE if none
	 */
	public function hasGroup($id) {
		$scopes = $this->response->getResponses();
		return isset($scopes[$id]);
	}

	/**
	 * Get group
	 *
	 * @param $id group identifier
	 * @return group or NULL if none
	 */
	public function getGroup($id = 'search') {
		$scopes = $this->response->getResponses();
		return (isset($scopes[$id]) ? $scopes[$id] : NULL);
	}

	/**
	 * Get groups
	 *
	 * @return group scopes
	 */
	public function getGroups() {
		return $this->response->getResponses();
	}


	/**
	 * Get current search query
	 *
	 * @return search query
	 */
	public function activeQuery() {
		$model = $this->getContextJson('model');
		if (isset($model->queryText)) {
			return $model->queryText;
		}
		return '';
	}

	/**
	 * Get current "showing results for"
	 *
	 * @return "showing results for" query or FALSE if none
	 */
	public function activeShowingResultsFor() {
		$model = $this->getContextJson('model');
		if (isset($model->queryTerms)) {
			$has = FALSE;
			$terms = array();
			foreach ($model->queryTerms as $index => $queryTerm) {
				if ($queryTerm->type == 'matched' && isset($queryTerm->guidances)) {
					if (sizeof($queryTerm->guidances) == 1) {
						$guidance = $queryTerm->guidances[0];
						if (levenshtein(strtolower($guidance->data[0]), strtolower($queryTerm->value)) > 0) {
							$terms[] = strtolower($guidance->data[0]);
							$has = TRUE;
							continue;
						}
					}
				}
				$terms[] = $queryTerm->value;
			}
			if ($has) {
				return implode(' ', $terms);
			}
		}
		return FALSE;
	}

	/**
	 * Get current "did you mean"
	 *
	 * @return "did you mean" queries
	 */
	public function activeDidYouMean() {
		$model = $this->getContextJson('model');
		if (!isset($model->queryTerms)) {
			return array();
		}
		$list = array();
		$prefix = '';
		foreach ($model->queryTerms as $index => $queryTerm) {
			if ($queryTerm->type != 'ambiguous' || $queryTerm->termExist || !isset($queryTerm->refinements)) {
				$prefix .= ' '.$queryTerm->value;
				continue;
			}
			foreach ($queryTerm->refinements as $refinement) {
				foreach ($refinement->values as $value) {
					$value = trim(strtolower($prefix.' '.$value->value));
					$distance = levenshtein($value, strtolower($queryTerm->value));
					if (!isset($list[$value]) && $distance > 0) {
						$urlParameters = array(
							'query' => $value
						);
						$list[$value] = array(
							'query' => $value,
							'queryAction' => array(
								'url' => $this->formatter->formatUrl('', $urlParameters),
								'parameters' => $urlParameters
							),
							'distance' => $distance
						);
					}
				}
			}
			$prefix .= ' '.$queryTerm->value;
		}
		uasort($list, array($this, 'sortByDistance'));
		return $list;
	}

	/**
	 * Get current ambiguities
	 *
	 * @return ambiguities
	 */
	public function activeAmbiguities() {
		if ($this->_ambiguities != NULL) {
			return $this->_ambiguities;
		}

		$this->_ambiguities = array();
		$model = $this->getContextJson('model');
		if (isset($model->queryTerms)) {
			foreach ($model->queryTerms as $index => $queryTerm) {
				if ($queryTerm->type != 'ambiguous' || !isset($queryTerm->refinements)) {
					continue;
				}
				$ambiguity = $this->findDisambiguation($index, $queryTerm->value, $queryTerm->refinements);
				if ($ambiguity) {
					$this->_ambiguities[] = $ambiguity;
				}
			}
		}
		return $this->_ambiguities;
	}

	/**
	 * Get current filters
	 *
	 * @param $groupId group identifier
	 * @return filters
	 */
	public function activeFilters($groupId = 'search') {
		if (isset($this->_filters[$groupId])) {
			return $this->_filters[$groupId];
		}

		$this->_filters[$groupId] = array();
		$model = $this->getContextJson('model');
		$group = $this->getGroup($groupId);
		if (isset($model->queryTerms)) {
			foreach ($model->queryTerms as $index => $queryTerm) {
				if ($queryTerm->type == 'ambiguous' && isset($queryTerm->refinements)) {
					if (sizeof($queryTerm->refinements) != 1) {
						continue;
					}
					$refinement = $queryTerm->refinements[0];
					if (sizeof($refinement->values) != 1) {
						continue;
					}
					$guidance = json_decode(
						json_encode(
							array(
								'mode' => 'guidance',
								'type' => 'text',
								'property' => $refinement->property,
								'data' => array($refinement->values[0]->value)
							)
						)
					);
					if (levenshtein(strtolower($refinement->values[0]->value), strtolower($queryTerm->value)) > 2) {
						$this->_filters[$groupId][$refinement->property][] = array(
							'mode' => 'term',
							'index' => $index,
							'property' => $guidance->property,
							'guidance' => $guidance
						);
					} else {
						$this->_filters[$groupId][$refinement->property][] = array(
							'mode' => 'guidance',
							'index' => -1,
							'property' => $guidance->property,
							'guidance' => $guidance
						);
					}
				} else if (($queryTerm->type == 'matched' || $queryTerm->type == 'refined') && isset($queryTerm->guidances)) {
					foreach ($queryTerm->guidances as $guidance) {
						if (!isset($this->_filters[$guidance->property])) {
							$this->_filters[$guidance->property] = array();
						}
						$this->_filters[$groupId][$guidance->property][] = array(
							'mode' => 'term',
							'index' => $index,
							'property' => $guidance->property,
							'guidance' => $guidance
						);
					}
				}
			}
		}
		if (isset($model->guidances)) {
			foreach ($model->guidances as $index => $guidance) {
				if (!isset($this->_filters[$guidance->property])) {
					$this->_filters[$guidance->property] = array();
				}
				$this->_filters[$groupId][$guidance->property][] = array(
					'mode' => 'guidance',
					'index' => $index,
					'property' => $guidance->property,
					'guidance' => $guidance
				);
			}
		}
		foreach ($this->_filters[$groupId] as $propertyId => $filters) {
			$property = $this->getProperty($propertyId, $groupId);
			if (!$property) {
				unset($this->_filters[$groupId][$propertyId]);
				continue;
			}
			foreach ($filters as $index => $filter) {
				$this->_filters[$groupId][$propertyId][$index]['name'] = $property['name'];
				$this->_filters[$groupId][$propertyId][$index]['value'] = $this->formatter->formatFilterValue($property, $filter['guidance']);

				$urlParameters = array(
					'context' => $this->encodeSequentialContexts()
				);
				if ($propertyId == 'categories') {
					$urlParameters['guidance'] = '-'.$propertyId;
				} else if ($filter['mode'] == 'term' && $filter['index'] >= 0) {
					$urlParameters['refine'] = $filter['index'];
				} else if ($filter['mode'] == 'guidance' && $filter['index'] >= 0) {
					$urlParameters['guidance'] = $filter['index'];
				} else {
					$urlParameters['guidance'] = '-'.$propertyId;
				}
				$this->_filters[$groupId][$propertyId][$index]['removeAction'] = array(
					'url' => $this->formatter->formatUrl('', $urlParameters),
					'parameters' => $urlParameters
				);

				$this->_filters[$groupId][$propertyId][$index]['alternative'] = $this->getAlternative($propertyId, $groupId);
			}
		}
		return $this->_filters[$groupId];
	}

	/**
	 * Get current page size
	 *
	 * @return page size
	 */
	public function activePageSize() {
		$model = $this->getContextJson('model');
		return (isset($model->pageSize) ? $model->pageSize : 0);
	}

	/** 
	 * Get current ranking
	 *
	 * @return ranking
	 */
	public function activeRanking() {
		$model = $this->getContextJson('model');
		return (isset($model->ranking) ? $model->ranking : '@score desc');
	}


	/**
	 * Check if current query is filtering results
	 *
	 * @return TRUE if query filters results
	 */
	public function isQueryFiltering() {
		if (!$this->requestExists('query')) {
			return TRUE;
		}
		$model = $this->getContextJson('model');
		if (!isset($model->queryTerms) || sizeof($model->queryTerms) == 0) {
			return TRUE;
		}
		foreach ($model->queryTerms as $term) {
			if ($term->type == 'unfiltered' || $term->type == 'unmatched') {
				continue;
			}
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Check if current context is filtering results
	 *
	 * @return TRUE if context filters results
	 */
	public function isFiltering() {
		$model = $this->getContextJson('model');
		if (isset($model->queryTerms)) {
			foreach ($model->queryTerms as $term) {
				if ($term->type == 'unfiltered' || $term->type == 'unmatched') {
					continue;
				}
				return TRUE;
			}
		}
		if (isset($model->guidances) && sizeof($model->guidances) > 0) {
			return TRUE;
		}
		return FALSE;
	}


	/**
	 * Get current properties
	 *
	 * @param $groupId group identifier
	 * @return properties
	 */
	public function getProperties($groupId = 'search') {
		if (isset($this->_properties[$groupId])) {
			return $this->_properties[$groupId];
		}

		$this->_properties[$groupId] = array();
		$group = $this->getGroup($groupId);
		if (isset($group->properties)) {
			foreach ($group->properties as $property) {
				$label = $property->id;
				foreach ($property->names as $name) {
					if ($name->language == $this->getResponse->getLanguage()) {
						$label = $name->value;
						break;
					}
				}
				$this->_properties[$groupId][$property->id] = array(
					'id' => $property->id,
					'name' => $label,
					'dataType' => $property->dataType,
					'type' => $property->type,
					'property' => $property
				);
			}
		} else if (isset($group->search->properties)) {
			foreach ($group->search->properties as $property) {
				$label = $property->id;
				foreach ($property->names as $name) {
					if ($name->language == $this->getResponse()->getLanguage()) {
						$label = $name->value;
						break;
					}
				}
				$this->_properties[$groupId][$property->id] = array(
					'id' => $property->id,
					'name' => $label,
					'dataType' => $property->dataType,
					'type' => $property->type,
					'property' => $property
				);
			}
		}
		return $this->_properties[$groupId];
	}

	/**
	 * Get property
	 *
	 * @param $property property identifier
	 * @param $groupId group identifier
	 * @return property or NULL if none
	 */
	public function getProperty($property, $groupId = 'search') {
		$properties = $this->getProperties($groupId);
		return (isset($properties[$property]) ? $properties[$property] : NULL);
	}

	/**
	 * Get current refinements
	 *
	 * @param $groupId group identifier
	 * @return refinements
	 */
	public function getRefinements($groupId = 'search') {
		if (isset($this->_refinements[$groupId])) {
			return $this->_refinements[$groupId];
		}

		$this->_refinements[$groupId] = array();
		$group = $this->getGroup($groupId);
		$attributes = NULL;
		if (isset($group->refinements)) {
			$attributes = $group->refinements;
		} else if (isset($group->search->attributes)) {
			$attributes = $group->search->attributes;
		}
		if ($attributes) {
			foreach ($attributes as $attribute) {
				$refinement = $this->findAttributeRefinement($attribute);
				if ($refinement) {
					$this->_refinements[$groupId][$attribute->property] = $refinement;
				}
			}
		}
		return $this->_refinements[$groupId];
	}

	/**
	 * Get refinement
	 *
	 * @param $property refinement property
	 * @param $groupId group identifier
	 * @return refinement or NULL if none
	 */
	public function getRefinement($property, $groupId = 'search') {
		$refinements = $this->getRefinements($groupId);
		return (isset($refinements[$property]) ? $refinements[$property] : NULL);
	}

	/**
	 * Get current alternatives
	 *
	 * @param $groupId group identifier
	 * @return alternatives
	 */
	public function getAlternatives($groupId = 'search') {
		if (isset($this->_alternatives[$groupId])) {
			return $this->_alternatives[$groupId];
		}

		$this->_alternatives[$groupId] = array();
		$group = $this->getGroup($groupId);
		$attributes = NULL;
		if (isset($group->alternatives)) {
			$attributes = $group->alternatives;
		} else if (isset($group->search->alternatives)) {
			$attributes = $group->search->alternatives;
		}
		if ($attributes) {
			foreach ($attributes as $attribute) {
				$alternative = $this->findAttributeAlternative($attribute);
				if ($alternative) {
					$this->_alternatives[$groupId][$attribute->property] = $alternative;
				}
			}
		}
		return $this->_alternatives[$groupId];
	}

	/**
	 * Get alternative
	 *
	 * @param $property alternative property
	 * @param $groupId group identifier
	 * @return alternative or NULL if none
	 */
	public function getAlternative($property, $groupId = 'search') {
		$alternatives = $this->getAlternatives($groupId);
		return (isset($alternatives[$property]) ? $alternatives[$property] : NULL);
	}

	/**
	 * Get current scenarios
	 *
	 * @param $groupId group identifier
	 * @return scenarios
	 */
	public function getScenarios($groupId = 'search') {
		if (isset($this->_scenarios[$groupId])) {
			return $this->_scenarios[$groupId];
		}

		$this->_scenarios[$groupId] = array();
		$group = $this->getGroup($groupId);
		if (isset($group->scenarios)) {
			$total = NULL;
			if (isset($group->search->total)) {
				$total = $group->search->total;
			}
			foreach ($group->scenarios as $scenario) {
				$refinements = array();
				$skip = $this->requestStringArray('skip');
				foreach ($scenario->attributes as $attribute) {
					if (in_array($attribute->property, $skip)) {
						continue;
					}
					$refinement = $this->getRefinement($attribute->property, $groupId);
					if (!$refinement) {
						continue;
					}
					$values = array();
					$usefulValues = 0;
					foreach ($refinement['values'] as $value) {
						if ($total !== NULL && $value['population'] >= $total) {
							continue;
						}
						if (sizeof($skip) > 0) {
							$value['addAction']['parameters']['skip'] = $skip;
							$value['addAction']['url'] = $this->formatter->formatUrl('', $value['addAction']['parameters']);
							$value['setAction']['parameters']['skip'] = $skip;
							$value['setAction']['url'] = $this->formatter->formatUrl('', $value['setAction']['parameters']);
						}
						$values[] = $value;
						if ($total !== NULL && isset($attribute->minimumValuePopulation) && $value['population'] < ($attribute->minimumValuePopulation * $total)) {
							continue;
						}
						if ($total !== NULL && isset($attribute->maximumValuePopulation) && $value['population'] > ($attribute->maximumValuePopulation * $total)) {
							continue;
						}
						$usefulValues++;
					}
					// HACK: flaschenpost
					if ($usefulValues > 1 || ($usefulValues > 0 && $attribute->property == 'awarded')) {
						$refinements[] = array(
							'prompt' => $attribute->prompt,
							'offset' => $attribute->offset,
							'property' => $attribute->property,
							'label' => $refinement['label'],
							'parents' => $refinement['parents'],
							'values' => $values,
							'attribute' => $refinement['attribute']
						);
						$skip[] = $attribute->property;
					}
				}
				$recommendations = array();
				foreach ($scenario->recommendations as $resource) {
					$recommendations[] = $this->buildResource($resource);
				}
				$this->_scenarios[$groupId][$scenario->id] = array(
					'id' => $scenario->id,
					'name' => $scenario->name,
					'refinements' => $refinements,
					'recommendations' => $recommendations,
					'scenario' => $scenario
				);
			}
		}
		return $this->_scenarios[$groupId];
	}

	/**
	 * Get current results
	 *
	 * @param $groupId group identifier
	 * @return results
	 */
	public function getResults($groupId = 'search') {
		if (isset($this->_results[$groupId])) {
			return $this->_results[$groupId];
		}

		$this->_results[$groupId] = array();
		$group = $this->getGroup($groupId);
		if (isset($group->search->results)) {
			foreach ($group->search->results as $index => $result) {
				if (!isset($result->views) || sizeof($result->views) == 0) {
					continue;
				}
				$resource = $this->buildResource($result->views[0]);
				$resource['score'] = $result->score;
				$resource['offset'] = $group->search->offset + $index;
				$this->_results[$groupId][] = $resource;
			}
		} else if (isset($group->sources)) {
			foreach ($group->sources as $resource) {
				$this->_results[$groupId][] = $this->buildResource($resource);
			}
		}
		return $this->_results[$groupId];
	}

	/**
	 * Get current result offset
	 *
	 * @param $groupId group identifier
	 * @return result offset
	 */
	public function getResultsOffset($groupId = 'search') {
		$group = $this->getGroup($groupId);
		return (isset($group->search->offset) ? $group->search->offset : 0);
	}

	/**
	 * Get current result total
	 *
	 * @param $groupId group identifier
	 * @return result total
	 */
	public function getResultsTotal($groupId = 'search') {
		$group = $this->getGroup($groupId);
		return (isset($group->search->total) ? $group->search->total : 0);
	}

	/**
	 * Get current recommendations
	 *
	 * @param $groupId group identifier
	 * @return recommendations
	 */
	public function getRecommendations($groupId = 'search') {
		if (isset($this->_recommendations[$groupId])) {
			return $this->_recommendations[$groupId];
		}

		$this->_recommendations[$groupId] = array();
		foreach ($this->getScenarios($groupId) as $scenario) {
			$this->_recommendations[$groupId] = array_merge($this->_recommendations[$groupId], $scenario['recommendations']);
		}
		usort($this->_recommendations[$groupId], array($this, 'sortByWeight'));
		return $this->_recommendations[$groupId];
	}


	/**
	 * Build resource descriptor
	 *
	 * @param $resource resource
	 * @return resource descriptor
	 */
	public function buildResource($resource) {
		$data = array(
			'id' => $resource->id,
			'type' => $resource->type,
			'language' => $resource->language,
			'name' => $resource->name,
			'properties' => array(),
			'weight' => isset($resource->weight) ? $resource->weight : 0,
			'resource' => $resource
		);
		$properties = array();
		foreach ($resource->properties as $value) {
			$list = $this->collapsePropertyValue($value);
			if (!isset($data[$value->property])) {
				$data[$value->property] = sizeof($list) > 1 ? $list : $list[0];
			}
			$data['properties'][$value->property][] = sizeof($list) > 1 ? $list : $list[0];
		}
		return $data;
	}

	/**
	 * Build disambiguation
	 *
	 * @param $disambiguation disambiguation
	 * @param $excludedPreviews list of excluded preview id
	 * @return disambiguation or NULL if none
	 */
	public function buildDisambiguation($disambiguation, $excludedPreviews = array()) {
		return $this->findDisambiguation(
			$disambiguation['index'],
			$disambiguation['term'],
			$disambiguation['refinements'],
			$excludedPreviews
		);
	}

	/**
	 * Build refinement
	 *
	 * @param $refinement refinement
	 * @param $excludedPreviews list of excluded preview id
	 * @return refinement or NULL if none
	 */
	public function buildRefinement($refinement, $excludedPreviews = array()) {
		return $this->findAttributeRefinement($refinement['attribute'], $excludedPreviews);
	}

	/**
	 * Build alternative
	 *
	 * @param $alternative alternative
	 * @param $excludedPreviews list of excluded preview id
	 * @return alternative or NULL if none
	 */
	public function buildAlternative($alternative, $excludedPreviews = array()) {
		return $this->findAttributeAlternative($alternative['attribute'], $excludedPreviews);
	}


	/**
	 * Compute user's preferred properties
	 *
	 * @param $index search index
	 * @return user's preferred properties
	 */
	public function getPreferredProperties($index = 'default') {
		$list = array();
		$profile = $this->getContextJson('profile');
		if (isset($profile->preferences)) {
			foreach ($profile->preferences as $preferences) {
				if ($preferences->index != $index || !isset($preferences->properties)) {
					continue;
				}
				$sum = 0;
				foreach ($preferences->properties as $property) {
					$list[$property->value] = $property->weight;
					$sum += $property->weight;
				}
				if ($sum > 0) {
					foreach ($list as $key => $value) {
						$list[$key] /= $sum;
					}
				}
			}
		}
		arsort($list);
		return $list;
	}

	/**
	 * Compute user's preferred property values
	 *
	 * @param $index search index
	 * @return user's preferred property values
	 */
	public function getPreferredPropertyValues($index = 'default') {
		$list = array();
		$profile = $this->getContextJson('profile');
		if (isset($profile->preferences)) {
			foreach ($profile->preferences as $preferences) {
				if ($preferences->index != $index || !isset($preferences->propertyValues)) {
					continue;
				}
				foreach ($preferences->propertyValues as $property) {
					$sum = 0;
					$list[$property->property] = array();
					foreach ($property->values as $value) {
						if (!isset($list[$property->property][$value->data])) {
							$list[$property->property][$value->data] = array('offset' => 0, 'weight' => 0);
						}
						$list[$property->property][$value->data]['weight'] += $value->weight;
						$sum += $value->weight;
					}
					if ($sum > 0) {
						foreach ($list[$property->property] as $key => $value) {
							$list[$property->property][$key]['weight'] /= $sum;
						}
					}
					uasort($list[$property->property], array($this, 'sortByWeight'));

					$offset = 1;
					foreach ($list[$property->property] as $key => $value) {
						$list[$property->property][$key]['offset'] = $offset++;
					}
				}
			}
		}
		return $list;
	}


	/**
	 * Print cem debug informations.
	 *
	 */
	public function printDebug() {
		$info = array(
			'version' => $this->response->getVersion(),
			'status'  => $this->response->getStatus(),
			'time'    => $this->response->getTime(),
			'message' => $this->response->getMessage()
		);
		echo('<div id="cem-debug" class="cem-debug-block cem-debug-spacer"><h1>Debug: info (');
		printf("%.02f [kb] in %.02f [s]", $this->response->getResponseSize() / 1024, $this->response->getTotalTime());
		echo(')</h1><div class="json-visible">');
		CEM_WebFormatter::printJsonObject($info);
		echo('</div></div>');
		foreach ($this->request->getRequests() as $index => $request) {
			if (isset($request['variables'])) {
				$variables = json_decode(json_encode($request['variables']));
			} else {
				$variables = array();
			}
			CEM_WebFormatter::printJsonBlock('request.'.$request['type'].(isset($request['action']) ? '['.$request['action'].']' : ''), $variables);
		}
		foreach ($this->getGroups() as $id => $scope) {
			CEM_WebFormatter::printJsonBlock('response.'.$id, $scope);
		}
		foreach ($this->getContext() as $name => $scope) {
			CEM_WebFormatter::printJsonScope($name, $scope);
		}
	}


	/**
	 * Find disambiguation
	 *
	 * @param $index term index
	 * @param $term term
	 * @param $refinements refinements
	 * @param $excludedPreviews list of excluded preview id
	 * @return refinements or NULL if none
	 */
	protected function findDisambiguation($index, $term, $refinements, $excludedPreviews = array()) {
		$count = 0;
		$entries = array();
		$previews = array();
		foreach ($refinements as $refinement) {
			$property = $this->getProperty($refinement->property);
			if (!$property) {
				continue;
			}

			$entries[$refinement->property] = array(
				'label' => $property['name'],
				'values' => array()
			);
			foreach ($refinement->values as $value) {
				// select preview
				$preview = NULL;
				$resources = array();
				if (isset($value->previews)) {
					foreach ($value->previews as $resource) {
						if (in_array($resource->id, $excludedPreviews)) {
							continue;
						}
						$resource = $this->buildResource($resource);
						$resources[] = $resource;
						if (!$preview && !in_array($resource['id'], $previews)) {
							$previews[] = $resource['id'];
							$preview = $resource;
						}
					}
				}
				$urlParameters = array(
					'context' => $this->encodeSequentialContexts(),
					'refine' => $index,
					'property' => $refinement->property,
					'value' => $value->value
				);
				$entries[$refinement->property]['values'][] = array(
					'name' => $value->value,
					'population' => $value->population,
					'refineAction' => array(
						'url' => $this->formatter->formatUrl('', $urlParameters),
						'parameters' => $urlParameters
					),
					'preview' => $preview,
					'resources' => $resources
				);
				$count++;
			}
		}
		if ($count > 1) {
			return array(
				'index' => $index,
				'term' => $term,
				'entries' => $entries,
				'refinements' => $refinements
			);
		}
		return NULL;
	}


	/**
	 * Find attribute refinement
	 *
	 * @param $attribute attribute descriptor
	 * @param $excludedPreviews list of excluded preview id
	 * @return attribute refinement or NULL if none
	 */
	protected function findAttributeRefinement($attribute, $excludedPreviews = array()) {
		$guidanceFilters = $this->activeFilters();
		$preferences = $this->getPreferredPropertyValues();
		return $this->findAttributeRefinementValues(
			$attribute,
			$attribute->values,
			isset($guidanceFilters[$attribute->property]) ? $guidanceFilters[$attribute->property] : array(),
			$excludedPreviews,
			isset($preferences[$attribute->property]) ? $preferences[$attribute->property] : array(),
			0,
			array()
		);
	}

	/**
	 * Find attribute refinement
	 *
	 * @param $attribute attribute descriptor
	 * @param $values current node's values
	 * @param $filters active filters
	 * @param $excludedPreviews list of excluded preview id
	 * @param $preferences property preferences
	 * @param $depth current node's depth
	 * @param $parents current node's parents
	 * @return attribute refinement or NULL if none
	 */
	protected function findAttributeRefinementValues($attribute, $values, $filters, $excludedPreviews, $preferences, $depth, $parents) {
		// skip node if hierarchical parent
		if ($attribute->hierarchical && sizeof($values) == 1 && isset($values[0]->children)) {
			array_push($parents, $values[0]);
			$refinement = $this->findAttributeRefinementValues(
				$attribute,
				$values[0]->children,
				$filters,
				$excludedPreviews,
				$preferences,
				$depth + 1,
				$parents
			);
			if ($refinement) {
				return $refinement;
			}
			array_pop($parents);
		}

		// find valid values
		$previews = array();
		$list = array();
		foreach ($values as $index => $value) {
			// skip node if no effect
			if ($value->population == $this->getResultsTotal()) {
				continue;
			}

			// skip node if already selected
			$selected = FALSE;
			foreach ($filters as $filter) {
				$selected = TRUE;
				if ($attribute->hierarchical) {
					if (sizeof($filter['guidance']->data) <= $depth) {
						$selected = FALSE;
						continue;
					}
					for ($i = 0; $i < $depth; $i++) {
						$selected = $selected && ($filter['guidance']->data[$i] === $parents[$i]->data[0]);
					}
					$selected = $selected && ($filter['guidance']->data[$depth] === $value->data[0]);
				} else {
					if (sizeof($filter['guidance']->data) != sizeof($value->data)) {
						$selected = FALSE;
						continue;
					}
					for ($i = 0; $i < sizeof($value->data); $i++) {
						$selected = $selected && ($filter['guidance']->data[$i] === $value->data[$i]);
					}
				}
				if ($selected) {
					break;
				}
			}
			if ($selected) {
				continue;
			}

			// build url
			$urlAddParameters = array(
				'context' => $this->encodeSequentialContexts(),
				'guidance' => '+'.$attribute->type,
				'property' => $attribute->property
			);
			$urlSetParameters = array(
				'context' => $this->encodeSequentialContexts(),
				'guidance' => $attribute->type,
				'property' => $attribute->property
			);
			if ($attribute->hierarchical) {
				$urlAddParameters['hierarchical'] = $depth + 1;
				$urlSetParameters['hierarchical'] = $depth + 1;
				for ($i = 0; $i < $depth; $i++) {
					$urlAddParameters['value'.$i] = $parents[$i]->data;
					$urlSetParameters['value'.$i] = $parents[$i]->data;
				}
				$urlAddParameters['value'.$depth] = $value->data;
				$urlSetParameters['value'.$depth] = $value->data;
			} else {
				if ($attribute->type == 'dateRange' || $attribute->type == 'numberRange') {
					$urlAddParameters['mode'] = 'range';
					$urlSetParameters['mode'] = 'range';
				}
				$urlAddParameters['value'] = $value->data;
				$urlSetParameters['value'] = $value->data;
			}

			// select preview
			$preview = NULL;
			$resources = array();
			if (isset($value->previews)) {
				foreach ($value->previews as $resource) {
					if (in_array($resource->id, $excludedPreviews)) {
						continue;
					}
					$resource = $this->buildResource($resource);
					$resources[] = $resource;
					if (!$preview && !in_array($resource['id'], $previews)) {
						$previews[] = $resource['id'];
						$preview = $resource;
					}
				}
			}

			// append value
			$name = $this->formatter->formatAttributeValue($attribute, $index, $value);
			$list[] = array(
				'index' => $index,
				'name' => $name,
				'population' => $value->population,
				'addAction' => array(
					'url' => $this->formatter->formatUrl('', $urlAddParameters),
					'parameters' => $urlAddParameters
				),
				'setAction' => array(
					'url' => $this->formatter->formatUrl('', $urlSetParameters),
					'parameters' => $urlSetParameters
				),
				'preference' => isset($preferences[$name]) && $preferences[$name]['weight'] > 0.1 ? $preferences[$name]['offset'] : 0,
				'favorite' => FALSE,
				'preview' => $preview,
				'resources' => $resources,
				'value' => $value
			);
		}
		if (sizeof($list) > 0) {
			$parentValues = array();
			foreach ($parents as $parentDepth => $parent) {
				// build url
				$urlParameters = array(
					'context' => $this->encodeSequentialContexts(),
					'guidance' => $attribute->type,
					'property' => $attribute->property
				);
				if ($attribute->hierarchical) {
					$urlParameters['hierarchical'] = $parentDepth + 1;
					for ($i = 0; $i < $parentDepth; $i++) {
						$urlParameters['value'.$i] = $parents[$i]->data;
					}
					$urlParameters['value'.$parentDepth] = $parent->data;
				} else {
					if ($attribute->type == 'dateRange' || $attribute->type == 'numberRange') {
						$urlParameters['mode'] = 'range';
					}
					$urlParameters['value'] = $parent->data;
				}

				$name = $this->formatter->formatAttributeValue($attribute, 0, $parent);
				$parentValues[] = array(
					'depth' => $parentDepth,
					'name' => $name,
					'population' => $parent->population,
					'setAction' => array(
						'url' => $this->formatter->formatUrl('', $urlParameters),
						'parameters' => $urlParameters
					),
					'preference' => 0,
					'favorite' => FALSE,
					'value' => $parent
				);
			}

			// find favorites
			$favorites = array();
			foreach ($list as $index => $value) {
				if ($value['preference'] > 0) {
					$favorites[$index] = $value['preference'];
				}
			}
			asort($favorites);
			if (sizeof($favorites) > ceil(sizeof($list) / 3)) {
				$favorites = array_slice($favorites, 0, ceil(sizeof($list) / 3), TRUE);
			}
			foreach ($favorites as $index => $value) {
				$list[$index]['favorite'] = TRUE;
			}

			return array(
				'property' => $attribute->property,
				'label' => $attribute->name,
				'parents' => $parentValues,
				'values' => $list,
				'attribute' => $attribute
			);
		}
		return NULL;
	}


	/**
	 * Find attribute alternative
	 *
	 * @param $attribute attribute descriptor
	 * @param $excludedPreviews list of excluded preview id
	 * @return attribute alternative or NULL if none
	 */
	protected function findAttributeAlternative($attribute, $excludedPreviews = array()) {
		$guidanceFilters = $this->activeFilters();
		$preferences = $this->getPreferredPropertyValues();
		return $this->findAttributeAlternativeValues(
			$attribute,
			$attribute->values,
			isset($guidanceFilters[$attribute->property]) ? $guidanceFilters[$attribute->property] : array(),
			$excludedPreviews,
			isset($preferences[$attribute->property]) ? $preferences[$attribute->property] : array(),
			0,
			array()
		);
	}

	/**
	 * Find attribute alternative
	 *
	 * @param $attribute attribute descriptor
	 * @param $values current node's values
	 * @param $filters active filters
	 * @param $excludedPreviews list of excluded preview id
	 * @param $preferences property preferences
	 * @param $depth current node's depth
	 * @param $parents current node's parents
	 * @return attribute alternative or NULL if none
	 */
	protected function findAttributeAlternativeValues($attribute, $values, $filters, $excludedPreviews, $preferences, $depth, $parents) {
		// skip node if hierarchical parent
		if ($attribute->hierarchical && sizeof($values) == 1 && isset($values[0]->children)) {
			array_push($parents, $values[0]);
			$alternative = $this->findAttributeAlternativeValues(
				$attribute,
				$values[0]->children,
				$filters,
				$excludedPreviews,
				$preferences,
				$depth + 1,
				$parents
			);
			if ($alternative) {
				return $alternative;
			}
			array_pop($parents);
		}

		// find valid values
		$previews = array();
		$list = array();
		foreach ($values as $index => $value) {
			// skip node if already selected
			$selected = FALSE;
			foreach ($filters as $filter) {
				$selected = TRUE;
				if ($attribute->hierarchical) {
					if (sizeof($filter['guidance']->data) <= $depth) {
						$selected = FALSE;
						continue;
					}
					for ($i = 0; $i < $depth; $i++) {
						$selected = $selected && ($filter['guidance']->data[$i] === $parents[$i]->data[0]);
					}
					$selected = $selected && ($filter['guidance']->data[$depth] === $value->data[0]);
				} else {
					if (sizeof($filter['guidance']->data) != sizeof($value->data)) {
						$selected = FALSE;
						continue;
					}
					for ($i = 0; $i < sizeof($value->data); $i++) {
						$selected = $selected && ($filter['guidance']->data[$i] === $value->data[$i]);
					}
				}
				if ($selected) {
					break;
				}
			}
			if ($selected) {
				continue;
			}

			// build name & url
			$urlParameters = array(
				'context' => $this->encodeSequentialContexts(),
				'guidance' => $attribute->type,
				'property' => $attribute->property
			);
			if ($attribute->hierarchical) {
				$urlParameters['hierarchical'] = $depth + 1;
				for ($i = 0; $i < $depth; $i++) {
					$urlParameters['value'.$i] = $parents[$i]->data;
				}
				$urlParameters['value'.$depth] = $value->data;
			} else {
				if ($attribute->type == 'dateRange' || $attribute->type == 'numberRange') {
					$urlParameters['mode'] = 'range';
				}
				$urlParameters['value'] = $value->data;
			}

			// select preview
			$preview = NULL;
			$resources = array();
			if (isset($value->previews)) {
				foreach ($value->previews as $resource) {
					if (in_array($resource->id, $excludedPreviews)) {
						continue;
					}
					$resource = $this->buildResource($resource);
					$resources[] = $resource;
					if (!$preview && !in_array($resource['id'], $previews)) {
						$previews[] = $resource['id'];
						$preview = $resource;
					}
				}
			}

			// append value
			$name  = $this->formatter->formatAttributeValue($attribute, $index, $value);
			$list[] = array(
				'index' => $index,
				'name' => $name,
				'population' => $value->population,
				'setAction' => array(
					'url' => $this->formatter->formatUrl('', $urlParameters),
					'parameters' => $urlParameters
				),
				'preference' => isset($preferences[$name]) && $preferences[$name]['weight'] > 0.1 && $preferences[$name]['offset'] < 3,
				'favorite' => FALSE,
				'preview' => $preview,
				'resources' => $resources,
				'value' => $value
			);
		}
		if (sizeof($list) > 0) {
			$parentValues = array();
			foreach ($parents as $parentDepth => $parent) {
				// build url
				$urlParameters = array(
					'context' => $this->encodeSequentialContexts(),
					'guidance' => $attribute->type,
					'property' => $attribute->property
				);
				if ($attribute->hierarchical) {
					$urlParameters['hierarchical'] = $parentDepth + 1;
					for ($i = 0; $i < $parentDepth; $i++) {
						$urlParameters['value'.$i] = $parents[$i]->data;
					}
					$urlParameters['value'.$parentDepth] = $parent->data;
				} else {
					if ($attribute->type == 'dateRange' || $attribute->type == 'numberRange') {
						$urlParameters['mode'] = 'range';
					}
					$urlParameters['value'] = $parent->data;
				}

				$name = $this->formatter->formatAttributeValue($attribute, 0, $parent);
				$parentValues[] = array(
					'depth' => $parentDepth,
					'name' => $name,
					'population' => $parent->population,
					'setAction' => array(
						'url' => $this->formatter->formatUrl('', $urlParameters),
						'parameters' => $urlParameters
					),
					'value' => $parent
				);
			}

			// find favorites
			$favorites = array();
			foreach ($list as $index => $value) {
				if ($value['preference'] > 0) {
					$favorites[$index] = $value['preference'];
				}
			}
			asort($favorites);
			if (sizeof($favorites) > ceil(sizeof($list) / 2)) {
				$favorites = array_slice($favorites, 0, ceil(sizeof($list) / 2), TRUE);
			}
			foreach ($favorites as $index => $value) {
				$list[$index]['favorite'] = TRUE;
			}

			return array(
				'property' => $attribute->property,
				'label' => $attribute->name,
				'parents' => $parentValues,
				'values' => $list,
				'valuesWithPreview' => sizeof($previews),
				'attribute' => $attribute
			);
		}
		return NULL;
	}


	/**
	 * Collect hierarchical values.
	 *
	 * @param $value current node
	 * @return property values
	 */
	private function collapsePropertyValue($value) {
		$list = array();
		$list[] = $value->value;
		if (isset($value->child)) {
			$list = array_merge($list, $this->collapsePropertyValue($value->child));
		}
		return $list;
	}


	/**
	 * Called to sort object by weight
	 *
	 */
	private function sortByWeight($a, $b) {
		if ($a['weight'] > $b['weight']) {
			return -1;
		} else if ($a['weight'] < $b['weight']) {
			return 1;
		}
		return 0;
	}

	/**
	 * Called to sort object by weight
	 *
	 */
	private function sortByDistance($a, $b) {
		if ($a['distance'] < $b['distance']) {
			return -1;
		} else if ($a['distance'] > $b['distance']) {
			return 1;
		}
		return 0;
	}
}

/**
 * @}
 */

?>