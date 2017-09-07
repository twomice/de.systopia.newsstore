<?php
/**
 * Parent class of all news store sources.
 */

abstract class CRM_Newsstore {
  /**
   * @var CRM_Newsstore_BAO_NewsStoreSource
   */
  protected $source;
  /**
   * Factory method.
   *
   * @param CRM_Newsstore_BAO_NewsStoreSource $newsStoreSource
   */
  public static function factory($newsStoreSource) {
    $class = static::validateClass('CRM_Newsstore_' . $newsStoreSource->type);
    $obj = new $class($newsStoreSource);
    return $obj;
  }
  /**
   * Ensures that the given class name is a valid CRM_Newsstore.
   * @throws InvalidArgumentException if it's not.
   */
  public static function validateClass($classname) {
    if (!class_exists($classname, $autoload=TRUE)) {
      throw new InvalidArgumentException("'$classname' is not found.");
    }
    $reflection = new \ReflectionClass($classname);
    if ($reflection->isSubclassOf('CRM_Newsstore')) {
      return $classname;
    }
    throw new GizmoNotFoundException("'$classname' is not a valid CRM_Newsstore class.");
  }

  /**
   * Delete items past retention days.
   *
   * @return int Number of items deleted.
   */
  public static function deleteOldItems() {
    $sql = "SELECT nsi.id
        FROM civicrm_newsstoreitem nsi
        INNER JOIN civicrm_newsstoreconsumed nsc ON nsi.id = nsc.newsstoreitem_id
        INNER JOIN civicrm_newsstoresource nss ON nsc.newsstoresource_id = nss.id
        GROUP BY nsi.id
        HAVING MAX(nsi.timestamp) < CURRENT_DATE - INTERVAL MAX(retention_days) DAY;";
    $params = [];
    $items_to_delete = CRM_Core_DAO::executeQuery($sql, [])->fetchMap('id', 'id');

    if ($items_to_delete) {
      $sql = "DELETE FROM civicrm_newsstoreitem WHERE ID IN ("
        . implode(',', $items_to_delete)
        . ")";
      CRM_Core_DAO::executeQuery($sql, []);
    }
    return count($items_to_delete);
  }
  /**
   * Constructor.
   *
   * @param CRM_Newsstore_BAO_NewsStoreSource $newsStoreSource
   */
  public function __construct($newsStoreSource) {
    $this->source = $newsStoreSource;
  }
  /**
   * Fetch items from source.
   *
   * Nb. this is also responsible for removing items that are older than the configured number of retention days.
   *
   * @return Array of integer fetched item counts split by key:
   *   - old
   *   - new
   *   - new_link (items were already fetched by a different source)
   *
   */
  public function fetch() {
    $old_items_count = 0;
    $newly_linked_items_count = 0;
    $new_items = 0;
    $items_to_link = [];

    // Get the raw items, keyed by URI.
    // The content of this array does not need to adhere to any standard at
    // this point and could be any type of variable. See preProcessRawItem() later.
    $raw_items = $this->fetchRawItems();

    // Exclude any that we already have.
    if ($raw_items) {

      $params = [];
      $i=1;
      $sql = [];
      foreach (array_keys($raw_items) as $uri) {
        $params[$i] = [$uri, 'String'];
        $sql[] = "%$i";
        $i++;
      }
      $sql = "SELECT uri, nsi.id, nsc.newsstoresource_id
        FROM civicrm_newsstoreitem nsi
        LEFT JOIN civicrm_newsstoreconsumed nsc
          ON nsi.id = nsc.newsstoreitem_id
        WHERE uri IN ("
        . implode(',', $sql) . ");";

      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      $items_already_linked = [];
      while ($dao->fetch()) {
        if ($dao->newsstoresource_id == $this->source->id) {
          // We have this item and it's already linked to this source.
          $items_already_linked[$dao->uri] = TRUE;
          // Ensure it's NOT in our list.
          unset($items_to_link[$dao->uri]);
          // Count it as old.
          $old_items_count++;
        }
        else {
          // We have this item linked to a different source, so
          // we may need to create a link to it, but first check that
          // we don't know it's already linked.
          if (!array_key_exists($dao->uri, $items_already_linked)) {
            $items_to_link[$dao->uri] = $dao->id;
          }
        }

        // Remove it from raw items since we've already cached it.
        unset($raw_items[$dao->uri]);
      }
      $dao->free();
    }
    $newly_linked_items_count = count($items_to_link);

    // Insert the new items.
    foreach($raw_items as $raw_item) {

      // Take the raw items and parse it into standard things we can store in a
      // NewsStoreItem.
      $values = $this->preProcessRawItem($raw_item);

      // Save it to database
      $item = new CRM_Newsstore_BAO_NewsStoreItem();
      $item->copyValues($values);
      $item->save();

      // Remember to make a link to this item from this source.
      $items_to_link[$item->uri] = $item->id;

      // Offer postprocess hook for this item.
      // This could be used, for example, to fetch and cache related resources
      // that we might need to know the NewsStoreItem's ID for.
      $this->postProcessRawItem($item);

      unset($item);
    }

    // Create links to items.
    foreach ($items_to_link as $uri=>$newsstoreitem_id) {
      // Create an unconsumed item linking it to this source.
      $link = new CRM_Newsstore_BAO_NewsStoreConsumed();
      $link->newsstoresource_id = $this->source->id;
      $link->newsstoreitem_id = $newsstoreitem_id;
      $link->is_consumed = 0;
      $link->save();
    }

    // Update the last fetched date for the source.
    $this->source->last_fetched = date('Y-m-d H:i:s');
    $this->source->save();

    // Delete old items.
    // Note that this is a general operation; it will delete old items from any
    // source not just this one.
    static::deleteOldItems();

    // Return interesting stats.
    return [
      'old' => $old_items_count,
      'new' => count($raw_items),
      'new_link' => $newly_linked_items_count,
    ];
  }
  /**
   * Fetch and parse items from the source.
   *
   * Nb. this is separate to preProcessRawItem in case there is any costly
   * processing done in preProcessRawItem that could be avoided for items that
   * are not new. However, if parsing the source is super efficient you can
   * just do it here.
   *
   * @return array keyed by the URI. There are no restrictions on the type of values.
   */
  abstract protected function fetchRawItems();

  /**
   * Convert the raw items into the fields stored in a NewsStoreItem.
   *
   * This default implementation just returns the item, which means that if
   * processing an item is not costly you can do it all in fetchRawItems() and
   * not have to write any more code.
   *
   * @return array
   */
  protected function preProcessRawItem($item) {
    return $item;
  }

  /**
   * Do any post processing that may be required now that this item is saved in
   * the database.
   *
   * This default implementation does nothing.
   */
  protected function postProcessRawItem($item) {

  }

}

