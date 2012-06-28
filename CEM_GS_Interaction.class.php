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
	 * SEO property mapping
	 */
	protected $seoPropertyMapping;

	/**
	 * Json decoded contexts (cache)
	 */
	private $_jsonContexts = array();

	/**
	 * Sequential context (cache)
	 */
	private $_sequentialContexts = NULL;

	/**
	 * Guidance path items (cache)
	 */
	private $_uriGuidances = array();

	/**
	 * Guidance path uri (cache)
	 */
	private $_uriGuidancesCache = NULL;

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
	 * @param $crypto encryption facility
	 * @param $request client request reference
	 * @param $response client response reference
	 * @param $options user-defined options
	 * @param $formatter value formatter
	 * @param $seoPropertyMapping seo property mapping
	 */
	public function __construct($crypto, $request, $response, $options, $formatter, $seoPropertyMapping = array()) {
		parent::__construct($crypto);
		$this->request = $request;
		$this->response = $response;
		$this->options = $options;
		$this->formatter = $formatter;
		$this->seoPropertyMapping = $seoPropertyMapping;

		$contexts = $this->response->getContext();
		if (isset($contexts['model'])) {
			$model = @json_decode($contexts['model']['data'], TRUE);
			if (isset($model['guidances'])) {
				$guidances = array();
				foreach ($model['guidances'] as $guidance) {
					$property = $guidance['property'];
					if ($guidance['type'] != 'text' || !isset($this->seoPropertyMapping[$property]) || isset($this->_uriGuidances[$property])) {
						$guidances[] = $guidance;
					} else {
						$this->_uriGuidances[$property] = $guidance['data'];
					}
				}
				if (sizeof($guidances) > 0) {
					$model['guidances'] = $guidances;
				} else {
					unset($model['guidances']);
				}
			}
			if (sizeof($model) > 0) {
				$contexts['model']['data'] = json_encode($model);
			} else {
				unset($contexts['model']);
			}
		}
		$data = '';
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
	 * @return encoded sequential contexts
	 */
	public function encodeSequentialContexts() {
		return $this->_sequentialContexts;
	}

	/**
	 * Encode state into url parameters
	 *
	 * @param $parameters additional query parameters
	 * @return query parameters with state
	 */
	public function encodeQueryContext($parameters = array(), $appendContext = FALSE) {
		if ($appendContext) {
			$context = $this->encodeSequentialContexts();
			if ($context) {
				$parameters['context'] = $context;
			}
		}
		return $parameters;
	}

	/**
	 * Encode parameters and state into url
	 *
	 * @param $parameters additional query parameters
	 * @return encoded url query
	 */
	public function encodeQuery($parameters = array(), $appendContext = FALSE) {
		return $this->formatter->formatUrl($this->buildUriGuidance(), $this->encodeQueryContext($parameters, $appendContext));
	}

	/**
	 * Encode action into url
	 *
	 * @param $action action
	 * @return encoded url query
	 */
	public function encodeAction($uri, $action, $appendContext = FALSE) {
		$parameters = $action['parameters'];
		if (isset($action['uriParameters'])) {
			foreach ($action['uriParameters'] as $parameter) {
				unset($parameters[$parameter]);
			}
		}
		return $this->formatter->formatUrl($uri.$action['uri'], $this->encodeQueryContext($parameters, $appendContext));
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
		if (isset($model->queryText) && $this->requestExists('query')) {
			if (strcmp($model->queryText, $this->requestString('query')) != 0) {
				return $model->queryText;
			}
		}
		if (isset($model->queryTerms)) {
			$has = FALSE;
			$terms = array();
			foreach ($model->queryTerms as $index => $queryTerm) {
				if ($queryTerm->type == 'unfiltered' || $queryTerm->type == 'unmatched') {
					$has = TRUE;
					continue;
				}
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
			if ($queryTerm->type != 'ambiguous' || !isset($queryTerm->refinements)) {
				$prefix .= ' '.$queryTerm->value;
				continue;
			}
			foreach ($queryTerm->refinements as $refinement) {
				$label = '';
				$property = $this->getProperty($refinement->property);
				if ($property) {
					$label = $property['name'];
				}
				foreach ($refinement->values as $value) {
					$rawValue = $value->value;
					$value = trim(strtolower($prefix.' '.$rawValue));
					$distance = levenshtein($value, strtolower($queryTerm->value));
					if (!isset($list[$value])) {
						if ($distance > 0) {
							if ($distance < strlen($queryTerm->value) / 5.0) {
								$list[$value] = array(
									'label' => $label,
									'query' => $value,
									'queryAction' => $this->buildQueryAction($value),
									'distance' => $distance
								);
							}
						} else {
							$urlParameters = array(
								'refine' => $index,
								'property' => $refinement->property,
								'value' => $rawValue
							);
							$list[$value] = array(
								'label' => $label,
								'query' => $value,
								'queryAction' => $this->buildQueryAction($value),
								'distance' => $distance
							);
						}
					}
				}
			}
			$prefix .= ' '.$queryTerm->value;
		}
		if (sizeof($list) > 1) {
			uasort($list, array($this, 'sortByDistance'));
			return $list;
		}
		return array();
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
					$distance = levenshtein(strtolower($refinement->values[0]->value), strtolower($queryTerm->value));
					if ($distance <= 2) {
						if (!isset($this->_filters[$groupId][$refinement->property])) {
							$this->_filters[$groupId][$refinement->property] = array();
						}
						$this->_filters[$groupId][$refinement->property][] = array(
							'mode' => 'guidance',
							'index' => -($index + 1),
							'property' => $guidance->property,
							'guidance' => $guidance
						);
					}  else if ($distance < strlen($queryTerm->value) / 5.0) {
						if (!isset($this->_filters[$groupId][$refinement->property])) {
							$this->_filters[$groupId][$refinement->property] = array();
						}
						$this->_filters[$groupId][$refinement->property][] = array(
							'mode' => 'term',
							'index' => $index,
							'property' => $guidance->property,
							'guidance' => $guidance
						);
					}
				} else if (($queryTerm->type == 'matched' || $queryTerm->type == 'refined') && isset($queryTerm->guidances)) {
					foreach ($queryTerm->guidances as $guidance) {
						if (!isset($this->_filters[$groupId][$guidance->property])) {
							$this->_filters[$groupId][$guidance->property] = array();
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
				if (!isset($this->_filters[$groupId][$guidance->property])) {
					$this->_filters[$groupId][$guidance->property] = array();
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

				if ($filter['mode'] == 'term' && $filter['index'] >= 0) {
					$this->_filters[$groupId][$propertyId][$index]['removeAction'] = $this->buildRefineAction($filter['index']);
				} else if (sizeof($filters) > 1 && $filter['mode'] == 'guidance' && $filter['index'] >= 0) {
					$this->_filters[$groupId][$propertyId][$index]['removeAction'] = $this->buildGuidanceRemoveAction($filter['index']);
				} else {
					$this->_filters[$groupId][$propertyId][$index]['removeAction'] = $this->buildGuidanceRemoveAction($propertyId);
				}
			}
		}
		foreach ($this->_filters[$groupId] as $propertyId => $filters) {
			$property = $this->getProperty($propertyId, $groupId);
			foreach ($filters as $index => $filter) {
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
		$state = $this->getContextJson('userState');
		return (isset($state->pageSize) ? $state->pageSize : 10);
	}

	/**
	 * Get current ranking
	 *
	 * @return ranking
	 */
	public function activeRanking() {
		$state = $this->getContextJson('userState');
		return (isset($state->ranking) ? $state->ranking : '@score desc,@random asc');
	}

	/**
	 * Get current scenario
	 *
	 * @return current scenario
	 */
	public function activeScenario() {
		$model = $this->getContextJson('model');
		return (isset($model->scenario) ? $model->scenario : '');
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
				if (isset($property->names)) {
					foreach ($property->names as $name) {
						if ($name->language == $this->getResponse()->getLanguage()) {
							$label = $name->value;
							break;
						}
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
				if (isset($property->names)) {
					foreach ($property->names as $name) {
						if ($name->language == $this->getResponse()->getLanguage()) {
							$label = $name->value;
							break;
						}
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
	 * Get scenarios
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
				$attributeOrder = array();
				$skip = $this->requestStringArray('skip');
				foreach ($scenario->attributes as $attribute) {
					$attributeOrder[] = $attribute->property;
					if (!$attribute->valid || in_array($attribute->property, $skip)) {
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
							// HACK: semeuse
							if ($this->getRequest()->getCustomer() != 'semeuse') {
								continue;
							}
						}
						if (sizeof($skip) > 0) {
							$value['addAction']['parameters']['skip'] = $skip;
							$value['setAction']['parameters']['skip'] = $skip;
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
					if ($this->getRequest()->getCustomer() == 'flaschenpost' && $usefulValues == 1 && $attribute->property == 'awarded') {
						$usefulValues++;
					}

					if ($usefulValues > 1) {
						$refinements[] = array(
							'prompt' => $attribute->prompt,
							'offset' => $attribute->offset,
							'property' => $attribute->property,
							'label' => $refinement['label'],
							'parents' => $refinement['parents'],
							'values' => $values,
							'valuesSelected' => $refinement['valuesSelected'],
							'valuesFiltering' => $refinement['valuesFiltering'],
							'valuesWithPreview' => $refinement['valuesWithPreview'],
							'attribute' => $refinement['attribute']
						);
						$skip[] = $attribute->property;
					}
				}
				$recommendations = array();
				foreach ($scenario->recommendations as $resource) {
					$resource = $this->buildResource($resource);
					$resource['scenario'] = $scenario->id;
					$recommendations[] = $resource;
				}
				$urlParameters = array(
					'scenario' => $scenario->id
				);
				$this->_scenarios[$groupId][$scenario->id] = array(
					'id' => $scenario->id,
					'name' => $scenario->name,
					'setAction' => $this->buildScenarioAction($scenario->id),
					'attributeOrder' => $attributeOrder,
					'refinements' => $refinements,
					'recommendations' => $recommendations,
					'scenario' => $scenario
				);
			}
		}
		return $this->_scenarios[$groupId];
	}

	/**
	 * Get scenario
	 *
	 * @param $scenario scenario identifier
	 * @param $groupId group identifier
	 * @return scenario or NULL if none
	 */
	public function getScenario($scenario, $groupId = 'search') {
		$scenarios = $this->getScenarios($groupId);
		return (isset($scenarios[$scenario]) ? $scenarios[$scenario] : NULL);
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
				if (isset($result->score)) {
					$resource['score'] = $result->score;
				} else {
					$resource['score'] = 0;
				}
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
			foreach ($scenario['recommendations'] as $recommendation) {
				$this->_recommendations[$groupId][] = $recommendation;
			}
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
		if (isset($resource->properties)) {
			foreach ($resource->properties as $value) {
				$list = $this->collapsePropertyValue($value);
				if (!isset($data[$value->property])) {
					$data[$value->property] = sizeof($list) > 1 ? $list : $list[0];
				}
				$data['properties'][$value->property][] = sizeof($list) > 1 ? $list : $list[0];
			}
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
				$entries[$refinement->property]['values'][] = array(
					'name' => $value->value,
					'population' => $value->population,
					'filtering' => $value->population < $this->getResultsTotal(),
					'favorite' => FALSE,
					'refineAction' => $this->buildRefineAction($index, $refinement->property, $value->value),
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
		if (in_array('hierarchical', $attribute->propertyFlags) && sizeof($values) == 1 && isset($values[0]->children)) {
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
		$nonFiltering = array();
		$totalSelected = 0;
		$totalFiltering = 0;
		$previews = array();
		$list = array();
		foreach ($values as $index => $value) {
			// skip node if already selected
			$selected = FALSE;
			$selectedFilter = NULL;
			foreach ($filters as $filter) {
				$selected = TRUE;
				if (in_array('hierarchical', $attribute->propertyFlags)) {
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
					$selectedFilter = $filter;
					break;
				}
			}
			if ($selected && in_array('hierarchical', $attribute->propertyFlags) && isset($value->children)) {
				array_push($parents, $value);
				$refinement = $this->findAttributeRefinementValues(
					$attribute,
					$value->children,
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
			if ($selected) {
				$totalSelected++;
			}
			if ($value->population < $this->getResultsTotal()) {
				$totalFiltering++;
			} else {
				$nonFiltering[] = $value;
			}
			$name = $this->formatter->formatAttributeValue($attribute, $index, $value);
			$list[] = array(
				'index' => $index,
				'name' => $name,
				'population' => $value->population,
				'selected' => $selected,
				'filtering' => $value->population < $this->getResultsTotal(),
				'addAction' => $this->buildAttributeAddAction($attribute, array_slice($parents, 0, $depth), $value),
				'setAction' => $this->buildAttributeSetAction($attribute, array_slice($parents, 0, $depth), $value),
				'removeAction' => $selectedFilter ? $selectedFilter['removeAction'] : $this->buildAttributeRemoveAction($attribute),
				'preference' => isset($preferences[$name]) && $preferences[$name]['weight'] > 0.1 ? $preferences[$name]['offset'] : 0,
				'favorite' => FALSE,
				'preview' => $preview,
				'resources' => $resources,
				'value' => $value
			);
		}

		// skip node if one node has all results
		if (in_array('hierarchical', $attribute->propertyFlags) && sizeof($nonFiltering) == 1 && isset($nonFiltering[0]->children)) {
			array_push($parents, $nonFiltering[0]);
			$refinement = $this->findAttributeRefinementValues(
				$attribute,
				$nonFiltering[0]->children,
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

		if (sizeof($list) > 0) {
			$parentValues = array();
			foreach ($parents as $parentDepth => $parent) {
				$name = $this->formatter->formatAttributeValue($attribute, 0, $parent);
				$parentValues[] = array(
					'depth' => $parentDepth,
					'name' => $name,
					'population' => $parent->population,
					'selected' => TRUE,
					'filtering' => FALSE,
					'addAction' => $this->buildAttributeAddAction($attribute, array_slice($parents, 0, $parentDepth), $parent),
					'setAction' => $this->buildAttributeSetAction($attribute, array_slice($parents, 0, $parentDepth), $parent),
					'removeAction' => $this->buildAttributeRemoveAction($attribute),
					'preference' => isset($preferences[$name]) && $preferences[$name]['weight'] > 0.1 ? $preferences[$name]['offset'] : 0,
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
				'valuesSelected' => $totalSelected,
				'valuesFiltering' => $totalFiltering,
				'valuesWithPreview' => sizeof($previews),
				'attribute' => $this->buildAttributeView($attribute, $parentValues, $list)
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
		if (in_array('hierarchical', $attribute->propertyFlags) && sizeof($values) == 1 && isset($values[0]->children)) {
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
		$nonFiltering = array();
		$totalSelected = 0;
		$totalFiltering = 0;
		$previews = array();
		$list = array();
		foreach ($values as $index => $value) {
			// skip node if already selected
			$selected = FALSE;
			$selectedFilter = NULL;
			foreach ($filters as $filter) {
				$selected = TRUE;
				if (in_array('hierarchical', $attribute->propertyFlags)) {
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
					if ($attribute->type == 'numberRange') {
						for ($i = 0; $i < sizeof($value->data); $i++) {
							$selected = $selected && ($filter['guidance']->data[$i] == $value->data[$i]);
						}
					} else {
						for ($i = 0; $i < sizeof($value->data); $i++) {
							$selected = $selected && ($filter['guidance']->data[$i] === $value->data[$i]);
						}
					}
				}
				if ($selected) {
					$selectedFilter = $filter;
					break;
				}
			}
			if ($selected && in_array('hierarchical', $attribute->propertyFlags) && isset($value->children)) {
				array_push($parents, $value);
				$alternative = $this->findAttributeAlternativeValues(
					$attribute,
					$value->children,
					$filters,
					$excludedPreviews,
					$preferences,
					$depth + 1,
					$parents
				);
				if ($alternative) { // && sizeof($alternative['parents']) > sizeof($parents)) {
					return $alternative;
				}
				array_pop($parents);
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
			if ($selected) {
				$totalSelected++;
			}
			if ($value->population < $this->getResultsTotal()) {
				$totalFiltering++;
			} else {
				$nonFiltering[] = $value;
			}
			$name  = $this->formatter->formatAttributeValue($attribute, $index, $value);
			$list[] = array(
				'index' => $index,
				'name' => $name,
				'population' => $value->population,
				'selected' => $selected,
				'filtering' => !$selected,
				'addAction' => $this->buildAttributeAddAction($attribute, array_slice($parents, 0, $depth), $value),
				'setAction' => $this->buildAttributeSetAction($attribute, array_slice($parents, 0, $depth), $value),
				'removeAction' => $selectedFilter ? $selectedFilter['removeAction'] : $this->buildAttributeRemoveAction($attribute),
				'preference' => isset($preferences[$name]) && $preferences[$name]['weight'] > 0.1 && $preferences[$name]['offset'] < 3,
				'favorite' => FALSE,
				'preview' => $preview,
				'resources' => $resources,
				'value' => $value
			);
		}

		// skip node if one node has all results
		if (in_array('hierarchical', $attribute->propertyFlags) && sizeof($nonFiltering) == 1 && isset($nonFiltering[0]->children)) {
			array_push($parents, $nonFiltering[0]);
			$alternative = $this->findAttributeAlternativeValues(
				$attribute,
				$nonFiltering[0]->children,
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

		if (sizeof($list) > 0) {
			$parentValues = array();
			foreach ($parents as $parentDepth => $parent) {
				$name = $this->formatter->formatAttributeValue($attribute, 0, $parent);
				$parentValues[] = array(
					'depth' => $parentDepth,
					'name' => $name,
					'population' => $parent->population,
					'selected' => TRUE,
					'filtering' => FALSE,
					'last' => sizeof($parentValues) == sizeof($parents) - 1,
					'addAction' => $this->buildAttributeAddAction($attribute, array_slice($parents, 0, $parentDepth), $parent),
					'setAction' => $this->buildAttributeSetAction($attribute, array_slice($parents, 0, $parentDepth), $parent),
					'removeAction' => $this->buildAttributeRemoveAction($attribute),
					'preference' => isset($preferences[$name]) && $preferences[$name]['weight'] > 0.1 && $preferences[$name]['offset'] < 3,
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
				'valuesSelected' => $totalSelected,
				'valuesFiltering' => $totalFiltering,
				'valuesWithPreview' => sizeof($previews),
				'attribute' => $this->buildAttributeView($attribute, $parentValues, $list)
			);
		}
		return NULL;
	}


	private function buildQueryAction($query) {
		return array(
			'uri' => $this->buildUriGuidance(),
			'parameters' => array(
				'catq' => $query
			)
		);
	}

	private function buildRefineAction($index, $property = NULL, $value = NULL) {
		if ($property && $value) {
			return array(
				'uri' => $this->buildUriGuidance(),
				'parameters' => array(
					'refine' => $index,
					'property' => $property,
					'value' => $value
				)
			);
		}
		return array(
			'uri' => $this->buildUriGuidance(),
			'parameters' => array(
				'refine' => $index
			)
		);
	}

	private function buildGuidanceRemoveAction($property) {
		if (is_numeric($property)) {
			return array(
				'uri' => $this->buildUriGuidance(),
				'parameters' => array(
					'filterdel' => $property
				)
			);
		}
		if (isset($this->seoPropertyMapping[$property])) {
			return array(
				'uri' => $this->buildUriGuidance(array(), array($property)),
				'uriParameters' => array('attrdel'),
				'parameters' => array(
					'attrdel' => $property
				)
			);
		}
		return array(
			'uri' => $this->buildUriGuidance(),
			'parameters' => array(
				'attrdel' => $property
			)
		);
	}

	private function buildScenarioAction($scenario) {
		return array(
			'uri' => $this->buildUriGuidance(),
			'parameters' => array(
				'scenario' => $scenario
			)
		);
	}

	private function buildAttributeAddAction($attribute, $parents, $value) {
		if ($attribute->type == 'text') {
			if (in_array('hierarchical', $attribute->propertyFlags)) {
				$data = array();
				foreach ($parents as $parent) {
					$data[] = $parent->data[0];
				}
				$data[] = $value->data[0];
				return array(
					'uri' => $this->buildUriGuidance(),
					'parameters' => array(
						'thattradd' => array($attribute->property => $this->optimizeArray($data))
					)
				);
			}
			return array(
				'uri' => $this->buildUriGuidance(),
				'parameters' => array(
					'tattradd' => array($attribute->property => $this->optimizeArray($value->data))
				)
			);
		}
		if ($attribute->type == 'dateRange') {
			return array(
				'uri' => $this->buildUriGuidance(),
				'parameters' => array(
					'dattradd' => array($attribute->property => implode('|', $value->data))
				)
			);
		}
		if ($attribute->type == 'numberRange') {
			return array(
				'uri' => $this->buildUriGuidance(),
				'parameters' => array(
					'nattradd' => array($attribute->property => implode('|', $value->data))
				)
			);
		}
		throw new Exception();
	}

	private function buildAttributeSetAction($attribute, $parents, $value) {
		if ($attribute->type == 'text') {
			if (in_array('hierarchical', $attribute->propertyFlags)) {
				$mode = 'thattr';
				$data = array();
				foreach ($parents as $parent) {
					$data[] = $parent->data[0];
				}
				$data[] = $value->data[0];
			} else {
				$mode = 'tattr';
				$data = $value->data;
			}
			if (isset($this->seoPropertyMapping[$attribute->property])) {
				return array(
					'uri' => $this->buildUriGuidance(array($attribute->property => $data)),
					'uriParameters' => array($mode),
					'parameters' => array(
						$mode => array($attribute->property => $this->optimizeArray($value->data))
					)
				);
			}
			return array(
				'uri' => $this->buildUriGuidance(),
				'parameters' => array(
					$mode => array($attribute->property => $this->optimizeArray($data))
				)
			);
		}
		if ($attribute->type == 'dateRange') {
			return array(
				'uri' => $this->buildUriGuidance(),
				'parameters' => array(
					'dattr' => array($attribute->property => implode('|', $value->data))
				)
			);
		}
		if ($attribute->type == 'numberRange') {
			return array(
				'uri' => $this->buildUriGuidance(),
				'parameters' => array(
					'nattr' => array($attribute->property => implode('|', $value->data))
				)
			);
		}
		throw new Exception();
	}

	private function buildAttributeRemoveAction($attribute) {
		if (isset($this->seoPropertyMapping[$attribute->property])) {
			return array(
				'uri' => $this->buildUriGuidance(array(), array($attribute->property)),
				'uriParameters' => array('attrdel'),
				'parameters' => array(
					'attrdel' => $attribute->property
				)
			);
		}
		return array(
			'uri' => $this->buildUriGuidance(),
			'parameters' => array(
				'attrdel' => $attribute->property
			)
		);
	}

	private function buildUriGuidance($included = array(), $excluded = array()) {
		if (sizeof($included) == 0 && sizeof($excluded) == 0 && $this->_uriGuidancesCache) {
			return $this->_uriGuidancesCache;
		}

		$guidances = array();
		foreach ($this->_uriGuidances as $property => $value) {
			if (in_array($property, $excluded)) {
				continue;
			}
			$guidances[$property] = $value;
		}
		foreach ($included as $property => $value) {
			if (in_array($property, $excluded)) {
				continue;
			}
			$guidances[$property] = $value;
		}
		uksort($guidances, array($this, 'sortSEOGuidance'));

		$uri = array();
		foreach ($guidances as $property => $value) {
			$uri[] = urlencode($this->seoPropertyMapping[$property]['map']);
			foreach ($value as $item) {
				$uri[] = urlencode($item);
			}
		}
		if (sizeof($uri) > 0) {
			$uri = '/'.implode('/', $uri);
		} else {
			$uri = '';
		}
		if (sizeof($included) == 0 && sizeof($excluded) == 0) {
			$this->_uriGuidancesCache = $uri;
		}
		return $uri;
	}


	/**
	 * Build an attribute subset
	 *
	 * @param $attribute complete attribute
	 * @param $parents attribute parent path
	 * @param $values attribute values
	 * @return attribute subset
	 */
	private function buildAttributeView($attribute, $parents, $values) {
		$out = array();
		foreach (array('type', 'property', 'hierarchical', 'defined', 'undefined', 'cardinality', 'coverage', 'entropy', 'relevance', 'weight', 'name', 'data', 'statistics', 'propertyFlags') as $key) {
			if (isset($attribute->$key)) {
				$out[$key] = $attribute->$key;
			}
		}
		$out['values'] = array();
		$list =& $out['values'];
		foreach ($parents as $parent) {
			$value = $this->buildAttributeValueView($parent['value']);
			$value['children'] = array();
			$list[] = $value;
			$list =& $list[sizeof($list) - 1]['children'];
		}
		foreach ($values as $value) {
			$list[] = $this->buildAttributeValueView($value['value']);
		}
		return json_decode(json_encode($out));
	}

	/**
	 * Build an attribute value view
	 *
	 * @param $value attribute value
	 * @return attribute value view
	 */
	private function buildAttributeValueView($value) {
		$out = array();
		foreach (array('population', 'weight', 'data', 'previews') as $key) {
			if (isset($value->$key)) {
				$out[$key] = $value->$key;
			}
		}
		return $out;
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
	 * Optimize array encoding
	 *
	 * @param $list input list
	 * @return optimized list
	 */
	private function optimizeArray($list) {
		if (sizeof($list) == 1) {
			return $list[0];
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

	/**
	 * Called to sort SEO properties
	 *
	 */
	private function sortSEOGuidance($a, $b) {
		foreach ($this->seoPropertyMapping as $src => $dst) {
			if ($a == $src) {
				return -1;
			}
			if ($b == $src) {
				return 1;
			}
		}
		return 0;
	}
}

/**
 * @}
 */

?>