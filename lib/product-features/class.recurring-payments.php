<?php
/**
 * Enable Recurring Payments Options for supporting product types and payment gateways
 * @package exchange-addon-recurring-payments
 * @since 1.0.0
*/


class IT_Exchange_Recurring_Payments {

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
			add_action( 'it_exchange_save_product', array( $this, 'save_feature_on_product_save' ) );
		}
		add_action( 'it_exchange_update_product_feature_recurring-payments', array( $this, 'save_feature' ), 9, 3 );
		add_filter( 'it_exchange_get_product_feature_recurring-payments', array( $this, 'get_feature' ), 9, 3 );
		add_action( 'it_exchange_enabled_addons_loaded', array( $this, 'add_feature_support_to_product_types' ) );
		add_filter( 'it_exchange_product_has_feature_recurring-payments', array( $this, 'product_has_feature') , 9, 3 );
		add_filter( 'it_exchange_product_supports_feature_recurring-payments', array( $this, 'product_supports_feature') , 9, 3 );
	}

	/**
	 * Deprecated Constructor. Registers hooks
	 *
	 * @since 1.0.0
	 *
	 * @return void
	*/
	function IT_Exchange_Recurring_Payments() {
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
			if ( !empty( $product_type ) &&  it_exchange_product_type_supports_feature( $product_type, 'recurring-payments' ) )
				add_action( 'it_exchange_product_metabox_callback_' . $product_type, array( $this, 'register_metabox' ) );
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
		add_meta_box( 'it-exchange-recurring-payments', __( 'Recurring Options', 'LION' ), array( $this, 'print_metabox' ), 'it_exchange_prod', 'it_exchange_advanced', 'high' );
	}

	/**
	 * This echos the base price metabox.
	 *
	 * @since 1.0.0
	 * @param object $post Product
	 * @return void
	*/
	function print_metabox( $post ) {
		// Grab the iThemes Exchange Product object from the WP $post object
		$product = it_exchange_get_product( $post );

		// Set the value of the feature for this product
		$enabled = it_exchange_get_product_feature( $product->ID, 'recurring-payments', array( 'setting' => 'recurring-enabled' ) );
		$trial_enabled = it_exchange_get_product_feature( $product->ID, 'recurring-payments', array( 'setting' => 'trial-enabled' ) );
		$trial_interval = it_exchange_get_product_feature( $product->ID, 'recurring-payments', array( 'setting' => 'trial-interval' ) );
		$trial_interval_count = it_exchange_get_product_feature( $product->ID, 'recurring-payments', array( 'setting' => 'trial-interval-count' ) );
		$auto_renew = it_exchange_get_product_feature( $product->ID, 'recurring-payments', array( 'setting' => 'auto-renew' ) );
		$interval = it_exchange_get_product_feature( $product->ID, 'recurring-payments', array( 'setting' => 'interval' ) );
		$interval_count = it_exchange_get_product_feature( $product->ID, 'recurring-payments', array( 'setting' => 'interval-count' ) );
		
		if ( !$enabled ) {
			$hidden = 'hidden';
			$auto_renew = 'off';
		} else {
			$hidden = '';
		}

	    $interval_types = array(
		    'day'   => __( 'Day(s)', 'LION' ),
		    'week'  => __( 'Week(s)', 'LION' ),
		    'month' => __( 'Month(s)', 'LION' ),
		    'year'  => __( 'Year(s)', 'LION' ),
		);
		$interval_types = apply_filters( 'it_exchange_recurring_payments_interval_types', $interval_types );
		
		// Echo the form field
		?>
        <div id="it-exchange-recurring-payment-settings">
	        <label for="it-exchange-recurring-payments-enabled"><?php _e( "Enable Recurring Payments?", 'LION' ); ?>
	        	<input id="it-exchange-recurring-payments-enabled" type="checkbox" name="it_exchange_recurring_payments_enabled" <?php checked( $enabled ); ?> />
	        </label>
	        <div id="recurring-payment-options" class="<?php echo $hidden; ?>">
		        <p>
			        <label for="it-exchange-recurring-payments-interval">
				        <?php _e( 'Recurs every...', 'LION' ); ?>
			        </label>
			        &nbsp;
			        <input id="it-exchange-recurring-payments-interval-count" type="number" min="0" class="small-input" name="it_exchange_recurring_payments_interval_count" value="<?php echo $interval_count; ?>" placeholder="#" />
			        <select id="it-exchange-recurring-payments-interval" name="it_exchange_recurring_payments_interval">
				        <?php
					    foreach( $interval_types as $name => $label ) {
						    echo '<option value="' . $name . '" ' . selected( $interval, $name, false ) . '>' . $label . '</option>';
					    }  
						?>
			        </select>
		        </p>
		        <label for="it-exchange-recurring-payments-auto-renew"><?php _e( "Enable Auto-Renewing?", 'LION' ); ?>
		        	<input id="it-exchange-recurring-payments-auto-renew" type="checkbox" name="it_exchange_recurring_payments_auto_renew" <?php checked( $auto_renew, 'on' ); ?> />
		        </label>
		        <?php
				if ( 'on' === $auto_renew ) {
					$trial_hidden = '';
				} else if ( !$trial_enabled ) {
					$trial_hidden = 'hidden';
				} else {
					$trial_hidden = '';
				}
				?>
		        <div id="trial-period-settings" class="<?php echo $trial_hidden; ?>">
			        <!-- We only show trial period settings for Membership products -->
					<?php if ( 'membership-product-type' === it_exchange_get_product_type( $product->ID ) || apply_filters( 'it_exchange_recurring_payments_free_trial_allowed', false, $product ) ) { ?>
			        <label for="it-exchange-recurring-payments-trial-enabled"><?php _e( "Enable a Trial Period?", 'LION' ); ?>
						<input id="it-exchange-recurring-payments-trial-enabled" type="checkbox" name="it_exchange_recurring_payments_trial_enabled" <?php checked( $trial_enabled ); ?> />
			        </label>
			        <?php
					if ( !$trial_enabled ) {
						$trail_options_hidden = 'hidden';
					} else {
						$trail_options_hidden = '';
					}
					?>
			        <p id="trial-period-options" class="<?php echo $trail_options_hidden; ?>">
				        <label for="it-exchange-recurring-payments-trial-interval-count">
					        <?php _e( 'Free trial for...', 'LION' ); ?>
				        </label>
				        &nbsp;
				        <input id="it-exchange-recurring-payments-trial-interval-count" type="number" min="0" class="small-input" name="it_exchange_recurring_payments_trial_interval_count" value="<?php echo $trial_interval_count; ?>" placeholder="#" />
				        <select id="it-exchange-recurring-payments-trial-interval" name="it_exchange_recurring_payments_trial_interval">
					        <?php
						    foreach( $interval_types as $name => $label ) {
							    echo '<option value="' . $name . '" ' . selected( $trial_interval, $name, false ) . '>' . $label . '</option>';
						    }  
							?>
				        </select>
			        </p>
				    <?php } ?>
		        </div>
	        </div>
        </div>
        <?php
	}

	/**
	 * This saves the base price value
	 *
	 * @since 1.0.0
	 *
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

		$enabled = !empty( $_POST['it_exchange_recurring_payments_enabled'] ) ? 'on' : 'off';
		it_exchange_update_product_feature( $product_id, 'recurring-payments', $enabled, array( 'setting' => 'recurring-enabled' ) );
		
		$enabled = !empty( $_POST['it_exchange_recurring_payments_trial_enabled'] ) ? 'on' : 'off';
		it_exchange_update_product_feature( $product_id, 'recurring-payments', $enabled, array( 'setting' => 'trial-enabled' ) );
		
		$trial_interval = !empty( $_POST['it_exchange_recurring_payments_trial_interval'] ) ? $_POST['it_exchange_recurring_payments_trial_interval'] : false;
		it_exchange_update_product_feature( $product_id, 'recurring-payments', $trial_interval, array( 'setting' => 'trial-interval' ) );
		$trial_interval_count = !empty( $_POST['it_exchange_recurring_payments_trial_interval_count'] ) ? $_POST['it_exchange_recurring_payments_trial_interval_count'] : false;
		it_exchange_update_product_feature( $product_id, 'recurring-payments', $trial_interval_count, array( 'setting' => 'trial-interval-count' ) );
		
		$enabled = !empty( $_POST['it_exchange_recurring_payments_auto_renew'] ) ? 'on' : 'off';
		it_exchange_update_product_feature( $product_id, 'recurring-payments', $enabled, array( 'setting' => 'auto-renew' ) );
		
		$interval = !empty( $_POST['it_exchange_recurring_payments_interval'] ) ? $_POST['it_exchange_recurring_payments_interval'] : false;
		it_exchange_update_product_feature( $product_id, 'recurring-payments', $interval, array( 'setting' => 'interval' ) );
		$interval_count = !empty( $_POST['it_exchange_recurring_payments_interval_count'] ) ? $_POST['it_exchange_recurring_payments_interval_count'] : false;
		it_exchange_update_product_feature( $product_id, 'recurring-payments', $interval_count, array( 'setting' => 'interval-count' ) );
		
	}

	/**
	 * This updates the base price for a product
	 *
	 * @since 1.0.0
	 *
	 * @param integer $product_id the product id
	 * @param mixed $new_price the new price
	 * @param array $options Optional arguments used to specify which feature is saved
	 * @return bolean
	*/
	function save_feature( $product_id, $new_value, $options=array() ) {
		if ( ! it_exchange_get_product( $product_id ) )
			return false;
		
		$defaults = array(
			'setting' => 'recurring-enabled',
		);
		$options = ITUtility::merge_defaults( $options, $defaults );
		
		switch ( $options['setting'] ) {
							
			case 'interval-count':
				update_post_meta( $product_id, '_it-exchange-product-recurring-interval-count', intval( $new_value ) );
				break;
				
			case 'interval':
				update_post_meta( $product_id, '_it-exchange-product-recurring-interval', $new_value );
				break;

			case 'auto-renew':
				if ( ! in_array( $new_value, array( 'on', 'off' ) ) )
					$new_value = 'off';
				update_post_meta( $product_id, '_it-exchange-product-recurring-auto-renew', $new_value );
				break;
				
			case 'trial-interval-count':
				update_post_meta( $product_id, '_it-exchange-product-recurring-trial-interval-count', intval( $new_value ) );
				break;
				
			case 'trial-interval':
				update_post_meta( $product_id, '_it-exchange-product-recurring-trial-interval', $new_value );
				break;
				
			case 'trial-enabled':
				if ( ! in_array( $new_value, array( 'on', 'off' ) ) )
					$new_value = 'off';
				update_post_meta( $product_id, '_it-exchange-product-recurring-trial-enabled', $new_value );
				break;
			
			case 'recurring-enabled':
				if ( ! in_array( $new_value, array( 'on', 'off' ) ) )
					$new_value = 'off';
				update_post_meta( $product_id, '_it-exchange-product-recurring-enabled', $new_value );
				break;
			
		}
			
		return true;
	}

	/**
	 * Return the product's base price
	 *
	 * @since 1.0.0
	 * @param mixed $base_price the values passed in by the WP Filter API. Ignored here.
	 * @param integer product_id the WordPress post ID
	 * @param array $options Optional arguments used to specify which feature is gotten
	 * @return string recurring-payments
	*/
	function get_feature( $existing, $product_id, $options=array() ) {
		// Is the the add / edit product page?
		$current_screen = is_admin() ? get_current_screen(): false;
		$editing_product = ( ! empty( $current_screen->id ) && 'it_exchange_prod' == $current_screen->id );
		
		// Using options to determine if we're getting the enabled setting or the actual time setting
		$defaults = array(
			'setting' => 'recurring-enabled',
		);
		$options = ITUtility::merge_defaults( $options, $defaults );
		
		switch ( $options['setting'] ) {
							
			case 'interval-count':
				if ( $interval_count = get_post_meta( $product_id, '_it-exchange-product-recurring-interval-count', true ) ) {
					return $interval_count;
				} else if ( $time = get_post_meta( $product_id, '_it-exchange-product-recurring-time', true ) ) {
					return 1;
				}
				return false;				

			case 'interval':
				if ( $interval = get_post_meta( $product_id, '_it-exchange-product-recurring-interval', true ) ) {
					return $interval;
				} else if ( $time = get_post_meta( $product_id, '_it-exchange-product-recurring-time', true ) ) {
					switch ( $time ) {
						case 'monthly':
							update_post_meta( $product_id, '_it-exchange-product-recurring-interval', 'month' );
							return 'month';
						case 'yearly':
							update_post_meta( $product_id, '_it-exchange-product-recurring-interval', 'year' );
							return 'year';
						default:
							return $time;
					}
				}
				return false;

			case 'auto-renew':
				$enabled = get_post_meta( $product_id, '_it-exchange-product-recurring-auto-renew', true );
				switch ( $enabled ) {
					case 'on':
						return 'on';
					case 'off':
					default:
						return 'off';
				}
				
			case 'trial-interval-count':
				return get_post_meta( $product_id, '_it-exchange-product-recurring-trial-interval-count', true );
				
			case 'trial-interval':
				return get_post_meta( $product_id, '_it-exchange-product-recurring-trial-interval', true );
				
			case 'trial-enabled':
				$enabled = get_post_meta( $product_id, '_it-exchange-product-recurring-trial-enabled', true );
				switch ( $enabled ) {
					case 'on':
						return true;
					case 'off':
					default:
						return false;
				}
				
			case 'recurring-enabled':
				$enabled = get_post_meta( $product_id, '_it-exchange-product-recurring-enabled', true );
				if ( !empty( $enabled ) ) {
					switch ( $enabled ) {
						case 'on':
							return true;
						case 'off':
						default:
							return false;
					}
				} else if ( $time = get_post_meta( $product_id, '_it-exchange-product-recurring-time', true ) ) {
					if ( 'forever' === $time ) {
						return false;
					} else {
						return true;
					}
				}
				
			case 'time':
				if ( $time = get_post_meta( $product_id, '_it-exchange-product-recurring-interval', true ) ) {
					switch ( $time ) {
						case 'month':
							return 'monthly';
						case 'year':
							return 'yearly';
						default:
							return $time;
					}
				}
				return get_post_meta( $product_id, '_it-exchange-product-recurring-time', true );
			
		}
		return false;
	}

	/**
	 * Does the product have the feature?
	 *
	 * @since 1.0.0
	 * @param mixed $result Not used by core
	 * @param integer $product_id
	 * @param array $options Optional arguments used to specify which feature is checked
	 * @return boolean
	*/
	function product_has_feature( $result, $product_id, $options=array() ) {
		$defaults['setting'] = 'recurring-enabled';
		$options = ITUtility::merge_defaults( $options, $defaults );

		// Does this product type support this feature?
		if ( false === $this->product_supports_feature( false, $product_id, $options ) )
			return false;

		// If it does support, does it have it?
		$feature = $this->get_feature( false, $product_id, $options );
		if ( !empty( $feature ) )
			return true;
		else
			return false;
	}

	/**
	 * Does the product support this feature?
	 *
	 * This is different than if it has the feature, a product can 
	 * support a feature but might not have the feature set.
	 *
	 * @since 1.0.0
	 * @param mixed $result Not used by core
	 * @param integer $product_id
	 * @param array $options Optional arguments used to specify which feature is checked
	 * @return boolean
	*/
	function product_supports_feature( $result, $product_id, $options=array() ) {
		$defaults['setting'] = 'recurring-enabled';
		$options = ITUtility::merge_defaults( $options, $defaults );

		// Does this product type support this feature?
		$product_type = it_exchange_get_product_type( $product_id );
		if ( ! it_exchange_product_type_supports_feature( $product_type, 'recurring-payments' ) ) 
			return false;

		if ( 'auto-renew' === $options['setting'] ) {
			if ( 'off' === it_exchange_get_product_feature( $product_id, 'recurring-payments', array( 'setting' => $options['setting'] ) ) )
				return false;
		}

		return true;
	}
}
$IT_Exchange_Recurring_Payments = new IT_Exchange_Recurring_Payments();
