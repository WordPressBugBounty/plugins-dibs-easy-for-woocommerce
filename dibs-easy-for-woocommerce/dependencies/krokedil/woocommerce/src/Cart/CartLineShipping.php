<?php

/**
 * Class to generate a cart line item from the WooCommerce cart shipping.
 */
namespace KrokedilNexiCheckoutDeps\Krokedil\WooCommerce\Cart;

use KrokedilNexiCheckoutDeps\Krokedil\WooCommerce\OrderLineData;
/**
 * Class to generate a cart line item from the WooCommerce cart shipping.
 *
 * Since shipping lines for carts are so different, we base it of the base OrderLineData class, instead of extending the CartLineItem class like the other cart line items.
 */
class CartLineShipping extends OrderLineData
{
    /**
     * Filter prefix.
     *
     * @var mixed
     */
    public $filter_prefix = 'cart_line_shipping';
    /**
     * The WooCommerce shipping rate.
     *
     * @var \WC_Shipping_Rate $shipping_rate
     */
    public $shipping_rate;
    /**
     * Constructor.
     *
     * @param \WC_Shipping_Rate $shipping_rate The WooCommerce shipping rate.
     * @param array             $config        Configuration array.
     */
    public function __construct($shipping_rate, $config = array())
    {
        parent::__construct($config);
        $this->shipping_rate = $shipping_rate;
        $this->set_name();
        $this->set_sku();
        $this->set_quantity();
        $this->set_unit_price();
        $this->set_subtotal_unit_price();
        $this->set_tax_rate();
        $this->set_total_amount();
        $this->set_subtotal_amount();
        $this->set_total_discount_amount();
        $this->set_total_discount_tax_amount();
        $this->set_total_tax_amount();
        $this->set_subtotal_tax_amount();
        $this->set_type();
        $this->set_product_url();
        $this->set_image_url();
        $this->set_compatibility();
    }
    /**
     * Abstract function to set product name
     *
     * @return void
     */
    public function set_name()
    {
        $this->name = apply_filters($this->get_filter_name('name'), $this->shipping_rate->get_label(), $this->shipping_rate);
    }
    /**
     * Abstract function to set product sku
     *
     * @return void
     */
    public function set_sku()
    {
        $this->sku = apply_filters($this->get_filter_name('sku'), $this->shipping_rate->get_id(), $this->shipping_rate);
    }
    /**
     * Abstract function to set product quantity
     *
     * @return void
     */
    public function set_quantity()
    {
        $this->quantity = apply_filters($this->get_filter_name('quantity'), 1, $this->shipping_rate);
    }
    /**
     * Abstract function to set product unit price
     *
     * @return void
     */
    public function set_unit_price()
    {
        $this->unit_price = apply_filters($this->get_filter_name('unit_price'), $this->format_price($this->shipping_rate->get_cost()), $this->shipping_rate);
    }
    /**
     * Abstract function to set product subtotal unit price
     *
     * @return void
     */
    public function set_subtotal_unit_price()
    {
        $this->subtotal_unit_price = apply_filters($this->get_filter_name('subtotal_unit_price'), $this->format_price($this->shipping_rate->get_cost()), $this->shipping_rate);
    }
    /**
     * Abstract function to set product tax rate
     *
     * @return void
     */
    public function set_tax_rate()
    {
        $item_tax_rate = 0;
        // Get the first key from the tax rates array.
        $taxes = $this->shipping_rate->get_taxes;
        if (!empty($taxes)) {
            $tax = new \WC_Tax();
            $tax_rate = 0;
            // There may be more than one tax rate applied. This is always the case when you have multiple tax rates defined under the same tax group with different priority.
            foreach ($taxes as $tax_id => $tax_amount) {
                $tax_rate += $tax->get_rate_percent_value($tax_id);
            }
            $item_tax_rate = \round($tax_rate * 100);
        } else {
            $item_tax_rate = 0.0 === \floatval($this->shipping_rate->get_cost()) ? 0 : \round(WC()->cart->get_shipping_tax() / $this->shipping_rate->get_cost() * 10000);
        }
        $this->tax_rate = apply_filters($this->get_filter_name('tax_rate'), $item_tax_rate, $this->shipping_rate);
    }
    /**
     * Abstract function to set product total amount
     *
     * @return void
     */
    public function set_total_amount()
    {
        $this->total_amount = apply_filters($this->get_filter_name('total_amount'), $this->format_price($this->shipping_rate->get_cost()), $this->shipping_rate);
    }
    /**
     * Abstract function to set product subtotal amount
     *
     * @return void
     */
    public function set_subtotal_amount()
    {
        $this->subtotal_amount = apply_filters($this->get_filter_name('subtotal_amount'), $this->format_price($this->shipping_rate->get_cost()), $this->shipping_rate);
    }
    /**
     * Abstract function to set product total discount amount
     *
     * @return void
     */
    public function set_total_discount_amount()
    {
        $this->total_discount_amount = apply_filters($this->get_filter_name('discount_amount'), 0, $this->shipping_rate);
    }
    /**
     * Abstract function to set product total discount tax amount
     *
     * @return void
     */
    public function set_total_discount_tax_amount()
    {
        $this->total_discount_tax_amount = apply_filters($this->get_filter_name('total_discount_tax_amount'), 0, $this->shipping_rate);
    }
    /**
     * Abstract function to set product total tax amount
     *
     * @return void
     */
    public function set_total_tax_amount()
    {
        $shipping_tax = 0.0 === $this->shipping_rate->get_shipping_tax() ? WC()->cart->get_shipping_tax() : $this->shipping_rate->get_shipping_tax();
        $this->total_tax_amount = apply_filters($this->get_filter_name('total_tax_amount'), $this->format_price($shipping_tax), $this->shipping_rate);
    }
    /**
     * Abstract function to set product subtotal tax amount
     *
     * @return void
     */
    public function set_subtotal_tax_amount()
    {
        $shipping_tax = 0.0 === $this->shipping_rate->get_shipping_tax() ? WC()->cart->get_shipping_tax() : $this->shipping_rate->get_shipping_tax();
        $this->subtotal_tax_amount = apply_filters($this->get_filter_name('subtotal_tax_amount'), $this->format_price($shipping_tax), $this->shipping_rate);
    }
    /**
     * Abstract function to set product type
     *
     * @return void
     */
    public function set_type()
    {
        $this->type = apply_filters($this->get_filter_name('type'), 'shipping', $this->shipping_rate);
    }
    /**
     * Abstract function to set product url
     *
     * @return void
     */
    public function set_product_url()
    {
        $this->product_url = apply_filters($this->get_filter_name('product_url'), null, $this->shipping_rate);
    }
    /**
     * Abstract function to set product image url
     *
     * @return void
     */
    public function set_image_url()
    {
        $this->image_url = apply_filters($this->get_filter_name('image_url'), null, $this->shipping_rate);
    }
    /**
     * Abstract function to set product compatibility
     *
     * @return void
     */
    public function set_compatibility()
    {
        $this->compatibility = apply_filters($this->get_filter_name('compatibility'), array(), $this->shipping_rate);
    }
}
