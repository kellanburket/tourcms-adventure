<?php
Class SiteConfig
{
    protected static $config = array();
    
	private function __construct() {}
    
	public static function set($key, $val) {
        return self::$config[$key] = $val;
    }

    public static function get($key) {
        return self::$config[$key];
    }
}