<?php

class CRM_Newsstore_BAO_NewsStoreItem extends CRM_Newsstore_DAO_NewsStoreItem {

  /**
   * Create a new NewsStoreItem based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Newsstore_DAO_NewsStoreItem|NULL
   *
  public static function create($params) {
    $className = 'CRM_Newsstore_DAO_NewsStoreItem';
    $entityName = 'NewsStoreItem';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  } */

}
