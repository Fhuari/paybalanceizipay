<?php
namespace PearPay\Admin;
class Setting
{
    private $menu_hook = '';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    public function enqueueAdminAssets($hook)
    {
        if ($hook !== $this->menu_hook) {
            return;
        }
        wp_enqueue_style('pear-admin-style', PEAR_PAY_PLUGIN_URL . 'assets/css/admin.css', [], PEAR_PAY_VERSION);
        wp_enqueue_script('pear-admin-app', PEAR_PAY_PLUGIN_URL . 'assets/js/admin.js', [], PEAR_PAY_VERSION, true);

        wp_localize_script('pear-admin-app', 'PEARPAY_ADMIN', [
            'rest_url' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);

    }
    
    // render view by vue js
    public function render()
    {
        ?>
        <div class="wrap">
            <div id="pearpay-admin"></div>
        </div>
        <?php
    }

    // add sidebar menu
    public function addAdminMenu()
    {
        $this->menu_hook = add_menu_page(
            'Pear Pay Izipay Settings',
            'Pay',
            'manage_options',
            'pear-pay-izipay',
            [$this, 'render'],
            'dashicons-screenoptions',
            6
        );
    }

}

