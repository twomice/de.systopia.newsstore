<?php

require_once __DIR__ . '/TestHelper.php';

/**
 * Tests API for NewsStore extension.
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test class.
 *    Simply create corresponding functions (e.g. "hook_civicrm_post(...)" or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or test****() functions will
 *    rollback automatically -- as long as you don't manipulate schema or truncate tables.
 *    If this test needs to manipulate schema or truncate tables, then either:
 *       a. Do all that using setupHeadless() and Civi\Test.
 *       b. Disable TransactionalInterface, and handle all setup/teardown yourself.
 *
 * @group headless
 */
class CRM_Newsstore_ApiTest extends CRM_Newsstore_TestHelper
{

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
   * Test that news items are/are not fetched based on source and is_consumed.
   */
  public function testItemGetFiltersBySourceAndConsumed() {
    $fixtures = $this->createOneSourceAndItem();

    // test getting NewsItems that have not been consumed works.
    $items = civicrm_api3('NewsStoreItem', 'get', ['source' => $fixtures->source['id'], 'sequential' => 1]);
    $this->assertEquals(1, $items['count']);
    $this->assertEquals($fixtures->item['id'], $items['values'][0]['id']);

    // test getting NewsItems that have been consumed works - should not be any.
    $items = civicrm_api3('NewsStoreItem', 'get', ['source' => $fixtures->source['id'], 'is_consumed' => 1]);
    $this->assertEquals(0, $items['count']);

    // consume the item via direct API call, then retest.
    civicrm_api3('NewsStoreConsumed', 'create', ['id' => $fixtures->link['id'], 'is_consumed' => 1]);

    // should not be any unconsumed items.
    $items = civicrm_api3('NewsStoreItem', 'get', ['source' => $fixtures->source['id'], 'sequential' => 1]);
    $this->assertEquals(0, $items['count']);

    // test getting NewsItems that have been consumed works - should not be any.
    $items = civicrm_api3('NewsStoreItem', 'get', ['source' => $fixtures->source['id'], 'is_consumed' => 1, 'sequential' => 1]);
    $this->assertEquals(1, $items['count']);
    $this->assertEquals($fixtures->item['id'], $items['values'][0]['id']);

  }

  /**
   * Test that news items are/are not fetched based on source and is_consumed.
   *
   * This is similar to testItemGetFiltersBySourceAndConsumed() except that
   * this tests the GetWithUsage API action which is capable of returning the
   * is_consumed and NewsStoreConsumed.id fields, rather than just a filtered set.
   */
  public function testItemGetWithUsage() {
    $fixtures = $this->createOneSourceAndItem();

    $items = civicrm_api3('NewsStoreItem', 'GetWithUsage', ['source' => $fixtures->source['id'], 'sequential' => 1]);
    $this->assertEquals(1, $items['count']);
    $this->assertEquals($fixtures->item['id'], $items['values'][0]['id']);
    $this->assertEquals(0, $items['values'][0]['is_consumed']);
    $this->assertEquals($fixtures->link['id'], $items['values'][0]['newsstoreconsumed_id']);

    // Consume the item via direct API call, then retest.
    civicrm_api3('NewsStoreConsumed', 'create', ['id' => $fixtures->link['id'], 'is_consumed' => 1]);

    $items = civicrm_api3('NewsStoreItem', 'GetWithUsage', ['source' => $fixtures->source['id'], 'sequential' => 1]);
    $this->assertEquals(1, $items['count']);
    $this->assertEquals($fixtures->item['id'], $items['values'][0]['id']);
    $this->assertEquals(1, $items['values'][0]['is_consumed']);
    $this->assertEquals($fixtures->link['id'], $items['values'][0]['newsstoreconsumed_id']);

  }

  /**
   * Test that we can fetch multiple sources in one call.
   */
  public function testSourceFetchMultipleSources() {

    // Create two sources and configure them such that the second includes one
    // of the items in the first, and one of its own.
    $vars_1 = $this->createDummySourceFixture();
    $this->setDummySourceItemFixtures($vars_1->uri, 'item_a');
    $vars_2 = $this->createDummySourceFixture();
    $this->setDummySourceItemFixtures($vars_2->uri, 'items_a_and_b');

    // Fetching these results we expect that:
    // Nothing is old (since no items have been fetched.)
    // The first source is fetched and loads item_a: 1 new.
    // The second source is fetched and loads items a and b: 1 new link, 1 new.
    // Total 2 new, 1 new link from 2 sources.
    //
    $result = civicrm_api3('NewsStoreSource', 'fetch', []);
    $this->assertEquals(
      ['old' => 0, 'new' => 2, 'new_link' => 1, 'sources_count' => 2],
      $result['values']);

  }

  /**
   * Helper DRY code for creating one source with one linked, unconsumed item.
   *
   * @return StdClass object with properties: source, item, link.
   */
  public function createOneSourceAndItem() {
    $source = civicrm_api3('NewsStoreSource', 'create', [
      'sequential' => 1,
      'name' => 'Test Feed',
      'uri' => 'http://example.com',
      'type' => 'dummy',
    ]);

    $this->assertGreaterThan(0, $source['id']);

    $item = civicrm_api3('NewsStoreItem', 'create', [
      'sequential' => 1,
      'uri' => 'http://example.com/item/1',
      'title' => 'test item',
    ]);
    $this->assertGreaterThan(0, $item['id']);

    // link the two.
    $link = civicrm_api3('NewsStoreConsumed', 'create', [
      'newsstoreitem_id' => $item['id'],
      'newsstoresource_id' => $source['id'],
      'is_consumed' => 0,
      'sequential' => 1,
    ]);
    $this->assertGreaterThan(0, $link['id']);

    return (object) [
      'source' => $source['values'][0],
      'item' => $item['values'][0],
      'link' => $link['values'][0],
    ];
  }

}
