<?php
require_once WP_PLUGIN_DIR . "/woocommerce/woocommerce.php";

if (!defined('ABSPATH')) {
    exit;
}

use JarbitLira\Credomatic\CredomaticClient;

/**
 * Integration Demo Integration.
 *
 * @package  Credomatic-Gateway
 * @category Integration
 * @author   WooThemes
 */
class Credomatic_Gateway extends WC_Payment_Gateway_CC
{
    private $user_name;
    private $private_key;
    private $public_key;
    private $web_service_url;

    protected $credomaticClient;

    public $form_fields;

    public function __construct($gateway)
    {
//        $this->gateway = $gateway;
        $this->setup_properties();

        $this->init_form_fields();
        $this->init_settings();

        $this->user_name = $this->get_option('user_name');
        $this->private_key = $this->get_option('private_key');
        $this->public_key = $this->get_option('public_key');
        $this->web_service_url = empty(trim($this->get_option('webservice_url'))) ? null
            : $this->get_option('webservice_url');

        $this->credomaticClient = new CredomaticClient($this->user_name,
            $this->private_key,
            $this->public_key,
            $this->web_service_url
        );

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
//        add_action('woocommerce_checkout_process', array($this, 'validate_fields'));
    }

    /**
     * Setup general properties for the gateway.
     */
    protected function setup_properties()
    {
        $this->id = 'credomatic_integration';
        $this->plugin_id = 'wc_';
        $this->has_fields = true;

        $this->title = __('Credit Card', $this->id);
        $this->method_title = __('Credomatic Integration', $this->id);
        $this->method_description = __('Have your customers pay with credit cards with credomatic gateway.', $this->id);
    }


    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', $this->id),
                'type' => 'checkbox',
                'label' => __('Enable credit card', $this->id),
                'default' => 'no',
            ),
            'title' => array(
                'title' => __('Title', $this->id),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', $this->id),
                'default' => __('Credit card payment', $this->id),
                'desc_tip' => true,
            ),
            'user_name' => array(
                'title' => __('Credomatic username', $this->id),
                'type' => 'text',
            ),
            'private_key' => array(
                'title' => __('Credomatic private key', $this->id),
                'type' => 'password',
            ),
            'public_key' => array(
                'title' => __('Credomatic public key', $this->id),
                'type' => 'password',
            ),
            'webservice_url' => array(
                'title' => __('Credomatic WebService', $this->id),
                'type' => 'text',
            )
        );
    }

    public function process_payment($order_id)
    {
        $postData = $this->get_post_data();
        $ccNumber = str_replace(' ', '', $postData[$this->id . '-card-number']);
        $ccexp = str_replace(' ', '', $postData[$this->id . '-card-expiry']);
        $cvv = str_replace(' ', '', $postData[$this->id . '-card-cvc']);

        $order = wc_get_order($order_id);

        $this->credomaticClient->processPayment($order_id, $order->get_total(), $ccNumber, $cvv, $ccexp);

        $result = $this->credomaticClient->getResult();

        if ($this->credomaticClient->succeeded()) {

            $order->payment_complete($result['transaction_id']);

            // Reduce stock levels
            wc_reduce_stock_levels($order_id);

            // Remove cart
            WC()->cart->empty_cart();

            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
        }

        return array(
            'result' => 'fail',
        );
    }

    public function validate_fields()
    {
        $data = $this->get_post_data();
        $valid = true;
        // Handle required fields
        $required_fields = array(
            'card-number' => __('Card number', 'woocommerce'),
            'card-expiry' => __('Expiry (MM/YY)', 'woocommerce'),
            'card-cvc' => __('Card code', 'woocommerce'),
        );

        foreach ($required_fields as $field_key => $field_name) {
            if (empty($data[$this->id . '-' . $field_key])) {
                wc_add_notice(sprintf(__('%s is a required field.', 'woocommerce'), '<strong>' . esc_html($field_name) . '</strong>'), 'error');
                $valid = false;
            }
        }

        return $valid;
    }

}

