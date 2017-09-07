<?php

require_once __DIR__ . '/TestHelper.php';

/**
 * Tests fetching items.
 *
 * @group headless
 */
class CRM_Newsstore_FetchTest extends CRM_Newsstore_TestHelper
{
  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  /**
   * Test that items can be inserted into the database.
   */
  public function testFetchInsertsItems() {

    $now = date('Y-m-d H:i:s');
    // Do initial fetch with a single item.
    $vars = $this->createOneDummySourceWithItems();
    // time elapses, and now the source has a different item.
    $this->setDummySourceItemFixtures($vars->uri, 'unique_item');

    // We expect 1 new item.
    $this->assertEquals(['old' => 0, 'new' => 1, 'new_link' => 0], $vars->stats);

    // There should now be one item for this source.
    $items = CRM_Newsstore_BAO_NewsStoreItem::apiGetWithUsage(['source' => $vars->source_id]);
    $this->assertCount(1, $items);

    // The item should have been properly saved.
    $item = reset($items);
    $this->assertEquals('uri1', $item['uri']);
    $this->assertEquals('Title 1', $item['title']);
    $this->assertEquals('body 1', $item['html']);
    $this->assertEquals('teaser 1', $item['teaser']);
    $this->assertEquals($this->getDate() . ' 00:00:00', $item['timestamp']);

    // The NewsStoreSource's last_fetch should be later than or equal to $now.
    $this->assertGreaterThanOrEqual($now, $vars->source_bao->last_fetched);

  }

  /**
   * Test that items can be inserted into the database.
   */
  public function testFetchBehavesWhenNothingFetched() {

    $now = date('Y-m-d H:i:s');
    $vars = $this->createDummySourceFixture();

    // Configure dummy with an empty items set.
    CRM_Newsstore_Dummy::$raw_items[$vars->uri] = [];

    // Run fetch.
    $stats = $vars->store->fetch();

    // We expect 1 new item, 1 old.
    $this->assertEquals(['old' => 0, 'new' => 0, 'new_link' => 0], $stats);

  }

  /**
   * Test that items can be inserted into the database.
   */
  public function testFetchInsertsOnlyNewItems() {

    $now = date('Y-m-d H:i:s');
    // Do initial fetch with a single item.
    $vars = $this->createOneDummySourceWithItems('item_a');
    // time elapses and a new item is now in the source.
    $this->setDummySourceItemFixtures($vars->uri, 'items_a_and_b');

    // Run fetch again.
    $stats = $vars->store->fetch();

    // We expect 1 new item, 1 old.
    $this->assertEquals(['old' => 1, 'new' => 1, 'new_link' => 0], $stats);

    // There should now be two items for this source.
    $items = CRM_Newsstore_BAO_NewsStoreItem::apiGetWithUsage(['source' => $vars->source_id]);
    $this->assertCount(2, $items);

    // index the items by uri for the purposes of testing.
    $items_keyed = [];
    foreach ($items as $_) {
      $items_keyed[$_['uri']] = $_;
    }

    // we should still have uri1
    $this->assertArrayHasKey('uri1', $items_keyed);
    // and also uri2
    $this->assertArrayHasKey('uri2', $items_keyed);

    // The original item should NOT have been updated.
    $item = $items_keyed['uri1'];
    $this->assertEquals('uri1', $item['uri']);
    $this->assertEquals('Title 1', $item['title']);
    $this->assertEquals('body 1', $item['html']);
    $this->assertEquals('teaser 1', $item['teaser']);
    $this->assertEquals($this->getDate() . ' 00:00:00', $item['timestamp']);

    // The new item should have been saved.
    $item = $items_keyed['uri2'];
    $this->assertEquals('uri2', $item['uri']);
    $this->assertEquals('Title 2', $item['title']);
    $this->assertEquals('body 2', $item['html']);
    $this->assertEquals('teaser 2', $item['teaser']);
    $this->assertEquals($this->getDate() . ' 00:00:00', $item['timestamp']);

    // Run fetch again.
    $stats = $vars->store->fetch();
  }

