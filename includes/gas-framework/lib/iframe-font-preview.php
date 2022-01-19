<?php
/*
 * Create a preview of a font
 */

if ( empty( $_GET ) ) {
	return;
}

// Sanitize the inputs
$get = array_map( 'htmlspecialchars', $_GET );

// Set the defaults
$textShadowLocation = ! empty( $get['text-shadow-location'] ) ? $get['text-shadow-location'] : 'none';
$textShadowDistance = ! empty( $get['text-shadow-distance'] ) ? $get['text-shadow-distance'] : '0px';
$textShadowBlur = ! empty( $get['text-shadow-blur'] ) ? $get['text-shadow-blur'] : '0px';
$textShadowColor = ! empty( $get['text-shadow-color'] ) ? $get['text-shadow-color'] : '#333333';
$textShadowOpacity = ! empty( $get['text-shadow-opacity'] ) ? $get['text-shadow-opacity'] : '1';
$fontFamily = ! empty( $get['font-family'] ) ? $get['font-family'] : 'Open+Sans';
$fontType = ! empty( $get['font-type'] ) ? $get['font-type'] : 'google';
$fontWeight = ! empty( $get['font-weight'] ) ? $get['font-weight'] : 'normal';
$fontStyle = ! empty( $get['font-style'] ) ? $get['font-style'] : 'normal';
$fontSize = ! empty( $get['font-size'] ) ? $get['font-size'] : '13px';
$color = ! empty( $get['color'] ) ? $get['color'] : '#333333';
$lineHeight = ! empty( $get['line-height'] ) ? $get['line-height'] : '1.5em';
$letterSpacing = ! empty( $get['letter-spacing'] ) ? $get['letter-spacing'] : 'normal';
$textTransform = ! empty( $get['text-transform'] ) ? $get['text-transform'] : 'none';
$fontVariant = ! empty( $get['font-variant'] ) ? $get['font-variant'] : 'normal';
$isDarkBody = ! empty( $get['dark'] ) ? $get['dark'] : '';
$text = ! empty( $get['text'] ) ? $get['text'] : '';

// @see	http://bavotasan.com/2011/convert-hex-color-to-rgb-using-php/
function hex2rgb( $hex ) {
	$hex = str_replace( '#', '', $hex );

	if ( strlen( $hex ) == 3 ) {
		$r = hexdec( substr( $hex,0,1 ).substr( $hex,0,1 ) );
		$g = hexdec( substr( $hex,1,1 ).substr( $hex,1,1 ) );
		$b = hexdec( substr( $hex,2,1 ).substr( $hex,2,1 ) );
	} else {
		$r = hexdec( substr( $hex,0,2 ) );
		$g = hexdec( substr( $hex,2,2 ) );
		$b = hexdec( substr( $hex,4,2 ) );
	}
	$rgb = array( $r, $g, $b );
	// return implode(",", $rgb); // returns the rgb values separated by commas
	return $rgb; // returns an array with the rgb values
}


$textShadow = '';
if ( $textShadowLocation != 'none' ) {
	if ( stripos( $textShadowLocation, 'left' ) !== false ) {
		$textShadow .= '-' . $textShadowDistance;
	} else if ( stripos( $textShadowLocation, 'right' ) !== false ) {
		$textShadow .= $textShadowDistance;
	} else {
		$textShadow .= '0';
	}
	$textShadow .= ' ';
	if ( stripos( $textShadowLocation, 'top' ) !== false ) {
		$textShadow .= '-' . $textShadowDistance;
	} else if ( stripos( $textShadowLocation, 'bottom' ) !== false ) {
		$textShadow .= $textShadowDistance;
	} else {
		$textShadow .= '0';
	}
	$textShadow .= ' ';
	$textShadow .= $textShadowBlur;
	$textShadow .= ' ';

	$rgb = hex2rgb( $textShadowColor );
	$rgb[] = $textShadowOpacity;

	$textShadow .= 'rgba(' . implode( ',', $rgb ) . ')';
} else {
	$textShadow .= $textShadowLocation;
}

