<?php

class CRM_Newsstore_BAO_NewsStoreSource extends CRM_Newsstore_DAO_NewsStoreSource {

  /**
   * Create a new NewsStoreSource based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Newsstore_DAO_NewsStoreSource|NULL
   *
  public static function create($params) {
    $className = 'CRM_Newsstore_DAO_NewsStoreSource';
    $entityName = 'NewsStoreSource';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  } */

  /**
   * Returns the NewsStoreSource.get result.
   *
   * This is all the NewsStoreSource data plus stats about items.
   */
  public static function apiGet($params) {
    $sql = "
          SELECT ns.*,
            COALESCE(nsstats.itemsTotal, 0) itemsTotal,
            COALESCE(nsstats.itemsUnconsumed, 0) itemsUnconsumed
          FROM civicrm_newsstoresource ns
          LEFT JOIN (
            SELECT newsstoresource_id id, COUNT(id) itemsTotal, SUM(is_consumed=0) itemsUnconsumed
            FROM civicrm_newsstoreconsumed
            GROUP BY newsstoresource_id
          ) nsstats ON ns.id = nsstats.id
          ORDER BY name;
      ";
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $return_values = $dao->fetchAll();
    $dao->free();
    return $return_values;
  }
  /**
   * Fetch items from the source.
   *
   * Invoked by Api NewsStoreSource.Fetch action.
   */
  public static function apiFetch($params) {

    $id = empty($params['id']) ? 0 : (int) $params['id'];
    if (! $id > 0) {
      throw new InvalidArgumentException("requires valid integer NewsStoreSource id");
    }

    $dao = static::findById($id);
    // Now need NewsStoreSource object.
    $store = CRM_Newsstore::factory($dao);
    $return_values = $store->fetch();
    return $return_values;
  }
}
