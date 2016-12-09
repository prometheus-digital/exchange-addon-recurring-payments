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
    	'pause-subscription' => false,
	);

    return ITUtility::merge_defaults( $values, $defaults );
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
	        <p><?php _e( 'Emails have been moved to the Settings -> Emails tab.', 'LION' ); ?></p>

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

	/**
	 * @param ITForm $form
	 * @param array  $settings
	 */
    function get_recurring_payments_form_table( $form, $settings = array() ) {

        if ( !empty( $settings ) )
            foreach ( $settings as $key => $var )
                $form->set_option( $key, $var );

        if ( ! empty( $_GET['page'] ) && 'it-exchange-setup' == $_GET['page'] ) : ?>
            <h3><?php _e( 'Recurring Payments', 'LION' ); ?></h3>
        <?php endif; ?>
        <div class="it-exchange-addon-settings it-exchange-recurring-payments-addon-settings">

	        <label for="pause-subscription"><?php _e( 'Allow Pausing', 'LION' ); ?></label>
	        <p><?php _e( 'Allow customers to pause their subscription.', 'LION' ); ?></p>
	        <?php $form->add_check_box( 'pause-subscription' ); ?>


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
