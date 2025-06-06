<?php
/**
 * Nets Gateway Swish class
 *
 * @package DIBS_Easy/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Nets_Easy_Gateway_Swish class
 */
class Nets_Easy_Gateway_Swish extends WC_Payment_Gateway {

	/**
	 * The checkout flow
	 *
	 * @var string
	 */
	public $checkout_flow = 'redirect';

	/**
	 * The payment gateway icon.
	 *
	 * @var string
	 */
	public $payment_gateway_icon = 'default';

	/**
	 * The payment gateway icon width.
	 *
	 * @var string
	 */
	public $payment_gateway_icon_max_width = '145';

	/**
	 * DIBS_Easy_Gateway constructor.
	 */
	public function __construct() {

		$this->id = 'nets_easy_swish';

		$this->method_title = __( 'Nexi Checkout Swish', 'dibs-easy-for-woocommerce' );

		$this->method_description = __( 'Nexi Checkout Swish payment', 'dibs-easy-for-woocommerce' );

		$this->description = $this->get_option( 'description', $this->method_description );

		// Load the form fields.
		$this->init_form_fields();
		// Load the settings.
		$this->init_settings();
		// Get the settings values.
		$this->title                          = $this->get_option( 'title', $this->method_title );
		$this->enabled                        = $this->get_option( 'enabled' );
		$this->checkout_flow                  = $this->settings['checkout_flow'] ?? $this->checkout_flow;
		$this->payment_gateway_icon           = $this->settings['payment_gateway_icon'] ?? $this->payment_gateway_icon;
		$this->payment_gateway_icon_max_width = $this->settings['payment_gateway_icon_max_width'] ?? $this->payment_gateway_icon_max_width;

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		$this->supports = array(
			'products',
			'refunds',
		);
	}

	/**
	 * Get gateway icon.
	 *
	 * @return string
	 */
	public function get_icon() {

		if ( empty( $this->payment_gateway_icon ) ) {
			return;
		}

		if ( 'default' === strtolower( $this->payment_gateway_icon ) ) {
			$icon_src   = WC_DIBS__URL . '/assets/images/nexi-icon-visa-mastercard.png';
			$icon_width = '100';
		} else {
			$icon_src   = $this->payment_gateway_icon;
			$icon_width = $this->payment_gateway_icon_max_width;
		}

		$icon_html = '<img src="' . $icon_src . '" alt="Nexi - Payments made easy" style="max-width:' . $icon_width . 'px"/>';
		return apply_filters( 'nets_easy_swish_icon_html', $icon_html );
	}

	/**
	 * Checks if method should be available.
	 *
	 * @return bool
	 */
	public function is_available() {

		if ( 'yes' !== $this->enabled ) {
			return false;
		}

		if ( is_admin() && ! wp_doing_ajax() ) {
			return true;
		}

		if ( 'SEK' !== get_woocommerce_currency() ) {
			return false;
		}

		if ( WC()->customer && method_exists( WC()->customer, 'get_billing_country' ) ) {
			if ( 'SE' !== WC()->customer->get_billing_country() ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Init form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = include WC_DIBS_PATH . '/includes/nets-easy-settings-swish.php';
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id WooCommerce order ID.
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		// If the order was created using WooCommerce blocks checkout, then we need to force the checkout flow to be redirect.
		if ( 'store-api' === $order->get_created_via() ) {
			$this->checkout_flow = 'redirect';
		}

		// Overlay flow.
		if ( 'overlay' === $this->checkout_flow && ! wp_is_mobile() && ! is_wc_endpoint_url( 'order-pay' ) ) {
			return $this->process_overlay_handler( $order_id );
		}

		// Redirect flow.
		return $this->process_redirect_handler( $order_id );
	}

	/**
	 * Process the refund.
	 *
	 * @param  int    $order_id WooCommerce order ID.
	 * @param  string $amount Refund amount.
	 * @param  string $reason Reason test message for the refund.
	 *
	 * @return bool
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );

		$response = Nets_Easy()->api->refund_nets_easy_order( $order_id );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( array_key_exists( 'refundId', $response ) ) { // Payment success
			// Translators: Nets refund ID.
			$order->add_order_note( sprintf( __( 'Refund made in Nexi with refund ID %s.', 'dibs-easy-for-woocommerce' ), $response['refundId'] ) ); // phpcs:ignore

			return true;
		}

		return false;
	}



	/**
	 * Process the payment via redirect flow.
	 *
	 * @param int $order_id The WooCommerce order id.
	 *
	 * @return array|string[]
	 */
	protected function process_redirect_handler( $order_id ) {

		// Create payment in Nets.
		$response = Nets_Easy()->api->create_nets_easy_order(
			array(
				'checkout_flow'                 => 'redirect',
				'order_id'                      => $order_id,
				'payment_methods_configuration' => 'Swish',
			)
		);
		if ( is_wp_error( $response ) ) {
			wc_add_notice( $response->get_error_message(), 'error' );
			return array(
				'result' => 'error',
			);
		}

		$order = wc_get_order( $order_id );
		if ( array_key_exists( 'hostedPaymentPageUrl', $response ) ) {
			// All good. Redirect customer to Nets payment page.
			$order->add_order_note( __( 'Customer redirected to Nets payment page.', 'dibs-easy-for-woocommerce' ) );
			$order->update_meta_data( '_dibs_payment_id', $response['paymentId'] );
			$order->save();

			return array(
				'result'   => 'success',
				'redirect' => esc_url_raw( add_query_arg( 'language', wc_dibs_get_locale(), $response['hostedPaymentPageUrl'] ) ),
			);
		}

		return array(
			'result' => 'error',
		);
	}

	/**
	 * Process the payment via overlay flow.
	 *
	 * @param int $order_id The WooCommerce order id.
	 *
	 * @return array|string[]
	 */
	protected function process_overlay_handler( $order_id ) {

		// Create payment in Nets.
		$response = Nets_Easy()->api->create_nets_easy_order(
			array(
				'checkout_flow'                 => 'overlay',
				'order_id'                      => $order_id,
				'payment_methods_configuration' => 'Swish',
			)
		);
		if ( is_wp_error( $response ) ) {
			wc_add_notice( $response->get_error_message(), 'error' );
			return array(
				'result' => 'error',
			);
		}

		$order = wc_get_order( $order_id );
		if ( array_key_exists( 'hostedPaymentPageUrl', $response ) ) {
			// All good. Redirect customer to DIBS payment page.
			$order->add_order_note( __( 'Nets payment page displayed in overlay.', 'dibs-easy-for-woocommerce' ) );
			$order->update_meta_data( '_dibs_payment_id', $response['paymentId'] );
			$order->save();
			return array(
				'result'   => 'success',
				'redirect' => '#netseasy:' . base64_encode( add_query_arg( 'language', wc_dibs_get_locale(), $response['hostedPaymentPageUrl'] ) ), // phpcs:ignore
			);
		}

		return array(
			'result' => 'error',
		);
	}
}
