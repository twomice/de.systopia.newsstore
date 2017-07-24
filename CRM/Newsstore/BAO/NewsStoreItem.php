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

  /**
   * Gets list of items for a particular source, plus some data from consumed table.
   *
   * Used by NewsStoreItem.GetWithUsage action.
   *
   * @param array $params. Required keys:
   * - 'source' valid source ID integer.
   * - 'is_consumed' 0|1|"any"
   *
   * @return array
   */
  public static function apiGetWithUsage($params) {

    // Ensure we have this declared.
    $params += ['is_consumed' => 'any'];

    $sql = "
          SELECT nsi.*,
            nsc.id newsstoreconsumed_id,
            nsc.is_consumed
          FROM civicrm_newsstoreitem nsi
          INNER JOIN civicrm_newsstoreconsumed nsc ON nsi.id = nsc.newsstoreitem_id AND nsc.newsstoresource_id = %1 "
        . ($params['is_consumed'] == 'any' ? '' : 'AND nsc.is_consumed = %2 ')
        . " ORDER BY nsi.timestamp DESC;";

    $sql_params = [1 => [$params['source'], 'Integer']];

    if ($params['is_consumed'] != 'any') {
      $sql_params[2] = [$params['is_consumed'], 'Integer'];
    }

    $dao = CRM_Core_DAO::executeQuery($sql, $sql_params);
    $return_values = $dao->fetchAll();
    $dao->free();
    return $return_values;
  }

}
