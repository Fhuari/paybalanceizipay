<?php

namespace PearPay\Payments\Izipay;

class IzipayNotify
{
    private string $from = 'felixhuari123@gmail.com';
    private array $recipients = [];

    public function __construct()
    {
        ini_set('log_errors', 'On');
        ini_set('error_log', __DIR__ . '/error.log');

        $this->recipients = $this->recipients_from_options();
    }

    /**
     * Envia un correo al administrador cuando Izipay confirma el pago.
     */
    public function notify(array $data, string $order_id)
    {
        $first_name = sanitize_text_field($data['name'] ?? '');
        $last_name = sanitize_text_field($data['lastName'] ?? '');
        $subject_pay = sanitize_text_field($data['subject'] ?? '');
        $email = sanitize_email($data['email'] ?? '');
        $type_pay = sanitize_text_field($data['typePay'] ?? 'IZIPAY');
        $total_pay = (float) ($data['totalpay'] ?? 0);

        $subject = 'Confirmacion de Pago - ' . $order_id;
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $first_name . ' <' . sanitize_email($this->from) . '>',
            'Reply-To: ' . $email,
        ];

        $sent = wp_mail(
            $this->recipients,
            $subject,
            $this->email_template($first_name, $last_name, $subject_pay, $email, $order_id, $type_pay, $total_pay),
            $headers,
            []
        );

        if ($sent) {
            return true;
        }

        error_log('IzipayNotify::Error enviando correo: ' . print_r([
            'to' => $this->recipients,
            'subject' => $subject,
            'headers' => $headers,
        ], true));

        return [
            'status' => 500,
            'title' => 'Error',
            'message' => 'El correo no se pudo enviar. Por favor, revisa los datos e intentalo nuevamente.',
        ];
    }

    /**
     * Convierte la opcion pearpay_mail en una lista limpia de correos.
     */
    private function recipients_from_options(): array
    {
        $emails = explode(',', (string) get_option('pearpay_mail', ''));
        $emails = array_map('trim', $emails);
        $emails = array_map('sanitize_email', $emails);

        return array_values(array_filter($emails));
    }

    /**
     * HTML del correo. Se mantiene aqui para no mezclar plantilla y envio.
     */
    private function email_template(
        string $first_name,
        string $last_name,
        string $subject_pay,
        string $email,
        string $order_id,
        string $type_pay,
        float $total_pay
    ): string {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Confirmacion de Pago</title>
        </head>
        <body style="font-family: Arial, sans-serif; background-color: #f7f7f7; margin: 0; padding: 4px; color: #333;">
            <div style="max-width: 600px; margin: 30px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); padding: 30px;">
                <div style="text-align: center; border-bottom: 1px solid #e0e0e0; padding-bottom: 15px;">
                    <h2 style="margin: 0; color: #4CAF50;">Pago recibido</h2>
                    <p style="margin: 5px 0 0;">en IziPay</p>
                </div>
                <div style="padding: 20px 0;">
                    <p><strong style="color: #555;">Nombre:</strong> ' . esc_html($first_name . ' ' . $last_name) . '</p>
                    <p><strong style="color: #555;">Email:</strong> ' . esc_html($email) . '</p>
                    <p><strong style="color: #555;">Subject:</strong> ' . esc_html($subject_pay) . '</p>
                    <p><strong style="color: #555;">ID del pago:</strong> ' . esc_html($order_id) . '</p>
                    <p><strong style="color: #555;">Metodo de pago:</strong> ' . esc_html($type_pay) . '</p>
                    <div style="font-size: 22px; color: #4CAF50; margin: 20px 0;">
                        Total Pagado: $' . esc_html(number_format($total_pay, 2)) . '
                    </div>
                </div>
                <div style="text-align: center; font-size: 12px; color: #999; margin-top: 30px;">
                    Esta es una notificacion automatica. ' . esc_html(date('d/m/Y H:i')) . '
                </div>
            </div>
        </body>
        </html>';
    }
}
