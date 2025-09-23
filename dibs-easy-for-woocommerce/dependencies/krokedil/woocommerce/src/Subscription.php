<?php

namespace KrokedilNexiCheckoutDeps\Krokedil\WooCommerce;

\defined('ABSPATH') || exit;
/**
 * Utility functions for Subscriptions.
 *
 * @package Krokedil/WooCommerce
 */
class Subscription
{
    /**
     * Check if a cart item is a subscription item.
     *
     * @param array $cart_item The cart item to check.
     *
     * @return bool True if the cart item is a subscription item, false otherwise.
     */
    public static function is_subscription_item($cart_item)
    {
        if (\class_exists('KrokedilNexiCheckoutDeps\\WC_Subscriptions_Product') && \KrokedilNexiCheckoutDeps\WC_Subscriptions_Product::is_subscription($cart_item['data'])) {
            return \true;
        }
        if (\method_exists('WCS_ATT_Cart', 'get_subscription_scheme') && \false !== \KrokedilNexiCheckoutDeps\WCS_ATT_Cart::get_subscription_scheme($cart_item)) {
            return \true;
        }
        return \false;
    }
}
