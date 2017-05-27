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
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_news_store_source_get($params) {
  $return_values = CRM_Newsstore_BAO_NewsStoreSource::apiGet($params);
  return civicrm_api3_create_success($return_values, $params, 'NewsStoreSource', 'get');
}
