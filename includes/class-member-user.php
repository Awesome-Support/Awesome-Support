<?php
/**
 * Awesome Support User.
 *
 * @package   Awesome Support
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2014-2017 AwesomeSupport
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class WPAS_Member_User
 *
 * This class is used to work with Awesome Clients. It avoids using WP_User too much, hence avoiding caching issues
 * occurring with databases containing a large number of users.
 *
 * @since 3.3
 */
class WPAS_Member_User extends WPAS_Member {

	public function __construct( $user ) {
		parent::__construct( $user );
	}

}