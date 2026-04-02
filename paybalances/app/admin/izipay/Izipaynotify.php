<?php
namespace PearPay\Admin\Izipay;

class Izipaynotify {
    public $name = 'Pay';
    public $title = '';
    public $from = 'felixhuari123@gmail.com';
    public $to;

   

    public function __construct(){
           ini_set('log_errors', 'On');
        ini_set('error_log', dirname(__FILE__) . '/error.log');
        $this->setRecipientsFromOptions();
    }
     public function setRecipientsFromOptions() {
        $emails = array_map('trim', explode(',', get_option('pearpay_mail')));
        $this->to = $emails;
    }
    public function notify($data,$orderId) {

        $firstName = $data['name'] ?? ''; // corregido: firtsName → firstName
        $lastName = $data['lastName'] ?? '';
        $subjectPay = $data['subject'] ?? '';
        $email = $data['email'] ?? '';
        $typePay = $data['typePay'] ?? 'IZIPAY';
        $totalPay = $data['totalpay'] ?? 0;


        // HTML email
        $message = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Confirmación de Pago</title>
        </head>
        <body style="font-family: Arial, sans-serif; background-color: #f7f7f7; margin: 0; padding: 4px; color: #333;">
            <div style="max-width: 600px; margin: 30px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); padding: 30px;">
            <div style="text-align: center; border-bottom: 1px solid #e0e0e0; padding-bottom: 15px;">
                <h2 style="margin: 0; color: #4CAF50;">Pago recibido</h2>
                <p style="margin: 5px 0 0;">en IziPay </p>
            </div>

            <div style="padding: 20px 0;">
                <p><strong style="color: #555;">Nombre:</strong> ' . $firstName . ' ' . $lastName . '</p>
                <p><strong style="color: #555;">Email:</strong> ' . htmlspecialchars($email) . '</p>
                <p><strong style="color: #555;">Subject:</strong> ' . htmlspecialchars($subjectPay) . '</p>
                <p><strong style="color: #555;">ID del pago:</strong> ' . htmlspecialchars($orderId) . '</p>
                <p><strong style="color: #555;">Método de pago:</strong> ' . htmlspecialchars($typePay) . '</p>

                <div style="font-size: 22px; color: #4CAF50; margin: 20px 0;">
                Total Pagado: $' . number_format($totalPay, 2) . '
                </div>';

     

        $message .= '
            </div>
            <div style="text-align: center; font-size: 12px; color: #999; margin-top: 30px;">
                Esta es una notificación automática.  '.date('d/m/Y H:i').'
            </div>
            </div>
        </body>
        </html>
        ';

        // Encabezados del email
        $subject = 'Confirmación de Pago - ' . $orderId;
         $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: '.sanitize_text_field($firstName) . ' <' . sanitize_email($this->from) . '>',
            'Reply-To: ' . sanitize_email($email), // Corregir espacio entre : y el email

        );
        // Enviar el email
        $send = wp_mail($this->to, $subject, $message, $headers,[]);

        if ($send) {
            return true;
        } else {
            // Registrar error si existe
            error_log('Izipaynotify::Error enviando correo: ' . print_r([
                'to' => $this->to,
                'subject' => $subject,
                'headers' => $headers,
                'message' => $message
            ], true));
            
            return [
                'status' => 500,
                'title' => 'Error',
                'message' => 'El correo no se pudo enviar. Por favor, revisa los datos e inténtalo nuevamente.'
            ];
        }
    }
}

