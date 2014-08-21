<?php

	$helper = get_tourcms_helper();

	$code_root = WP_PLUGIN_DIR."/tourcms-adventure";
	include_once("site_config.php");
	include_once("functions.php");
	include_once("tourcms.php");
	
	SiteConfig::set("channel_id", $helper->get_channel_id());
	SiteConfig::set("api_private_key", $helper->get_api_key());
	SiteConfig::set("marketplace_id", 0);

	SiteConfig::set("page_title", "Pride of Maui");
	SiteConfig::set("cache_duration", 0);
	SiteConfig::set("cache_dir", "cache");
	SiteConfig::set("code_root", $code_root);	

	$tourcms = new TourCMS();