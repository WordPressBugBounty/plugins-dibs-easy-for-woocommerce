<?php

/**
 * Class to generate a cart line item from the WooCommerce StoreApi cart item.
 */
namespace KrokedilNexiCheckoutDeps\Krokedil\WooCommerce\StoreApi;

use KrokedilNexiCheckoutDeps\Krokedil\WooCommerce\OrderLineData;
/**
 * Class to generate a cart line item from the WooCommerce StoreApi cart item.
 */
class StoreApiLineItem extends OrderLineData
{
    /**
     * Filter prefix.
     *
     * @var mixed
     */
    public $filter_prefix = 'store_api_cart_line_item';
    /**
     * The WooCommerce cart item from the store api.
     *
     * @var array $cart_item
     */
    public $cart_item;
    /**
     * The WooCommerce product.
     *
     * @var \WC_Product $product
     */
    public $product;
    /**
     * Constructor.
     *
     * @param array $cart_item The WooCommerce cart item.
     * @param array $config Configuration array.
     */
    public function __construct($cart_item, $config = array())
    {
        parent::__construct($config);
        $this->cart_item = $cart_item;
        $this->product = wc_get_product($cart_item['id']);
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
     * Function to set product name
     *
     * @return void
     */
    public function set_name()
    {
        $name = $this->cart_item['name'];
        $this->name = apply_filters($this->get_filter_name('name'), $name, $this->cart_item);
    }
    /**
     * Function to set product sku
     *
     * @return void
     */
    public function set_sku()
    {
        $item_reference = $this->cart_item['sku'] ? $this->cart_item['sku'] : $this->cart_item['id'];
        $this->sku = apply_filters($this->get_filter_name('sku'), $item_reference, $this->cart_item);
    }
    /**
     * Function to set product quantity
     *
     * @return void
     */
    public function set_quantity()
    {
        $this->quantity = apply_filters($this->get_filter_name('quantity'), $this->cart_item['quantity'], $this->cart_item);
    }
    /**
     * Function to set product unit price
     *
     * @return void
     */
    public function set_unit_price()
    {
        $unit_price = $this->cart_item['prices']['price'];
        $this->unit_price = apply_filters($this->get_filter_name('unit_price'), $this->format_price_from_minor($unit_price), $this->cart_item);
    }
    /**
     * Function to set product subtotal unit price
     *
     * @return void
     */
    public function set_subtotal_unit_price()
    {
        $subtotal_unit_price = $this->cart_item['totals']['line_subtotal'] / $this->cart_item['quantity'];
        $this->subtotal_unit_price = apply_filters($this->get_filter_name('subtotal_unit_price'), $this->format_price_from_minor($subtotal_unit_price), $this->cart_item);
    }
    /**
     * Function to set product tax rate
     *
     * @return void
     */
    public function set_tax_rate()
    {
        $item_tax_rate = 0;
        if ($this->product->is_taxable() && $this->cart_item['totals']['line_total_tax'] > 0) {
            $_tax = new \WC_Tax();
            $tmp_rates = $_tax->get_rates($this->product->get_tax_class());
            $vat = \array_shift($tmp_rates);
            if (isset($vat['rate'])) {
                $item_tax_rate = \round($vat['rate'] * 100);
            } else {
                $item_tax_rate = 0;
            }
        }
        $this->tax_rate = apply_filters($this->get_filter_name('tax_rate'), $item_tax_rate, $this->cart_item);
    }
    /**
     * Function to set product total amount
     *
     * @return void
     */
    public function set_total_amount()
    {
        $this->total_amount = apply_filters($this->get_filter_name('total_amount'), $this->format_price_from_minor($this->cart_item['totals']['line_total']), $this->cart_item);
    }
    /**
     * Function to set product subtotal amount
     *
     * @return void
     */
    public function set_subtotal_amount()
    {
        $this->subtotal_amount = apply_filters($this->get_filter_name('subtotal_amount'), $this->format_price_from_minor($this->cart_item['totals']['line_subtotal']), $this->cart_item);
    }
    /**
     * Function to set product total discount amount
     *
     * @return void
     */
    public function set_total_discount_amount()
    {
        $total_discount_amount = $this->cart_item['totals']['line_subtotal'] - $this->cart_item['totals']['line_total'];
        $this->total_discount_amount = apply_filters($this->get_filter_name('total_discount_amount'), $this->format_price_from_minor($total_discount_amount), $this->cart_item);
    }
    /**
     * Abstract function to set product total discount tax amount
     *
     * @return void
     */
    public function set_total_discount_tax_amount()
    {
        $total_discount_tax_amount = $this->cart_item['totals']['line_subtotal_tax'] - $this->cart_item['totals']['line_total_tax'];
        $this->total_discount_tax_amount = apply_filters($this->get_filter_name('total_discount_tax_amount'), $this->format_price_from_minor($total_discount_tax_amount), $this->cart_item);
    }
    /**
     * Function to set product total tax amount
     *
     * @return void
     */
    public function set_total_tax_amount()
    {
        $this->total_tax_amount = apply_filters($this->get_filter_name('total_tax_amount'), $this->format_price_from_minor($this->cart_item['totals']['line_total_tax']), $this->cart_item);
    }
    /**
     * Function to set product subtotal tax amount
     *
     * @return void
     */
    public function set_subtotal_tax_amount()
    {
        $this->subtotal_tax_amount = apply_filters($this->get_filter_name('subtotal_tax_amount'), $this->format_price_from_minor($this->cart_item['totals']['line_subtotal_tax']), $this->cart_item);
    }
    /**
     * Function to set product type
     *
     * @return void
     */
    public function set_type()
    {
        $this->type = apply_filters($this->get_filter_name('type'), $this->product->get_type(), $this->cart_item);
    }
    /**
     * Function to set product url
     *
     * @return void
     */
    public function set_product_url()
    {
        $product_url = null;
        if ($this->product) {
            $product_url = get_permalink($this->product->get_id());
        }
        $this->product_url = apply_filters($this->get_filter_name('product_url'), $product_url ? $product_url : null, $this->cart_item);
    }
    /**
     * Function to set product image url
     *
     * @return void
     */
    public function set_image_url()
    {
        $image_url = null;
        if ($this->product) {
            $image_url = wp_get_attachment_image_url($this->product->get_image_id(), 'woocommerce_thumbnail');
        }
        $this->image_url = apply_filters($this->get_filter_name('image_url'), $image_url ? $image_url : null, $this->cart_item);
    }
    /**
     * Function to set product compatibility
     *
     * @return void
     */
    public function set_compatibility()
    {
        $this->compatibility = apply_filters($this->get_filter_name('compatibility'), array(), $this->cart_item);
    }
}
