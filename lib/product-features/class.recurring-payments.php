<?php
/**
 * Enable Recurring Payments Options for supporting product types and payment gateways
 *
 * @since 0.3.8
 * @package IT_Exchange
*/


class IT_Exchange_Recurring_Payments {

	/**
	 * Constructor. Registers hooks
	 *
	 * @since 0.3.8
	 *
	 * @return void
	*/
	function IT_Exchange_Recurring_Payments() {
		if ( is_admin() ) {
			add_action( 'load-post-new.php', array( $this, 'init_feature_metaboxes' ) );
			add_action( 'load-post.php', array( $this, 'init_feature_metaboxes' ) );
			add_action( 'it_exchange_save_product', array( $this, 'save_feature_on_product_save' ) );
		}
		add_action( 'it_exchange_update_product_feature_recurring-payments', array( $this, 'save_feature' ), 9, 3 );
		add_filter( 'it_exchange_get_product_feature_recurring-payments', array( $this, 'get_feature' ), 9, 3 );
		add_action( 'it_exchange_enabled_addons_loaded', array( $this, 'add_feature_support_to_product_types' ) );
		add_filter( 'it_exchange_product_has_feature_recurring-payments', array( $this, 'product_has_feature') , 9, 3 );
		add_filter( 'it_exchange_product_supports_feature_recurring-payments', array( $this, 'product_supports_feature') , 9, 3 );
	}

	/**
	 * Register the product and add it to enabled product-type addons
	 *
	 * @since 0.3.8
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
	 * @since 0.3.8
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
			if ( !empty( $product_type ) &&  it_exchange_product_type_supports_feature( $product_type, 'recurring-payments' ) )
				add_action( 'it_exchange_product_metabox_callback_' . $product_type, array( $this, 'register_metabox' ) );
		}
		
	}

	/**
	 * Registers the price metabox for a specific product type
	 *
	 * Hooked to it_exchange_product_metabox_callback_[product-type] where product type supports recurring-payments
	 *
	 * @since 0.3.8
	 * @return void
	*/
	function register_metabox() {
		add_meta_box( 'it-exchange-recurring-payments', __( 'Recurring Payments', 'LION' ), array( $this, 'print_metabox' ), 'it_exchange_prod', 'it_exchange_normal', 'high' );
	}

