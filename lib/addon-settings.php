<?php
/**
 * Exchange will build your add-on's settings page for you and link to it from our add-on
 * screen. You are free to link from it elsewhere as well if you'd like... or to not use our API
 * at all. This file has all the functions related to registering the page, printing the form, and saving
 * the options. This includes the wizard settings. Additionally, we use the Exchange storage API to
 * save / retreive options. Add-ons are not required to do this.
*/

/**
 * This is the function registered in the options array when it_exchange_register_addon
 * was called for recurring payments
 *
 * It tells Exchange where to find the settings page
 *
 * @return void
*/
function it_exchange_recurring_payments_addon_settings_callback() {
    $IT_Exchange_Recurring_Payments_Add_On = new IT_Exchange_Recurring_Payments_Add_On();
    $IT_Exchange_Recurring_Payments_Add_On->print_settings_page();
}

/**
 * Default settings for recurring payments
 *
 * @since 1.0.0
 *
 * @param array $values
 * @return array
*/
function it_exchange_recurring_payments_addon_default_settings( $values ) {
    $defaults = array(
        'recurring-payments-cancel-subject'   => __( 'Cancellation Notification', 'LION' ),
        'recurring-payments-cancel-body'      => __( 'Hello [it_exchange_email show=name],

Your recurring payment has been cancelled.

Thank you.
[it_exchange_email show=sitename]', 'LION' ),
        'recurring-payments-deactivate-subject'   => __( 'Expiration Notification', 'LION' ),
        'recurring-payments-deactivate-body'      => __( 'Hello [it_exchange_email show=name],

Your recurring payment has expired.

Thank you.
[it_exchange_email show=sitename]', 'LION' ),
	);
    $values = ITUtility::merge_defaults( $values, $defaults );
    return $values;
}
add_filter( 'it_storage_get_defaults_exchange_addon_recurring_payments', 'it_exchange_recurring_payments_addon_default_settings' );

/**
 * Class for Recurring Payments
 * @since 1.0.0
*/
class IT_Exchange_Recurring_Payments_Add_On {

    /**
     * @var boolean $_is_admin true or false
     * @since 1.0.0
    */
    var $_is_admin;

    /**
     * @var string $_current_page Current $_GET['page'] value
     * @since 1.0.0
    */
    var $_current_page;

    /**
     * @var string $_current_add_on Current $_GET['add-on-settings'] value
     * @since 1.0.0
    */
    var $_current_add_on;

    /**
     * @var string $status_message will be displayed if not empty
     * @since 1.0.0
    */
    var $status_message;

    /**
     * @var string $error_message will be displayed if not empty
     * @since 1.0.0
    */
    var $error_message;

    /**
     * Class constructor
     *
     * Sets up the class.
     * @since 1.0.0
     * @return void
    */
    function __construct() {
        $this->_is_admin       = is_admin();
        $this->_current_page   = empty( $_GET['page'] ) ? false : $_GET['page'];
        $this->_current_add_on = empty( $_GET['add-on-settings'] ) ? false : $_GET['add-on-settings'];

        if ( ! empty( $_POST ) && $this->_is_admin && 'it-exchange-addons' == $this->_current_page && 'recurring-payments' == $this->_current_add_on ) {
            add_action( 'it_exchange_save_add_on_settings_recurring_payments', array( $this, 'save_settings' ) );
            do_action( 'it_exchange_save_add_on_settings_recurring_payments' );
        }

        // Creates our option in the database
        // add_action( 'admin_init', array( $this, 'exchange_recurringpayments_plugin_updater', 0 ) );
        // add_action( 'admin_init', array( $this, 'exchange_recurringpayments_register_option' ) );
        // add_action( 'admin_notices', array( $this, 'exchange_recurringpayments_admin_notices' ) );
        // add_action( 'admin_init', array( $this, 'exchange_recurringpayments_deactivate_license' ) );
        // add_action( 'admin_init', array( $this, 'exchange_recurringpayments_deactivate_license' ) );
        // add_action( 'admin_init', array( $this, 'exchange_recurringpayments_activate_license' ) );
    }

    /**
     * Class deprecated constructor
     *
     * Sets up the class.
     * @since 1.0.0
     * @return void
    */
    function IT_Exchange_Recurring_Payments_Add_On() {
		self::__construct();
    }

    /**
     * Prints settings page
     *
     * @since 1.0.0
     * @return void
    */
    function print_settings_page() {
        $settings = it_exchange_get_option( 'addon_recurring_payments', true );
        $form_values  = empty( $this->error_message ) ? $settings : ITForm::get_post_data();
        $form_options = array(
            'id'      => apply_filters( 'it_exchange_add_on_recurring-payments', 'it-exchange-add-on-recurring-payments-settings' ),
            'enctype' => apply_filters( 'it_exchange_add_on_recurring-payments_settings_form_enctype', false ),
            'action'  => 'admin.php?page=it-exchange-addons&add-on-settings=recurring-payments',
        );
        $form         = new ITForm( $form_values, array( 'prefix' => 'it-exchange-add-on-recurring_payments' ) );

        if ( ! empty ( $this->status_message ) )
            ITUtility::show_status_message( $this->status_message );
        if ( ! empty( $this->error_message ) )
            ITUtility::show_error_message( $this->error_message );

        ?>
        <div class="wrap">
            <?php screen_icon( 'it-exchange' ); ?>
            <h2><?php _e( 'Recurring Payments Settings', 'LION' ); ?></h2>

            <?php do_action( 'it_exchange_recurring-payments_settings_page_top' ); ?>
            <?php do_action( 'it_exchange_addon_settings_page_top' ); ?>
            <?php $form->start_form( $form_options, 'it-exchange-recurring-payments-settings' ); ?>
                <?php do_action( 'it_exchange_recurring_payments__settings_form_top' ); ?>
                <?php $this->get_recurring_payments_form_table( $form, $form_values ); ?>
                <?php do_action( 'it_exchange_recurring-payments_settings_form_bottom' ); ?>
                <p class="submit">
                    <?php $form->add_submit( 'submit', array( 'value' => __( 'Save Changes', 'LION' ), 'class' => 'button button-primary button-large' ) ); ?>
                </p>
            <?php $form->end_form(); ?>
            <?php do_action( 'it_exchange_recurring-payments_settings_page_bottom' ); ?>
            <?php do_action( 'it_exchange_addon_settings_page_bottom' ); ?>
        </div>
        <?php
    }

    function get_recurring_payments_form_table( $form, $settings = array() ) {

		global $wp_version;

        $general_settings = it_exchange_get_option( 'settings_general' );

        if ( !empty( $settings ) )
            foreach ( $settings as $key => $var )
                $form->set_option( $key, $var );

        if ( ! empty( $_GET['page'] ) && 'it-exchange-setup' == $_GET['page'] ) : ?>
            <h3><?php _e( 'Recurring Payments', 'LION' ); ?></h3>
        <?php endif; ?>
        <div class="it-exchange-addon-settings it-exchange-recurring-payments-addon-settings">
          <h4>License Key</h4>
          <?php
             $exchangewp_recurringpayments_options = get_option( 'it-storage-exchange_addon_recurring_payments' );
             $license = $exchangewp_recurringpayments_options['recurringpayments_license'];
             // var_dump($license);
             $exstatus = trim( get_option( 'exchange_recurringpayments_license_status' ) );
             // var_dump($exstatus);
          ?>
          <p>
           <label class="description" for="exchange_recurringpayments_license_key"><?php _e('Enter your license key'); ?></label>
           <!-- <input id="recurringpayments_license" name="it-exchange-add-on-recurringpayments-recurringpayments_license" type="text" value="<?php #esc_attr_e( $license ); ?>" /> -->
           <?php $form->add_text_box( 'recurringpayments_license' ); ?>
           <span>
             <?php if( $exstatus !== false && $exstatus == 'valid' ) { ?>
          			<span style="color:green;"><?php _e('active'); ?></span>
          			<?php wp_nonce_field( 'exchange_recurringpayments_nonce', 'exchange_recurringpayments_nonce' ); ?>
          			<input type="submit" class="button-secondary" name="exchange_recurringpayments_license_deactivate" value="<?php _e('Deactivate License'); ?>"/>
          		<?php } else {
          			wp_nonce_field( 'exchange_recurringpayments_nonce', 'exchange_recurringpayments_nonce' ); ?>
          			<input type="submit" class="button-secondary" name="exchange_recurringpayments_license_activate" value="<?php _e('Activate License'); ?>"/>
          		<?php } ?>
           </span>
          </p>
            <h4><?php _e( 'Recurring Payment Cancelled Email', 'LION' ); ?></h4>
            <p>
                <label for="recurring-payments-cancel-subject"><?php _e( 'Email Subject', 'LION' ); ?> <span class="tip" title="<?php _e( 'The subject you want users who have cancelled their subscriptions to receive.', 'LION' ); ?>">i</span></label>
                <?php $form->add_text_box( 'recurring-payments-cancel-subject' ); ?>
            </p>
            <p>
                <label for="recurring-payments-cancel-body"><?php _e( 'Email Message', 'LION' ); ?> <span class="tip" title="<?php _e( 'The message you want users who have cancelled their subscriptions to receive.', 'LION' ); ?>">i</span></label>
                <?php
                if ( $wp_version >= 3.3 && function_exists( 'wp_editor' ) ) {
                    echo wp_editor( $settings['recurring-payments-cancel-body'], 'recurring-payments-cancel-body', array( 'textarea_name' => 'it-exchange-add-on-recurring_payments-recurring-payments-cancel-body', 'textarea_rows' => 10, 'textarea_cols' => 30, 'editor_class' => 'large-text' ) );

					//We do this for some ITForm trickery... just to add recurring-payments-cancel-body to the used inputs field
					$form->get_text_area( 'recurring-payments-cancel-body', array( 'rows' => 10, 'cols' => 30, 'class' => 'large-text' ) );
                } else {
                    $form->add_text_area( 'recurring-payments-cancel-body', array( 'rows' => 10, 'cols' => 30, 'class' => 'large-text' ) );
				}
				?>
            </p>

            <h4><?php _e( 'Recurring Payment Expired Email', 'LION' ); ?></h4>
            <p>
                <label for="recurring-payments-deactivate-subject"><?php _e( 'Email Subject', 'LION' ); ?> <span class="tip" title="<?php _e( 'The subject you want users who have cancelled their subscriptions to receive.', 'LION' ); ?>">i</span></label>
                <?php $form->add_text_box( 'recurring-payments-deactivate-subject' ); ?>
            </p>
            <p>
                <label for="recurring-payments-deactivate-body"><?php _e( 'Email Message', 'LION' ); ?> <span class="tip" title="<?php _e( 'The message you want users who have cancelled their subscriptions to receive.', 'LION' ); ?>">i</span></label>
                <?php
                if ( $wp_version >= 3.3 && function_exists( 'wp_editor' ) ) {
                    echo wp_editor( $settings['recurring-payments-deactivate-body'], 'recurring-payments-deactivate-body', array( 'textarea_name' => 'it-exchange-add-on-recurring_payments-recurring-payments-deactivate-body', 'textarea_rows' => 10, 'textarea_cols' => 30, 'editor_class' => 'large-text' ) );

					//We do this for some ITForm trickery... just to add recurring-payments-cancel-body to the used inputs field
					$form->get_text_area( 'recurring-payments-deactivate-body', array( 'rows' => 10, 'cols' => 30, 'class' => 'large-text' ) );
                } else {
                    $form->add_text_area( 'recurring-payments-deactivate-body', array( 'rows' => 10, 'cols' => 30, 'class' => 'large-text' ) );
				}
				?>
            </p>

            <p class="description">
            <?php
            _e( 'Enter the email that is sent to administrator after a customer completes a successful purchase. HTML is accepted. Available shortcode functions:', 'LION' );
            echo '<br />';
            printf( __( 'You call these shortcode functions like this: %s', 'LION' ), '[it_exchange_email show=order_table option=purchase_message]' );
            echo '<ul>';
            echo '<li>name - ' . __( "The buyer's first name", 'LION' ) . '</li>';
            echo '<li>fullname - ' . __( "The buyer's full name, first and last", 'LION' ) . '</li>';
            echo '<li>username - ' . __( "The buyer's username on the site, if they registered an account", 'LION' ) . '</li>';
            echo '<li>sitename - ' . __( 'Your site name', 'LION' ) . '</li>';
            echo '<li>login_link - ' . __( 'Adds a link to the login page on your website.', 'LION' ) . '</li>';
            do_action( 'it_exchange_email_template_tags_list' );
            echo '</ul>';
            ?>
            </p>
        </div>
        <?php
    }

    /**
     * Save settings
     *
     * @since 1.0.0
     * @return void
    */
    function save_settings() {
        $defaults = it_exchange_get_option( 'addon_recurring_payments' );
        $new_values = wp_parse_args( ITForm::get_post_data(), $defaults );

        // Check nonce
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'it-exchange-recurring-payments-settings' ) ) {
            $this->error_message = __( 'Error. Please try again', 'LION' );
            return;
        }

        $errors = apply_filters( 'it_exchange_add_on_recurring_payments_validate_settings', $this->get_form_errors( $new_values ), $new_values );
        if ( ! $errors && it_exchange_save_option( 'addon_recurring_payments', $new_values ) ) {
            ITUtility::show_status_message( __( 'Settings saved.', 'LION' ) );
        } else if ( $errors ) {
            $errors = implode( '<br />', $errors );
            $this->error_message = $errors;
        } else {
            $this->status_message = __( 'Settings not saved.', 'LION' );
        }
        if( isset( $_POST['exchange_recurringpayments_license_activate'] ) ) {

      		// run a quick security check
      	 	if( ! check_admin_referer( 'exchange_recurringpayments_nonce', 'exchange_recurringpayments_nonce' ) )
      			return; // get out if we didn't click the Activate button

      		// retrieve the license from the database
      		// $license = trim( get_option( 'exchange_recurringpayments_license_key' ) );
         $exchangewp_recurringpayments_options = get_option( 'it-storage-exchange_addon_recurring_payments' );
         $license = trim( $exchangewp_recurringpayments_options['recurringpayments_license'] );

      		// data to send in our API request
      		$api_params = array(
      			'edd_action' => 'activate_license',
      			'license'    => $license,
      			'item_name'  => urlencode( 'recurring-payments' ), // the name of our product in EDD
      			'url'        => home_url()
      		);

      		// Call the custom API.
      		$response = wp_remote_post( 'https://exchangewp.com', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

      		// make sure the response came back okay
      		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

      			if ( is_wp_error( $response ) ) {
      				$message = $response->get_error_message();
      			} else {
      				$message = __( 'An error occurred, please try again.' );
      			}

      		} else {

      			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

      			if ( false === $license_data->success ) {

      				switch( $license_data->error ) {

      					case 'expired' :

      						$message = sprintf(
      							__( 'Your license key expired on %s.' ),
      							date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
      						);
      						break;

      					case 'revoked' :

      						$message = __( 'Your license key has been disabled.' );
      						break;

      					case 'missing' :

      						$message = __( 'Invalid license.' );
      						break;

      					case 'invalid' :
      					case 'site_inactive' :

      						$message = __( 'Your license is not active for this URL.' );
      						break;

      					case 'item_name_mismatch' :

      						$message = sprintf( __( 'This appears to be an invalid license key for %s.' ), 'recurringpayments' );
      						break;

      					case 'no_activations_left':

      						$message = __( 'Your license key has reached its activation limit.' );
      						break;

      					default :

      						$message = __( 'An error occurred, please try again.' );
      						break;
      				}

      			}

      		}

      		// Check if anything passed on a message constituting a failure
      		if ( ! empty( $message ) ) {
      			return;
      		}

      		//$license_data->license will be either "valid" or "invalid"
      		update_option( 'exchange_recurringpayments_license_status', $license_data->license );
      		return;
      	}

         // deactivate here
         // listen for our activate button to be clicked
      	if( isset( $_POST['exchange_recurringpayments_license_deactivate'] ) ) {

      		// run a quick security check
      	 	if( ! check_admin_referer( 'exchange_recurringpayments_nonce', 'exchange_recurringpayments_nonce' ) )
      			return; // get out if we didn't click the Activate button

         $exchangewp_recurringpayments_options = get_option( 'it-storage-exchange_addon_recurring_payments' );
         $license = $exchangewp_recurringpayments_options['recurringpayments_license'];

      		// data to send in our API request
      		$api_params = array(
      			'edd_action' => 'deactivate_license',
      			'license'    => $license,
      			'item_name'  => urlencode( 'recurring-payments' ), // the name of our product in EDD
      			'url'        => home_url()
      		);
      		// Call the custom API.
      		$response = wp_remote_post( 'https://exchangewp.com', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

      		// make sure the response came back okay
      		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

      			if ( is_wp_error( $response ) ) {
      				$message = $response->get_error_message();
      			} else {
      				$message = __( 'An error occurred, please try again.' );
      			}

      			return;
      		}

      		// decode the license data
      		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
      		// $license_data->license will be either "deactivated" or "failed"
      		if( $license_data->license == 'deactivated' ) {
      			delete_option( 'exchange_recurringpayments_license_status' );
      		}

      		return;

      	}

        return;

      }

    /**
    * This is a means of catching errors from the activation method above and displaying it to the customer
    *
    * @since 1.2.2
    */
    function exchange_recurringpayments_admin_notices() {
      if ( isset( $_GET['sl_activation'] ) && ! empty( $_GET['message'] ) ) {

      	switch( $_GET['sl_activation'] ) {

      		case 'false':
      			$message = urldecode( $_GET['message'] );
      			?>
      			<div class="error">
      				<p><?php echo $message; ?></p>
      			</div>
      			<?php
      			break;

      		case 'true':
      		default:
      			// Developers can put a custom success message here for when activation is successful if they way.
      			break;

      	}
      }
    }

    /**
     * Validates for values
     *
     * Returns string of errors if anything is invalid
     *
     * @since 1.0.0
     * @return void
    */
    public function get_form_errors( $values ) {

        $errors = array();
        if ( empty( $values['recurring-payments-cancel-subject'] ) )
            $errors[] = __( 'Please include an email subject for cancellations', 'LION' );
        if ( empty( $values['recurring-payments-cancel-body'] ) )
            $errors[] = __( 'Please include an email body for cancellations', 'LION' );
        if ( empty( $values['recurring-payments-deactivate-subject'] ) )
            $errors[] = __( 'Please include an email subject for expirations', 'LION' );
        if ( empty( $values['recurring-payments-deactivate-body'] ) )
            $errors[] = __( 'Please include an email body for expirations', 'LION' );

        return $errors;
    }

}
