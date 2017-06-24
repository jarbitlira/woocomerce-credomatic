<?php
/**
 * Plugin Name:  Credomatic Gateway
 * Plugin URI:   http://jarbitlira.com/credomatic-checkout/
 * Description:  Provides a Credomatic Payment Gateway..
 * Version:      1.0.0
 * Author:       Jarbit Lira
 * Author URI:   https://jarbitlira.com/
 * License:      MIT License
 */

/**
 * Check if WooCommerce is active
 **/

require_once "vendor/autoload.php";
require_once "includes/class-credomatic-gateway.php";

/**
 * Class Credomatic_Gateway_Integration
 * * @package  Credomatic-Gateway
 */
class Credomatic_Gateway_Integration extends Credomatic_Gateway
{

    /**
     * Construct the plugin.
     */
    public function __construct()
    {
        add_action('plugins_loaded', array($this, 'init'));
        parent::__construct($this);
    }

    /**
     * Initialize the plugin.
     */
    public function init()
    {
        // Checks if WooCommerce is installed.
        if (class_exists('WC_Integration')) {
            // Include our integration class.
            include_once 'includes/class-credomatic-gateway.php';
            // Register the integration.
            add_filter('woocommerce_payment_gateways', array($this, 'add_integration'));
        } else {
            // throw an admin error if you like
        }
    }

    /**
     * Add a new integration to WooCommerce.
     */
    public function add_integration($integrations)
    {
        $integrations[] = 'Credomatic_Gateway_Integration';
        return $integrations;
    }
}

$Credomatic_Gateway_Integration = new Credomatic_Gateway_Integration(__FILE__);

