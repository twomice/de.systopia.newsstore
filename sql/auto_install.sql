DROP TABLE IF EXISTS `civicrm_newsstoreconsumed`;
DROP TABLE IF EXISTS `civicrm_newsstoreitem`;
DROP TABLE IF EXISTS `civicrm_newsstoresource`;

-- /*******************************************************
-- *
-- * civicrm_newsstoresource
-- *
-- * Manages a particular NewsStore, e.g. a particular RSS feed. Part of the NewsStore extension.
-- *
-- *******************************************************/
CREATE TABLE `civicrm_newsstoresource` (


     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique NewsStoreSource ID',
     `name` varchar(255) NOT NULL   COMMENT 'Human name to identify this NewsStoreSource used in administrative interface.',
     `uri` varchar(1024) NOT NULL   COMMENT 'A URI used by the class implementing fetch to identify the source. e.g. for an RSS source it might be https://mysite.com/rss.xml',
     `type` varchar(255) NOT NULL   COMMENT 'The name of the implementing class. Different classes are availble for different types of source, e.g. RSS or Drupal Views.',
     `last_fetched` datetime NULL   COMMENT 'When this source was last fetched from.',
     `retention_days` int unsigned NOT NULL  DEFAULT 30 COMMENT 'How many days should items be kept for?',
     `fetch_frequency` varchar(20) NOT NULL  DEFAULT 'off' COMMENT 'How many days should items be kept for?' 
,
    PRIMARY KEY ( `id` )
)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci  ;

-- /*******************************************************
-- *
-- * civicrm_newsstoreitem
-- *
-- * Represents a single item fetched by a NewsStoreSource, part of the NewsStore extension.
-- *
-- *******************************************************/
CREATE TABLE `civicrm_newsstoreitem` (


     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique NewsStoreItem ID',
     `uri` varchar(512) NOT NULL   COMMENT 'Unique URI for this news item. Typically a link to a web page.',
     `title` varchar(255) NOT NULL   COMMENT 'Title of item',
     `body` longtext    COMMENT 'The main body content of the item.',
     `teaser` longtext    COMMENT 'Short summary of the content.',
     `timestamp` timestamp NOT NULL   COMMENT 'Either the date published or the date fetched, if published date missing or not relevant.' 
,
    PRIMARY KEY ( `id` )
 
)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci  ;

-- /*******************************************************
-- *
-- * civicrm_newsstoreconsumed
-- *
-- * Maps between NewsStoreSource and NewsStoreItem recording which items have been consumed by which source.
-- *
-- *******************************************************/
CREATE TABLE `civicrm_newsstoreconsumed` (


     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique NewsStoreConsumed ID',
     `newsstoresource_id` int unsigned NOT NULL   COMMENT 'Foreign key identifying a NewsStoreSource',
     `newsstoreitem_id` int unsigned NOT NULL   COMMENT 'Foreign key identifying a NewsStoreItem',
     `is_consumed` tinyint NOT NULL  DEFAULT 0 COMMENT 'True means the NewsStoreSource has consumed the NewsStoreItem.' 
,
    PRIMARY KEY ( `id` )
 
    ,     UNIQUE INDEX `sourceitem`(
        newsstoresource_id
      , newsstoreitem_id
  )
  
,          CONSTRAINT FK_civicrm_newsstoreconsumed_newsstoresource_id FOREIGN KEY (`newsstoresource_id`) REFERENCES `civicrm_newsstoresource`(`id`) ON DELETE CASCADE,          CONSTRAINT FK_civicrm_newsstoreconsumed_newsstoreitem_id FOREIGN KEY (`newsstoreitem_id`) REFERENCES `civicrm_newsstoreitem`(`id`) ON DELETE CASCADE  
)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci  ;

