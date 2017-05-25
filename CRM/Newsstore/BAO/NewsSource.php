<?php

class CRM_Newsstore_BAO_NewsSource extends CRM_Newsstore_DAO_NewsSource {

  /**
   * Create a new NewsSource based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Newsstore_DAO_NewsSource|NULL
   *
  public static function create($params) {
    $className = 'CRM_Newsstore_DAO_NewsSource';
    $entityName = 'NewsSource';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  } */

}
