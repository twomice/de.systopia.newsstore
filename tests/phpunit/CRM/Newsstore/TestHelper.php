<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

class CRM_Newsstore_TestHelper extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  /**
   * counter for unique source fixtures.
   */
  public $fixture_count = 0;

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * DRY code.
   *
   * @param string $fixture item_a|items_a_and_b|unique_item
   * Optional parameter useful when you want to call this multiple times to
   * create multiple things.
   */
  public function createOneDummySourceWithItems($fixture='item_a') {

    $vars = $this->createDummySourceFixture();
    $this->setDummySourceItemFixtures($vars->uri, $fixture);
    // Get our store and make it fetch items.
    $vars->stats = $vars->store->fetch();

    return $vars;
  }
  /**
   * Get a date.
   *
   * Because the retention_days is relative to the current date, we need the
   * test data dates to also be relative. Defaults to a week ago.
   *
   * @param int $ago Days ago.
   * @return string 2017-05-02
   */
  public function getDate($ago=7) {
    return date('Y-m-d', strtotime("today - $ago days"));
  }
  /**
   * DRY code.
   *
   * @param string $fixture item_a|items_a_and_b|unique_item
   * Optional parameter useful when you want to call this multiple times to
   * create multiple things.
   */
  public function setDummySourceItemFixtures($uri, $fixture='item_a') {
    $recent_date = $this->getDate();

    // Configure dummy news store fixture.
    switch ($fixture) {
    case 'item_a':
      CRM_Newsstore_Dummy::$raw_items[$uri] = [
        'uri1' => [
          'uri'       => 'uri1',
          'title'     => 'Title 1',
          'html'      => 'body 1',
          'teaser'    => 'teaser 1',
          'timestamp' => $recent_date,
        ]
      ];
      break;

    case 'items_a_and_b':
      CRM_Newsstore_Dummy::$raw_items[$uri] = [
        'uri1' => [
          'uri'       => 'uri1',
          'title'     => 'Title 1',
          'html'      => 'body 1',
          'teaser'    => 'teaser 1',
          'timestamp' => $recent_date,
        ],
        'uri2' => [
          'uri'       => 'uri2',
          'title'     => 'Title 2',
          'html'      => 'body 2',
          'teaser'    => 'teaser 2',
          'timestamp' => $recent_date,
        ],
      ];
      break;

    case 'unique_item':
      CRM_Newsstore_Dummy::$raw_items[$uri] = [
        'uri1' => [
          'uri'       => 'uri1',
          'title'     => 'Title 1',
          'html'      => 'body 1',
          'teaser'    => 'teaser 1',
          'timestamp' => $recent_date,
        ],
      ];
      break;

    default:
      throw new InvalidArgumentException("fixture must be specified as item_a|items_a_and_b|unique_item");
    }
  }
  /**
   * DRY code to create source fixture.
   */
  public function createDummySourceFixture() {

    $this->fixture_count++;

    // Create source fixture.
    $source = civicrm_api3('NewsStoreSource', 'create', [
      'sequential' => 1,
      'name'       => 'Test Feed ' . $this->fixture_count,
      'uri'        => "http://example.com/feed/$this->fixture_count",
      'type'       => 'Dummy',
    ]);
    $vars = (object) ['source_id' => $source['id'], 'uri' => "http://example.com/feed/$this->fixture_count"];
    $vars->source_bao = CRM_Newsstore_BAO_NewsStoreSource::findById($vars->source_id);
    $vars->store = CRM_Newsstore::factory($vars->source_bao);

    return $vars;
  }
}

