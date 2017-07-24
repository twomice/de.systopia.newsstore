<?php
/**
 * Class to implement RSS fetching.
 */
class CRM_Newsstore_Rss extends CRM_Newsstore
{
  /**
   * Namespaces cache.
   */
  protected $namespaces;

  /**
   * Fetch and parse items from the source.
   *
   * @return array keyed by the URI. There are no restrictions on the type of values.
   */
  protected function fetchRawItems() {

    if (empty($this->source->uri)) {
      throw new Exception("Missing URI for RSS feed");
    }

    list($status, $result) = CRM_Utils_HttpClient::singleton()->get($this->source->uri);
    if ($status != CRM_Utils_HttpClient::STATUS_OK) {
      throw new Exception("Failed to fetch feed.");
    }

    $items = $this->parseFeed($result);
    return $items;
  }
  /**
   * Parse the feed.
   *
   * @param string $raw_feed
   * @return array of items keyed by URI ('link') with the following keys:
   * - uri
   * - title
   * - html
   * - teaser
   * - object
   * - timestamp
   */
  public function parseFeed($raw_feed) {
    if (!($feed = simplexml_load_string($raw_feed))) {
      throw new Exception("Failed to parse feed with simplexml_load_string.");
    }
    $this->namespaces = $feed->getDocNamespaces(TRUE);

    $items = [];
    foreach ($feed->channel->item as $item) {
      $uri = (string) $item->link;
      $items[$uri] = [
        'uri'       => $uri,
        'title'     => (string) $item->title,
        'html'      => (string) $item->description,
        'timestamp' => date('Y-m-d H:i:s', strtotime((string) $item->pubDate)),
      ];

      // Create teaser.
      $teaser = html_entity_decode(strip_tags((string) $item->description));
      $strlen = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
      $substr = function_exists('mb_substr') ? 'mb_substr' : 'substr';
      if ($strlen($teaser) > 300) {
        $teaser = $substr($teaser, 0, 300) . '...';
      }
      $items[$uri]['teaser'] = $teaser;

      // Parse the feed items into a serialized array, ready for storage.
      $parsed_item = [];
      $this->parseNode($parsed_item, $item);
      $items[$uri]['object'] = serialize($parsed_item);

    }
    return $items;
  }
  /**
   * Parse an XML node into a flat array.
   *
   *
   *
   * @param array &$out Array to append to.
   * @param SimpleXMLElement $node
   * @param string $prefix
   */
  public function parseNode(&$out, $node, $prefix='', $ns='') {

    $prefix .= ($ns ? "$ns:" : '') . $node->getName();

    // Store text content.
    $out[$prefix] = trim((string) $node);

    // Store any attributes.
    foreach ($node->attributes() as $attr=>$val) {
      $out["$prefix@$attr"] = (string) $val;
    }

    // Process child nodes.
    foreach ($node->children() as $child_node) {
      $this->parseNode($out, $child_node, "$prefix/");
    }

    // Process child nodes of other namespaces declared in the document.
    foreach ($this->namespaces as $ns_prefix => $namespace) {
      foreach ($node->children($namespace) as $child_node) {
        $this->parseNode($out, $child_node, "$prefix/", $ns_prefix);
      }
    }
  }

}