	/**
	 * This echos the base price metabox.
	 *
	 * @since 0.3.8
	 * @todo remove unnecessary label (if it is still there)
	 * @return void
	*/
	function print_metabox( $post ) {
		// Grab the iThemes Exchange Product object from the WP $post object
		$product = it_exchange_get_product( $post );

		// Set the value of the feature for this product
		$product_feature_auto_renew = it_exchange_get_product_feature( $product->ID, 'recurring-payments', array( 'setting' => 'auto-renew' ) );
		$product_feature_time = it_exchange_get_product_feature( $product->ID, 'recurring-payments', array( 'setting' => 'time' ) );
		
		$recurring_options = array(
			'forever'   => _( 'Forever', 'LION' ),
			'monthly'   => _( 'Monthly', 'LION' ),
			'yearly'    => _( 'Yearly', 'LION' ),
		);
		$recurring_options = apply_filters( 'it_exchange_recurring_payment_options', $recurring_options );
		
		if ( 'forever' === $product_feature_time ) {
			$hidden = 'hidden';
			$product_feature_auto_renew = 'off';
		} else {
			$hidden = '';
		}

		// Echo the form field
		?>
			<label for="recurring-payments">&nbsp;</label> <!-- Justin I just put this there for spacing, probably not the best solution ;) -->
            <div id="it-exchange-recurring-payment-settings">
            <span class="it-exchange-recurring-payment-auto-renew <?php echo $hidden; ?> auto-renew-<?php echo $product_feature_auto_renew; ?>" title="<?php printf( __( 'Auto-Renew: %s', 'LION' ), strtoupper( $product_feature_auto_renew ) ); ?>">
            	&infin;
            	<input type="hidden" name="it_exchange_recurring_payments_auto_renew" value="<?php echo $product_feature_auto_renew; ?>" />
            </span>
            &nbsp;
            <select class="it-exchange-recurring-payment-time-options" name="it_exchange_recurring_payments_time">
			<?php
				foreach ( $recurring_options as $key => $name ) {
					echo '<option value="' . $key . '" ' . selected( $product_feature_time, $key, false ) . '>' . $name . '</option>';	
				}
            ?>
            </select>
            </div>
		<?php
	}

	/**
	 * This saves the base price value
	 *
	 * @since 0.3.8
	 *
	 * @param object $post wp post object
	 * @return void
	*/
	function save_feature_on_product_save() {
		// Abort if we can't determine a product type
		if ( ! $product_type = it_exchange_get_product_type() )
			return;

		// Abort if we don't have a product ID
		$product_id = empty( $_POST['ID'] ) ? false : $_POST['ID'];
		if ( ! $product_id )
			return;

		// Abort if this product type doesn't support this feature 
		if ( ! it_exchange_product_type_supports_feature( $product_type, 'recurring-payments' ) )
			return;

		it_exchange_update_product_feature( $product_id, 'recurring-payments', $_POST['it_exchange_recurring_payments_auto_renew'], array( 'setting' => 'auto-renew' ) );
		it_exchange_update_product_feature( $product_id, 'recurring-payments', $_POST['it_exchange_recurring_payments_time'], array( 'setting' => 'time' ) );
	}

	/**
	 * This updates the base price for a product
	 *
	 * @since 0.3.8
	 *
	 * @param integer $product_id the product id
	 * @param mixed $new_price the new price
	 * @return bolean
	*/
	function save_feature( $product_id, $new_value, $options=array() ) {
		if ( ! it_exchange_get_product( $product_id ) )
			return false;

		// Using options to determine if we're setting the enabled setting or the actual time setting
		$defaults = array(
			'setting' => 'time',
		);
		$options = ITUtility::merge_defaults( $options, $defaults );

		// Only accept settings for max_number (default) or 'enabled' (checkbox)
		if ( 'time' == $options['setting'] ) {
			update_post_meta( $product_id, '_it-exchange-product-recurring-time', $new_value );
			return true;
		} else if ( 'auto-renew' == $options['setting'] ) {
			// auto-renew setting must be on or off.
			if ( ! in_array( $new_value, array( 'on', 'off' ) ) )
				$new_value = 'off';
			update_post_meta( $product_id, '_it-exchange-product-recurring-auto-renew', $new_value );
			return true;
		}
		return false;
	}

	/**
	 * Return the product's base price
	 *
	 * @since 0.3.8
	 * @param mixed $base_price the values passed in by the WP Filter API. Ignored here.
	 * @param integer product_id the WordPress post ID
	 * @return string recurring-payments
	*/
	function get_feature( $existing, $product_id, $options=array() ) {
		// Is the the add / edit product page?
		$current_screen = is_admin() ? get_current_screen(): false;
		$editing_product = ( ! empty( $current_screen->id ) && 'it_exchange_prod' == $current_screen->id );
		
		// Using options to determine if we're getting the enabled setting or the actual time setting
		$defaults = array(
			'setting' => 'time',
		);
		$options = ITUtility::merge_defaults( $options, $defaults );
		
		if ( 'time' == $options['setting'] ) {
			return get_post_meta( $product_id, '_it-exchange-product-recurring-time', true );
		} else if ( 'auto-renew' == $options['setting'] ) {
			$autorenew = get_post_meta( $product_id, '_it-exchange-product-recurring-auto-renew', true );
			if ( ! in_array( $autorenew, array( 'on', 'off' ) ) )
				$autorenew = 'off';
			return $autorenew;
		}
		return false;
	}

	/**
	 * Does the product have the feature?
	 *
	 * @since 0.4.0
	 * @param mixed $result Not used by core
	 * @param integer $product_id
	 * @return boolean
	*/
	function product_has_feature( $result, $product_id ) {
		// Does this product type support this feature?
		if ( false === $this->product_supports_feature( false, $product_id ) )
			return false;
		return (boolean) $this->get_feature( false, $product_id );
	}

	/**
	 * Does the product support this feature?
	 *
	 * This is different than if it has the feature, a product can 
	 * support a feature but might not have the feature set.
	 *
	 * @since 0.4.0
	 * @param mixed $result Not used by core
	 * @param integer $product_id
	 * @return boolean
	*/
	function product_supports_feature( $result, $product_id ) {
		// Does this product type support this feature?
		$product_type = it_exchange_get_product_type( $product_id );
		if ( ! it_exchange_product_type_supports_feature( $product_type, 'purchase-quantity' ) )
			return false;

		// Determine if this product has turned off product quantity
		if ( 'no' == it_exchange_get_product_feature( $product_id, 'purchase-quantity', array( 'setting' => 'enabled' ) ) )
			return false;

		return true;
	}
}
$IT_Exchange_Recurring_Payments = new IT_Exchange_Recurring_Payments();
