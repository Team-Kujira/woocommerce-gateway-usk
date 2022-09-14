<?php

/**
 * Kujira Standard Payment Gateway.
 *
 * Provides a Kujira Standard Payment Gateway.
 *
 * @class       WC_Gateway_Kujira
 * @extends     WC_Payment_Gateway
 * @version     2.3.0
 * @package     Kujira
 */


use Automattic\Jetpack\Constants;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * WC_Gateway_Kujira Class.
 */
class Kujira_WC_Gateway extends WC_Payment_Gateway
{

	/**
	 * Whether or not logging is enabled
	 *
	 * @var bool
	 */
	public static $log_enabled = false;

	/**
	 * Logger instance
	 *
	 * @var WC_Logger
	 */
	public static $log = false;

	/**
	 * Constructor for the gateway.
	 */
	public function __construct()
	{
		$this->id                = 'kujira';
		$this->has_fields        = true;
		$this->icon 			 = "https://3949068434-files.gitbook.io/~/files/v0/b/gitbook-x-prod.appspot.com/o/spaces%2FyPeco0BDFBM85kKY4Hkl%2Fuploads%2F8WVViam1nBsMw89XXElN%2Fusk.png?alt=media&token=92572e21-0d20-4174-9932-12204c456450";
		$this->method_title      = __('USK', 'kujira');
		/* translators: %s: Link to WC system status page */
		$this->method_description = __('Accept payments from customers in USK. Customers will sign the transaction without ever leaving your store', 'kujira');
		$this->supports           = array(
			'products',
			'refunds',
		);

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title          = $this->get_option('title');
		$this->description    = $this->get_option('description');
		$this->testmode       = 'yes' === $this->get_option('testmode', 'no');
		$this->debug          = 'yes' === $this->get_option('debug', 'no');
		self::$log_enabled    = $this->debug;

		if ($this->testmode) {
			/* translators: %s: Link to Kujira sandbox testing guide page */
			$this->description .= ' ' . sprintf(__('SANDBOX ENABLED. You can use sandbox testing accounts only. See the <a href="%s">Kujira Sandbox Testing Guide</a> for more details.', 'kujira'), 'https://developer.Kujira.com/docs/classic/lifecycle/ug_sandbox/');
			$this->description  = trim($this->description);
		}

		// Actions.
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		add_action('woocommerce_order_status_processing', array($this, 'capture_payment'));
		add_action('woocommerce_order_status_completed', array($this, 'capture_payment'));
		add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));


		if ('yes' === $this->enabled) {
			add_filter('woocommerce_thankyou_order_received_text', array($this, 'order_received_text'), 10, 2);
		}
	}

	/**
	 * Return whether or not this gateway still requires setup to function.
	 *
	 * When this gateway is toggled on via AJAX, if this returns true a
	 * redirect will occur to the settings page instead.
	 *
	 * @since 3.4.0
	 * @return bool
	 */
	public function needs_setup()
	{
		return !$this->account;
	}

	/**
	 * Logging method.
	 *
	 * @param string $message Log message.
	 * @param string $level Optional. Default 'info'. Possible values:
	 *                      emergency|alert|critical|error|warning|notice|info|debug.
	 */
	public static function log($message, $level = 'info')
	{
		if (self::$log_enabled) {
			if (empty(self::$log)) {
				self::$log = wc_get_logger();
			}
			self::$log->log($level, $message, array('source' => 'Kujira'));
		}
	}

	/**
	 * Processes and saves options.
	 * If there is an error thrown, will continue to save and validate fields, but will leave the erroring field out.
	 *
	 * @return bool was anything saved?
	 */
	public function process_admin_options()
	{
		$saved = parent::process_admin_options();

		// Maybe clear logs.
		if ('yes' !== $this->get_option('debug', 'no')) {
			if (empty(self::$log)) {
				self::$log = wc_get_logger();
			}
			self::$log->clear('Kujira');
		}

		return $saved;
	}


	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields()
	{
		$this->form_fields = array(
			'enabled' => array(
				'title' => __('Enable/Disable', 'kujira'),
				'type' => 'checkbox',
				'label' => __('Enable USK Payment', 'kujira'),
				'default' => 'yes'
			),
			'title' => array(
				'title' => __('Account', 'kujira'),
				'type' => 'text',
				'description' => __('The description shown to the customer', 'kujira'),
				'default' => __('USK by Kujira', 'kujira'),
				'desc_tip'      => true,
			),
			'account' => array(
				'title' => __('Account', 'kujira'),
				'type' => 'text',
				'description' => __('Your kujira network wallet address.', 'kujira'),
				'default' => __('kujira1234...', 'kujira'),
				'desc_tip'      => true,
			),
		);
	}



	public function payment_fields()
	{
		$amount = $this->get_order_total();
		$to = $this->settings["account"];
?>
		<div id="kujira-usk-checkout" data-to="<?php echo $to ?>" data-amount="<?php echo $amount ?>"></div>
<?php
	}



	/**
	 * Process the payment and return the result.
	 *
	 * @param  int $order_id Order ID.
	 * @return array
	 */
	public function process_payment($order_id)
	{
		// include_once dirname(__FILE__) . '/includes/class-wc-gateway-Kujira-request.php';

		// $order          = wc_get_order($order_id);
		// $Kujira_request = new WC_Gateway_Kujira_Request($this);

		// return array(
		// 	'result'   => 'success',
		// 	'redirect' => $Kujira_request->get_request_url($order, $this->testmode),
		// );
	}

	/**
	 * Can the order be refunded via Kujira?
	 *
	 * @param  WC_Order $order Order object.
	 * @return bool
	 */
	public function can_refund_order($order)
	{
		$has_api_creds = false;

		if ($this->testmode) {
			$has_api_creds = $this->get_option('sandbox_api_username') && $this->get_option('sandbox_api_password') && $this->get_option('sandbox_api_signature');
		} else {
			$has_api_creds = $this->get_option('api_username') && $this->get_option('api_password') && $this->get_option('api_signature');
		}

		return $order && $order->get_transaction_id() && $has_api_creds;
	}

	/**
	 * Init the API class and set the username/password etc.
	 */
	protected function init_api()
	{
		// include_once dirname(__FILE__) . '/includes/class-wc-gateway-Kujira-api-handler.php';

		// WC_Gateway_Kujira_API_Handler::$api_username  = $this->testmode ? $this->get_option('sandbox_api_username') : $this->get_option('api_username');
		// WC_Gateway_Kujira_API_Handler::$api_password  = $this->testmode ? $this->get_option('sandbox_api_password') : $this->get_option('api_password');
		// WC_Gateway_Kujira_API_Handler::$api_signature = $this->testmode ? $this->get_option('sandbox_api_signature') : $this->get_option('api_signature');
		// WC_Gateway_Kujira_API_Handler::$sandbox       = $this->testmode;
	}

	/**
	 * Process a refund if supported.
	 *
	 * @param  int    $order_id Order ID.
	 * @param  float  $amount Refund amount.
	 * @param  string $reason Refund reason.
	 * @return bool|WP_Error
	 */
	public function process_refund($order_id, $amount = null, $reason = '')
	{
		// $order = wc_get_order($order_id);

		// if (!$this->can_refund_order($order)) {
		// 	return new WP_Error('error', __('Refund failed.', 'woocommerce'));
		// }

		// $this->init_api();

		// $result = WC_Gateway_Kujira_API_Handler::refund_transaction($order, $amount, $reason);

		// if (is_wp_error($result)) {
		// 	$this->log('Refund Failed: ' . $result->get_error_message(), 'error');
		// 	return new WP_Error('error', $result->get_error_message());
		// }

		// $this->log('Refund Result: ' . wc_print_r($result, true));

		// switch (strtolower($result->ACK)) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		// 	case 'success':
		// 	case 'successwithwarning':
		// 		$order->add_order_note(
		// 			/* translators: 1: Refund amount, 2: Refund ID */
		// 			sprintf(__('Refunded %1$s - Refund ID: %2$s', 'woocommerce'), $result->GROSSREFUNDAMT, $result->REFUNDTRANSACTIONID) // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		// 		);
		// 		return true;
		// }

		// return isset($result->L_LONGMESSAGE0) ? new WP_Error('error', $result->L_LONGMESSAGE0) : false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	/**
	 * Capture payment when the order is changed from on-hold to complete or processing
	 *
	 * @param  int $order_id Order ID.
	 */
	public function capture_payment($order_id)
	{
		// $order = wc_get_order($order_id);

		// if ('Kujira' === $order->get_payment_method() && 'pending' === $order->get_meta('_Kujira_status', true) && $order->get_transaction_id()) {
		// 	$this->init_api();
		// 	$result = WC_Gateway_Kujira_API_Handler::do_capture($order);

		// 	if (is_wp_error($result)) {
		// 		$this->log('Capture Failed: ' . $result->get_error_message(), 'error');
		// 		/* translators: %s: Kujira gateway error message */
		// 		$order->add_order_note(sprintf(__('Payment could not be captured: %s', 'woocommerce'), $result->get_error_message()));
		// 		return;
		// 	}

		// 	$this->log('Capture Result: ' . wc_print_r($result, true));

		// 	// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		// 	if (!empty($result->PAYMENTSTATUS)) {
		// 		switch ($result->PAYMENTSTATUS) {
		// 			case 'Completed':
		// 				/* translators: 1: Amount, 2: Authorization ID, 3: Transaction ID */
		// 				$order->add_order_note(sprintf(__('Payment of %1$s was captured - Auth ID: %2$s, Transaction ID: %3$s', 'woocommerce'), $result->AMT, $result->AUTHORIZATIONID, $result->TRANSACTIONID));
		// 				$order->update_meta_data('_Kujira_status', $result->PAYMENTSTATUS);
		// 				$order->set_transaction_id($result->TRANSACTIONID);
		// 				$order->save();
		// 				break;
		// 			default:
		// 				/* translators: 1: Authorization ID, 2: Payment status */
		// 				$order->add_order_note(sprintf(__('Payment could not be captured - Auth ID: %1$s, Status: %2$s', 'woocommerce'), $result->AUTHORIZATIONID, $result->PAYMENTSTATUS));
		// 				break;
		// 		}
		// 	}
		// 	// phpcs:enable
		// }
	}

	/**
	 * Custom Kujira order received text.
	 *
	 * @since 3.9.0
	 * @param string   $text Default text.
	 * @param WC_Order $order Order data.
	 * @return string
	 */
	public function order_received_text($text, $order)
	{
		if ($order && $this->id === $order->get_payment_method()) {
			return esc_html__('Thank you for your payment. Your transaction has been completed, and a receipt for your purchase has been emailed to you. Log into your Kujira account to view transaction details.', 'kujira');
		}

		return $text;
	}

	/**
	 * Determines whether Kujira Standard should be loaded or not.
	 *
	 * By default Kujira Standard isn't loaded on new installs or on existing sites which haven't set up the gateway.
	 *
	 * @since 5.5.0
	 *
	 * @return bool Whether Kujira Standard should be loaded.
	 */
	public function should_load()
	{
		$option_key  = '_should_load';
		$should_load = $this->get_option($option_key);

		if ('' === $should_load) {

			// New installs without Kujira Standard enabled don't load it.
			if ('no' === $this->enabled && WC_Install::is_new_install()) {
				$should_load = false;
			} else {
				$should_load = true;
			}

			$this->update_option($option_key, wc_bool_to_string($should_load));
		} else {
			$should_load = wc_string_to_bool($should_load);
		}

		/**
		 * Allow third-parties to filter whether Kujira Standard should be loaded or not.
		 *
		 * @since 5.5.0
		 *
		 * @param bool              $should_load Whether Kujira Standard should be loaded.
		 * @param WC_Gateway_Kujira $this        The WC_Gateway_Kujira instance.
		 */
		return apply_filters('woocommerce_should_load_Kujira_standard', $should_load, $this);
	}
}
