<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

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
class CRM_Newsstore_ApiTest extends \PHPUnit_Framework_TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

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
   * Example: Test that a version is returned.
   */
  public function testConsumed() {
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
    ]);
    $this->assertGreaterThan(0, $link['id']);

    // test getting NewsItems that have not been consumed works.
    $items = civicrm_api3('NewsStoreItem', 'get', ['source' => $source['id'], 'sequential' => 1]);
    $this->assertEquals(1, $items['count']);
    $this->assertEquals($item['id'], $items['values'][0]['id']);

    // test getting NewsItems that have been consumed works - should not be any.
    $items = civicrm_api3('NewsStoreItem', 'get', ['source' => $source['id'], 'is_consumed' => 1]);
    $this->assertEquals(0, $items['count']);

    // consume the item via direct API call, then retest.
    civicrm_api3('NewsStoreConsumed', 'create', [
      'id' => $link['id'],
      'is_consumed' => 1,
    ]);

    // should not be any unconsumed items.
    $items = civicrm_api3('NewsStoreItem', 'get', ['source' => $source['id'], 'sequential' => 1]);
    $this->assertEquals(0, $items['count']);

    // test getting NewsItems that have been consumed works - should not be any.
    $items = civicrm_api3('NewsStoreItem', 'get', ['source' => $source['id'], 'is_consumed' => 1, 'sequential' => 1]);
    $this->assertEquals(1, $items['count']);
    $this->assertEquals($item['id'], $items['values'][0]['id']);

  }

}
