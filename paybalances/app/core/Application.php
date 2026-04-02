<?php


namespace PearPay\Core;

use PearPay\Admin\Setting;
use PearPay\Admin\Api;
use PearPay\Public\PearHandle;
use PearPay\Admin\Izipay\Pay;

class Application
{
    public static function init()
    {
        // Inicializar componentes del plugin
         // Registrar configuraciones solo en el admin
        if (is_admin()) {
            new Setting();
        }
        // REST API must be registered for non-admin requests too
        new Api();
        // Registrar shortcode para el pago
        new PearHandle();
        new Pay();
    }
}