  /**
   * If an item is already loaded, but was not loaded for this source,
   * we need to add a NewsStoreConsumed link to the source, but not download it again.
   */
  public function testFetchExistingItemsToNewSource() {

    $now = date('Y-m-d H:i:s');
    $vars = $this->createOneDummySourceWithItems();
    $vars2 = $this->createOneDummySourceWithItems();

    // We expect 1 new item (even though it was known to another source, it is new to us), 0 old.
    $this->assertEquals(['old' => 0, 'new' => 0, 'new_link' => 1], $vars2->stats);

    $stats = $vars2->store->fetch();
    $this->assertEquals(['old' => 1, 'new' => 0, 'new_link' => 0], $stats);

  }

  /**
   * Test that old items are deleted.
   *
   * This is really a test of the SQL and we do everything in here with SQL.
   */
  public function testRetentionSimple() {

    $now = date('Y-m-d H:i:s');
    $this->assertEquals('', $this->fetchAllItemIds());

    // Create stores.
    CRM_Core_DAO::executeQuery('INSERT INTO civicrm_newsstoresource (id, name, retention_days, uri, type) VALUES
      (1, "one", 10, "http://one", "dummy"),
      (2, "two", 20, "http://two", "dummy");', []);

    // Create items.
    $ancient = $this->getDate(25); // Date older than oldest retention
    $modern = $this->getDate(15);  // Date older than one retention but younger then the other.
    $contemporary = $this->getDate(0); // Today is younger than all retentions.
    CRM_Core_DAO::executeQuery("INSERT INTO civicrm_newsstoreitem (id, title, timestamp, uri) VALUES
      (1, 'ancient item', '$ancient', 'http://ancient'),
      (2, 'modern item', '$modern', 'http://modern'),
      (3, 'contemporary item', '$contemporary', 'http://contemporary');", []);

    // Create Link all items to the first source only. This has a 10 day retention.
    CRM_Core_DAO::executeQuery("INSERT INTO civicrm_newsstoreconsumed (newsstoreitem_id, newsstoresource_id) VALUES
      (1, 1), (2, 1), (3,1);", []);

    $deleted = CRM_Newsstore::deleteOldItems();
    // Should delete the ancient and the modern ones.
    $this->assertEquals(2, $deleted);
    $this->assertEquals('3', $this->fetchAllItemIds());

    // Recreate and link the deleted items.
    CRM_Core_DAO::executeQuery("INSERT INTO civicrm_newsstoreitem (id, title, timestamp, uri) VALUES
      (1, 'ancient item', '$ancient', 'http://ancient'),
      (2, 'modern item', '$modern', 'http://modern')
      ;", []);
    // Relink them and also link everything to the second feed which has a 20 day retention.
    CRM_Core_DAO::executeQuery("INSERT INTO civicrm_newsstoreconsumed (newsstoreitem_id, newsstoresource_id) VALUES
      (1, 1), (2, 1),
      (1, 2), (2, 2), (3, 2)
      ;", []);
    $deleted = CRM_Newsstore::deleteOldItems();
    // Should delete just the ancient one.
    $this->assertEquals(1, $deleted);
    $this->assertEquals('2,3', $this->fetchAllItemIds());

    $deleted = CRM_Newsstore::deleteOldItems();
    // Should not have deleted anything.
    $this->assertEquals(0, $deleted);
    $this->assertEquals('2,3', $this->fetchAllItemIds());

    // Clean up.
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_newsstoreitem;', []);
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_newsstoresource;', []);
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_newsstoreconsumed;', []);

  }
  /**
   * DRY code, used in testRetentionSimple.
   *
   * Returns ordered list of item ids as a string like "1,2"
   *
   * @return string
   */
  protected function fetchAllItemIds() {
    return implode(',', array_values(CRM_Core_DAO::executeQuery("SELECT id FROM civicrm_newsstoreitem ORDER BY id", [])->fetchMap('id', 'id')));
  }


}

