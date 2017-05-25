#Proposal: "NewsStore" Extension

*Björn Endres, SYSTOPIA, version 0.1, 2017-04-25*

**CiviCRM Extension to handle (backend) news items from various sources.**

## Concept

This concept is closely related to Rich Lott's "Automated RSS Mailer" proposal. The idea is to have a separate extension providing the infrastructure for dealing with consumable news items. These items can be collected from various sources (e.g. RSS feeds, Drupal data, CiviCRM internals like new Contact or Event entities, etc.). These items can then be fetched, cached, read, and consumed by any number of clients in the system. The NewsStore extension itself doesn't have to contain any client implementations.


## NewsItem entity

There should be a new CiviCRM entity called NewsItem with the following attributes:

 * ``uri``: a string uniquely identifying any news item
 * ``title``: the item's title
 * ``body``: the news item's body encoded in HTML
 * ``timestamp``: date
 * ``teaser``: an abbreviated version of the body, also HTML
 * ``ressources``: an array of URLs used in the body

## NewsSource entity

 * ``id``: unique ID
 * ``type``: some kind of reference to the implementing class
 * ``uri``: a URI defining its parameters, e.g. a RSS URL
 * ``range_from``: date field - all items between the two dates are known to the NewsStore
 * ``range_to``: see "range_from". This is logically the same as a "last fetched" value.
 * ``item_count``: number of items known to the store
 * ``new_count``: number of new (not consumend) items known to the store
 * ``retention_days``: how long should items be cached (in days).

## API

### NewsItem.get

Get (cached) NewsItems from the store.

 * ``sources``: array of NewsSource IDs [mandatory]
 * ``is_new``: boolean [default: yes])
 * ``date_from``: timestamp-based restriction of search
 * ``date_to``: timestamp-based restriction of search
 *  **returns:** list of NewsItem

### NewsItem.consume

Mark the given items as "read".

 * ``source_id``: the NewsSource ID
 * ``items``: array of NewsItem URIs.

### NewsSource.create

Generic CiviCRM-style create call, i.e. passing the ID is an update. Parameters see above: "NewsSource Entity"

### NewsSource.get

Generic CiviCRM-style get call, i.e. parameters are a search filter. Parameters see above: "NewsSource Entity"

### NewsSource.delete

Generic CiviCRM-style delete call, i.e. passing the ID deletes that NewsSource.

### NewsSource.fetch

Trigger an update of the stored/cached news items. This would most likely be called by a cron-job.

 * ``source_ids``: array of NewsSource IDs [default: all]

### NewsSource.purge

Delete old cached news items.

 * ``source_ids``: array of NewsSource IDs [default: all]
 * ``is_consumed``: if you set this to 0, all items will be purged, not just the consumed ones [default: 1]
 * ``retention_days``: only purge items older than this vallue (in days) [default: the NewStore's ``retention_days`` value]



## Config Page

There should be a backend configuration UI to manage the NewsSources and to manually fetch new items. It should also show some statistics on each source in one, ideally as an overview where you can see all sources at once.


## Architecture Remarks

In order to facilitate adding more sources there should be an abstract ``NewsSource`` class, so it can be implemented to process data from the different sources outlined above (see also the entity attributes above). The entites below don't necessarily have to be full-blown CiviCRM entities, but the API actions should be complete as it'll be a client's only interface to the NewsStore.

The storage of the items (by URI) and the information of wheter it's been consumed should be separated. This way we can have two NewsSource instances using the same cached items (i.e. same RSS feed), but being consumed by two different clients without them "stealing" each other's news. This should make storing the items in a cache only indexed by URI rather simple. For example, a second source would then fetch only the RSS, and realise that all the items are already in the cache.

It might be necessary to use the same cache for storing ressources (like images).

## Disclaimer

This is just a draft, I'm open for discussion and changes.