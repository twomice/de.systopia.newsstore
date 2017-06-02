<?php
/**
 * @class Dummy Source for testing.
 */

class CRM_Newsstore_Dummy extends CRM_Newsstore {

  /**
   * holds values.
   */
  public static $raw_items = [
    'the-uri' => [
      'uri'       => 'the-uri',
      'title'     => 'Title 1',
      'body'      => 'body 1',
      'teaser'    => 'teaser 1',
      'timestamp' => '2017-01-01',
    ]
  ];

  /**
   * Fetch and parse items from the source.
   *
   * @return array keyed by the URI. There are no restrictions on the type of values.
   */
  protected function fetchRawItems() {
    return static::$raw_items;
  }
}
