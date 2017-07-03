<?php

/**
 * NewsStoreItem.create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_news_store_item_create_spec(&$spec) {
  // $spec['some_parameter']['api.required'] = 1;
}

/**
 * NewsStoreItem.create API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_news_store_item_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * NewsStoreItem.delete API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_news_store_item_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * NewsStoreItem.get API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 */
function _civicrm_api3_news_store_item_get_spec(&$spec) {

  $spec['source'] = [
    'description' => 'News Source that this belongs to',
    'required' => FALSE,
    'type' => 1,
  ];

  $spec['is_consumed'] = [
    'description' => 'Whether this item has been consumed or not. Nb. this requires source also be set.',
    'required' => FALSE,
    'type' => 2,
    'options' => ['any' => 'Any', '1' => 'Has been consumed', '0' => 'Has NOT been consumed'],
  ];
}

/**
 * NewsStoreItem.get API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_news_store_item_get($params) {
  $bao_name =_civicrm_api3_get_BAO(__FUNCTION__);

  $require_joins = FALSE;
  // Prepare this once.
  $source_sql = CRM_Utils_SQL_Select::fragment()
    ->join('nsc', 'INNER JOIN civicrm_newsstoreconsumed nsc ON a.id = nsc.newsstoreitem_id');

  if (!empty($params['source'])) {
    $require_joins = TRUE;
    $source_sql->where('nsc.newsstoresource_id IN (#source)', ['source' => $params['source']]);

    news_store_item_fix_consumed_api($params);

    // Look out for user having selected 2 - be nicer to insist on just one but can't figure out how to specify that.
    if ($params['is_consumed'] !== 'any') {
      $require_joins = TRUE;

      $source_sql->where('nsc.is_consumed = #is_consumed', [
        'is_consumed' => $params['is_consumed']
      ]);
    }
  }
  if (empty($params['source']) && isset($params['is_consumed'])) {
    throw new InvalidArgumentException("You must specify a news source in order to filter by is_consumed.");
  }

  if (!$require_joins) {
    // We didn't need this after all.
    $source_sql = NULL;
  }

  // Nb. This does not work, since the select fields are reset by _civicrm_api3_basic_get
  // There does not seem to be a way to do this without writing an entirely custom getter.
  //$query->selects('nsc.is_consumed', 'is_consumed');

  return _civicrm_api3_basic_get($bao_name, $params, TRUE, "", $source_sql);
}
/**
 * NewsStoreItem.GetWithUsage API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_news_store_item_GetWithUsage_spec(&$spec) {
  $spec['source'] = [
    'description' => 'The ID of the NewsStoreSource that you want items for.',
    'api.required' => 1,
  ];
  $spec['is_consumed'] = [
    'description' => 'Whether this item has been consumed or not.',
    'required' => FALSE,
    'type' => 2,
    'options' => ['any' => 'Any', '1' => 'Has been consumed', '0' => 'Has NOT been consumed'],
  ];
}

/**
 * NewsStoreItem.GetWithUsage API
 *
 * Custom collection getter to allow for joining against a particular source.
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_news_store_item_GetWithUsage($params) {

  if (empty($params['source']) || ! ((int) $params['source'] > 0)) {
    throw new API_Exception("Source must be specified as the integer source ID");
  }

  news_store_item_fix_consumed_api($params);

  $return_values = CRM_Newsstore_BAO_NewsStoreItem::apiGetWithUsage($params);
  return civicrm_api3_create_success($return_values, $params, 'NewsSourceItem', 'GetWithUsage');
}

/**
 * Ensure is_consumed is set in params.
 *
 * Shared code.
 *
 * @param array &$params API input params.
 * @param mixed $default_value if missing. Defaults to 0 to find only unconsumed
 * items. Other options are 1 or 'any'.
 */
function news_store_item_fix_consumed_api(&$params, $default_value = 0) {

  if (!isset($params['is_consumed'])) {
    // Ensure we have a default.
    $params['is_consumed'] = $default_value;
  }

  // Fix array passed.
  if (is_array($params['is_consumed'])) {
    if (count($params['is_consumed']) > 1) {
      // Rationalise strange use of API!
      $params['is_consumed'] = 'any';
    }
    else {
      // Take first.
      $params['is_consumed'] = reset($params['is_consumed']);
    }
  }

  if (!in_array($params['is_consumed'], [0, 1, 'any'])) {
    throw new InvalidArgumentException("is_consumed must be 0, 1 or 'any'.");
  }

}
