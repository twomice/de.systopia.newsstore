<?php

/**
 * NewsStoreSource.create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_news_store_source_create_spec(&$spec) {
  // $spec['some_parameter']['api.required'] = 1;
}

/**
 * NewsStoreSource.create API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_news_store_source_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * NewsStoreSource.delete API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_news_store_source_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * NewsStoreSource.get API
 *
 * This is a custom getter so we can add in some stats.
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_news_store_source_get($params) {
  $return_values = CRM_Newsstore_BAO_NewsStoreSource::apiGet($params);
  return civicrm_api3_create_success($return_values, $params, 'NewsStoreSource', 'get');
}
/**
 * NewsStoreSource.Fetch API spec.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 */
function _civicrm_api3_news_store_source_fetch_spec(&$spec) {
  $spec['id'] = [
    'description' => 'The NewsStoreSource Id to fetch items for.',
    'type' => 1, // integer
  ];
}
/**
 * NewsStoreSource.Fetch API: Fetch items from the source.
 *
 * Nb. The output values are:
 * - 'old' Number of items that were already known to this source.
 * - 'new' Number of items that were new.
 * - 'new_link' Number of items that were new to this source (but had already
 *   been fetched by another).
 * - 'sources_count' Number of different sources that were fetched.
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_news_store_source_fetch($params) {

  // Delegate to Get method to find the filtered sources.
  $sources_params = $params;
  // We only need ID.
  $sources_params['return'] = 'id';
  // Lets sort sources by ID. While this is not necessary generally, it gives
  // predictable behaviour which is easier to test.
  $sources_params['options'] = ['sort' => 'id'];
  $sources = CRM_Newsstore_BAO_NewsStoreSource::apiGet($sources_params);

  // Initialise output values.
  $stats = ['old' => 0, 'new' => 0, 'new_link' => 0, 'sources_count' => count($sources)];

  // Fetch one at a time.
  foreach ($sources as $source) {
    $return_values = CRM_Newsstore_BAO_NewsStoreSource::apiFetch(['id' => $source['id']]);
    // Combine data.
    foreach (['old', 'new', 'new_link'] as $key) {
      $stats[$key] += (int) $return_values[$key];
    }
  }

  return civicrm_api3_create_success($stats, $params, 'NewsStoreSource', 'Fetch');
}
