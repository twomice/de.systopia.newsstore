<?php
/**
 * Class to implement RSS fetching.
 */
class CRM_Newsstore_Rss extends CRM_Newsstore
{
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

    if (!($feed = simplexml_load_string($result))) {
      throw new Exception("Failed to parse feed.");
    }

    $items = [];

    foreach ($feed->channel->item as $item) {
      $uri = (string) $item->link;
      $items[$uri] = [
        'uri' => $uri,
        'title' => (string) $item->title,
        'body' => (string) $item->description,
        'timestamp'  => date('Y-m-d H:i:s', strtotime((string) $item->pubDate)),
      ];
      // Create teaser.
      $teaser = html_entity_decode(strip_tags((string) $item->description));
      $strlen = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
      $substr = function_exists('mb_substr') ? 'mb_substr' : 'substr';
      if ($strlen($teaser) > 300) {
        $teaser = $substr($teaser, 0, 300) . '...';
      }
      $items[$uri]['teaser'] = $teaser;
    }

    return $items;
  }
}
