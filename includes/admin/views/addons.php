<?php
/**
 * Try to get addons from the transient.
 * 
 * @var object
 */
$items = get_transient( 'wpas_addons' );
setlocale( LC_MONETARY, get_locale() );

if ( false === $items ) {

	$route    = esc_url( 'http://getawesomesupport.com/edd-api/products/' );
	$api_key  = trim( 'd83df1849d3204ed6641faa92ed55eb2' );
	$token    = trim( '39e17c3737d608900e2f403b55dda68d' );
	$endpoint = add_query_arg( array( 'key' => $api_key, 'token' => $token ), $route );
	$response = wp_remote_get( $endpoint );

	if ( 200 === wp_remote_retrieve_response_code( $response ) ) {

		$body = wp_remote_retrieve_body( $response );
		$content = json_decode( $body );
		
		if ( is_object( $content ) && isset( $content->products ) ) {
			set_transient( 'wpas_addons', $content->products, 60 * 60 * 24 ); // Cache for 24 hours
			$items = $content->products;
		}

	}
}
?>
<div class="wrap about-wrap">
	<h1><?php _e( 'Addons', 'wpas' ); ?></h1>

	<?php
	if ( false === $items ):
		?><p><?php printf( __( 'To check out all our addons please visit <a href="%s" target="_blank">http://getawesomesupport.com</a>', 'wpas' ), esc_url( 'http://getawesomesupport.com/addons' ) ); ?></p><?php
	else:
		// wpas_debug_display( $items );
		foreach ( $items as $key => $item ):

			/* Get the item price */
			$price = false;

			/* This item has a fixed price */
			if ( isset( $item->pricing->amount ) ) {
				$price = money_format( '%(#10n', number_format( $item->pricing->amount, 0 ) );
			}

			/* This item has variable pricing */
			else {
				if ( isset( $item->pricing->singlesite ) ) {
					$price = money_format( '%(#10n', number_format( $item->pricing->singlesite, 0 ) );
				}
			} ?>
			<div class="wpas-addon-item" id="wpas-addon-item-<?php echo intval( $item->info->id ); ?>">
				<h2><?php echo esc_attr( $item->info->title ); ?></h2>
				<?php if ( !empty( $item->info->thumbnail ) ): ?><img src="<?php echo esc_url( $item->info->thumbnail ); ?>"><?php endif; ?>
				<?php if ( !empty( $item->info->excerpt ) ): echo wpautop( $item->info->excerpt ); endif; ?>
				<?php if ( false !== $price ): ?><div class="wpas_addon_item_pricing"><?php echo $price; ?></div><?php endif; ?>
				<a href="<?php echo esc_url( $item->info->link ); ?>" target="_blank"><?php _e( 'Buy', 'wpas' ); ?></a>
			</div>
		<?php endforeach;

	endif; ?>
</div>