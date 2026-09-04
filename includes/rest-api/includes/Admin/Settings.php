<?php


namespace WPAS_API\Admin;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of Settings
	 *
	 * @return Settings
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Settings ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	protected function __construct() {}

}