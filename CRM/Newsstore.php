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
   */
  public function __construct($newsStoreSource) {
    $this->source = $newsStoreSource;
  }
  /**
   * Fetch items from source.
   *
   */
  public function fetch() {
    $old_items_count = 0;

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
      $sql = "SELECT uri FROM civicrm_newsstoreitem WHERE uri IN ("
        . implode(',', $sql) . ");";

      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      while ($dao->fetch()) {
        unset($raw_items[$dao->uri]);
        $old_items_count++;
      }
      $dao->free();

    }

    // Insert the new items.
    foreach($raw_items as $raw_item) {

      // Take the raw items and parse it into standard things we can store in a
      // NewsStoreItem.
      $values = $this->preProcessRawItem($raw_item);

      // Save it to database
      $item = new CRM_Newsstore_BAO_NewsStoreItem();
      $item->copyValues($values);
      $item->save();

      // Create an unconsumed item linking it to this source.
      $link = new CRM_Newsstore_BAO_NewsStoreConsumed();
      $link->newsstoresource_id = $this->source->id;
      $link->newsstoreitem_id = $item->id;
      $link->is_consumed = 0;
      $link->save();

      // Offer postprocess hook for this item.
      // This could be used, for example, to fetch and cache related resources
      // that we might need to know the NewsStoreItem's ID for.
      $this->postProcessRawItem($item);

      unset($item);
    }

    // Update the last fetched date for the source.
    $this->source->last_fetched = date('Y-m-d H:i:s');
    $this->source->save();

    // Return interesting stats.
    return [
      'old' => $old_items_count,
      'new' => count($raw_items),
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

