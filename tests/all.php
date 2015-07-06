<?php
$tests_dir = dirname( __FILE__ );
$old_cwd   = getcwd();

chdir( $tests_dir );

function wpas_tests_load_recursively( $path ) {

	foreach ( scandir( $path ) as $file ) {

		if ( in_array( $file, array( '.', '..' ) ) ) {
			continue;
		}

		$filepath = $path . DIRECTORY_SEPARATOR . $file;

		if ( is_file( $filepath ) ) {
			if ( 'test-' === substr( $file, 0, 5 ) ) {
				include_once( $filepath );
			}
		}

		elseif ( is_dir( $filepath ) ) {
			wpas_tests_load_recursively( $filepath );
		}

	}

}

/**
 * Load all test files.
 */
wpas_tests_load_recursively( $tests_dir );

class all {
    public static function suite() {
        $suite = new PHPUnit_Framework_TestSuite();
		
		foreach( get_declared_classes() as $class ) {
			if ( is_subclass_of( $class, 'WP_UnitTestCase' ) ) {
				$suite->addTestSuite( $class );
			}
		}
        return $suite;
    }
}

chdir( $old_cwd );
