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

<style type="text/css">
.wpas-addon-all .wpas-addon-item {
	margin-bottom: 40px;
	padding-bottom: 40px;
	border-bottom: 1px solid #ddd;
}
.wpas-addon-all .wpas-addon-item:last-child {
	margin-bottom: 0px;
	border-bottom: none;
}
.wpas-addon-all .last-feature {
	padding-top: 40px;
}
.wpas-addon-img-wrap {
	width: 100%;
	min-height: 311px;
	background: #ddd url('<?php echo admin_url(); ?>/images/wpspin_light-2x.gif') no-repeat 50% 50%;
}
.wpas-addon-img {
	box-shadow: 0px 0px 25px rgba(0,0,0,0.25);
}
.wpas-addon-item-pricing {
	background: white;
	padding: 5px 10px;
	border: 1px solid #ddd;
	border-radius: 3px;
}
</style>

<div class="wrap about-wrap">
	<h1>Addons</h1>
	<div class="about-text">Even though Awesome Support has a lot of built-in features, it is impossible to make everyone happy. This is why we have lots of addons to help you tailor your support system.</div>

	<div class="changelog wpas-addon-all">
		<?php
		if ( false === $items ):
			?><p>To check out all our addons please visit <a href="http://getawesomesupport.com/addons" target="_blank">http://getawesomesupport.com/addons</a></p><?php
		else:
			// wpas_debug_display( $items );
			foreach ( $items as $key => $item ):

				/* Get the item price */
				$price = false;

				/* This item has a fixed price */
				if ( isset( $item->pricing->amount ) ) {
					$price = number_format( $item->pricing->amount, 0 );
				}

				/* This item has variable pricing */
				else {
					if ( isset( $item->pricing->singlesite ) ) {
						$price = number_format( $item->pricing->singlesite, 0 );
					}
				} ?>
				<div class="feature-section col two-col wpas-addon-item" id="wpas-addon-item-<?php echo intval( $item->info->id ); ?>">
					<div class="col-1">
						<div class="wpas-addon-img-wrap"><?php if ( !empty( $item->info->thumbnail ) ): ?><img class="wpas-addon-img" src="<?php echo esc_url( $item->info->thumbnail ); ?>"><?php endif; ?></div>
					</div>
					<div class="col-2 last-feature">
						<h3><?php echo esc_attr( $item->info->title ); ?> <small class="wpas-addon-item-pricing">from <?php if ( false !== $price ): ?><strong>$<?php echo $price; ?></strong><?php endif; ?></small></h3>
						<p><?php if ( !empty( $item->info->excerpt ) ): echo wpautop( $item->info->excerpt ); endif; ?></p>
						<a class="button-primary" href="<?php echo esc_url( $item->info->link ); ?>" target="_blank">View details</a>
					</div>
				</div>
			<?php endforeach;

		endif; ?>
	</div>
</div>