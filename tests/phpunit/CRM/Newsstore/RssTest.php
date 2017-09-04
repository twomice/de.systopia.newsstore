<?php
/**
 * @file
 * Tests the RSS feed parser.
 *
 */


use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

require_once __DIR__ . '/TestHelper.php';

/**
 * Test RSS feed parsing.
 *
 * @group headless
 */
class CRM_Newsstore_RssTest extends CRM_Newsstore_TestHelper {
  /**
   */
  public function testParsingSingleItem() {
    $rss = new CRM_Newsstore_Rss(NULL);

    $filename = __DIR__ . DIRECTORY_SEPARATOR . "rss-fixture-1.xml";
    $file     = file_get_contents($filename);
    $items    = $rss->parseFeed($file);

    $item     = current($items);

    // First check strip_tags is working on html.
    $this->assertEquals(
       "<p>Here is the content. <strong>Strong emphasis</strong> &amp; other HTML too.</p>\n"
      ."        alert(\"Evil script tags are not allowed\");\n"
      ."        <p>Another paragraph.</p>", trim($item['html']));

    $object   = unserialize($item['object']);
    $this->assertEquals([
      'item'                  => '',
      'item/pubDate'          => 'Tue, 14 Mar 2017 17:00:00 +0100',
      'item/title'            => 'Demo Title',
      'item/enclosure'        => '',
      'item/enclosure@url'    => 'http://example.com/image1.jpg',
      'item/enclosure@type'   => 'image/jpg',
      'item/enclosure@length' => '0',
      'item/link'             => 'http://example.com/item1',
      'item/source'           => '© Copyright notice',
      'item/source@url'       => 'http://example.com/image1.jpg',
      'item/content:encoded'  => 'Foo | Bar | Baz',
      'item/dc:creator'       => 'Wilma Flintstone',
      // Note that strip_tags is NOT called on the object data. This is left to clients.
      'item/description'      =>
       "<p>Here is the content. <strong>Strong emphasis</strong> &amp; other HTML too.</p>\n"
      ."        <script>alert(\"Evil script tags are not allowed\");</script>\n"
      ."        <p>Another paragraph.</p>",
    ], $object);
  }
  /**
   */
  public function testParsingMultipleItems() {
    $rss = new CRM_Newsstore_Rss(NULL);

    $filename = __DIR__ . DIRECTORY_SEPARATOR . "rss-fixture-2.xml";
    $file     = file_get_contents($filename);
    $items    = $rss->parseFeed($file);

    $item     = $items['http://example.com/item1'];
    $object   = unserialize($item['object']);
    $this->assertEquals([
      'item'                  => '',
      'item/pubDate'          => 'Tue, 14 Mar 2017 17:00:00 +0100',
      'item/title'            => 'Demo Title 1',
      'item/link'             => 'http://example.com/item1',
      'item/description'      => 'Item one',
    ], $object);

    $item = $items['http://example.com/item2'];
    $object = unserialize($item['object']);
    $this->assertEquals([
      'item'                  => '',
      'item/pubDate'          => 'Tue, 14 Mar 2017 17:00:00 +0100',
      'item/title'            => 'Demo Title 2',
      'item/link'             => 'http://example.com/item2',
      'item/description'      => 'Item two',
    ], $object);
  }
}
