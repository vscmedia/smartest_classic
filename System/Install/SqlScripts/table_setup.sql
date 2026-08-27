-- Smartest Table Setup, Schema version 24

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

-- --------------------------------------------------------

--
-- Table structure for table `AssetClasses`
--

CREATE TABLE `AssetClasses` (
  `assetclass_id` int NOT NULL AUTO_INCREMENT,
  `assetclass_name` varchar(32) NOT NULL DEFAULT '',
  `assetclass_label` varchar(32) NOT NULL DEFAULT 'Untitled Asset Class',
  `assetclass_site_id` int NOT NULL DEFAULT '1',
  `assetclass_is_sitewide` tinyint(1) NOT NULL DEFAULT '0',
  `assetclass_shared` tinyint(1) NOT NULL DEFAULT '1',
  `assetclass_type` varchar(64) NOT NULL DEFAULT '',
  `assetclass_info` text,
  `assetclass_parent_id` int unsigned DEFAULT NULL,
  `assetclass_update_on_page_publish` tinyint(1) NOT NULL DEFAULT '1',
  `assetclass_filter_type` varchar(64) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT 'SM_ASSETCLASS_FILTERTYPE_NONE',
  `assetclass_filter_value` varchar(64) NOT NULL DEFAULT '',
  `assetclass_is_system` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`assetclass_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------

--
-- Table structure for table `AssetIdentifiers`
--

CREATE TABLE `AssetIdentifiers` (
  `assetidentifier_id` int unsigned NOT NULL AUTO_INCREMENT,
  `assetidentifier_draft_asset_id` int unsigned DEFAULT NULL,
  `assetidentifier_live_asset_id` int unsigned DEFAULT NULL,
  `assetidentifier_assetclass_id` int unsigned NOT NULL DEFAULT '0',
  `assetidentifier_instance_name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'default',
  `assetidentifier_platform` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'none',
  `assetidentifier_page_id` mediumint unsigned NOT NULL DEFAULT '0',
  `assetidentifier_item_id` mediumint unsigned DEFAULT NULL,
  `assetidentifier_block_id` int DEFAULT NULL,
  `assetidentifier_user_id` int DEFAULT NULL,
  `assetidentifier_tag_id` int DEFAULT NULL,
  `assetidentifier_site_id` mediumint DEFAULT NULL,
  `assetidentifier_draft_render_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `assetidentifier_live_render_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `assetidentifier_language` varchar(8) NOT NULL DEFAULT 'eng',
  PRIMARY KEY (`assetidentifier_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Assets`
--

CREATE TABLE `Assets` (
  `asset_id` int unsigned NOT NULL AUTO_INCREMENT,
  `asset_webid` varchar(36) NOT NULL DEFAULT '',
  `asset_label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'Unlabelled Asset',
  `asset_stringid` varchar(64) NOT NULL DEFAULT '',
  `asset_url` varchar(255) NOT NULL DEFAULT '',
  `asset_type` varchar(64) NOT NULL DEFAULT '',
  `asset_language` varchar(8) NOT NULL DEFAULT 'eng',
  `asset_site_id` mediumint NOT NULL DEFAULT '1',
  `asset_user_id` int unsigned NOT NULL DEFAULT '1',
  `asset_created` int NOT NULL DEFAULT '0',
  `asset_modified` int NOT NULL DEFAULT '0',
  `asset_shared` tinyint(1) NOT NULL DEFAULT '0',
  `asset_deleted` tinyint(1) NOT NULL DEFAULT '0',
  `asset_fragment_id` mediumint unsigned DEFAULT '0',
  `asset_parent_id` int DEFAULT NULL,
  `asset_variant_name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `asset_variant_label` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `asset_model_id` int DEFAULT NULL,
  `asset_thumbnail_id` int DEFAULT NULL,
  `asset_parameter_defaults` varchar(255) NOT NULL DEFAULT '',
  `asset_info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `asset_is_held` tinyint(1) NOT NULL DEFAULT '0',
  `asset_held_by` mediumint NOT NULL DEFAULT '0',
  `asset_is_approved` tinyint(1) NOT NULL DEFAULT '0',
  `asset_is_subbed` tinyint(1) NOT NULL DEFAULT '0',
  `asset_is_archived` tinyint(1) NOT NULL DEFAULT '0',
  `asset_submitted_from_public` tinyint(1) NOT NULL DEFAULT '0',
  `asset_public_status_trusted` tinyint(1) NOT NULL DEFAULT '0',
  `asset_is_system` tinyint(1) NOT NULL DEFAULT '0',
  `asset_is_hidden` tinyint(1) NOT NULL DEFAULT '0',
  `asset_search_field` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`asset_id`),
  UNIQUE KEY `Asset_webid` (`asset_webid`,`asset_stringid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Blocks`
--

CREATE TABLE `Blocks` (
  `block_id` int unsigned NOT NULL AUTO_INCREMENT,
  `block_webid` varchar(64) NOT NULL DEFAULT '',
  `block_title` varchar(255) NOT NULL DEFAULT '',
  `block_name` varchar(255) NOT NULL DEFAULT '',
  `block_draft_asset_id` int DEFAULT NULL,
  `block_live_asset_id` int DEFAULT NULL,
  `block_info` text,
  `block_created` int unsigned NOT NULL DEFAULT '0',
  `block_modified` int unsigned NOT NULL DEFAULT '0',
  `block_last_published` int unsigned NOT NULL DEFAULT '0',
  `block_parent_block_id` int unsigned DEFAULT NULL,
  `block_blocklist_id` int DEFAULT NULL,
  `block_type` varchar(64) NOT NULL DEFAULT '',
  `block_status` varchar(64) NOT NULL DEFAULT '',
  `block_order_index` smallint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`block_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Comments`
--

CREATE TABLE `Comments` (
  `comment_id` mediumint NOT NULL AUTO_INCREMENT,
  `comment_object_id` mediumint DEFAULT NULL,
  `comment_type` varchar(32) NOT NULL DEFAULT '',
  `comment_status` varchar(32) NOT NULL DEFAULT '',
  `comment_author_user_id` mediumint DEFAULT NULL,
  `comment_author_name` varchar(128) NOT NULL DEFAULT '',
  `comment_author_website` varchar(128) NOT NULL DEFAULT '',
  `comment_content` text,
  `comment_language` varchar(8) NOT NULL DEFAULT 'eng',
  `comment_posted_at` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`comment_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `DropDowns`
--

CREATE TABLE `DropDowns` (
  `dropdown_id` mediumint NOT NULL AUTO_INCREMENT,
  `dropdown_name` varchar(64) NOT NULL DEFAULT '',
  `dropdown_label` varchar(64) NOT NULL DEFAULT '',
  `dropdown_datatype` varchar(64) NOT NULL DEFAULT '',
  `dropdown_language` varchar(8) NOT NULL DEFAULT 'eng',
  `dropdown_is_system` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`dropdown_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `DropDownValues`
--

CREATE TABLE `DropDownValues` (
  `dropdownvalue_id` mediumint NOT NULL AUTO_INCREMENT,
  `dropdownvalue_dropdown_id` mediumint NOT NULL DEFAULT '0',
  `dropdownvalue_order` mediumint NOT NULL DEFAULT '0',
  `dropdownvalue_label` varchar(64) NOT NULL DEFAULT '',
  `dropdownvalue_value` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`dropdownvalue_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ItemClasses`
--

CREATE TABLE `ItemClasses` (
  `itemclass_id` int unsigned NOT NULL AUTO_INCREMENT,
  `itemclass_type` varchar(32) NOT NULL DEFAULT 'SM_ITEMCLASS_MODEL',
  `itemclass_webid` varchar(36) NOT NULL DEFAULT '',
  `itemclass_parent_id` int unsigned DEFAULT NULL,
  `itemclass_name` varchar(40) NOT NULL DEFAULT '',
  `itemclass_plural_name` varchar(64) NOT NULL DEFAULT '',
  `itemclass_site_id` int unsigned DEFAULT NULL,
  `itemclass_shared` tinyint(1) NOT NULL DEFAULT '0',
  `itemclass_varname` varchar(64) NOT NULL DEFAULT '',
  `itemclass_class_file_checksum` varchar(32) DEFAULT NULL,
  `itemclass_default_description_property_id` int unsigned DEFAULT NULL,
  `itemclass_default_sort_property_id` int unsigned DEFAULT NULL,
  `itemclass_default_thumbnail_property_id` int unsigned DEFAULT NULL,
  `itemclass_default_date_property_id` int unsigned DEFAULT NULL,
  `itemclass_primary_property_id` int unsigned DEFAULT NULL,
  `itemclass_settings` text,
  `itemclass_blog_mode` tinyint(1) NOT NULL DEFAULT '0',
  `itemclass_uses_draft_properties` tinyint(1) NOT NULL DEFAULT '1',
  `itemclass_userid` int NOT NULL DEFAULT '0',
  `itemclass_rating_max_score` smallint NOT NULL DEFAULT '5',
  `itemclass_is_system` tinyint(1) NOT NULL DEFAULT '0',
  `itemclass_is_hidden` tinyint(1) NOT NULL DEFAULT '0',
  `itemclass_created_from_buildkit` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`itemclass_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ItemProperties`
--

CREATE TABLE `ItemProperties` (
  `itemproperty_id` int unsigned NOT NULL AUTO_INCREMENT,
  `itemproperty_webid` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `itemproperty_name` varchar(32) NOT NULL DEFAULT '',
  `itemproperty_varname` varchar(32) NOT NULL DEFAULT '',
  `itemproperty_required` varchar(16) NOT NULL DEFAULT 'FALSE',
  `itemproperty_datatype` varchar(32) NOT NULL DEFAULT 'SM_DATATYPE_SL_TEXT',
  `itemproperty_foreign_key_filter` varchar(128) DEFAULT NULL,
  `itemproperty_itemclass_id` int NOT NULL DEFAULT '0',
  `itemproperty_defaultvalue` varchar(100) NOT NULL DEFAULT '',
  `itemproperty_defaultformat` varchar(32) DEFAULT NULL,
  `itemproperty_info` text,
  `itemproperty_share_values_autocomplete` tinyint(1) NOT NULL DEFAULT '0',
  `itemproperty_option_set_type` varchar(64) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT 'SM_PROPERTY_FILTERTYPE_NONE',
  `itemproperty_option_set_id` int NOT NULL DEFAULT '0',
  `itemproperty_order_index` int DEFAULT NULL,
  `itemproperty_storage_migrated` tinyint(1) NOT NULL DEFAULT '0',
  `itemproperty_last_regularized` int DEFAULT NULL,
  PRIMARY KEY (`itemproperty_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ItemPropertyValues`
--

CREATE TABLE `ItemPropertyValues` (
  `itempropertyvalue_id` int unsigned NOT NULL AUTO_INCREMENT,
  `itempropertyvalue_item_id` int NOT NULL DEFAULT '0',
  `itempropertyvalue_property_id` int NOT NULL DEFAULT '0',
  `itempropertyvalue_name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `itempropertyvalue_parent_value_id` int NOT NULL DEFAULT '0',
  `itempropertyvalue_draft_content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `itempropertyvalue_content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `itempropertyvalue_draft_info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `itempropertyvalue_live_info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `itempropertyvalue_language` varchar(8) NOT NULL DEFAULT 'eng',
  PRIMARY KEY (`itempropertyvalue_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Items`
--

CREATE TABLE `Items` (
  `item_id` int NOT NULL AUTO_INCREMENT,
  `item_webid` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `item_itemclass_id` int unsigned NOT NULL DEFAULT '0',
  `item_site_id` int unsigned NOT NULL DEFAULT '0',
  `item_shared` tinyint unsigned NOT NULL DEFAULT '0',
  `item_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `item_slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `item_type` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'SM_ITEMTYPE_NORMAL',
  `item_alt_title_tag` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `item_use_alt_title_tag` tinyint(1) NOT NULL DEFAULT '0',
  `item_public` varchar(16) NOT NULL DEFAULT 'FALSE',
  `item_parent_id` int DEFAULT NULL,
  `item_metapage_id` int unsigned NOT NULL DEFAULT '0',
  `item_search_field` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `item_is_held` tinyint(1) NOT NULL DEFAULT '0',
  `item_held_by` int NOT NULL DEFAULT '0',
  `item_deleted` tinyint(1) NOT NULL DEFAULT '0',
  `item_created` int NOT NULL DEFAULT '0',
  `item_modified` int NOT NULL DEFAULT '0',
  `item_last_published` int NOT NULL DEFAULT '0',
  `item_changes_approved` tinyint(1) NOT NULL DEFAULT '0',
  `item_createdby_userid` int NOT NULL DEFAULT '0',
  `item_createdat_ip` varchar(44) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0.0.0.0',
  `item_is_archived` tinyint(1) NOT NULL DEFAULT '0',
  `item_submitted_from_public` tinyint(1) NOT NULL DEFAULT '0',
  `item_public_status_trusted` tinyint(1) NOT NULL DEFAULT '0',
  `item_language` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'eng',
  `item_order_index` int DEFAULT NULL,
  `item_num_hits` bigint NOT NULL DEFAULT '0',
  `item_num_ratings` int NOT NULL DEFAULT '0',
  `item_average_rating` float NOT NULL DEFAULT '4',
  PRIMARY KEY (`item_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Lists`
--

CREATE TABLE `Lists` (
  `list_id` int NOT NULL AUTO_INCREMENT,
  `list_name` varchar(32) NOT NULL DEFAULT '',
  `list_title` varchar(64) DEFAULT '',
  `list_draft_header_image_id` int DEFAULT NULL,
  `list_live_header_image_id` int DEFAULT NULL,
  `list_type` varchar(32) NOT NULL DEFAULT 'SM_LIST_SIMPLE',
  `list_draft_set_id` int unsigned NOT NULL DEFAULT '0',
  `list_live_set_id` int unsigned NOT NULL DEFAULT '0',
  `list_draft_set_filter` varchar(64) NOT NULL DEFAULT '',
  `list_live_set_filter` varchar(64) NOT NULL DEFAULT '',
  `list_draft_secondary_set_id` int DEFAULT NULL,
  `list_live_secondary_set_id` int DEFAULT NULL,
  `list_draft_template_file` varchar(64) NOT NULL DEFAULT 'default_list.tpl',
  `list_live_template_file` varchar(64) NOT NULL DEFAULT 'default_list.tpl',
  `list_maximum_length` int NOT NULL DEFAULT '0',
  `list_page_id` mediumint NOT NULL DEFAULT '0',
  `list_item_id` mediumint DEFAULT NULL,
  `list_global` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`list_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ManyToManyLookups`
--

CREATE TABLE `ManyToManyLookups` (
  `mtmlookup_id` int unsigned NOT NULL AUTO_INCREMENT,
  `mtmlookup_type` varchar(64) NOT NULL DEFAULT '',
  `mtmlookup_instance_name` varchar(64) NOT NULL DEFAULT '',
  `mtmlookup_context_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `mtmlookup_order_index` mediumint DEFAULT NULL,
  `mtmlookup_order_index_2` mediumint DEFAULT NULL,
  `mtmlookup_order_index_3` mediumint DEFAULT NULL,
  `mtmlookup_order_index_4` mediumint DEFAULT NULL,
  `mtmlookup_status_flag` varchar(64) NOT NULL DEFAULT 'SM_MTMLOOKUPSTATUS_LIVE',
  `mtmlookup_entity_1_foreignkey` mediumint NOT NULL DEFAULT '0',
  `mtmlookup_entity_2_foreignkey` mediumint NOT NULL DEFAULT '0',
  `mtmlookup_entity_3_foreignkey` mediumint NOT NULL DEFAULT '0',
  `mtmlookup_entity_4_foreignkey` mediumint NOT NULL DEFAULT '0',
  PRIMARY KEY (`mtmlookup_id`),
  KEY `mtmlookup_type` (`mtmlookup_type`),
  KEY `mtmlookup_entity_1_foreignkey` (`mtmlookup_entity_1_foreignkey`),
  KEY `mtmlookup_entity_2_foreignkey` (`mtmlookup_entity_2_foreignkey`),
  KEY `mtmlookup_entity_3_foreignkey` (`mtmlookup_entity_3_foreignkey`),
  KEY `mtmlookup_entity_4_foreignkey` (`mtmlookup_entity_4_foreignkey`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `PageLayoutPresetDefinitions`
--

CREATE TABLE `PageLayoutPresetDefinitions` (
  `plpd_id` mediumint NOT NULL AUTO_INCREMENT,
  `plpd_preset_id` mediumint NOT NULL DEFAULT '0',
  `plpd_element_type` varchar(32) NOT NULL DEFAULT '',
  `plpd_element_id` mediumint NOT NULL DEFAULT '0',
  `plpd_element_value` varchar(255) NOT NULL DEFAULT '0',
  `plpd_template_id` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`plpd_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `PageLayoutPresets`
--

CREATE TABLE `PageLayoutPresets` (
  `plp_id` mediumint NOT NULL AUTO_INCREMENT,
  `plp_site_id` int unsigned NOT NULL DEFAULT '0',
  `plp_shared` tinyint(1) NOT NULL DEFAULT '0',
  `plp_label` varchar(64) NOT NULL DEFAULT '',
  `plp_master_template_name` varchar(64) NOT NULL DEFAULT '',
  `plp_created_by_user_id` mediumint NOT NULL DEFAULT '0',
  `plp_orig_from_page_id` mediumint NOT NULL DEFAULT '0',
  PRIMARY KEY (`plp_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `PageProperties`
--

CREATE TABLE `PageProperties` (
  `pageproperty_id` mediumint NOT NULL AUTO_INCREMENT,
  `pageproperty_site_id` mediumint NOT NULL DEFAULT '0',
  `pageproperty_is_sitewide` tinyint(1) NOT NULL DEFAULT '0',
  `pageproperty_name` varchar(64) NOT NULL DEFAULT '',
  `pageproperty_label` varchar(64) NOT NULL DEFAULT '',
  `pageproperty_type` varchar(64) NOT NULL DEFAULT '',
  `pageproperty_foreign_key_filter` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`pageproperty_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `PagePropertyValues`
--

CREATE TABLE `PagePropertyValues` (
  `pagepropertyvalue_id` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `pagepropertyvalue_page_id` mediumint unsigned NOT NULL DEFAULT '0',
  `pagepropertyvalue_item_id` int unsigned DEFAULT NULL,
  `pagepropertyvalue_site_id` mediumint DEFAULT NULL,
  `pagepropertyvalue_pageproperty_id` mediumint unsigned NOT NULL DEFAULT '0',
  `pagepropertyvalue_live_value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `pagepropertyvalue_draft_value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `pagepropertyvalue_language` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'eng',
  PRIMARY KEY (`pagepropertyvalue_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Pages`
--

CREATE TABLE `Pages` (
  `page_id` int NOT NULL AUTO_INCREMENT,
  `page_webid` varchar(36) NOT NULL DEFAULT '',
  `page_site_id` mediumint unsigned NOT NULL DEFAULT '0',
  `page_dataset_id` mediumint DEFAULT NULL,
  `page_name` varchar(64) NOT NULL DEFAULT '',
  `page_title` varchar(48) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'Untitled Page',
  `page_icon_image` varchar(64) NOT NULL DEFAULT '',
  `page_icon_image_id` int DEFAULT NULL,
  `page_parent` int NOT NULL DEFAULT '0',
  `page_order_index` int unsigned NOT NULL DEFAULT '1',
  `page_search_field` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `page_is_held` tinyint(1) NOT NULL DEFAULT '0',
  `page_held_by` int DEFAULT NULL,
  `page_is_section` tinyint NOT NULL DEFAULT '0',
  `page_live_template` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `page_draft_template` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `page_type` varchar(64) NOT NULL DEFAULT 'NORMAL',
  `page_force_static_title` tinyint(1) NOT NULL DEFAULT '0',
  `page_deleted` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'FALSE',
  `page_cache_as_html` varchar(5) NOT NULL DEFAULT 'TRUE',
  `page_cache_interval` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PERMANENT',
  `page_created` int NOT NULL DEFAULT '0',
  `page_modified` int NOT NULL DEFAULT '0',
  `page_changes_approved` tinyint(1) NOT NULL DEFAULT '0',
  `page_last_built` int DEFAULT NULL,
  `page_last_published` int NOT NULL DEFAULT '0',
  `page_is_published` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'FALSE',
  `page_keywords` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `page_meta_description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `page_description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `page_createdby_userid` int NOT NULL DEFAULT '0',
  `page_info` text,
  PRIMARY KEY (`page_id`),
  KEY `page_webid` (`page_webid`),
  KEY `page_name` (`page_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `PageUrls`
--

CREATE TABLE `PageUrls` (
  `pageurl_id` mediumint NOT NULL AUTO_INCREMENT,
  `pageurl_name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pageurl_page_id` mediumint NOT NULL DEFAULT '0',
  `pageurl_item_id` int NOT NULL DEFAULT '0',
  `pageurl_site_id` int NOT NULL DEFAULT '0',
  `pageurl_asset_id` int NOT NULL DEFAULT '0',
  `pageurl_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pageurl_type` varchar(64) NOT NULL DEFAULT 'SM_PAGEURL_NORMAL',
  `pageurl_redirect_type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pageurl_destination` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pageurl_is_default` tinyint(1) NOT NULL DEFAULT '0',
  `pageurl_num_hits` int unsigned NOT NULL DEFAULT '0',
  `pageurl_language` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'eng',
  `pageurl_expires` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`pageurl_id`),
  KEY `pageurl_page_id` (`pageurl_page_id`),
  KEY `pageurl_url` (`pageurl_url`(191))
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Roles`
--

CREATE TABLE `Roles` (
  `role_id` int unsigned NOT NULL AUTO_INCREMENT,
  `role_label` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `usergroup_name` (`role_label`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `RolesTokensLookup`
--

CREATE TABLE `RolesTokensLookup` (
  `rtlookup_id` int NOT NULL AUTO_INCREMENT,
  `rtlookup_token_id` int NOT NULL DEFAULT '0',
  `rtlookup_role_id` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`rtlookup_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `SetRules`
--

CREATE TABLE `SetRules` (
  `setrule_id` int NOT NULL AUTO_INCREMENT,
  `setrule_set_id` int NOT NULL DEFAULT '0',
  `setrule_label` varchar(64) NOT NULL DEFAULT '',
  `setrule_itemproperty_id` varchar(32) NOT NULL DEFAULT '0',
  `setrule_operator` varchar(64) NOT NULL DEFAULT '',
  `setrule_value` text,
  PRIMARY KEY (`setrule_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Sets`
--

CREATE TABLE `Sets` (
  `set_id` int NOT NULL AUTO_INCREMENT,
  `set_webid` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `set_name` varchar(64) NOT NULL DEFAULT '',
  `set_label` varchar(64) NOT NULL DEFAULT '',
  `set_cover_asset_id` int DEFAULT NULL,
  `set_itemclass_id` int NOT NULL DEFAULT '0',
  `set_parent_node_id` int DEFAULT NULL,
  `set_site_id` int unsigned NOT NULL DEFAULT '0',
  `set_shared` int unsigned NOT NULL DEFAULT '0',
  `set_is_system` tinyint(1) NOT NULL DEFAULT '0',
  `set_is_hidden` tinyint(1) NOT NULL DEFAULT '0',
  `set_data_source_site_id` varchar(16) NOT NULL DEFAULT 'ALL',
  `set_type` varchar(32) NOT NULL DEFAULT 'DYNAMIC',
  `set_sort_field` varchar(32) NOT NULL DEFAULT '',
  `set_sort_direction` varchar(4) NOT NULL DEFAULT 'ASC',
  `set_feed_sort_field` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `set_feed_sort_direction` varchar(4) NOT NULL DEFAULT 'DESC',
  `set_feed_nonce` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `set_varname` varchar(64) NOT NULL DEFAULT '',
  `set_lookup_source` varchar(32) DEFAULT NULL,
  `set_filter_type` varchar(64) NOT NULL DEFAULT '',
  `set_filter_value` varchar(64) NOT NULL DEFAULT '',
  `set_info` text,
  PRIMARY KEY (`set_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `SetsItemsLookup`
--

CREATE TABLE `SetsItemsLookup` (
  `setlookup_id` int unsigned NOT NULL AUTO_INCREMENT,
  `setlookup_set_id` mediumint NOT NULL DEFAULT '0',
  `setlookup_item_id` mediumint NOT NULL DEFAULT '0',
  `setlookup_order` mediumint NOT NULL DEFAULT '0',
  PRIMARY KEY (`setlookup_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Settings`
--

CREATE TABLE `Settings` (
  `setting_id` int unsigned NOT NULL AUTO_INCREMENT,
  `setting_parent_id` int NOT NULL DEFAULT '0',
  `setting_site_id` int NOT NULL DEFAULT '0',
  `setting_user_id` int NOT NULL DEFAULT '0',
  `setting_application_id` varchar(128) NOT NULL DEFAULT '',
  `setting_type` varchar(64) NOT NULL DEFAULT '',
  `setting_name` varchar(50) NOT NULL DEFAULT '',
  `setting_value` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`setting_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Sites`
--

CREATE TABLE `Sites` (
  `site_id` int unsigned NOT NULL AUTO_INCREMENT,
  `site_unique_id` varchar(23) DEFAULT NULL,
  `site_name` varchar(128) NOT NULL DEFAULT '',
  `site_internal_label` varchar(255) DEFAULT NULL,
  `site_is_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `site_title_format` varchar(255) NOT NULL DEFAULT '$site | $page',
  `site_domain` varchar(128) NOT NULL DEFAULT '',
  `site_url_prefix` varchar(32) DEFAULT NULL,
  `site_directory_name` varchar(64) DEFAULT NULL,
  `site_admin_email` varchar(64) DEFAULT NULL,
  `site_top_page_id` int unsigned DEFAULT NULL,
  `site_tag_page_id` int unsigned DEFAULT NULL,
  `site_search_page_id` int unsigned DEFAULT NULL,
  `site_error_page_id` int unsigned DEFAULT NULL,
  `site_logo_image_asset_id` int unsigned DEFAULT NULL,
  `site_primary_container_id` int unsigned DEFAULT NULL,
  `site_primary_text_placeholder_id` int unsigned DEFAULT NULL,
  `site_default_blocklist_style_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`site_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Tags`
--

CREATE TABLE `Tags` (
  `tag_id` int unsigned NOT NULL AUTO_INCREMENT,
  `tag_name` varchar(64) NOT NULL DEFAULT '',
  `tag_label` varchar(64) NOT NULL DEFAULT '',
  `tag_featured` tinyint(1) NOT NULL DEFAULT '0',
  `tag_description_text_asset_id` int DEFAULT NULL,
  `tag_icon_image_asset_id` int DEFAULT NULL,
  `tag_site_id` int DEFAULT NULL,
  `tag_model_id` int DEFAULT NULL,
  `tag_language` varchar(8) NOT NULL DEFAULT 'eng',
  `tag_type` varchar(64) NOT NULL DEFAULT 'SM_TAGTYPE_TAG',
  PRIMARY KEY (`tag_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `TagsObjectsLookup`
--

CREATE TABLE `TagsObjectsLookup` (
  `taglookup_id` int unsigned NOT NULL AUTO_INCREMENT,
  `taglookup_tag_id` int unsigned NOT NULL DEFAULT '0',
  `taglookup_object_id` int unsigned NOT NULL DEFAULT '0',
  `taglookup_type` varchar(32) NOT NULL DEFAULT 'SM_PAGE_TAG_LINK',
  `taglookup_metapage_id` int unsigned NOT NULL DEFAULT '0',
  `taglookup_order_index` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`taglookup_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `TextFragments`
--

CREATE TABLE `TextFragments` (
  `textfragment_id` int unsigned NOT NULL AUTO_INCREMENT,
  `textfragment_webid` varchar(36) NOT NULL DEFAULT '',
  `textfragment_asset_id` mediumint NOT NULL DEFAULT '0',
  `textfragment_content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `textfragment_file` varchar(128) NOT NULL DEFAULT '',
  `textfragment_created` int NOT NULL DEFAULT '0',
  `textfragment_modified` int unsigned NOT NULL DEFAULT '0',
  `textfragment_is_approved` tinyint(1) NOT NULL DEFAULT '0',
  `textfragment_type` varchar(64) NOT NULL DEFAULT 'SM_TEXTFRAGMENTTYPE_CURRENT_VERSION',
  PRIMARY KEY (`textfragment_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `TodoItems`
--

CREATE TABLE `TodoItems` (
  `todoitem_id` mediumint NOT NULL AUTO_INCREMENT,
  `todoitem_assigning_user_id` int NOT NULL DEFAULT '0',
  `todoitem_receiving_user_id` int NOT NULL DEFAULT '0',
  `todoitem_type` varchar(32) NOT NULL DEFAULT 'SM_TODOITEMTYPE_PERSONAL',
  `todoitem_token` varchar(64) DEFAULT NULL,
  `todoitem_foreign_object_type` varchar(32) DEFAULT NULL,
  `todoitem_foreign_object_id` int DEFAULT NULL,
  `todoitem_time_assigned` int NOT NULL DEFAULT '0',
  `todoitem_time_completed` int NOT NULL DEFAULT '0',
  `todoitem_priority` int NOT NULL DEFAULT '2',
  `todoitem_is_complete` tinyint unsigned NOT NULL DEFAULT '0',
  `todoitem_ignore` tinyint(1) NOT NULL DEFAULT '0',
  `todoitem_description` text,
  `todoitem_size` int NOT NULL DEFAULT '2',
  PRIMARY KEY (`todoitem_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Users`
--

CREATE TABLE `Users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL DEFAULT '',
  `password` varchar(255) NOT NULL DEFAULT '',
  `user_password_salt` varchar(255) NOT NULL DEFAULT '',
  `user_password_last_changed` int unsigned NOT NULL DEFAULT '0',
  `user_password_change_required` tinyint(1) NOT NULL DEFAULT '0',
  `user_activation_key` varchar(64) NOT NULL DEFAULT '',
  `user_firstname` varchar(64) NOT NULL DEFAULT '',
  `user_lastname` varchar(64) NOT NULL DEFAULT '',
  `user_invert_name_order` tinyint(1) NOT NULL DEFAULT '0',
  `user_email` varchar(64) NOT NULL DEFAULT '',
  `user_website` varchar(255) NOT NULL DEFAULT '',
  `user_organization_name` varchar(128) DEFAULT NULL,
  `user_bio` text,
  `user_bio_asset_id` int unsigned DEFAULT NULL,
  `user_birthday` date DEFAULT NULL,
  `user_profile_pic_asset_id` int unsigned DEFAULT NULL,
  `user_register_date` int NOT NULL DEFAULT '0',
  `user_last_visit` int NOT NULL DEFAULT '0',
  `user_activated` tinyint(1) NOT NULL DEFAULT '1',
  `user_is_smartest_account` tinyint(1) NOT NULL DEFAULT '0',
  `user_info` text,
  `user_oauth_consumer_token` varchar(255) NOT NULL DEFAULT '',
  `user_oauth_consumer_secret` varchar(255) NOT NULL DEFAULT '',
  `user_oauth_access_token` varchar(255) NOT NULL DEFAULT '',
  `user_oauth_access_token_secret` varchar(255) NOT NULL DEFAULT '',
  `user_oauth_service_id` varchar(64) NOT NULL DEFAULT '',
  `user_type` varchar(64) NOT NULL DEFAULT 'SM_USERTYPE_SYSTEM_USER',
  `user_answers_to_user_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `UsersTokensLookup`
--

CREATE TABLE `UsersTokensLookup` (
  `utlookup_id` mediumint NOT NULL AUTO_INCREMENT,
  `utlookup_user_id` mediumint NOT NULL DEFAULT '0',
  `utlookup_token_id` mediumint NOT NULL DEFAULT '0',
  `utlookup_site_id` int DEFAULT NULL,
  `utlookup_object_id` int DEFAULT NULL,
  `utlookup_is_global` tinyint(1) NOT NULL DEFAULT '0',
  `utlookup_order_index` int unsigned NOT NULL DEFAULT '0',
  `utlookup_granted_timestamp` int NOT NULL DEFAULT '0',
  `utlookup_granted_by_user_id` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`utlookup_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO `Settings` (`setting_id`, `setting_parent_id`, `setting_site_id`, `setting_user_id`, `setting_application_id`, `setting_type`, `setting_name`, `setting_value`) VALUES
(1, 0, 0, 0, '', 'SM_SETTINGTYPE_SYSTEM_META', 'database_minimum_revision', '899'),
(2, 0, 0, 0, '', 'SM_SETTINGTYPE_SYSTEM_META', 'database_version', '24');

INSERT INTO `AssetClasses` (`assetclass_name`, `assetclass_label`, `assetclass_site_id`, `assetclass_shared`, `assetclass_type`, `assetclass_is_system`) VALUES
('page_specific_stylesheet', 'Page-specific Stylesheet', 1, 1, 'SM_ASSETCLASS_STYLESHEET', 1),
('page_specific_javascript', 'Page-specific Javascript', 1, 1, 'SM_ASSETCLASS_JAVASCRIPT', 1),
('page_layout', 'Page layout', 1, 1, 'SM_ASSETCLASS_CONTAINER', 1);
