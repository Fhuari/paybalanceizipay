<?php
/*
 * Plugin Name:       Pear Pay Izipay
 * Plugin URI:        https://pearperu.com/plugins/
 * Description:       Plugin de pago Pear Peru para WordPress con integración Izipay.
 * Version:           1.1.02
 * Requires at least: 5.2
 * Requires PHP:      8.2
 * Author:            Felix Huari
 * Author URI:        https://pearperu.com/
 * License:           GPL v2 o posterior
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://pearperu.com/plugins/
 * Text Domain:       pear-pay-izipay
 * Domain Path:       /languages
 */

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
const PEAR_PAY_VERSION = '1.0.0';
// define('PEAR_VERSION', '1.0.0');

define('PEAR_PAY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PEAR_PAY_PLUGIN_URL', plugin_dir_url(__FILE__));


// if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
//     require_once __DIR__ . '/vendor/autoload.php';
// } else {
//     // Si no existe, hay que correr composer dump-autoload
//     return;
// }
spl_autoload_register(function ($class) {
    $prefix = 'PearPay\\';
    $base_dir = __DIR__ . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});
if ( class_exists('PearPay\Core\Application') ) {
    PearPay\Core\Application::init();
} else {

  
    // Opcionalmente, mostrar un mensaje en el panel de administración
    add_action('admin_notices', function() {
        echo '<div class="error"><p>Error: La clase PearPay\Core\Application no se encontró. Por favor, asegúrese de que todos los archivos del plugin estén presentes.</p></div>';
    });
    return;
}



// require_once PEAR_PAY_PLUGIN_DIR . '/app/core/Aplication.php';
// use PearPay\Core\PR_Aplication;
// PR_Aplication::init(); // ✅
  
// 

// $app = new Application();
// $app->boot();

register_activation_hook(__FILE__, 'pear_pay_activation');

function pear_pay_activation()
{
    
  return true;
}


