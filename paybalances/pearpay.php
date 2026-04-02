<?php
/*
 * Plugin Name:       Pear Pay 
 * Plugin URI:        https://pearperu.com/plugins/
 * Description:       Plugin de pago Pear Peru para WordPress con integración Izipay.
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

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
const PEAR_PAY_VERSION = '1.0.0';
// define('PEAR_VERSION', '1.0.0');

define('PEAR_PAY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PEAR_PAY_PLUGIN_URL', plugin_dir_url(__FILE__));


spl_autoload_register(function ($class) {
    $prefix = 'PearPay\\';
    $base_dir = __DIR__ . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $parts = explode('\\', $relative_class);
    if (count($parts) > 1) {
        $dir_map = array(
            'Core' => 'core',
            'Admin' => 'admin',
            'Public' => 'public',
            'Db' => 'Db',
        );
        if (isset($dir_map[$parts[0]])) {
            $parts[0] = $dir_map[$parts[0]];
        }
    }

    $file = $base_dir . implode('/', $parts) . '.php';
    if (!file_exists($file)) {
        // Linux is case-sensitive; normalize directory segments (not class filename).
        $alt_parts = $parts;
        $last_index = count($alt_parts) - 1;
        for ($i = 0; $i < $last_index; $i++) {
            $alt_parts[$i] = strtolower($alt_parts[$i]);
        }
        $alt_file = $base_dir . implode('/', $alt_parts) . '.php';
        if (file_exists($alt_file)) {
            $file = $alt_file;
        }
    }
    if (file_exists($file)) {
        require $file;
    }
});
if ( class_exists('PearPay\\Core\\Application') ) {
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
    if (class_exists('PearPay\\Db\\Database')) {
        PearPay\Db\Database::install();
    }

    return true;
}


