<?php
/*
 * Plugin Name:       Pear Pay
 * Plugin URI:        https://pearperu.com/plugins/
 * Description:       Plugin de pago Pear Peru para WordPress con integracion Izipay.
 * Version:           1.1.02
 * Requires at least: 5.2
 * Requires PHP:      8.2
 * Author:            Felix H.
 * Author URI:        https://pearperu.com/
 * License:           GPL v2 o posterior
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://pearperu.com/plugins/
 * Text Domain:       pear-pay-izipay
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('PEAR_PAY_VERSION')) {
    define('PEAR_PAY_VERSION', '1.1.03');
}

if (!defined('PEAR_PAY_PLUGIN_FILE')) {
    define('PEAR_PAY_PLUGIN_FILE', __FILE__);
}

if (!defined('PEAR_PAY_PLUGIN_DIR')) {
    define('PEAR_PAY_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

if (!defined('PEAR_PAY_PLUGIN_URL')) {
    define('PEAR_PAY_PLUGIN_URL', plugin_dir_url(__FILE__));
}

spl_autoload_register(function (string $class): void {
    $prefix = 'PearPay\\';
    $prefix_length = strlen($prefix);

    if (strncmp($prefix, $class, $prefix_length) !== 0) {
        return;
    }
    $relative_class = substr($class, $prefix_length);
    $file = PEAR_PAY_PLUGIN_DIR . 'app/' . str_replace('\\', '/', $relative_class) . '.php';

    if (is_readable($file)) {
        require $file;
    }
});

register_activation_hook(__FILE__, 'pear_pay_activation');

if (class_exists('PearPay\\Core\\Application')) {
    PearPay\Core\Application::init();
} else {
    add_action('admin_notices', 'pear_pay_missing_application_notice');
}

function pear_pay_activation(): bool
{
    if (class_exists('PearPay\\Database\\Database')) {
        PearPay\Database\Database::install();
    }

    return true;
}

function pear_pay_missing_application_notice(): void
{
    echo '<div class="notice notice-error"><p>Error: La clase PearPay\Core\Application no se encontro. Por favor, asegurese de que todos los archivos del plugin esten presentes.</p></div>';
}

