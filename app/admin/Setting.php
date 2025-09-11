<?php
namespace PearPay\Admin;
class Setting
{
    // Configuration settings would go here
    // register styles, scripts, etc to admin
    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        // Mostrar errores solo si existen en error.log
        add_action('admin_menu', [$this, 'addAdminMenu']);
        ini_set('log_errors', 'On');
        ini_set('error_log', dirname(__FILE__) . '/error.log');
    }
    public function enqueueAdminAssets()
    {
        // Enqueue your admin styles and scripts here
        wp_enqueue_style('pear-admin-style', PEAR_PAY_PLUGIN_URL . 'assets/css/admin-style.css', [], PEAR_PAY_VERSION);
        wp_enqueue_script('pear-admin-script', PEAR_PAY_PLUGIN_URL . 'assets/js/admin-script.js', ['jquery'], PEAR_PAY_VERSION, true);

    }
    
    // render view by vue js
    // pearpay_mail
    public function render()
    {
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pearpay_settings_nonce']) && wp_verify_nonce($_POST['pearpay_settings_nonce'], 'pearpay_settings_save')) {
            $pearpay_mail = ($_POST['pearpay_mail']);
            $pearpay_env = isset($_POST['pearpay_env']) && $_POST['pearpay_env'] === 'production' ? 'production' : 'dev';

            update_option('pearpay_mail', $pearpay_mail);
            update_option('pearpay_env', $pearpay_env);

            echo '<div class="updated notice"><p>Settings saved.</p></div>';
        }

        $pearpay_mail = get_option('pearpay_mail', '');
        $pearpay_env = get_option('pearpay_env', 'dev');
   
        
        ?>
        <div class="wrap">

        
            <h1>Pear Pay Settings</h1>
            <form method="post">
                <?php wp_nonce_field('pearpay_settings_save', 'pearpay_settings_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="pearpay_mail">PearPay Mail</label></th>
                        <td>
                            <input type="text" name="pearpay_mail" id="pearpay_mail" value="<?php echo ($pearpay_mail); ?>" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Environment</th>
                        <td>
                            <label>
                                <input type="radio" name="pearpay_env" value="dev" <?php checked($pearpay_env, 'dev'); ?>> Dev
                            </label>
                            <label style="margin-left:20px;">
                                <input type="radio" name="pearpay_env" value="production" <?php checked($pearpay_env, 'production'); ?>> Production
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        <?php
    }

    // add sidebar menu
    public function addAdminMenu()
    {
        add_menu_page(
            'Pear Pay Izipay Settings',
            'Pear Pay Izipay',
            'manage_options',
            'pear-pay-izipay',
            [$this, 'render'],
            'dashicons-admin-generic',
            6
        );
    }

}

