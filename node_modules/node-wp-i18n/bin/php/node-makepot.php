<?php
require_once( dirname( __FILE__ ) . '/makepot.php' );

/**
 * POT generation methods for node-wp-i18n.
 */
class NodeMakePOT extends MakePOT {
	/**
	 * Valid project types.
	 *
	 * @type array
	 */
	public $projects = array(
		'wp-plugin',
		'wp-theme',
	);

	/**
	 * Generate a POT file for a plugin.
	 *
	 * @param string $dir Directory to search for gettext calls.
	 * @param string $output POT file name.
	 * @param string $slug Optional. Plugin slug.
	 * @param string $main_file Optional. Plugin main {plugin-name}.php file path.
	 * @param string $excludes Optional. Comma-separated list of exclusion patterns.
	 * @param string $includes Optional. Comma-separated list of inclusion patterns.
	 * @return bool
	 */
	public function wp_plugin( $dir, $output, $slug = null, $main_file = null, $excludes = '', $includes = '' ) {
		$main_file = $dir . '/' . $main_file;
		$source = $this->get_first_lines( $main_file, $this->max_header_lines );
		$excludes = $this->normalize_patterns( $excludes );
		$includes = $this->normalize_patterns( $includes );

		$placeholders = array();
		$placeholders['version'] = $this->get_addon_header( 'Version', $source );
		$placeholders['author'] = $this->get_addon_header( 'Author', $source );
		$placeholders['name'] = $this->get_addon_header( 'Plugin Name', $source );
		$placeholders['slug'] = $slug;

		$license = $this->get_addon_header( 'License', $source );
		if ( $license ) {
			$this->meta['wp-plugin']['comments'] = "Copyright (C) {year} {author}\nThis file is distributed under the {$license}.";
		} else {
			$this->meta['wp-plugin']['comments'] = "Copyright (C) {year} {author}\nThis file is distributed under the same license as the {package-name} package.";
		}

		$result = $this->xgettext( 'wp-plugin', $dir, $output, $placeholders, $excludes, $includes );
		if ( ! $result ) {
			return false;
		}

		$potextmeta = new PotExtMeta;
		$result = $potextmeta->append( $main_file, $output );
		return $result;
	}

	/**
	 * Generate a POT file for a theme.
	 *
	 * @param string $dir Directory to search for gettext calls.
	 * @param string $output POT file name.
	 * @param string $slug Optional. Theme slug.
	 * @param string $main_file Optional. Theme main style.css file path.
	 * @param string $excludes Optional. Comma-separated list of exclusion patterns.
	 * @param string $includes Optional. Comma-separated list of inclusion patterns.
	 * @return bool
	 */
	public function wp_theme( $dir, $output, $slug = null, $main_file = null, $excludes = '', $includes = '' ) {
		$main_file = $dir . '/' . $main_file;
		$source = $this->get_first_lines( $main_file, $this->max_header_lines );
		$excludes = $this->normalize_patterns( $excludes );
		$includes = $this->normalize_patterns( $includes );

		$placeholders = array();
		$placeholders['version'] = $this->get_addon_header( 'Version', $source );
		$placeholders['author'] = $this->get_addon_header( 'Author', $source );
		$placeholders['name'] = $this->get_addon_header( 'Theme Name', $source );
		$placeholders['slug'] = $slug;

		$license = $this->get_addon_header( 'License', $source );
		if ( $license ) {
			$this->meta['wp-theme']['comments'] = "<!=Copyright (C) {year} {author}\nThis file is distributed under the {$license}.=!>";
		} else {
			$this->meta['wp-theme']['comments'] = "<!=Copyright (C) {year} {author}\nThis file is distributed under the same license as the {package-name} package.=!>";
		}

		$result = $this->xgettext( 'wp-theme', $dir, $output, $placeholders, $excludes, $includes );
		if ( ! $result ) {
			return false;
		}

		$potextmeta = new PotExtMeta;
		$result = $potextmeta->append( $main_file, $output, array( 'Theme Name', 'Theme URI', 'Description', 'Author', 'Author URI' ) );
		if ( ! $result ) {
			return false;
		}

		// If we're dealing with a pre-3.4 default theme, don't extract page templates before 3.4.
		$extract_templates = ! in_array( $slug, array( 'twentyten', 'twentyeleven', 'default', 'classic' ) );
		if ( ! $extract_templates ) {
			$wp_dir = dirname( dirname( dirname( $dir ) ) );
			$extract_templates = file_exists( "$wp_dir/wp-admin/user/about.php" ) || ! file_exists( "$wp_dir/wp-load.php" );
		}

		if ( $extract_templates ) {
			$result = $potextmeta->append( $dir, $output, array( 'Template Name' ) );
			if ( ! $result ) {
				return false;
			}

			$files = scandir( $dir );
			foreach ( $files as $file ) {
				if ( in_array( $file, array( '.', '..', '.git', 'CVS', 'node_modules' ) ) ) {
					continue;
				}

				if ( is_dir( $dir . '/' . $file ) ) {
					$result = $potextmeta->append( $dir . '/' . $file, $output, array( 'Template Name' ) );
					if ( ! $result ) {
						return false;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Convert a string or array of exclusion/inclusion patterns into an array.
	 *
	 * @param string|array $patterns Comma-separated string or array of exclusion/inclusion patterns.
	 * @return array
	 */
	protected function normalize_patterns( $patterns ) {
		if ( is_string( $patterns ) ) {
			$patterns = explode( ',', $patterns );
		}

		// Remove empty items and non-strings.
		return array_filter( array_filter( (array) $patterns ), 'is_string' );
	}
}

/**
 * CLI interface.
 */
$makepot = new NodeMakePOT;
$method = str_replace( '-', '_', $argv[1] );

if ( in_array( count( $argv ), range( 3, 8 ) ) && in_array( $method, get_class_methods( $makepot ) ) ) {
	$res = call_user_func(
		array( $makepot, $method ), // Method
		realpath( $argv[2] ),       // Directory
		$argv[3],                   // Output
		$argv[4],                   // Slug
		$argv[5],                   // Main File
		$argv[6],                   // Excludes
		$argv[7]                    // Includes
	);

	if ( false === $res ) {
		fwrite( STDERR, "Could not generate POT file!\n" );
	}
}
