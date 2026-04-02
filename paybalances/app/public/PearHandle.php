<?php

namespace PearPay\Public;

use PearPay\Admin\Izipay\IzipayConfig;
use PearPay\Db\OptionRepository;

class PearHandle
{
    public function __construct()
    {
        add_shortcode('pear_pay', [$this, 'render']);
        add_shortcode('pear_pay_options', [$this, 'render_options']);
        add_shortcode('pear_pay_balance', [$this, 'render_balance']);

        // Registro de scripts y estilos en el hook correcto
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);

    }

    /**
     * Registrar assets (no encolarlos todavía)
     */
    public function register_assets()
    {
        // CSS
        // wp_register_style(
        //     'pear-pay-public',
        //     plugins_url('../../assets/css/public.css', __FILE__),
        //     [],
        //     null
        // );

        // Vue + tu script
        wp_register_script('pear-pay', plugins_url('../../assets/js/pearpay.js', __FILE__), [], null, true);
        wp_register_style(
            'pear-izipay-reset',
            'https://static.micuentaweb.pe/static/js/krypton-client/V4.0/ext/classic-reset.css',
            [],
            null
        );
        wp_register_script(
            'pear-izipay-classic',
            'https://static.micuentaweb.pe/static/js/krypton-client/V4.0/ext/classic.js',
            [],
            null,
            true
        );

        $izipay_credentials = IzipayConfig::credentials();
        $pearpay_data = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pearpay_nonce'),
            'action_token' => 'izipay_generate_token',
            'action_balance' => 'pearpay_balance',
            'action_validate' => 'pearpay_validate',
            'settings' => [
                'commission_type' => get_option('pearpay_commission_type', 'percent'),
                'commission_value' => floatval(get_option('pearpay_commission_value', 0)),
                'show_payment_options' => (bool) get_option('pearpay_show_payment_options', true),
                'currency' => get_option('pearpay_currency', 'USD'),
                'theme_color' => get_option('pearpay_theme_color', '#1d2327'),
                'theme_border'=> get_option('pearpay_theme_border'),
                'confirm_message' => get_option('pearpay_confirm_message', 'Payment successful!'),
                'izipay_public_key' => $izipay_credentials['public_key'] ?? '',
                'balance_texts' => [
                    'title_en' => get_option('pearpay_balance_title_en', 'Pay your balance or make a deposit'),
                    'title_es' => get_option('pearpay_balance_title_es', 'Pague su saldo o realice un depósito'),
                    'desc_en' => get_option('pearpay_balance_desc_en', 'Pay any remaining balance or make a custom deposit. Enter the amount you wish to pay.'),
                    'desc_es' => get_option('pearpay_balance_desc_es', 'Pague cualquier saldo restante o realice un depósito personalizado. Ingrese el monto que desea pagar.'),
                    'label_en' => get_option('pearpay_balance_label_en', 'Enter the payment amount'),
                    'label_es' => get_option('pearpay_balance_label_es', 'Introduzca el importe del pago'),
                ],
            ],
            'options' => OptionRepository::active(),
        ];

        wp_localize_script('pear-pay', 'PEARPAY', $pearpay_data);
    }

    /**
     * Renderiza el shortcode
     */
    public function render($atts)
    {
        return $this->render_common('all');
    }

    public function render_options($atts)
    {
        return $this->render_common('options');
    }

    public function render_balance($atts)
    {
        return $this->render_common('balance');
    }

    private function render_common($mode)
    {
        // Solo encolar cuando se usa el shortcode
        wp_enqueue_style('pear-izipay-reset');
        wp_enqueue_script('pear-izipay-classic');
        wp_enqueue_script('pear-pay');

        $pearpay_env = \PearPay\Admin\Izipay\IzipayConfig::env();
        $lang = get_locale() ?? 'en';
        $lang_code = substr($lang, 0, 2);

        ob_start();
        ?>
        <div class="pear-pay-public pearpay-root"
             data-lang="<?php echo esc_attr($lang_code); ?>"
             data-devmode="<?php echo esc_attr($pearpay_env); ?>"
             data-mode="<?php echo esc_attr($mode); ?>">
        </div>
        <?php
        return ob_get_clean();
    }
}
