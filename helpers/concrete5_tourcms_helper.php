<?php
require_once('tourcms_helper.php');

class concrete5_tourcms_helper extends platform_helper {

	public function load_database() {
		if (class_exists("Loader")) {
			return Loader::db();		
		} else {
			return parent::load_database();
		}
	}

}
?>