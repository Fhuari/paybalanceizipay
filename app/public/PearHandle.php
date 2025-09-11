<?php

namespace PearPay\Public;

class PearHandle
{
    public function __construct()
    {
        add_shortcode('pear_pay', [$this, 'render']);

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
        wp_register_script(
            'pear-pay',
            plugins_url('../../assets/js/pearpay.js', __FILE__),
            [],
            null,
            true
        );
        $pearpay_data = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pearpay_nonce'),
            'action_token' => 'izipay_generate_token',
            'action_balance' => 'pearpay_balance',
            'action_validate' => 'pearpay_validate',
        ];

        wp_localize_script('pear-pay', 'PEARPAY',   $pearpay_data);

    }

    /**
     * Renderiza el shortcode
     */
    public function render($atts)
    {
        // Solo encolar cuando se usa el shortcode
        wp_enqueue_script('pear-pay');    

        $pearpay_env = get_option('pearpay_env');
        // Obtener el idioma actual de WordPress
        $lang = get_locale();
        // Extraer solo el código de idioma (por ejemplo, 'es' de 'es_ES')
        $lang_code = substr($lang, 0, 2);

        ob_start();
        ?>
        <div class="pear-pay-public" id="apppay">
            <link rel="stylesheet" href="https://static.micuentaweb.pe/static/js/krypton-client/V4.0/ext/classic-reset.css" />
            <script src="https://static.micuentaweb.pe/static/js/krypton-client/V4.0/ext/classic.js"></script>
            <!-- Aquí Vue montará su componente -->
            <apppay :lang="<?php echo esc_attr($lang_code); ?>" devmode="<?php echo esc_attr($pearpay_env); ?>"></apppay>
        </div>
        <?php
        return ob_get_clean();
    }

  
 
}