?>
<html>
	<head>
		<?php
		if ( $fontType == 'google' ) {
			$weight = $fontWeight;
			if ( $weight == 'normal' ) {
				$weight = array( '400' );
			} else if ( $weight == 'bold' ) {
				$weight = array( '500' );// , '700' );
			} else if ( $weight == 'bolder' ) {
				$weight = array( '800' );// , '900' );
			} else if ( $weight == 'lighter' ) {
				$weight = array( '100' );// , '200', '300' );
			} else {
				$weight = array( $weight );
			}
			if ( $fontStyle == 'italic' ) {
				foreach ( $weight as $key => $value ) {
					$weight[ $key ] = $value . 'italic';
				}
			}

			printf( "<link rel='stylesheet' href='//fonts.googleapis.com/css?subset=latin,cyrillic-ext,greek-ext,greek,latin-ext,vietnamese,cyrillic' type='text/css' media='all' />");
			$fontFamily = str_replace( ' ', '+', str_replace( '%20', '+', $fontFamily ) );
			$fontFamily = '"' . $fontFamily . '"';
			$weight = implode( ',', $weight );
		} else {
			$fontFamily = str_replace( '&quot;', '"', stripslashes( $fontFamily ) );
		}
		?>
		<style>
			p {
				margin-left: 50px;
				margin-right: 20px;
				font-family: <?php echo $fontFamily; ?>;
				color: <?php echo $color; ?>;
				font-size: <?php echo $fontSize; ?>;
				font-weight: <?php echo $fontWeight; ?>;
				font-style: <?php echo $fontStyle; ?>;
				line-height: <?php echo $lineHeight; ?>;
				letter-spacing: <?php echo $letterSpacing; ?>;
				text-transform: <?php echo $textTransform; ?>;
				font-variant: <?php echo $fontVariant; ?>;
				text-shadow: <?php echo $textShadow; ?>;
			}
			body {
				margin: 20px 0;
				background: #fff;
				-webkit-touch-callout: none;
				-webkit-user-select: none;
				-khtml-user-select: none;
				-moz-user-select: none;
				-ms-user-select: none;
				user-select: none;
				font-family: <?php echo $fontFamily; ?>;
				<?php if (! empty($weight) ) { ?>
					font-weight: <?php echo $weight; ?>;
				<?php } ?>
			}
			body.dark {
				background: #333;
			}
		</style>
		<script>
			function toggleClass( element, className ) {
			    if ( ! element || ! className ) {
			        return;
			    }

			    var classString = element.className, nameIndex = classString.indexOf( className );
			    if ( nameIndex == -1 ) {
			        classString += ' ' + className;
			    } else {
			        classString = classString.substr( 0, nameIndex ) + classString.substr( nameIndex + className.length );
			    }
			    element.className = classString;
			}
		</script>
	</head>
	<body class='<?php echo $isDarkBody ? 'dark' : '' ?>'>
		<?php
		if ( empty( $text ) ) :
			?>
			<p>Grumpy wizards make toxic brew for the evil Queen and Jack</p>
			<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam at dolor non purus adipiscing rhoncus. Nullam vitae turpis pharetra odio feugiat gravida sed ac velit. Nullam luctus ultrices suscipit. Fusce condimentum laoreet cursus. Suspendisse sed accumsan tortor. Quisque pharetra pulvinar ante, feugiat varius nibh sodales nec. Fusce vel mattis lectus. Vivamus magna felis, pharetra in lacinia sed, condimentum quis nisi. Ut at rutrum urna. Vivamus convallis posuere metus vel ullamcorper.</p>
			<?php
		else :
			echo '<p>' . str_replace( "\n", '</p><p>', $text ) . '</p>';
		endif;
		?>
	</body>
</html>
