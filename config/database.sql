-- ********************************************************
-- *                                                      *
-- * IMPORTANT NOTE                                       *
-- *                                                      *
-- * Do not import this file manually but use the Contao  *
-- * install tool to create and maintain database tables! *
-- *                                                      *
-- ********************************************************


-- --------------------------------------------------------

-- 
-- Table `tl_iso_wishlist`
-- 

CREATE TABLE `tl_iso_wishlist` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `pid` int(10) unsigned NOT NULL default '0',
  `tstamp` int(10) unsigned NOT NULL default '0',
  `session` varchar(64) NOT NULL default '',
  `store_id` int(2) unsigned NOT NULL default '0',
  `settings` blob NULL,
  PRIMARY KEY  (`id`),
  KEY `pid` (`pid`, `store_id`),
  KEY `session` (`session`, `store_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

-- 
-- Table `tl_iso_wishlist_items`
-- 

CREATE TABLE `tl_iso_wishlist_items` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `pid` int(10) unsigned NOT NULL default '0',
  `tstamp` int(10) unsigned NOT NULL default '0',
  `product_id` int(10) unsigned NOT NULL default '0',
  `product_sku` varchar(128) NOT NULL default '',
  `product_name` varchar(255) NOT NULL default '',
  `product_options` blob NULL,
  `product_quantity` int(10) unsigned NOT NULL default '0',
  `price` decimal(12,2) NOT NULL default '0.00',
  `tax_id` varchar(32) NOT NULL default '',
  `href_reader` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `pid` (`pid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

-- 
-- Table `tl_module`
-- 

CREATE TABLE `tl_module` (
  `iso_wishlist_jumpTo` int(10) unsigned NOT NULL default '0',
  `iso_wishlist_form` int(10) unsigned NOT NULL default '0',
  `iso_wishlist_recipient` varchar(255) NOT NULL default ''
  `iso_wishlist_clearList` char(1) NOT NULL default '',
  `iso_wishlist_definedRecipients` varchar(255) NOT NULL default '',
  `iso_wishlist_recipientFromFormField` char(1) NOT NULL default '',
  `iso_wishlist_formField` int(10) unsigned NOT NULL default '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

