<?php

namespace PearPay\Frontend\Shortcodes;

use PearPay\Database\OptionRepository;
use PearPay\Payments\Izipay\IzipayConfig;

class PaymentShortcode
{
    private const SCRIPT_HANDLE = 'pear-pay';
    private const IZIPAY_RESET_HANDLE = 'pear-izipay-reset';
    private const IZIPAY_CLASSIC_HANDLE = 'pear-izipay-classic';

    public function __construct()
    {
        add_shortcode('pear_pay', [$this, 'render']);
        add_shortcode('pear_pay_options', [$this, 'render_options']);
        add_shortcode('pear_pay_balance', [$this, 'render_balance']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
    }

    /**
     * Registra los assets, pero los encola solo cuando aparece el shortcode.
     */
    public function register_assets(): void
    {
        wp_register_script(
            self::SCRIPT_HANDLE,
            PEAR_PAY_PLUGIN_URL . 'assets/js/pearpay.js',
            [],
            PEAR_PAY_VERSION,
            true
        );

        wp_register_style(
            self::IZIPAY_RESET_HANDLE,
            'https://static.micuentaweb.pe/static/js/krypton-client/V4.0/ext/classic-reset.css',
            [],
            null
        );

        wp_register_script(
            self::IZIPAY_CLASSIC_HANDLE,
            'https://static.micuentaweb.pe/static/js/krypton-client/V4.0/ext/classic.js',
            [],
            null,
            true
        );

        wp_localize_script(self::SCRIPT_HANDLE, 'PEARPAY', $this->public_data());
    }

    public function render($atts): string
    {
        return $this->render_common('all');
    }

    public function render_options($atts): string
    {
        return $this->render_common('options');
    }

    public function render_balance($atts): string
    {
        return $this->render_common('balance');
    }

    /**
     * Datos que el frontend Vue necesita para iniciar el formulario.
     */
    private function public_data(): array
    {
        $izipay_credentials = IzipayConfig::credentials();

        return [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pearpay_nonce'),
            'action_token' => 'izipay_generate_token',
            'action_balance' => 'pearpay_balance',
            'action_validate' => 'pearpay_validate',
            'settings' => [
                'commission_type' => get_option('pearpay_commission_type', 'percent'),
                'commission_value' => (float) get_option('pearpay_commission_value', 0),
                'show_payment_options' => (bool) get_option('pearpay_show_payment_options', true),
                'currency' => get_option('pearpay_currency', 'USD'),
                'theme_color' => get_option('pearpay_theme_color', '#1d2327'),
                'theme_border' => absint(get_option('pearpay_theme_border', 3)),
                'confirm_message' => get_option('pearpay_confirm_message', 'Payment successful!'),
                'izipay_public_key' => $izipay_credentials['public_key'] ?? '',
                'balance_texts' => [
                    'title_en' => get_option('pearpay_balance_title_en', 'Pay your balance or make a deposit'),
                    'title_es' => get_option('pearpay_balance_title_es', 'Pague su saldo o realice un deposito'),
                    'desc_en' => get_option('pearpay_balance_desc_en', 'Pay any remaining balance or make a custom deposit. Enter the amount you wish to pay.'),
                    'desc_es' => get_option('pearpay_balance_desc_es', 'Pague cualquier saldo restante o realice un deposito personalizado. Ingrese el monto que desea pagar.'),
                    'label_en' => get_option('pearpay_balance_label_en', 'Enter the payment amount'),
                    'label_es' => get_option('pearpay_balance_label_es', 'Introduzca el importe del pago'),
                ],
            ],
            'options' => OptionRepository::active(),
        ];
    }

    private function render_common(string $mode): string
    {
        wp_enqueue_style(self::IZIPAY_RESET_HANDLE);
        wp_enqueue_script(self::IZIPAY_CLASSIC_HANDLE);
        wp_enqueue_script(self::SCRIPT_HANDLE);

        $lang_code = substr(get_locale() ?: 'en', 0, 2);

        ob_start();
        ?>
        <div class="pear-pay-public pearpay-root"
             data-lang="<?php echo esc_attr($lang_code); ?>"
             data-devmode="<?php echo esc_attr(IzipayConfig::env()); ?>"
             data-mode="<?php echo esc_attr($mode); ?>">
        </div>
        <?php

        return ob_get_clean();
    }
}
