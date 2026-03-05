<?php

namespace MUMEI_AYUDA_API\Admin;

class Init {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of \MUMEI_AYUDA_API\Admin
	 *
	 * @return Init
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Init ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	protected function __construct() {
		$this->includes();
	}

	protected function includes() {
		Settings::get_instance();
	}

}