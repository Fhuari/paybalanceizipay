<?php

namespace PearPay\Admin\Pages;

class SettingsPage
{
    private string $menu_hook = '';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Registra la pagina del plugin dentro del menu lateral de WordPress.
     */
    public function add_admin_menu(): void
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

    /**
     * Carga Vue/Admin solo en la pantalla del plugin.
     */
    public function enqueue_admin_assets(string $hook): void
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

    public function render(): void
    {
        ?>
        <div class="wrap">
            <div id="pearpay-admin"></div>
        </div>
        <?php
    }
}
