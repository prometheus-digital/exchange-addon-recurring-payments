<?php
/**
 * Enable Recurring Payments Options for supporting product types and payment gateways
 * @package exchange-addon-recurring-payments
 * @since 1.0.0
*/


class IT_Exchange_Recurring_Payments_Info {

	/**
	 * Constructor. Registers hooks
	 *
	 * @since 1.0.0
	 *
	 * @return void
	*/
	function __construct() {
		if ( is_admin() ) {
			add_action( 'load-post-new.php', array( $this, 'init_feature_metaboxes' ) );
			add_action( 'load-post.php', array( $this, 'init_feature_metaboxes' ) );
		}
		add_action( 'it_exchange_enabled_addons_loaded', array( $this, 'add_feature_support_to_product_types' ) );
	}

	/**
	 * Deprecated Constructor. Registers hooks
	 *
	 * @since 1.0.0
	 *
	 * @return void
	*/
	function IT_Exchange_Recurring_Payments_Info() {
		self::__construct();
	}

	/**
	 * Register the product and add it to enabled product-type addons
	 *
	 * @since 1.0.0
	*/
	function add_feature_support_to_product_types() {
		// Register the recurring-payments_addon
		$slug        = 'recurring-payments';
		$description = 'The recurring payment options for a product';
		it_exchange_register_product_feature( $slug, $description );

		// Add it to all enabled product-type addons
		$products = it_exchange_get_enabled_addons( array( 'category' => 'product-type' ) );
		foreach( $products as $key => $params ) {
			it_exchange_add_feature_support_to_product_type( 'recurring-payments', $params['slug'] );
		}
	}

	/**
	 * Register's the metabox for any product type that supports the recurring-payments feature
	 *
	 * @since 1.0.0
	 * @return void
	*/
	function init_feature_metaboxes() {
		
		global $post;
		
		if ( isset( $_REQUEST['post_type'] ) ) {
			$post_type = $_REQUEST['post_type'];
		} else {
			if ( isset( $_REQUEST['post'] ) )
				$post_id = (int) $_REQUEST['post'];
			elseif ( isset( $_REQUEST['post_ID'] ) )
				$post_id = (int) $_REQUEST['post_ID'];
			else
				$post_id = 0;

			if ( $post_id )
				$post = get_post( $post_id );

			if ( isset( $post ) && !empty( $post ) )
				$post_type = $post->post_type;
		}
			
		if ( !empty( $_REQUEST['it-exchange-product-type'] ) )
			$product_type = $_REQUEST['it-exchange-product-type'];
		else
			$product_type = it_exchange_get_product_type( $post );
				
		if ( !empty( $post_type ) && 'it_exchange_prod' === $post_type ) {
			if ( !empty( $product_type ) &&  it_exchange_product_type_supports_feature( $product_type, 'recurring-payments' ) ) {
				add_action( 'it_exchange_product_metabox_callback_' . $product_type, array( $this, 'register_metabox' ) );
			}
		}
		
	}

	/**
	 * Registers the price metabox for a specific product type
	 *
	 * Hooked to it_exchange_product_metabox_callback_[product-type] where product type supports recurring-payments
	 *
	 * @since 1.0.0
	 * @return void
	*/
	function register_metabox() {
		add_meta_box( 'it-exchange-recurring-payments-info', __( 'Recurring Payments Info', 'LION' ), array( $this, 'print_metabox' ), 'it_exchange_prod', 'it_exchange_normal', 'high' );
	}

	/**
	 * This echos the base price metabox.
	 *
	 * @since 1.0.0
	 * @param object $post Product
	 * @return void
	*/
	function print_metabox( $post ) {
		// Echo the form field
		?>
		<div id="it-exchange-recurring-payment-info-icon">
			<span class="tip" title="<?php _e( 'Recurring Payment options have moved to the Advanced section.', 'LION' ); ?>">i</span>
		</div>
		<?php
	}
}
$IT_Exchange_Recurring_Payments_Info = new IT_Exchange_Recurring_Payments_Info();