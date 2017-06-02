<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * Tests fetching items.
 *
 * @group headless
 */
class CRM_Newsstore_FetchTest extends \PHPUnit_Framework_TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

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
    $vars = $this->createDummySourceWithOneItem();

    // We expect 1 new item.
    $this->assertEquals(['old' => 0, 'new' => 1, 'new_link' => 0], $vars->stats);

    // There should now be one item for this source.
    $items = CRM_Newsstore_BAO_NewsStoreItem::apiGetWithUsage(['source' => $vars->source_id]);
    $this->assertCount(1, $items);

    // The item should have been properly saved.
    $item = reset($items);
    $this->assertEquals('uri1', $item['uri']);
    $this->assertEquals('Title 1', $item['title']);
    $this->assertEquals('body 1', $item['body']);
    $this->assertEquals('teaser 1', $item['teaser']);
    $this->assertEquals('2017-01-01 00:00:00', $item['timestamp']);

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
    CRM_Newsstore_Dummy::$raw_items = [];

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
    $vars = $this->createDummySourceWithOneItem();

    // Now reconfigure dummy news store fixture with another row of data.
    CRM_Newsstore_Dummy::$raw_items = [
      'uri1' => [
        'uri'       => 'uri1',
        'title'     => 'changed',
        'body'      => 'changed',
        'teaser'    => 'changed',
        'timestamp' => '2017-01-02',
      ],
      'uri2' => [
        'uri'       => 'uri2',
        'title'     => 'Title 2',
        'body'      => 'body 2',
        'teaser'    => 'teaser 2',
        'timestamp' => '2017-01-02',
      ],
    ];

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
    $this->assertEquals('body 1', $item['body']);
    $this->assertEquals('teaser 1', $item['teaser']);
    $this->assertEquals('2017-01-01 00:00:00', $item['timestamp']);

    // The new item should have been saved.
    $item = $items_keyed['uri2'];
    $this->assertEquals('uri2', $item['uri']);
    $this->assertEquals('Title 2', $item['title']);
    $this->assertEquals('body 2', $item['body']);
    $this->assertEquals('teaser 2', $item['teaser']);
    $this->assertEquals('2017-01-02 00:00:00', $item['timestamp']);

    // Run fetch again.
    $stats = $vars->store->fetch();
  }

  /**
   * If an item is already loaded, but was not loaded for this source,
   * we need to add a NewsStoreConsumed link to the source, but not download it again.
   */
  public function testFetchExistingItemsToNewSource() {

    $now = date('Y-m-d H:i:s');
    $vars = $this->createDummySourceWithOneItem();

    // Create second source fixture.
    $source_2 = civicrm_api3('NewsStoreSource', 'create', [
      'sequential' => 1,
      'name' => 'Second source',
      'uri' => 'http://example.com/2',
      'type' => 'Dummy',
    ]);
    $source_bao_2 = CRM_Newsstore_BAO_NewsStoreSource::findById($source_2['id']);
    $store_2 = CRM_Newsstore::factory($source_bao_2);
    $stats = $store_2->fetch();

    // We expect 1 new item (even though it was known to another source, it is new to us), 0 old.
    $this->assertEquals(['old' => 0, 'new' => 0, 'new_link' => 1], $stats);

    $stats = $store_2->fetch();
    $this->assertEquals(['old' => 1, 'new' => 0, 'new_link' => 0], $stats);

  }

  /**
   * DRY code.
   */
  public function createDummySourceWithOneItem() {

    $vars = $this->createDummySourceFixture();

    // Configure dummy news store fixture.
    CRM_Newsstore_Dummy::$raw_items = [
      'uri1' => [
        'uri'       => 'uri1',
        'title'     => 'Title 1',
        'body'      => 'body 1',
        'teaser'    => 'teaser 1',
        'timestamp' => '2017-01-01',
      ]
    ];

    // Get our store and make it fetch items.
    $vars->stats = $vars->store->fetch();

    return $vars;
  }
  /**
   * DRY code to create source fixture.
   */
  public function createDummySourceFixture() {

    // Create source fixture.
    $source = civicrm_api3('NewsStoreSource', 'create', [
      'sequential' => 1,
      'name' => 'Test Feed',
      'uri' => 'http://example.com',
      'type' => 'Dummy',
    ]);
    $vars = (object) ['source_id' => $source['id']];
    $vars->source_bao = CRM_Newsstore_BAO_NewsStoreSource::findById($vars->source_id);
    $vars->store = CRM_Newsstore::factory($vars->source_bao);

    return $vars;
  }
}

