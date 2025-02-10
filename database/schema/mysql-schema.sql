/*M!999999\- enable the sandbox mode */ 
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `account_interstitials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_interstitials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `type` varchar(191) DEFAULT NULL,
  `view` varchar(191) DEFAULT NULL,
  `item_id` bigint(20) unsigned DEFAULT NULL,
  `item_type` varchar(191) DEFAULT NULL,
  `is_spam` tinyint(1) DEFAULT NULL,
  `in_violation` tinyint(1) DEFAULT NULL,
  `violation_id` int(10) unsigned DEFAULT NULL,
  `email_notify` tinyint(1) DEFAULT NULL,
  `has_media` tinyint(1) DEFAULT 0,
  `blurhash` varchar(191) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `violation_header` text DEFAULT NULL,
  `violation_body` text DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `appeal_message` text DEFAULT NULL,
  `appeal_requested_at` timestamp NULL DEFAULT NULL,
  `appeal_handled_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `severity_index` tinyint(3) unsigned DEFAULT NULL,
  `thread_id` bigint(20) unsigned DEFAULT NULL,
  `emailed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_interstitials_thread_id_unique` (`thread_id`),
  KEY `account_interstitials_user_id_index` (`user_id`),
  KEY `account_interstitials_appeal_requested_at_index` (`appeal_requested_at`),
  KEY `account_interstitials_appeal_handled_at_index` (`appeal_handled_at`),
  KEY `account_interstitials_read_at_index` (`read_at`),
  KEY `account_interstitials_severity_index_index` (`severity_index`),
  KEY `account_interstitials_is_spam_index` (`is_spam`),
  KEY `account_interstitials_in_violation_index` (`in_violation`),
  KEY `account_interstitials_violation_id_index` (`violation_id`),
  KEY `account_interstitials_email_notify_index` (`email_notify`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `account_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `item_id` bigint(20) unsigned DEFAULT NULL,
  `item_type` varchar(191) DEFAULT NULL,
  `action` varchar(191) DEFAULT NULL,
  `message` varchar(191) DEFAULT NULL,
  `link` varchar(191) DEFAULT NULL,
  `ip_address` varchar(191) DEFAULT NULL,
  `user_agent` varchar(191) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `account_logs_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `to_id` bigint(20) unsigned DEFAULT NULL,
  `from_id` bigint(20) unsigned DEFAULT NULL,
  `object_type` varchar(191) DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `admin_invites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_invites` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) DEFAULT NULL,
  `invite_code` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `message` text DEFAULT NULL,
  `max_uses` int(10) unsigned DEFAULT NULL,
  `uses` int(10) unsigned NOT NULL DEFAULT 0,
  `skip_email_verification` tinyint(1) NOT NULL DEFAULT 0,
  `expires_at` timestamp NULL DEFAULT NULL,
  `used_by` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`used_by`)),
  `admin_user_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_invites_invite_code_unique` (`invite_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `admin_shadow_filters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_shadow_filters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` bigint(20) unsigned DEFAULT NULL,
  `item_type` varchar(191) NOT NULL,
  `item_id` bigint(20) unsigned NOT NULL,
  `is_local` tinyint(1) NOT NULL DEFAULT 1,
  `note` text DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`history`)),
  `ruleset` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ruleset`)),
  `prevent_ap_fanout` tinyint(1) NOT NULL DEFAULT 0,
  `prevent_new_dms` tinyint(1) NOT NULL DEFAULT 0,
  `ignore_reports` tinyint(1) NOT NULL DEFAULT 0,
  `ignore_mentions` tinyint(1) NOT NULL DEFAULT 0,
  `ignore_links` tinyint(1) NOT NULL DEFAULT 0,
  `ignore_hashtags` tinyint(1) NOT NULL DEFAULT 0,
  `hide_from_public_feeds` tinyint(1) NOT NULL DEFAULT 0,
  `hide_from_tag_feeds` tinyint(1) NOT NULL DEFAULT 0,
  `hide_embeds` tinyint(1) NOT NULL DEFAULT 0,
  `hide_from_story_carousel` tinyint(1) NOT NULL DEFAULT 0,
  `hide_from_search_autocomplete` tinyint(1) NOT NULL DEFAULT 0,
  `hide_from_search` tinyint(1) NOT NULL DEFAULT 0,
  `requires_login` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `admin_shadow_filters_item_type_item_id_index` (`item_type`,`item_id`),
  KEY `admin_shadow_filters_is_local_index` (`is_local`),
  KEY `admin_shadow_filters_active_index` (`active`),
  KEY `admin_shadow_filters_prevent_ap_fanout_index` (`prevent_ap_fanout`),
  KEY `admin_shadow_filters_prevent_new_dms_index` (`prevent_new_dms`),
  KEY `admin_shadow_filters_ignore_reports_index` (`ignore_reports`),
  KEY `admin_shadow_filters_ignore_mentions_index` (`ignore_mentions`),
  KEY `admin_shadow_filters_ignore_links_index` (`ignore_links`),
  KEY `admin_shadow_filters_ignore_hashtags_index` (`ignore_hashtags`),
  KEY `admin_shadow_filters_hide_from_public_feeds_index` (`hide_from_public_feeds`),
  KEY `admin_shadow_filters_hide_from_tag_feeds_index` (`hide_from_tag_feeds`),
  KEY `admin_shadow_filters_hide_embeds_index` (`hide_embeds`),
  KEY `admin_shadow_filters_hide_from_story_carousel_index` (`hide_from_story_carousel`),
  KEY `admin_shadow_filters_hide_from_search_autocomplete_index` (`hide_from_search_autocomplete`),
  KEY `admin_shadow_filters_hide_from_search_index` (`hide_from_search`),
  KEY `admin_shadow_filters_requires_login_index` (`requires_login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_registers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_registers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(191) NOT NULL,
  `verify_code` varchar(191) NOT NULL,
  `email_delivered_at` timestamp NULL DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `app_registers_email_verify_code_unique` (`email`,`verify_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `autospam_custom_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `autospam_custom_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(191) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT 1,
  `is_spam` tinyint(1) NOT NULL DEFAULT 1,
  `note` text DEFAULT NULL,
  `category` varchar(191) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `autospam_custom_tokens_token_category_unique` (`token`,`category`),
  KEY `autospam_custom_tokens_token_index` (`token`),
  KEY `autospam_custom_tokens_weight_index` (`weight`),
  KEY `autospam_custom_tokens_is_spam_index` (`is_spam`),
  KEY `autospam_custom_tokens_category_index` (`category`),
  KEY `autospam_custom_tokens_active_index` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `avatars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `avatars` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `profile_id` bigint(20) unsigned NOT NULL,
  `media_path` varchar(191) DEFAULT NULL,
  `remote_url` varchar(191) DEFAULT NULL,
  `cdn_url` varchar(191) DEFAULT NULL,
  `is_remote` tinyint(1) DEFAULT NULL,
  `size` int(10) unsigned DEFAULT NULL,
  `change_count` int(10) unsigned NOT NULL DEFAULT 0,
  `last_fetched_at` timestamp NULL DEFAULT NULL,
  `last_processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `avatars_profile_id_unique` (`profile_id`),
  KEY `avatars_remote_url_index` (`remote_url`),
  KEY `avatars_deleted_at_index` (`deleted_at`),
  KEY `avatars_is_remote_index` (`is_remote`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bookmarks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bookmarks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `status_id` bigint(20) unsigned NOT NULL,
  `profile_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bookmarks_status_id_profile_id_unique` (`status_id`,`profile_id`),
  KEY `bookmarks_status_id_index` (`status_id`),
  KEY `bookmarks_profile_id_index` (`profile_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(191) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(191) NOT NULL,
  `owner` varchar(191) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `circle_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `circle_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` bigint(20) unsigned DEFAULT NULL,
  `circle_id` bigint(20) unsigned NOT NULL,
  `profile_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `circle_profiles_circle_id_profile_id_unique` (`circle_id`,`profile_id`),
  KEY `circle_profiles_owner_id_index` (`owner_id`),
  KEY `circle_profiles_circle_id_index` (`circle_id`),
  KEY `circle_profiles_profile_id_index` (`profile_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `circles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `circles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `profile_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `scope` varchar(191) NOT NULL DEFAULT 'public',
  `bcc` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `circles_profile_id_index` (`profile_id`),
  KEY `circles_active_index` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `collection_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `collection_items` (
  `id` bigint(20) unsigned NOT NULL,
  `collection_id` bigint(20) unsigned NOT NULL,
  `order` int(10) unsigned DEFAULT NULL,
  `object_type` varchar(191) NOT NULL DEFAULT 'post',
  `object_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `collection_items_collection_id_index` (`collection_id`),
  KEY `collection_items_object_type_index` (`object_type`),
  KEY `collection_items_object_id_index` (`object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `collections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `collections` (
  `id` bigint(20) unsigned NOT NULL,
  `profile_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_nsfw` tinyint(1) NOT NULL DEFAULT 0,
  `visibility` varchar(191) NOT NULL DEFAULT 'public',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `collections_visibility_index` (`visibility`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `profile_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `status_id` bigint(20) unsigned NOT NULL,
  `comment` text DEFAULT NULL,
  `rendered` text DEFAULT NULL,
  `entities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`entities`)),
  `is_remote` tinyint(1) NOT NULL DEFAULT 0,
  `rendered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `config_cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `config_cache` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `k` varchar(191) NOT NULL,
  `v` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `config_cache_k_unique` (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contacts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `response_requested` tinyint(1) NOT NULL DEFAULT 0,
  `message` text NOT NULL,
  `response` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contacts_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `to_id` bigint(20) unsigned NOT NULL,
  `from_id` bigint(20) unsigned NOT NULL,
  `dm_id` bigint(20) unsigned DEFAULT NULL,
  `status_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(191) DEFAULT NULL,
  `is_hidden` tinyint(1) NOT NULL DEFAULT 0,
  `has_seen` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `conversations_to_id_from_id_unique` (`to_id`,`from_id`),
  KEY `conversations_to_id_index` (`to_id`),
  KEY `conversations_from_id_index` (`from_id`),
  KEY `conversations_is_hidden_index` (`is_hidden`),
  KEY `conversations_has_seen_index` (`has_seen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `curated_register_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `curated_register_activities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `register_id` int(10) unsigned DEFAULT NULL,
  `admin_id` int(10) unsigned DEFAULT NULL,
  `reply_to_id` int(10) unsigned DEFAULT NULL,
  `secret_code` varchar(191) DEFAULT NULL,
  `type` varchar(191) DEFAULT NULL,
  `title` varchar(191) DEFAULT NULL,
  `link` varchar(191) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `from_admin` tinyint(1) NOT NULL DEFAULT 0,
  `from_user` tinyint(1) NOT NULL DEFAULT 0,
  `admin_only_view` tinyint(1) NOT NULL DEFAULT 1,
  `action_required` tinyint(1) NOT NULL DEFAULT 0,
  `admin_notified_at` timestamp NULL DEFAULT NULL,
  `action_taken_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `curated_register_activities_register_id_index` (`register_id`),
  KEY `curated_register_activities_reply_to_id_index` (`reply_to_id`),
  KEY `curated_register_activities_type_index` (`type`),
  KEY `curated_register_activities_from_admin_index` (`from_admin`),
  KEY `curated_register_activities_from_user_index` (`from_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `curated_register_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `curated_register_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `content` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `order` tinyint(3) unsigned NOT NULL DEFAULT 10,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `curated_register_templates_is_active_index` (`is_active`),
  KEY `curated_register_templates_order_index` (`order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `curated_registers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `curated_registers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(191) DEFAULT NULL,
  `username` varchar(191) DEFAULT NULL,
  `password` varchar(191) DEFAULT NULL,
  `ip_address` varchar(191) DEFAULT NULL,
  `verify_code` varchar(191) DEFAULT NULL,
  `reason_to_join` text DEFAULT NULL,
  `invited_by` bigint(20) unsigned DEFAULT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `is_rejected` tinyint(1) NOT NULL DEFAULT 0,
  `is_awaiting_more_info` tinyint(1) NOT NULL DEFAULT 0,
  `user_has_responded` tinyint(1) NOT NULL DEFAULT 0,
  `is_closed` tinyint(1) NOT NULL DEFAULT 0,
  `autofollow_account_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`autofollow_account_ids`)),
  `admin_notes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`admin_notes`)),
  `approved_by_admin_id` int(10) unsigned DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `admin_notified_at` timestamp NULL DEFAULT NULL,
  `action_taken_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `curated_registers_email_unique` (`email`),
  UNIQUE KEY `curated_registers_username_unique` (`username`),
  KEY `curated_registers_invited_by_index` (`invited_by`),
  KEY `curated_registers_is_approved_index` (`is_approved`),
  KEY `curated_registers_is_rejected_index` (`is_rejected`),
  KEY `curated_registers_is_awaiting_more_info_index` (`is_awaiting_more_info`),
  KEY `curated_registers_is_closed_index` (`is_closed`),
  KEY `curated_registers_user_has_responded_index` (`user_has_responded`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custom_emoji`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `custom_emoji` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shortcode` varchar(191) NOT NULL,
  `media_path` varchar(191) DEFAULT NULL,
  `domain` varchar(191) DEFAULT NULL,
  `disabled` tinyint(1) NOT NULL DEFAULT 0,
  `uri` varchar(191) DEFAULT NULL,
  `image_remote_url` varchar(191) DEFAULT NULL,
  `category_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `custom_emoji_shortcode_domain_unique` (`shortcode`,`domain`),
  KEY `custom_emoji_shortcode_index` (`shortcode`),
  KEY `custom_emoji_domain_index` (`domain`),
  KEY `custom_emoji_disabled_index` (`disabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custom_emoji_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `custom_emoji_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `disabled` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `custom_emoji_categories_name_unique` (`name`),
  KEY `custom_emoji_categories_disabled_index` (`disabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `default_domain_blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `default_domain_blocks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `domain` varchar(191) NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `default_domain_blocks_domain_unique` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `direct_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `direct_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `to_id` bigint(20) unsigned NOT NULL,
  `from_id` bigint(20) unsigned NOT NULL,
  `type` varchar(191) DEFAULT 'text',
  `from_profile_ids` varchar(191) DEFAULT NULL,
  `group_message` tinyint(1) NOT NULL DEFAULT 0,
  `is_hidden` tinyint(1) NOT NULL DEFAULT 0,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `status_id` bigint(20) unsigned NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `direct_messages_to_id_from_id_status_id_unique` (`to_id`,`from_id`,`status_id`),
  KEY `direct_messages_to_id_index` (`to_id`),
  KEY `direct_messages_from_id_index` (`from_id`),
  KEY `direct_messages_type_index` (`type`),
  KEY `direct_messages_is_hidden_index` (`is_hidden`),
  KEY `direct_messages_status_id_index` (`status_id`),
  KEY `direct_messages_group_message_index` (`group_message`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `discover_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `discover_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) DEFAULT NULL,
  `slug` varchar(191) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `order` tinyint(3) unsigned NOT NULL DEFAULT 5,
  `media_id` bigint(20) unsigned DEFAULT NULL,
  `no_nsfw` tinyint(1) NOT NULL DEFAULT 1,
  `local_only` tinyint(1) NOT NULL DEFAULT 1,
  `public_only` tinyint(1) NOT NULL DEFAULT 1,
  `photos_only` tinyint(1) NOT NULL DEFAULT 1,
  `active_until` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `discover_categories_slug_unique` (`slug`),
  UNIQUE KEY `discover_categories_media_id_unique` (`media_id`),
  KEY `discover_categories_active_index` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `discover_category_hashtags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `discover_category_hashtags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `discover_category_id` bigint(20) unsigned NOT NULL,
  `hashtag_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `disc_hashtag_unique` (`discover_category_id`,`hashtag_id`),
  KEY `discover_category_hashtags_discover_category_id_index` (`discover_category_id`),
  KEY `discover_category_hashtags_hashtag_id_index` (`hashtag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `email_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_verifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `email` varchar(191) DEFAULT NULL,
  `user_token` varchar(191) NOT NULL,
  `random_token` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `email_verifications_user_token_index` (`user_token`),
  KEY `email_verifications_random_token_index` (`random_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(191) DEFAULT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `follow_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `follow_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `follower_id` bigint(20) unsigned NOT NULL,
  `following_id` bigint(20) unsigned NOT NULL,
  `activity` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`activity`)),
  `is_rejected` tinyint(1) NOT NULL DEFAULT 0,
  `is_local` tinyint(1) NOT NULL DEFAULT 0,
  `handled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `follow_requests_follower_id_index` (`follower_id`),
  KEY `follow_requests_following_id_index` (`following_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `followers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `followers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `profile_id` bigint(20) unsigned NOT NULL,
  `following_id` bigint(20) unsigned NOT NULL,
  `local_profile` tinyint(1) NOT NULL DEFAULT 1,
  `local_following` tinyint(1) NOT NULL DEFAULT 1,
  `show_reblogs` tinyint(1) NOT NULL DEFAULT 1,
  `notify` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `followers_profile_id_following_id_unique` (`profile_id`,`following_id`),
  KEY `followers_local_profile_index` (`local_profile`),
  KEY `followers_local_following_index` (`local_following`),
  KEY `followers_created_at_index` (`created_at`),
  KEY `followers_profile_id_index` (`profile_id`),
  KEY `followers_following_id_index` (`following_id`),
  KEY `followers_show_reblogs_index` (`show_reblogs`),
  KEY `followers_notify_index` (`notify`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_activity_graphs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_activity_graphs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `instance_id` bigint(20) DEFAULT NULL,
  `actor_id` bigint(20) DEFAULT NULL,
  `verb` varchar(191) DEFAULT NULL,
  `id_url` varchar(191) DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_activity_graphs_id_url_unique` (`id_url`),
  KEY `group_activity_graphs_instance_id_index` (`instance_id`),
  KEY `group_activity_graphs_actor_id_index` (`actor_id`),
  KEY `group_activity_graphs_verb_index` (`verb`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_blocks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint(20) unsigned NOT NULL,
  `admin_id` bigint(20) unsigned DEFAULT NULL,
  `profile_id` bigint(20) unsigned DEFAULT NULL,
  `instance_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(191) DEFAULT NULL,
  `reason` varchar(191) DEFAULT NULL,
  `is_user` tinyint(1) NOT NULL,
  `moderated` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_blocks_group_id_profile_id_instance_id_unique` (`group_id`,`profile_id`,`instance_id`),
  KEY `group_blocks_group_id_index` (`group_id`),
  KEY `group_blocks_profile_id_index` (`profile_id`),
  KEY `group_blocks_instance_id_index` (`instance_id`),
  KEY `group_blocks_name_index` (`name`),
  KEY `group_blocks_is_user_index` (`is_user`),
  KEY `group_blocks_moderated_index` (`moderated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `order` tinyint(3) unsigned DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_categories_name_unique` (`name`),
  UNIQUE KEY `group_categories_slug_unique` (`slug`),
  KEY `group_categories_active_index` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint(20) unsigned NOT NULL,
  `profile_id` bigint(20) unsigned DEFAULT NULL,
  `status_id` bigint(20) unsigned DEFAULT NULL,
  `in_reply_to_id` bigint(20) unsigned DEFAULT NULL,
  `remote_url` varchar(191) DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `is_nsfw` tinyint(1) NOT NULL DEFAULT 0,
  `visibility` varchar(191) DEFAULT NULL,
  `likes_count` int(10) unsigned NOT NULL DEFAULT 0,
  `replies_count` int(10) unsigned NOT NULL DEFAULT 0,
  `cw_summary` text DEFAULT NULL,
  `media_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`media_ids`)),
  `status` varchar(191) DEFAULT NULL,
  `type` varchar(191) DEFAULT 'text',
  `local` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_comments_remote_url_unique` (`remote_url`),
  KEY `group_comments_group_id_index` (`group_id`),
  KEY `group_comments_status_id_index` (`status_id`),
  KEY `group_comments_in_reply_to_id_index` (`in_reply_to_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint(20) unsigned DEFAULT NULL,
  `profile_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(191) DEFAULT NULL,
  `type` varchar(191) NOT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `location` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`location`)),
  `description` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `open` tinyint(1) NOT NULL DEFAULT 0,
  `comments_open` tinyint(1) NOT NULL DEFAULT 0,
  `show_guest_list` tinyint(1) NOT NULL DEFAULT 0,
  `start_at` timestamp NULL DEFAULT NULL,
  `end_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `group_events_group_id_index` (`group_id`),
  KEY `group_events_profile_id_index` (`profile_id`),
  KEY `group_events_type_index` (`type`),
  KEY `group_events_open_index` (`open`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_hashtags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_hashtags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `formatted` varchar(191) DEFAULT NULL,
  `recommended` tinyint(1) NOT NULL DEFAULT 0,
  `sensitive` tinyint(1) NOT NULL DEFAULT 0,
  `banned` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_hashtags_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_interactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_interactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint(20) unsigned NOT NULL,
  `profile_id` bigint(20) unsigned NOT NULL,
  `type` varchar(191) DEFAULT NULL,
  `item_type` varchar(191) DEFAULT NULL,
  `item_id` varchar(191) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `group_interactions_group_id_index` (`group_id`),
  KEY `group_interactions_profile_id_index` (`profile_id`),
  KEY `group_interactions_type_index` (`type`),
  KEY `group_interactions_item_type_index` (`item_type`),
  KEY `group_interactions_item_id_index` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_invitations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint(20) unsigned NOT NULL,
  `from_profile_id` bigint(20) unsigned NOT NULL,
  `to_profile_id` bigint(20) unsigned NOT NULL,
  `role` varchar(191) DEFAULT NULL,
  `to_local` tinyint(1) NOT NULL DEFAULT 1,
  `from_local` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_invitations_group_id_to_profile_id_unique` (`group_id`,`to_profile_id`),
  KEY `group_invitations_group_id_index` (`group_id`),
  KEY `group_invitations_from_profile_id_index` (`from_profile_id`),
  KEY `group_invitations_to_profile_id_index` (`to_profile_id`),
  KEY `group_invitations_to_local_index` (`to_local`),
  KEY `group_invitations_from_local_index` (`from_local`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_likes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_likes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint(20) unsigned NOT NULL,
  `profile_id` bigint(20) unsigned NOT NULL,
  `status_id` bigint(20) unsigned DEFAULT NULL,
  `comment_id` bigint(20) unsigned DEFAULT NULL,
  `local` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_likes_gpsc_unique` (`group_id`,`profile_id`,`status_id`,`comment_id`),
  KEY `group_likes_profile_id_index` (`profile_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_limits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint(20) unsigned NOT NULL,
  `profile_id` bigint(20) unsigned NOT NULL,
  `limits` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`limits`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_limits_group_id_profile_id_unique` (`group_id`,`profile_id`),
  KEY `group_limits_group_id_index` (`group_id`),
  KEY `group_limits_profile_id_index` (`profile_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_media` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint(20) unsigned NOT NULL,
  `profile_id` bigint(20) unsigned NOT NULL,
  `status_id` bigint(20) unsigned DEFAULT NULL,
  `media_path` varchar(191) NOT NULL,
  `thumbnail_url` text DEFAULT NULL,
  `cdn_url` text DEFAULT NULL,
  `url` text DEFAULT NULL,
  `mime` varchar(191) DEFAULT NULL,
  `size` int(10) unsigned DEFAULT NULL,
  `cw_summary` text DEFAULT NULL,
  `license` varchar(191) DEFAULT NULL,
  `blurhash` varchar(191) DEFAULT NULL,
  `order` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `width` int(10) unsigned DEFAULT NULL,
  `height` int(10) unsigned DEFAULT NULL,
  `local_user` tinyint(1) NOT NULL DEFAULT 1,
  `is_cached` tinyint(1) NOT NULL DEFAULT 0,
  `is_comment` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `version` varchar(191) NOT NULL DEFAULT '1',
  `skip_optimize` tinyint(1) NOT NULL DEFAULT 0,
  `processed_at` timestamp NULL DEFAULT NULL,
  `thumbnail_generated` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_media_media_path_unique` (`media_path`),
  KEY `group_media_status_id_index` (`status_id`),
  KEY `group_media_is_comment_index` (`is_comment`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_members` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint(20) unsigned NOT NULL,
  `profile_id` bigint(20) unsigned NOT NULL,
  `role` varchar(191) NOT NULL DEFAULT 'member',
  `local_group` tinyint(1) NOT NULL DEFAULT 0,
  `local_profile` tinyint(1) NOT NULL DEFAULT 0,
  `join_request` tinyint(1) NOT NULL DEFAULT 0,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_members_group_id_profile_id_unique` (`group_id`,`profile_id`),
  KEY `group_members_group_id_index` (`group_id`),
  KEY `group_members_profile_id_index` (`profile_id`),
  KEY `group_members_role_index` (`role`),
  KEY `group_members_local_group_index` (`local_group`),
  KEY `group_members_local_profile_index` (`local_profile`),
  KEY `group_members_join_request_index` (`join_request`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_post_hashtags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_post_hashtags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `hashtag_id` bigint(20) unsigned NOT NULL,
  `group_id` bigint(20) unsigned NOT NULL,
  `profile_id` bigint(20) unsigned NOT NULL,
  `status_id` bigint(20) unsigned DEFAULT NULL,
  `status_visibility` varchar(191) DEFAULT NULL,
  `nsfw` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_post_hashtags_gda_unique` (`hashtag_id`,`group_id`,`profile_id`,`status_id`),
  KEY `group_post_hashtags_profile_id_foreign` (`profile_id`),
  KEY `group_post_hashtags_status_id_foreign` (`status_id`),
  KEY `group_post_hashtags_hashtag_id_index` (`hashtag_id`),
  KEY `group_post_hashtags_group_id_index` (`group_id`),
  CONSTRAINT `group_post_hashtags_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `group_post_hashtags_hashtag_id_foreign` FOREIGN KEY (`hashtag_id`) REFERENCES `group_hashtags` (`id`) ON DELETE CASCADE,
  CONSTRAINT `group_post_hashtags_profile_id_foreign` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `group_post_hashtags_status_id_foreign` FOREIGN KEY (`status_id`) REFERENCES `group_posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_posts` (
  `id` bigint(20) unsigned NOT NULL,
  `group_id` bigint(20) unsigned NOT NULL,
  `profile_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(191) DEFAULT NULL,
  `remote_url` varchar(191) DEFAULT NULL,
  `reply_count` int(10) unsigned DEFAULT NULL,
  `status` varchar(191) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `visibility` varchar(191) DEFAULT NULL,
  `is_nsfw` tinyint(1) NOT NULL DEFAULT 0,
  `likes_count` int(10) unsigned NOT NULL DEFAULT 0,
  `cw_summary` text DEFAULT NULL,
  `media_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`media_ids`)),
  `comments_disabled` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_posts_remote_url_unique` (`remote_url`),
  KEY `group_posts_group_id_index` (`group_id`),
  KEY `group_posts_profile_id_index` (`profile_id`),
  KEY `group_posts_type_index` (`type`),
  KEY `group_posts_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint(20) unsigned NOT NULL,
  `profile_id` bigint(20) unsigned NOT NULL,
  `type` varchar(191) DEFAULT NULL,
  `item_type` varchar(191) DEFAULT NULL,
  `item_id` varchar(191) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `open` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_reports_group_id_profile_id_item_type_item_id_unique` (`group_id`,`profile_id`,`item_type`,`item_id`),
  KEY `group_reports_group_id_index` (`group_id`),
  KEY `group_reports_profile_id_index` (`profile_id`),
  KEY `group_reports_type_index` (`type`),
  KEY `group_reports_item_type_index` (`item_type`),
  KEY `group_reports_item_id_index` (`item_id`),
  KEY `group_reports_open_index` (`open`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) DEFAULT NULL,
  `abilities` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_roles_group_id_slug_unique` (`group_id`,`slug`),
  KEY `group_roles_group_id_index` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_stores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_stores` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint(20) unsigned DEFAULT NULL,
  `store_key` varchar(191) NOT NULL,
  `store_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`store_value`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_stores_group_id_store_key_unique` (`group_id`,`store_key`),
  KEY `group_stores_group_id_index` (`group_id`),
  KEY `group_stores_store_key_index` (`store_key`),
  CONSTRAINT `group_stores_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups` (
  `id` bigint(20) unsigned NOT NULL,
  `category_id` int(10) unsigned NOT NULL DEFAULT 1,
  `profile_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(191) DEFAULT NULL,
  `name` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `rules` text DEFAULT NULL,
  `local` tinyint(1) NOT NULL DEFAULT 1,
  `remote_url` varchar(191) DEFAULT NULL,
  `inbox_url` varchar(191) DEFAULT NULL,
  `is_private` tinyint(1) NOT NULL DEFAULT 0,
  `local_only` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `member_count` int(10) unsigned DEFAULT NULL,
  `recommended` tinyint(1) NOT NULL DEFAULT 0,
  `discoverable` tinyint(1) NOT NULL DEFAULT 0,
  `activitypub` tinyint(1) NOT NULL DEFAULT 0,
  `is_nsfw` tinyint(1) NOT NULL DEFAULT 0,
  `dms` tinyint(1) NOT NULL DEFAULT 0,
  `autospam` tinyint(1) NOT NULL DEFAULT 0,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `last_active_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `groups_profile_id_index` (`profile_id`),
  KEY `groups_status_index` (`status`),
  KEY `groups_local_index` (`local`),
  KEY `groups_category_id_index` (`category_id`),
  KEY `groups_recommended_index` (`recommended`),
  KEY `groups_discoverable_index` (`discoverable`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hashtag_follows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hashtag_follows` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `profile_id` bigint(20) unsigned NOT NULL,
  `hashtag_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hashtag_follows_user_id_profile_id_hashtag_id_unique` (`user_id`,`profile_id`,`hashtag_id`),
  KEY `hashtag_follows_user_id_index` (`user_id`),
  KEY `hashtag_follows_profile_id_index` (`profile_id`),
  KEY `hashtag_follows_hashtag_id_index` (`hashtag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hashtag_related`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hashtag_related` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `hashtag_id` bigint(20) unsigned NOT NULL,
  `related_tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`related_tags`)),
  `agg_score` bigint(20) unsigned DEFAULT NULL,
  `last_calculated_at` timestamp NULL DEFAULT NULL,
  `last_moderated_at` timestamp NULL DEFAULT NULL,
  `skip_refresh` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hashtag_related_hashtag_id_unique` (`hashtag_id`),
  KEY `hashtag_related_agg_score_index` (`agg_score`),
  KEY `hashtag_related_last_calculated_at_index` (`last_calculated_at`),
  KEY `hashtag_related_last_moderated_at_index` (`last_moderated_at`),
  KEY `hashtag_related_skip_refresh_index` (`skip_refresh`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hashtags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hashtags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `can_trend` tinyint(1) DEFAULT NULL,
  `can_search` tinyint(1) DEFAULT NULL,
  `is_nsfw` tinyint(1) NOT NULL DEFAULT 0,
  `is_banned` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `cached_count` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hashtags_name_unique` (`name`),
  UNIQUE KEY `hashtags_slug_unique` (`slug`),
  KEY `hashtags_is_nsfw_index` (`is_nsfw`),
  KEY `hashtags_is_banned_index` (`is_banned`),
  KEY `hashtags_can_trend_index` (`can_trend`),
  KEY `hashtags_can_search_index` (`can_search`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `import_datas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `import_datas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `profile_id` bigint(20) unsigned NOT NULL,
  `job_id` bigint(20) unsigned DEFAULT NULL,
  `service` varchar(191) NOT NULL DEFAULT 'instagram',
  `path` varchar(191) DEFAULT NULL,
  `stage` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `original_name` varchar(191) DEFAULT NULL,
  `import_accepted` tinyint(1) DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `import_datas_job_id_original_name_unique` (`job_id`,`original_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `import_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `import_jobs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `profile_id` bigint(20) unsigned NOT NULL,
  `service` varchar(191) NOT NULL DEFAULT 'instagram',
  `uuid` varchar(191) DEFAULT NULL,
  `storage_path` varchar(191) DEFAULT NULL,
  `stage` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `media_json` text DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `import_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `import_posts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `profile_id` bigint(20) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `service` varchar(191) NOT NULL,
  `post_hash` varchar(191) DEFAULT NULL,
  `filename` varchar(191) NOT NULL,
  `media_count` tinyint(3) unsigned NOT NULL,
  `post_type` varchar(191) DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `media` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`media`)),
  `creation_year` tinyint(3) unsigned DEFAULT NULL,
  `creation_month` tinyint(3) unsigned DEFAULT NULL,
  `creation_day` tinyint(3) unsigned DEFAULT NULL,
  `creation_id` tinyint(3) unsigned DEFAULT NULL,
  `status_id` bigint(20) unsigned DEFAULT NULL,
  `creation_date` timestamp NULL DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `skip_missing_media` tinyint(1) NOT NULL DEFAULT 0,
  `uploaded_to_s3` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `import_posts_user_id_post_hash_unique` (`user_id`,`post_hash`),
  UNIQUE KEY `import_posts_uid_phash_unique` (`user_id`,`creation_year`,`creation_month`,`creation_day`,`creation_id`),
  UNIQUE KEY `import_posts_status_id_unique` (`status_id`),
  KEY `import_posts_profile_id_index` (`profile_id`),
  KEY `import_posts_user_id_index` (`user_id`),
  KEY `import_posts_service_index` (`service`),
  KEY `import_posts_post_hash_index` (`post_hash`),
  KEY `import_posts_filename_index` (`filename`),
  KEY `import_posts_skip_missing_media_index` (`skip_missing_media`),
  KEY `import_posts_uploaded_to_s3_index` (`uploaded_to_s3`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `instance_actors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `instance_actors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `private_key` text DEFAULT NULL,
  `public_key` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `instances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `instances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `domain` varchar(191) NOT NULL,
  `active_deliver` tinyint(1) DEFAULT NULL,
  `url` varchar(191) DEFAULT NULL,
  `name` varchar(191) DEFAULT NULL,
  `admin_url` varchar(191) DEFAULT NULL,
  `limit_reason` varchar(191) DEFAULT NULL,
  `unlisted` tinyint(1) NOT NULL DEFAULT 0,
  `auto_cw` tinyint(1) NOT NULL DEFAULT 0,
  `banned` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `software` varchar(191) DEFAULT NULL,
  `user_count` int(10) unsigned DEFAULT NULL,
  `status_count` int(10) unsigned DEFAULT NULL,
  `last_crawled_at` timestamp NULL DEFAULT NULL,
  `actors_last_synced_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `manually_added` tinyint(1) NOT NULL DEFAULT 0,
  `base_domain` varchar(191) DEFAULT NULL,
  `ban_subdomains` tinyint(1) DEFAULT NULL,
  `ip_address` varchar(191) DEFAULT NULL,
  `list_limitation` tinyint(1) NOT NULL DEFAULT 0,
  `valid_nodeinfo` tinyint(1) DEFAULT NULL,
  `nodeinfo_last_fetched` timestamp NULL DEFAULT NULL,
  `delivery_timeout` tinyint(1) NOT NULL DEFAULT 0,
  `delivery_next_after` timestamp NULL DEFAULT NULL,
  `shared_inbox` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instances_domain_unique` (`domain`),
  KEY `instances_software_index` (`software`),
  KEY `instances_banned_index` (`banned`),
  KEY `instances_auto_cw_index` (`auto_cw`),
  KEY `instances_unlisted_index` (`unlisted`),
  KEY `instances_ban_subdomains_index` (`ban_subdomains`),
  KEY `instances_list_limitation_index` (`list_limitation`),
  KEY `instances_active_deliver_index` (`active_deliver`),
  KEY `instances_shared_inbox_index` (`shared_inbox`),
  KEY `instances_nodeinfo_last_fetched_index` (`nodeinfo_last_fetched`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(191) NOT NULL,
  `name` varchar(191) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(191) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `likes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `likes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `profile_id` bigint(20) unsigned NOT NULL,
  `status_id` bigint(20) unsigned NOT NULL,
  `status_profile_id` bigint(20) unsigned DEFAULT NULL,
  `is_comment` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `likes_profile_id_status_id_unique` (`profile_id`,`status_id`),
  KEY `likes_created_at_index` (`created_at`),
  KEY `likes_deleted_at_index` (`deleted_at`),
  KEY `likes_profile_id_index` (`profile_id`),
  KEY `likes_status_id_index` (`status_id`),
  KEY `likes_status_profile_id_index` (`status_profile_id`),
  KEY `likes_is_comment_index` (`is_comment`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `live_streams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `live_streams` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `profile_id` bigint(20) unsigned NOT NULL,
  `stream_id` varchar(191) DEFAULT NULL,
  `stream_key` varchar(191) DEFAULT NULL,
  `visibility` varchar(191) DEFAULT NULL,
  `name` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `thumbnail_path` varchar(191) DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `live_chat` tinyint(1) NOT NULL DEFAULT 1,
  `mod_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`mod_ids`)),
  `discoverable` tinyint(1) DEFAULT NULL,
  `live_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `live_streams_stream_id_unique` (`stream_id`),
  KEY `live_streams_profile_id_index` (`profile_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `login_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_links` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(191) NOT NULL,
  `secret` varchar(191) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `ip` varchar(191) DEFAULT NULL,
  `user_agent` varchar(191) DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `revoked_at` timestamp NULL DEFAULT NULL,
  `resent_at` timestamp NULL DEFAULT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login_links_key_index` (`key`),
  KEY `login_links_secret_index` (`secret`),
  KEY `login_links_user_id_index` (`user_id`),
  KEY `login_links_revoked_at_index` (`revoked_at`),
  KEY `login_links_resent_at_index` (`resent_at`),
  KEY `login_links_used_at_index` (`used_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `media` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `status_id` bigint(20) unsigned DEFAULT NULL,
  `profile_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `is_nsfw` tinyint(1) NOT NULL DEFAULT 0,
  `remote_media` tinyint(1) NOT NULL DEFAULT 0,
  `original_sha256` varchar(191) DEFAULT NULL,
  `optimized_sha256` varchar(191) DEFAULT NULL,
  `media_path` varchar(191) NOT NULL,
  `thumbnail_path` varchar(191) DEFAULT NULL,
  `cdn_url` text DEFAULT NULL,
  `optimized_url` varchar(191) DEFAULT NULL,
  `thumbnail_url` varchar(191) DEFAULT NULL,
  `remote_url` varchar(191) DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `hls_path` varchar(191) DEFAULT NULL,
  `order` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `mime` varchar(191) DEFAULT NULL,
  `size` int(10) unsigned DEFAULT NULL,
  `orientation` varchar(191) DEFAULT NULL,
  `filter_name` varchar(191) DEFAULT NULL,
  `filter_class` varchar(191) DEFAULT NULL,
  `license` varchar(191) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `hls_transcoded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `key` varchar(191) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `version` tinyint(4) NOT NULL DEFAULT 1,
  `blurhash` varchar(191) DEFAULT NULL,
  `srcset` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`srcset`)),
  `width` int(10) unsigned DEFAULT NULL,
  `height` int(10) unsigned DEFAULT NULL,
  `skip_optimize` tinyint(1) DEFAULT NULL,
  `replicated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_status_id_media_path_unique` (`status_id`,`media_path`),
  KEY `media_original_sha256_index` (`original_sha256`),
  KEY `media_optimized_sha256_index` (`optimized_sha256`),
  KEY `media_user_id_index` (`user_id`),
  KEY `media_deleted_at_index` (`deleted_at`),
  KEY `media_skip_optimize_index` (`skip_optimize`),
  KEY `media_profile_id_index` (`profile_id`),
  KEY `media_mime_index` (`mime`),
  KEY `media_license_index` (`license`),
  KEY `media_status_id_index` (`status_id`),
  KEY `media_replicated_at_index` (`replicated_at`),
  KEY `media_version_index` (`version`),
  KEY `media_remote_media_index` (`remote_media`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media_blocklists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `media_blocklists` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sha256` varchar(191) DEFAULT NULL,
  `sha512` varchar(191) DEFAULT NULL,
  `name` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_blocklists_sha256_unique` (`sha256`),
  UNIQUE KEY `media_blocklists_sha512_unique` (`sha512`),
  KEY `media_blocklists_active_index` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `media_tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `status_id` bigint(20) unsigned DEFAULT NULL,
  `media_id` bigint(20) unsigned NOT NULL,
  `profile_id` bigint(20) unsigned NOT NULL,
  `tagged_username` varchar(191) DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_tags_media_id_profile_id_unique` (`media_id`,`profile_id`),
  KEY `media_tags_status_id_index` (`status_id`),
  KEY `media_tags_media_id_index` (`media_id`),
  KEY `media_tags_profile_id_index` (`profile_id`),
  KEY `media_tags_is_public_index` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mentions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mentions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `status_id` bigint(20) unsigned NOT NULL,
  `profile_id` bigint(20) unsigned NOT NULL,
  `local` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mentions_deleted_at_index` (`deleted_at`),
  KEY `mentions_status_id_index` (`status_id`),
  KEY `mentions_profile_id_index` (`profile_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mod_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mod_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `user_username` varchar(191) DEFAULT NULL,
  `object_uid` bigint(20) unsigned DEFAULT NULL,
  `object_id` bigint(20) unsigned DEFAULT NULL,
  `object_type` varchar(191) DEFAULT NULL,
  `action` varchar(191) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `access_level` varchar(191) DEFAULT 'admin',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mod_logs_user_id_index` (`user_id`),
  KEY `mod_logs_object_uid_index` (`object_uid`),
  KEY `mod_logs_object_id_index` (`object_id`),
  KEY `mod_logs_object_type_index` (`object_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `moderated_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `moderated_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `profile_url` varchar(191) DEFAULT NULL,
  `profile_id` bigint(20) unsigned DEFAULT NULL,
  `domain` varchar(191) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `is_banned` tinyint(1) NOT NULL DEFAULT 0,
  `is_nsfw` tinyint(1) NOT NULL DEFAULT 0,
  `is_unlisted` tinyint(1) NOT NULL DEFAULT 0,
  `is_noautolink` tinyint(1) NOT NULL DEFAULT 0,
  `is_nodms` tinyint(1) NOT NULL DEFAULT 0,
  `is_notrending` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `moderated_profiles_profile_url_unique` (`profile_url`),
  UNIQUE KEY `moderated_profiles_profile_id_unique` (`profile_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `newsroom`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `newsroom` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `header_photo_url` varchar(191) DEFAULT NULL,
  `title` varchar(191) DEFAULT NULL,
  `slug` varchar(191) DEFAULT NULL,
  `category` varchar(191) NOT NULL DEFAULT 'update',
  `summary` text DEFAULT NULL,
  `body` text DEFAULT NULL,
  `body_rendered` text DEFAULT NULL,
  `link` varchar(191) DEFAULT NULL,
  `force_modal` tinyint(1) NOT NULL DEFAULT 0,
  `show_timeline` tinyint(1) NOT NULL DEFAULT 0,
  `show_link` tinyint(1) NOT NULL DEFAULT 0,
  `auth_only` tinyint(1) NOT NULL DEFAULT 1,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `newsroom_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `profile_id` bigint(20) unsigned NOT NULL,
  `actor_id` bigint(20) unsigned DEFAULT NULL,
  `item_id` bigint(20) unsigned DEFAULT NULL,
  `item_type` varchar(191) DEFAULT NULL,
  `action` varchar(191) DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_profile_id_index` (`profile_id`),
  KEY `notifications_created_at_index` (`created_at`),
  KEY `notifications_actor_id_index` (`actor_id`),
  KEY `notifications_deleted_at_index` (`deleted_at`),
  KEY `notifications_item_id_index` (`item_id`),
  KEY `notifications_item_type_index` (`item_type`),
  KEY `notifications_action_index` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oauth_access_tokens` (
  `id` varchar(100) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) DEFAULT NULL,
  `scopes` text DEFAULT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_access_tokens_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_auth_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oauth_auth_codes` (
  `id` varchar(100) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `scopes` text DEFAULT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_auth_codes_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oauth_clients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `secret` varchar(100) DEFAULT NULL,
  `provider` varchar(191) DEFAULT NULL,
  `redirect` text NOT NULL,
  `personal_access_client` tinyint(1) NOT NULL,
  `password_client` tinyint(1) NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_clients_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_personal_access_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oauth_personal_access_clients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_refresh_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oauth_refresh_tokens` (
  `id` varchar(100) NOT NULL,
  `access_token_id` varchar(100) NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_refresh_tokens_access_token_id_index` (`access_token_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `root` varchar(191) DEFAULT NULL,
  `slug` varchar(191) DEFAULT NULL,
  `title` varchar(191) DEFAULT NULL,
  `category_id` int(10) unsigned DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `template` varchar(191) NOT NULL DEFAULT 'layouts.app',
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `cached` tinyint(1) NOT NULL DEFAULT 1,
  `active_until` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pages_slug_unique` (`slug`),
  KEY `pages_root_index` (`root`),
  KEY `pages_category_id_index` (`category_id`),
  KEY `pages_template_index` (`template`),
  KEY `pages_active_index` (`active`),
  KEY `pages_cached_index` (`cached`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `parental_controls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parental_controls` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL,
  `child_id` int(10) unsigned DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `verify_code` varchar(191) DEFAULT NULL,
  `email_sent_at` timestamp NULL DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `parental_controls_child_id_unique` (`child_id`),
  UNIQUE KEY `parental_controls_email_unique` (`email`),
  KEY `parental_controls_parent_id_index` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `email` varchar(191) NOT NULL,
  `token` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `places`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `places` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(191) NOT NULL,
  `name` varchar(191) NOT NULL,
  `state` varchar(191) DEFAULT NULL,
  `country` varchar(191) NOT NULL,
  `aliases` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`aliases`)),
  `lat` decimal(9,6) DEFAULT NULL,
  `long` decimal(9,6) DEFAULT NULL,
  `score` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `cached_post_count` bigint(20) unsigned DEFAULT NULL,
  `last_checked_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `places_slug_country_lat_long_unique` (`slug`,`country`,`lat`,`long`),
  KEY `places_slug_index` (`slug`),
  KEY `places_name_index` (`name`),
  KEY `places_country_index` (`country`),
  KEY `places_state_index` (`state`),
  KEY `places_score_index` (`score`),
  KEY `places_last_checked_at_index` (`last_checked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `poll_votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `poll_votes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `story_id` bigint(20) unsigned DEFAULT NULL,
  `status_id` bigint(20) unsigned DEFAULT NULL,
  `profile_id` bigint(20) unsigned NOT NULL,
  `poll_id` bigint(20) unsigned NOT NULL,
  `choice` int(10) unsigned NOT NULL DEFAULT 0,
  `uri` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `poll_votes_story_id_index` (`story_id`),
  KEY `poll_votes_status_id_index` (`status_id`),
  KEY `poll_votes_profile_id_index` (`profile_id`),
  KEY `poll_votes_poll_id_index` (`poll_id`),
  KEY `poll_votes_choice_index` (`choice`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `polls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `polls` (
  `id` bigint(20) unsigned NOT NULL,
  `story_id` bigint(20) unsigned DEFAULT NULL,
  `status_id` bigint(20) unsigned DEFAULT NULL,
  `group_id` bigint(20) unsigned DEFAULT NULL,
  `profile_id` bigint(20) unsigned NOT NULL,
  `poll_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`poll_options`)),
  `cached_tallies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`cached_tallies`)),
  `multiple` tinyint(1) NOT NULL DEFAULT 0,
  `hide_totals` tinyint(1) NOT NULL DEFAULT 0,
  `votes_count` int(10) unsigned NOT NULL DEFAULT 0,
  `last_fetched_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `polls_story_id_index` (`story_id`),
  KEY `polls_status_id_index` (`status_id`),
  KEY `polls_group_id_index` (`group_id`),
  KEY `polls_profile_id_index` (`profile_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `portfolios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `portfolios` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `profile_id` bigint(20) unsigned NOT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `show_captions` tinyint(1) DEFAULT 1,
  `show_license` tinyint(1) DEFAULT 1,
  `show_location` tinyint(1) DEFAULT 1,
  `show_timestamp` tinyint(1) DEFAULT 1,
  `show_link` tinyint(1) DEFAULT 1,
  `profile_source` varchar(191) DEFAULT 'recent',
  `show_avatar` tinyint(1) DEFAULT 1,
  `show_bio` tinyint(1) DEFAULT 1,
  `profile_layout` varchar(191) DEFAULT 'grid',
  `profile_container` varchar(191) DEFAULT 'fixed',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `portfolios_profile_id_unique` (`profile_id`),
  UNIQUE KEY `portfolios_user_id_unique` (`user_id`),
  KEY `portfolios_active_index` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `profile_aliases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `profile_aliases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `profile_id` bigint(20) unsigned DEFAULT NULL,
  `acct` varchar(191) DEFAULT NULL,
  `uri` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `profile_id_acct_unique` (`profile_id`,`acct`),
  KEY `profile_aliases_profile_id_index` (`profile_id`),
  CONSTRAINT `profile_aliases_profile_id_foreign` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `profile_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `profile_migrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `profile_id` bigint(20) unsigned NOT NULL,
  `acct` varchar(191) DEFAULT NULL,
  `followers_count` bigint(20) unsigned NOT NULL DEFAULT 0,
  `target_profile_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `profile_sponsors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `profile_sponsors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `profile_id` bigint(20) unsigned NOT NULL,
  `sponsors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sponsors`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `profile_sponsors_profile_id_unique` (`profile_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `domain` varchar(191) DEFAULT NULL,
  `username` varchar(191) DEFAULT NULL,
  `status` varchar(191) DEFAULT NULL,
  `name` varchar(191) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `unlisted` tinyint(1) NOT NULL DEFAULT 0,
  `cw` tinyint(1) NOT NULL DEFAULT 0,
  `no_autolink` tinyint(1) NOT NULL DEFAULT 0,
  `location` varchar(191) DEFAULT NULL,
  `website` varchar(191) DEFAULT NULL,
  `profile_layout` varchar(191) DEFAULT NULL,
  `header_bg` varchar(191) DEFAULT NULL,
  `post_layout` varchar(191) DEFAULT NULL,
  `is_private` tinyint(1) NOT NULL DEFAULT 0,
  `sharedInbox` varchar(191) DEFAULT NULL,
  `inbox_url` varchar(191) DEFAULT NULL,
  `outbox_url` varchar(191) DEFAULT NULL,
  `key_id` varchar(191) DEFAULT NULL,
  `follower_url` varchar(191) DEFAULT NULL,
  `following_url` varchar(191) DEFAULT NULL,
  `private_key` text DEFAULT NULL,
  `public_key` text DEFAULT NULL,
  `remote_url` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `delete_after` timestamp NULL DEFAULT NULL,
  `is_suggestable` tinyint(1) NOT NULL DEFAULT 0,
  `last_fetched_at` timestamp NULL DEFAULT NULL,
  `status_count` int(10) unsigned DEFAULT 0,
  `followers_count` int(10) unsigned DEFAULT 0,
  `following_count` int(10) unsigned DEFAULT 0,
  `webfinger` varchar(191) DEFAULT NULL,
  `avatar_url` varchar(191) DEFAULT NULL,
  `last_status_at` timestamp NULL DEFAULT NULL,
  `moved_to_profile_id` bigint(20) unsigned DEFAULT NULL,
  `indexable` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `profiles_domain_username_unique` (`domain`,`username`),
  UNIQUE KEY `profiles_key_id_unique` (`key_id`),
  UNIQUE KEY `profiles_webfinger_unique` (`webfinger`),
  KEY `profiles_username_index` (`username`),
  KEY `profiles_sharedinbox_index` (`sharedInbox`),
  KEY `profiles_status_index` (`status`),
  KEY `profiles_unlisted_index` (`unlisted`),
  KEY `profiles_cw_index` (`cw`),
  KEY `profiles_no_autolink_index` (`no_autolink`),
  KEY `profiles_domain_index` (`domain`),
  KEY `profiles_deleted_at_index` (`deleted_at`),
  KEY `profiles_is_suggestable_index` (`is_suggestable`),
  KEY `profiles_key_id_index` (`key_id`),
  KEY `profiles_remote_url_index` (`remote_url`),
  KEY `profiles_user_id_index` (`user_id`),
  KEY `profiles_last_fetched_at_index` (`last_fetched_at`),
  KEY `profiles_indexable_index` (`indexable`),
  KEY `profiles_followers_count_index` (`followers_count`),
  KEY `profiles_following_count_index` (`following_count`),
  KEY `profiles_status_count_index` (`status_count`),
  KEY `profiles_is_private_index` (`is_private`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pulse_aggregates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pulse_aggregates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `bucket` int(10) unsigned NOT NULL,
  `period` mediumint(8) unsigned NOT NULL,
  `type` varchar(191) NOT NULL,
  `key` mediumtext NOT NULL,
  `key_hash` binary(16) GENERATED ALWAYS AS (unhex(md5(`key`))) VIRTUAL,
  `aggregate` varchar(191) NOT NULL,
  `value` decimal(20,2) NOT NULL,
  `count` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pulse_aggregates_bucket_period_type_aggregate_key_hash_unique` (`bucket`,`period`,`type`,`aggregate`,`key_hash`),
  KEY `pulse_aggregates_period_bucket_index` (`period`,`bucket`),
  KEY `pulse_aggregates_type_index` (`type`),
  KEY `pulse_aggregates_period_type_aggregate_bucket_index` (`period`,`type`,`aggregate`,`bucket`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pulse_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pulse_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` int(10) unsigned NOT NULL,
  `type` varchar(191) NOT NULL,
  `key` mediumtext NOT NULL,
  `key_hash` binary(16) GENERATED ALWAYS AS (unhex(md5(`key`))) VIRTUAL,
  `value` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pulse_entries_timestamp_index` (`timestamp`),
  KEY `pulse_entries_type_index` (`type`),
  KEY `pulse_entries_key_hash_index` (`key_hash`),
  KEY `pulse_entries_timestamp_type_key_hash_value_index` (`timestamp`,`type`,`key_hash`,`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pulse_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pulse_values` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` int(10) unsigned NOT NULL,
  `type` varchar(191) NOT NULL,
  `key` mediumtext NOT NULL,
  `key_hash` binary(16) GENERATED ALWAYS AS (unhex(md5(`key`))) VIRTUAL,
  `value` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pulse_values_type_key_hash_unique` (`type`,`key_hash`),
  KEY `pulse_values_timestamp_index` (`timestamp`),
  KEY `pulse_values_type_index` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `push_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `push_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subscribable_type` varchar(191) NOT NULL,
  `subscribable_id` bigint(20) unsigned NOT NULL,
  `endpoint` varchar(500) NOT NULL,
  `public_key` varchar(191) DEFAULT NULL,
  `auth_token` varchar(191) DEFAULT NULL,
  `content_encoding` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `push_subscriptions_endpoint_unique` (`endpoint`),
  KEY `push_subscriptions_subscribable_type_subscribable_id_index` (`subscribable_type`,`subscribable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `remote_auth_instances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `remote_auth_instances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `domain` varchar(191) DEFAULT NULL,
  `instance_id` int(10) unsigned DEFAULT NULL,
  `client_id` varchar(191) DEFAULT NULL,
  `client_secret` varchar(191) DEFAULT NULL,
  `redirect_uri` varchar(191) DEFAULT NULL,
  `root_domain` varchar(191) DEFAULT NULL,
  `allowed` tinyint(1) DEFAULT NULL,
  `banned` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `last_refreshed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `remote_auth_instances_domain_unique` (`domain`),
  KEY `remote_auth_instances_instance_id_index` (`instance_id`),
  KEY `remote_auth_instances_root_domain_index` (`root_domain`),
  KEY `remote_auth_instances_allowed_index` (`allowed`),
  KEY `remote_auth_instances_banned_index` (`banned`),
  KEY `remote_auth_instances_active_index` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `remote_auths`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `remote_auths` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `software` varchar(191) DEFAULT NULL,
  `domain` varchar(191) DEFAULT NULL,
  `webfinger` varchar(191) DEFAULT NULL,
  `instance_id` int(10) unsigned DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `client_id` int(10) unsigned DEFAULT NULL,
  `ip_address` varchar(191) DEFAULT NULL,
  `bearer_token` text DEFAULT NULL,
  `verify_credentials` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`verify_credentials`)),
  `last_successful_login_at` timestamp NULL DEFAULT NULL,
  `last_verify_credentials_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `remote_auths_webfinger_unique` (`webfinger`),
  UNIQUE KEY `remote_auths_user_id_unique` (`user_id`),
  KEY `remote_auths_domain_index` (`domain`),
  KEY `remote_auths_instance_id_index` (`instance_id`),
  KEY `remote_auths_client_id_index` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `remote_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `remote_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `status_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`status_ids`)),
  `comment` text DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `uri` varchar(191) DEFAULT NULL,
  `instance_id` int(10) unsigned DEFAULT NULL,
  `action_taken_at` timestamp NULL DEFAULT NULL,
  `report_meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`report_meta`)),
  `action_taken_meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`action_taken_meta`)),
  `action_taken_by_account_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `remote_reports_action_taken_at_index` (`action_taken_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `report_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `report_comments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint(20) unsigned NOT NULL,
  `profile_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `report_comments_report_id_index` (`report_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `report_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `report_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `profile_id` bigint(20) unsigned NOT NULL,
  `item_id` bigint(20) unsigned DEFAULT NULL,
  `item_type` varchar(191) DEFAULT NULL,
  `action` varchar(191) DEFAULT NULL,
  `system_message` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `profile_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `object_id` bigint(20) unsigned NOT NULL,
  `object_type` varchar(191) DEFAULT NULL,
  `reported_profile_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(191) DEFAULT NULL,
  `message` varchar(191) DEFAULT NULL,
  `admin_seen` timestamp NULL DEFAULT NULL,
  `not_interested` tinyint(1) NOT NULL DEFAULT 0,
  `spam` tinyint(1) NOT NULL DEFAULT 0,
  `nsfw` tinyint(1) NOT NULL DEFAULT 0,
  `abusive` tinyint(1) NOT NULL DEFAULT 0,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reports_user_id_object_type_object_id_unique` (`user_id`,`object_type`,`object_id`),
  KEY `reports_user_id_index` (`user_id`),
  KEY `reports_profile_id_index` (`profile_id`),
  KEY `reports_object_id_index` (`object_id`),
  KEY `reports_object_type_index` (`object_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(191) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` text NOT NULL,
  `last_activity` int(11) NOT NULL,
  UNIQUE KEY `sessions_id_unique` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `status_archiveds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `status_archiveds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `status_id` bigint(20) unsigned NOT NULL,
  `profile_id` bigint(20) unsigned NOT NULL,
  `original_scope` varchar(191) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status_archiveds_status_id_index` (`status_id`),
  KEY `status_archiveds_profile_id_index` (`profile_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `status_edits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `status_edits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `status_id` bigint(20) unsigned NOT NULL,
  `profile_id` bigint(20) unsigned NOT NULL,
  `caption` text DEFAULT NULL,
  `spoiler_text` text DEFAULT NULL,
  `ordered_media_attachment_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ordered_media_attachment_ids`)),
  `media_descriptions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`media_descriptions`)),
  `poll_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`poll_options`)),
  `is_nsfw` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status_edits_status_id_index` (`status_id`),
  KEY `status_edits_profile_id_index` (`profile_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `status_hashtags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `status_hashtags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `status_id` bigint(20) unsigned NOT NULL,
  `hashtag_id` bigint(20) unsigned NOT NULL,
  `profile_id` bigint(20) unsigned DEFAULT NULL,
  `status_visibility` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `status_hashtags_status_id_hashtag_id_unique` (`status_id`,`hashtag_id`),
  KEY `status_hashtags_status_id_index` (`status_id`),
  KEY `status_hashtags_hashtag_id_index` (`hashtag_id`),
  KEY `status_hashtags_profile_id_index` (`profile_id`),
  KEY `status_hashtags_status_visibility_index` (`status_visibility`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `status_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `status_views` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `status_id` bigint(20) unsigned DEFAULT NULL,
  `status_profile_id` bigint(20) unsigned DEFAULT NULL,
  `profile_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `status_views_status_id_profile_id_unique` (`status_id`,`profile_id`),
  KEY `status_views_status_id_index` (`status_id`),
  KEY `status_views_status_profile_id_index` (`status_profile_id`),
  KEY `status_views_profile_id_index` (`profile_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `statuses` (
  `id` bigint(20) unsigned NOT NULL,
  `uri` varchar(191) DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `rendered` text DEFAULT NULL,
  `profile_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(191) DEFAULT NULL,
  `in_reply_to_id` bigint(20) unsigned DEFAULT NULL,
  `reblog_of_id` bigint(20) unsigned DEFAULT NULL,
  `url` varchar(191) DEFAULT NULL,
  `is_nsfw` tinyint(1) NOT NULL DEFAULT 0,
  `scope` varchar(191) NOT NULL DEFAULT 'public',
  `visibility` enum('public','unlisted','private','direct','draft') NOT NULL DEFAULT 'public',
  `reply` tinyint(1) NOT NULL DEFAULT 0,
  `likes_count` bigint(20) unsigned NOT NULL DEFAULT 0,
  `reblogs_count` bigint(20) unsigned NOT NULL DEFAULT 0,
  `language` varchar(191) DEFAULT NULL,
  `conversation_id` bigint(20) unsigned DEFAULT NULL,
  `local` tinyint(1) NOT NULL DEFAULT 1,
  `application_id` bigint(20) unsigned DEFAULT NULL,
  `in_reply_to_profile_id` bigint(20) unsigned DEFAULT NULL,
  `entities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`entities`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `cw_summary` varchar(191) DEFAULT NULL,
  `reply_count` int(10) unsigned DEFAULT NULL,
  `comments_disabled` tinyint(1) NOT NULL DEFAULT 0,
  `place_id` bigint(20) unsigned DEFAULT NULL,
  `object_url` varchar(191) DEFAULT NULL,
  `edited_at` timestamp NULL DEFAULT NULL,
  `trendable` tinyint(1) DEFAULT NULL,
  `media_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`media_ids`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `statuses_uri_unique` (`uri`),
  UNIQUE KEY `statuses_object_url_unique` (`object_url`),
  KEY `statuses_scope_index` (`scope`),
  KEY `statuses_type_index` (`type`),
  KEY `statuses_visibility_index` (`visibility`),
  KEY `statuses_in_reply_or_reblog_index` (`in_reply_to_id`,`reblog_of_id`),
  KEY `statuses_uri_index` (`uri`),
  KEY `statuses_is_nsfw_index` (`is_nsfw`),
  KEY `statuses_created_at_index` (`created_at`),
  KEY `statuses_profile_id_index` (`profile_id`),
  KEY `statuses_local_index` (`local`),
  KEY `statuses_deleted_at_index` (`deleted_at`),
  KEY `statuses_place_id_index` (`place_id`),
  KEY `statuses_in_reply_to_id_index` (`in_reply_to_id`),
  KEY `statuses_reblog_of_id_index` (`reblog_of_id`),
  KEY `statuses_url_index` (`url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `profile_id` bigint(20) unsigned NOT NULL,
  `type` varchar(191) DEFAULT NULL,
  `size` int(10) unsigned DEFAULT NULL,
  `mime` varchar(191) DEFAULT NULL,
  `duration` smallint(5) unsigned NOT NULL,
  `path` varchar(191) DEFAULT NULL,
  `remote_url` varchar(191) DEFAULT NULL,
  `media_url` varchar(191) DEFAULT NULL,
  `cdn_url` varchar(191) DEFAULT NULL,
  `public` tinyint(1) NOT NULL DEFAULT 0,
  `local` tinyint(1) NOT NULL DEFAULT 0,
  `view_count` int(10) unsigned DEFAULT NULL,
  `comment_count` int(10) unsigned DEFAULT NULL,
  `story` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`story`)),
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `name` varchar(191) DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `can_reply` tinyint(1) NOT NULL DEFAULT 1,
  `can_react` tinyint(1) NOT NULL DEFAULT 1,
  `object_id` varchar(191) DEFAULT NULL,
  `object_uri` varchar(191) DEFAULT NULL,
  `bearcap_token` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stories_profile_id_path_unique` (`profile_id`,`path`),
  UNIQUE KEY `stories_remote_url_unique` (`remote_url`),
  UNIQUE KEY `stories_media_url_unique` (`media_url`),
  UNIQUE KEY `stories_object_id_unique` (`object_id`),
  UNIQUE KEY `stories_object_uri_unique` (`object_uri`),
  KEY `stories_profile_id_index` (`profile_id`),
  KEY `stories_public_index` (`public`),
  KEY `stories_local_index` (`local`),
  KEY `stories_is_archived_index` (`is_archived`),
  KEY `stories_active_index` (`active`),
  KEY `stories_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `story_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `story_views` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `story_id` bigint(20) unsigned NOT NULL,
  `profile_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `story_views_profile_id_story_id_unique` (`profile_id`,`story_id`),
  KEY `story_views_story_id_index` (`story_id`),
  KEY `story_views_profile_id_index` (`profile_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `telescope_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `telescope_entries` (
  `sequence` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `batch_id` char(36) NOT NULL,
  `family_hash` varchar(191) DEFAULT NULL,
  `should_display_on_index` tinyint(1) NOT NULL DEFAULT 1,
  `type` varchar(20) NOT NULL,
  `content` longtext NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`sequence`),
  UNIQUE KEY `telescope_entries_uuid_unique` (`uuid`),
  KEY `telescope_entries_batch_id_index` (`batch_id`),
  KEY `telescope_entries_family_hash_index` (`family_hash`),
  KEY `telescope_entries_created_at_index` (`created_at`),
  KEY `telescope_entries_type_should_display_on_index_index` (`type`,`should_display_on_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `telescope_entries_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `telescope_entries_tags` (
  `entry_uuid` char(36) NOT NULL,
  `tag` varchar(191) NOT NULL,
  PRIMARY KEY (`entry_uuid`,`tag`),
  KEY `telescope_entries_tags_tag_index` (`tag`),
  CONSTRAINT `telescope_entries_tags_entry_uuid_foreign` FOREIGN KEY (`entry_uuid`) REFERENCES `telescope_entries` (`uuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `telescope_monitoring`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `telescope_monitoring` (
  `tag` varchar(191) NOT NULL,
  PRIMARY KEY (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `uikit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uikit` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `k` varchar(191) NOT NULL,
  `v` text DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `defv` text DEFAULT NULL,
  `dhis` text DEFAULT NULL,
  `edit_count` int(10) unsigned DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uikit_k_unique` (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_app_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_app_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `profile_id` bigint(20) unsigned NOT NULL,
  `common` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`common`)),
  `custom` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_app_settings_user_id_unique` (`user_id`),
  UNIQUE KEY `user_app_settings_profile_id_unique` (`profile_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_devices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `ip` varchar(191) NOT NULL,
  `user_agent` varchar(191) NOT NULL,
  `fingerprint` varchar(191) DEFAULT NULL,
  `name` varchar(191) DEFAULT NULL,
  `trusted` tinyint(1) DEFAULT NULL,
  `last_active_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_ip_agent_index` (`user_id`,`ip`,`user_agent`,`fingerprint`),
  KEY `user_devices_user_id_index` (`user_id`),
  KEY `user_devices_ip_index` (`ip`),
  KEY `user_devices_user_agent_index` (`user_agent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_domain_blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_domain_blocks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `profile_id` bigint(20) unsigned NOT NULL,
  `domain` varchar(191) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_domain_blocks_by_id` (`profile_id`,`domain`),
  KEY `user_domain_blocks_profile_id_index` (`profile_id`),
  KEY `user_domain_blocks_domain_index` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_email_forgots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_email_forgots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `ip_address` varchar(191) DEFAULT NULL,
  `user_agent` varchar(191) DEFAULT NULL,
  `referrer` varchar(191) DEFAULT NULL,
  `email_sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_email_forgots_user_id_index` (`user_id`),
  KEY `user_email_forgots_email_sent_at_index` (`email_sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_filters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_filters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `filterable_id` bigint(20) unsigned NOT NULL,
  `filterable_type` varchar(191) NOT NULL,
  `filter_type` varchar(191) NOT NULL DEFAULT 'block',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `filter_unique` (`user_id`,`filterable_id`,`filterable_type`,`filter_type`),
  KEY `user_filters_user_id_index` (`user_id`),
  KEY `user_filters_filter_type_index` (`filter_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_invites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_invites` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `profile_id` bigint(20) unsigned NOT NULL,
  `email` varchar(191) NOT NULL,
  `message` text DEFAULT NULL,
  `key` varchar(191) NOT NULL,
  `token` varchar(191) NOT NULL,
  `valid_until` timestamp NULL DEFAULT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_invites_email_unique` (`email`),
  KEY `user_invites_user_id_index` (`user_id`),
  KEY `user_invites_profile_id_index` (`profile_id`),
  KEY `user_invites_used_at_index` (`used_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_pronouns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_pronouns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `profile_id` bigint(20) NOT NULL,
  `pronouns` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pronouns`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_pronouns_profile_id_unique` (`profile_id`),
  UNIQUE KEY `user_pronouns_user_id_unique` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `profile_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`roles`)),
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_roles_user_id_unique` (`user_id`),
  UNIQUE KEY `user_roles_profile_id_unique` (`profile_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `role` varchar(191) NOT NULL DEFAULT 'user',
  `crawlable` tinyint(1) NOT NULL DEFAULT 1,
  `show_guests` tinyint(1) NOT NULL DEFAULT 1,
  `show_discover` tinyint(1) NOT NULL DEFAULT 1,
  `public_dm` tinyint(1) NOT NULL DEFAULT 0,
  `hide_cw_search` tinyint(1) NOT NULL DEFAULT 1,
  `hide_blocked_search` tinyint(1) NOT NULL DEFAULT 1,
  `always_show_cw` tinyint(1) NOT NULL DEFAULT 0,
  `compose_media_descriptions` tinyint(1) NOT NULL DEFAULT 0,
  `reduce_motion` tinyint(1) NOT NULL DEFAULT 0,
  `optimize_screen_reader` tinyint(1) NOT NULL DEFAULT 0,
  `high_contrast_mode` tinyint(1) NOT NULL DEFAULT 0,
  `video_autoplay` tinyint(1) NOT NULL DEFAULT 0,
  `send_email_new_follower` tinyint(1) NOT NULL DEFAULT 0,
  `send_email_new_follower_request` tinyint(1) NOT NULL DEFAULT 1,
  `send_email_on_share` tinyint(1) NOT NULL DEFAULT 0,
  `send_email_on_like` tinyint(1) NOT NULL DEFAULT 0,
  `send_email_on_mention` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `show_profile_followers` tinyint(1) NOT NULL DEFAULT 1,
  `show_profile_follower_count` tinyint(1) NOT NULL DEFAULT 1,
  `show_profile_following` tinyint(1) NOT NULL DEFAULT 1,
  `show_profile_following_count` tinyint(1) NOT NULL DEFAULT 1,
  `compose_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`compose_settings`)),
  `other` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`other`)),
  `show_atom` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_settings_user_id_unique` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `profile_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(191) DEFAULT NULL,
  `username` varchar(191) DEFAULT NULL,
  `email` varchar(191) NOT NULL,
  `status` varchar(191) DEFAULT NULL,
  `language` varchar(191) DEFAULT NULL,
  `password` varchar(191) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `last_active_at` timestamp NULL DEFAULT NULL,
  `2fa_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `2fa_secret` varchar(191) DEFAULT NULL,
  `2fa_backup_codes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`2fa_backup_codes`)),
  `2fa_setup_at` timestamp NULL DEFAULT NULL,
  `delete_after` timestamp NULL DEFAULT NULL,
  `has_interstitial` tinyint(1) NOT NULL DEFAULT 0,
  `guid` varchar(191) DEFAULT NULL,
  `domain` varchar(191) DEFAULT NULL,
  `register_source` varchar(191) DEFAULT 'web',
  `app_register_token` varchar(191) DEFAULT NULL,
  `app_register_ip` varchar(191) DEFAULT NULL,
  `has_roles` tinyint(1) NOT NULL DEFAULT 0,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `role_id` tinyint(3) unsigned DEFAULT NULL,
  `expo_token` varchar(191) DEFAULT NULL,
  `notify_like` tinyint(1) NOT NULL DEFAULT 1,
  `notify_follow` tinyint(1) NOT NULL DEFAULT 1,
  `notify_mention` tinyint(1) NOT NULL DEFAULT 1,
  `notify_comment` tinyint(1) NOT NULL DEFAULT 1,
  `storage_used` bigint(20) unsigned NOT NULL DEFAULT 0,
  `storage_used_updated_at` timestamp NULL DEFAULT NULL,
  `notify_enabled` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_profile_id_unique` (`profile_id`),
  UNIQUE KEY `users_guid_unique` (`guid`),
  KEY `users_status_index` (`status`),
  KEY `users_deleted_at_index` (`deleted_at`),
  KEY `users_language_index` (`language`),
  KEY `users_has_interstitial_index` (`has_interstitial`),
  KEY `users_last_active_at_index` (`last_active_at`),
  KEY `users_register_source_index` (`register_source`),
  KEY `users_role_id_index` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `websockets_statistics_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `websockets_statistics_entries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `app_id` varchar(191) NOT NULL,
  `peak_connection_count` int(11) NOT NULL,
  `websocket_message_count` int(11) NOT NULL,
  `api_message_count` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

/*M!999999\- enable the sandbox mode */ 
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2014_10_12_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2014_10_12_100000_create_password_resets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2016_06_01_000001_create_oauth_auth_codes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2016_06_01_000002_create_oauth_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2016_06_01_000003_create_oauth_refresh_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2016_06_01_000004_create_oauth_clients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2016_06_01_000005_create_oauth_personal_access_clients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2018_04_16_000059_create_sessions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2018_04_16_002611_create_profiles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2018_04_16_005848_create_statuses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2018_04_16_011918_create_media_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2018_04_17_012812_create_likes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2018_04_18_021047_create_followers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2018_04_18_044421_create_comments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2018_04_22_233721_create_web_subs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2018_04_26_000057_create_import_datas_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2018_04_26_003259_create_import_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2018_04_30_044507_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2018_04_30_044539_create_failed_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2018_05_04_054007_create_reports_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2018_05_06_214815_create_hashtags_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2018_05_06_215006_create_status_hashtags_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2018_05_07_021835_create_avatars_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2018_05_07_025743_create_notifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2018_05_31_043327_create_bookmarks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2018_06_04_061435_update_notifications_table_add_polymorphic_relationship',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2018_06_08_003624_create_mentions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2018_06_11_030049_add_filters_to_media_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2018_06_14_001318_add_soft_deletes_to_models',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2018_06_14_041422_create_email_verifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2018_06_22_062621_create_report_comments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2018_06_22_062628_create_report_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2018_07_05_010303_create_account_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2018_07_12_054015_create_user_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2018_07_15_011916_add_2fa_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2018_07_15_013106_create_user_filters_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2018_08_08_100000_create_telescope_entries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2018_08_12_042648_update_status_table_change_caption_to_text',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2018_08_22_022306_update_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2018_08_27_004653_update_media_table_add_alt_text',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2018_09_02_042235_create_follow_requests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2018_09_02_043240_update_profile_table_add_ap_urls',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2018_09_02_043609_create_instances_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2018_09_10_024252_update_import_datas_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2018_09_11_202435_create_activities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2018_09_18_043334_add_cw_desc_to_status',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2018_09_19_060554_create_stories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2018_09_19_060611_create_story_reactions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2018_09_27_040314_create_collections_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2018_09_30_051108_create_direct_messages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2018_10_02_040917_create_collection_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2018_10_09_043717_update_status_visibility_defaults',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2018_10_17_033327_update_status_add_scope_column',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2018_10_17_233623_update_follower_table_add_remote_flags',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2018_10_18_035552_update_media_add_alt_text',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2018_10_25_030944_update_profile_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2018_12_01_020238_add_type_to_status_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2018_12_22_055940_add_account_status_to_profiles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2018_12_24_032921_add_delete_after_to_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2018_12_30_065102_update_profiles_table_use_text_for_bio',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2019_01_11_005556_update_profiles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2019_01_12_054413_stories',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2019_01_22_030129_create_pages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2019_02_01_023357_add_remote_to_avatars_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2019_02_07_004642_create_discover_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2019_02_07_021214_create_discover_category_hashtags_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2019_02_08_192219_create_websockets_statistics_entries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2019_02_09_045935_create_circles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2019_02_09_045956_create_circle_profiles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2019_02_13_195702_add_indexes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2019_02_13_221138_add_soft_delete_indexes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2019_02_15_033323_create_user_invites_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2019_03_02_023245_add_profile_id_to_status_hashtags_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2019_03_06_065528_create_user_devices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2019_03_12_043935_add_snowflakeids_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2019_03_31_191216_add_replies_count_to_statuses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2019_04_16_184644_add_layout_to_profiles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2019_04_25_200411_add_snowflake_ids_to_collections_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2019_04_28_024733_add_suggestions_to_profiles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2019_05_04_174911_add_header_to_profiles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2019_06_06_032316_create_contacts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2019_06_16_051157_add_profile_ids_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2019_07_05_034644_create_hashtag_follows_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2019_07_08_045824_add_status_visibility_to_status_hashtags_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2019_07_11_234836_create_profile_sponsors_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2019_07_16_010525_remove_web_subs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2019_08_07_184030_create_places_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2019_08_12_074612_add_unique_to_statuses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2019_09_09_032757_add_object_id_to_statuses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2019_09_21_015556_add_language_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2019_12_10_023604_create_newsroom_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2019_12_25_042317_update_stories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2020_02_14_063209_create_mod_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2020_04_11_045459_add_fetched_at_to_profiles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2020_04_13_045435_create_uikit_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2020_06_30_180159_create_media_tags_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2020_07_25_230100_create_media_blocklists_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2020_08_18_022520_add_remote_url_to_stories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2020_11_14_221947_add_type_to_direct_messages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2020_12_01_073200_add_indexes_to_likes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2020_12_03_050018_create_account_interstitials_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2020_12_13_203138_add_uuids_to_failed_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2020_12_13_203646_add_providers_column_to_oauth_clients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2020_12_14_103423_create_login_links_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2020_12_24_063410_create_status_views_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2020_12_25_220825_add_status_profile_id_to_likes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2020_12_27_013953_add_text_column_to_media_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2020_12_27_040951_add_skip_optimize_to_media_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2020_12_28_012026_create_status_archiveds_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2020_12_30_070905_add_last_active_at_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2021_01_14_034521_add_cache_locks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2021_01_15_050602_create_instance_actors_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2021_01_25_011355_add_cdn_url_to_avatars_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2021_04_24_045522_add_active_to_stories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2021_04_28_060450_create_config_caches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2021_05_12_042153_create_user_pronouns_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (117,'2021_07_23_062326_add_compose_settings_to_user_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (118,'2021_07_29_014835_create_polls_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (119,'2021_07_29_014849_create_poll_votes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (120,'2021_08_04_095125_create_groups_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (121,'2021_08_04_095143_create_group_members_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (122,'2021_08_04_095238_create_group_posts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (123,'2021_08_04_100435_create_group_roles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (124,'2021_08_16_072457_create_group_invitations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (125,'2021_08_16_100034_create_group_interactions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (126,'2021_08_17_073839_create_group_reports_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (127,'2021_08_23_062246_update_stories_table_fix_expires_at_column',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (128,'2021_08_30_050137_add_software_column_to_instances_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (129,'2021_09_26_112423_create_group_blocks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (130,'2021_09_29_023230_create_group_limits_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (131,'2021_10_01_083917_create_group_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (132,'2021_10_09_004230_create_group_hashtags_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (133,'2021_10_09_004436_create_group_post_hashtags_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (134,'2021_10_13_002033_create_group_stores_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (135,'2021_10_13_002041_create_group_events_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (136,'2021_10_13_002124_create_group_activity_graphs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (137,'2021_11_06_100552_add_more_settings_to_user_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (138,'2021_11_09_105629_add_action_to_account_interstitials_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (139,'2022_01_03_052623_add_last_status_at_to_profiles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (140,'2022_01_08_103817_add_index_to_followers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (141,'2022_01_16_060052_create_portfolios_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (142,'2022_01_19_025041_create_custom_emoji_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (143,'2022_02_13_091135_add_missing_reblog_of_id_types_to_statuses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (144,'2022_03_09_042023_add_ldap_columns_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (145,'2022_04_08_065311_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (146,'2022_04_20_061915_create_conversations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (147,'2022_05_26_034550_create_live_streams_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (148,'2022_06_03_051308_add_object_column_to_follow_requests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (149,'2022_09_01_000000_fix_webfinger_profile_duplicate_accounts',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (150,'2022_09_01_043002_generate_missing_profile_webfinger',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (151,'2022_09_19_093029_fix_double_json_encoded_settings_in_usersettings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (152,'2022_10_07_045520_add_reblog_of_id_index_to_statuses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (153,'2022_10_07_055133_remove_old_compound_index_from_statuses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (154,'2022_10_07_072311_add_status_id_index_to_bookmarks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (155,'2022_10_07_072555_add_status_id_index_to_direct_messages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (156,'2022_10_07_072859_add_status_id_index_to_mentions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (157,'2022_10_07_073337_add_indexes_to_reports_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (158,'2022_10_07_110644_add_item_id_and_item_type_indexes_to_notifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (159,'2022_10_09_043758_fix_cdn_url_in_avatars_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (160,'2022_10_31_043257_add_actors_last_synced_at_to_instances_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (161,'2022_11_24_065214_add_register_source_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (162,'2022_11_30_123940_update_avatars_table_remove_cdn_url_unique_constraint',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (163,'2022_12_05_064156_add_key_id_index_to_profiles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (164,'2022_12_13_092726_create_admin_invites_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (165,'2022_12_18_012352_add_status_id_index_to_media_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (166,'2022_12_18_034556_add_remote_media_index_to_media_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (167,'2022_12_18_133815_add_default_value_to_admin_invites_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (168,'2022_12_20_075729_add_action_index_to_notifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (169,'2022_12_27_013417_add_can_trend_to_hashtags_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (170,'2022_12_27_102053_update_hashtag_count',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (171,'2022_12_31_034627_fix_account_status_deletes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (172,'2023_01_15_041933_add_missing_profile_id_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (173,'2023_01_19_141156_fix_bookmark_visibility',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (174,'2023_01_21_124608_fix_duplicate_profiles',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (175,'2023_01_29_034653_create_status_edits_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (176,'2023_02_04_053028_fix_cloud_media_paths',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (177,'2023_03_19_050342_add_notes_to_instances_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (178,'2023_04_20_092740_fix_account_blocks',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (179,'2023_04_24_101904_create_remote_reports_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (180,'2023_05_03_023758_update_postgres_visibility_defaults_on_statuses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (181,'2023_05_03_042219_fix_postgres_hashtags',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (182,'2023_05_07_091703_add_edited_at_to_statuses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (183,'2023_05_13_045228_remove_unused_columns_from_notifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (184,'2023_05_13_123119_remove_status_entities_from_statuses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (185,'2023_05_15_050604_create_autospam_custom_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (186,'2023_05_19_102013_add_enable_atom_feed_to_user_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (187,'2023_05_29_072206_create_user_app_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (188,'2023_06_07_000001_create_pulse_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (189,'2023_06_10_031634_create_import_posts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (190,'2023_06_28_103008_add_user_id_index_to_profiles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (191,'2023_07_07_025757_create_remote_auths_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (192,'2023_07_07_030427_create_remote_auth_instances_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (193,'2023_07_11_080040_add_show_reblogs_to_followers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (194,'2023_08_07_021252_create_profile_aliases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (195,'2023_08_08_045430_add_moved_to_profile_id_to_profiles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (196,'2023_08_25_050021_add_indexable_column_to_profiles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (197,'2023_09_12_044900_create_admin_shadow_filters_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (198,'2023_11_13_062429_add_followers_count_index_to_profiles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (199,'2023_11_16_124107_create_hashtag_related_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (200,'2023_11_26_082439_add_state_and_score_to_places_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (201,'2023_12_04_041631_create_push_subscriptions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (202,'2023_12_05_092152_add_active_deliver_to_instances_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (203,'2023_12_08_074345_add_direct_object_urls_to_statuses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (204,'2023_12_13_060425_add_uploaded_to_s3_to_import_posts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (205,'2023_12_16_052413_create_user_domain_blocks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (206,'2023_12_19_081928_create_job_batches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (207,'2023_12_21_103223_purge_deleted_status_hashtags',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (208,'2023_12_21_104103_create_default_domain_blocks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (209,'2023_12_27_081801_create_user_roles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (210,'2023_12_27_082024_add_has_roles_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (211,'2024_01_09_052419_create_parental_controls_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (212,'2024_01_16_073327_create_curated_registers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (213,'2024_01_20_091352_create_curated_register_activities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (214,'2024_01_22_090048_create_user_email_forgots_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (215,'2024_02_24_093824_add_has_responded_to_curated_registers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (216,'2024_02_24_105641_create_curated_register_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (217,'2024_03_02_094235_create_profile_migrations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (218,'2024_03_08_122947_add_shared_inbox_attribute_to_instances_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (219,'2024_03_08_123356_add_shared_inboxes_to_instances_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (220,'2024_05_20_062706_update_group_posts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (221,'2024_05_20_063638_create_group_comments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (222,'2024_05_20_073054_create_group_likes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (223,'2024_05_20_083159_create_group_media_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (224,'2024_05_31_090555_update_instances_table_add_index_to_nodeinfo_last_fetched_at',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (225,'2024_06_03_232204_add_url_index_to_statuses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (226,'2024_06_19_084835_add_total_local_posts_to_config_cache',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (227,'2024_07_22_065800_add_expo_token_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (228,'2024_07_29_081002_add_storage_used_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (229,'2024_09_18_093322_add_notify_shares_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (230,'2024_10_06_035032_modify_caption_field_in_media_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (231,'2024_10_15_044935_create_moderated_profiles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (232,'2025_01_18_061532_fix_local_statuses',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (233,'2025_01_28_102016_create_app_registers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (234,'2025_02_10_194847_fix_non_nullable_postgres_errors',1);
