<?php

namespace PearPay\Core;

use PearPay\Admin\Controllers\ApiController;
use PearPay\Admin\Pages\SettingsPage;
use PearPay\Frontend\Shortcodes\PaymentShortcode;
use PearPay\Payments\Izipay\PaymentAjaxController;

class Application
{
    /**
     * Punto central de arranque del plugin.
     *
     * Aqui se instancian los modulos principales para mantener pearpay.php
     * como bootstrap y no mezclar logica de negocio en el archivo principal.
     */
    public static function init(): void
    {
        if (is_admin()) {
            new SettingsPage();
        }
        new ApiController();
        new PaymentShortcode();
        new PaymentAjaxController();
    }
}
