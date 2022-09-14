<?php

/**
 * the plugin Payment Gateway.
 *
 * Provides a the plugin Payment Gateway.
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
		$this->method_description = __('Accept payments from customers in USK. Customers will sign the transaction without ever leaving your store', 'kujira');
		$this->supports           = array(
			'products',
			'refunds',
		);

		$this->init_form_fields();
		$this->init_settings();

		$this->title          = $this->get_option('title');
		$this->description    = $this->get_option('description');

		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

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
		global $woocommerce;
		$order = new WC_Order($order_id);
		$data = $this->get_post_data();

		$tx = $data["usk_tx"];
		$res = Kujira_Chain::broadcast($tx);

		if ($res->success()) {
			$order->add_meta_data('hash', $res->hash);
			$order->add_order_note(__('USK payment received: https://finder.kujira.app/kaiyo-1/tx/' . $res->hash, 'woothemes'));
			$order->payment_complete();
			$woocommerce->cart->empty_cart();

			// Return thankyou redirect
			return array(
				'result' => 'success',
				'redirect' => $this->get_return_url($order)
			);
		} else {
			wc_add_notice(__('Payment error:', 'woothemes') . ' ' . $res->error, 'error');
			return;
		}
	}

	/**
	 * Can the order be refunded via Kujira?
	 *
	 * @param  WC_Order $order Order object.
	 * @return bool
	 */
	public function can_refund_order($order)
	{
		return false;
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
			$tx = 'https://finder.kujira.app/kaiyo-1/tx/' . $order->get_meta('hash');
			ob_start(); ?>
			Thank you for your payment. Your transaction has been completed, You can view the transaction <a href="<?php echo $tx; ?>" target="_blank">here.</a>
<?php
			return ob_get_clean();
		}

		return $text;
	}

	/**
	 * Determines whether the plugin should be loaded or not.
	 *
	 * By default the plugin isn't loaded on new installs or on existing sites which haven't set up the gateway.
	 *
	 * @since 5.5.0
	 *
	 * @return bool Whether the plugin should be loaded.
	 */
	public function should_load()
	{
		$option_key  = '_should_load';
		$should_load = $this->get_option($option_key);

		if ('' === $should_load) {

			// New installs without the plugin enabled don't load it.
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
		 * Allow third-parties to filter whether the plugin should be loaded or not.
		 *
		 * @since 5.5.0
		 *
		 * @param bool              $should_load Whether the plugin should be loaded.
		 * @param WC_Gateway_Kujira $this        The WC_Gateway_Kujira instance.
		 */
		return apply_filters('woocommerce_should_load_kujira', $should_load, $this);
	}
}
