DROP TABLE IF EXISTS `civicrm_newssource`;

-- /*******************************************************
-- *
-- * civicrm_newssource
-- *
-- * FIXME
-- *
-- *******************************************************/
CREATE TABLE `civicrm_newssource` (


     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique NewsSource ID',
     `name` varchar(255) NOT NULL   COMMENT 'Human name to identify this NewsSource used in administrative interface.',
     `uri` varchar(1024) NOT NULL   COMMENT 'A URI used by the class implementing fetch to identify the source. e.g. for an RSS source it might be https://mysite.com/rss.xml',
     `type` varchar(255) NOT NULL   COMMENT 'The name of the implementing class. Different classes are availble for different types of source, e.g. RSS or Drupal Views.',
     `last_fetched` datetime NULL   COMMENT 'When this source was last fetched from.',
     `retention_days` int unsigned NOT NULL  DEFAULT 30 COMMENT 'How many days should items be kept for?',
     `fetch_frequency` varchar(20) NOT NULL   COMMENT 'How many days should items be kept for?',
     `contact_id` int unsigned    COMMENT 'FK to Contact' 
,
    PRIMARY KEY ( `id` )
 
 
,          CONSTRAINT FK_civicrm_newssource_contact_id FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE  
)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci  ;


