<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../config/email.php';
require_once __DIR__ . '/../../vendor/autoload.php';

class MailSender {
    private static function getConfig() {
        return require __DIR__ . '/../config/email.php';
    }

    // Wysyłanie przypomnienia o serwisie
    public static function sendReminderEmail($locationName, $lastServiceDate, $to = null) {
        $config = self::getConfig();
        $to = $to ?? $config['to_email'];

        $mail = new PHPMailer(true);
        try {
            // Konfiguracja serwera SMTP
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
            $mail->SMTPSecure = $config['encryption'];
            $mail->Port = $config['port'];

            // Nadawca
            $mail->setFrom($config['from_email'], $config['from_name']);
            $mail->addAddress($to);

            // Treść wiadomości
            $mail->isHTML(true);
            $mail->Subject = "Przypomnienie o serwisowaniu - $locationName";
            $mail->Body = "
                <h3>Przypomnienie o serwisie</h3>
                <p>Ostatni serwis dla lokalizacji <strong>$locationName</strong> odbyl sie: <strong>$lastServiceDate</strong></p>
            ";

            // Wysyłka e-maila
            if (!$mail->send()) {
                error_log(" Błąd wysyłki dla lokalizacji $locationName: " . $mail->ErrorInfo);
                return false;
            }
            return true;
        } catch (Exception $e) {
            error_log(" Błąd wysyłki e-maila: " . $mail->ErrorInfo);
            return false;
        }
    }

    // Wysyłanie przypomnienia o wydarzeniu
    public static function sendEventReminderEmail($userName, $title, $description, $eventDate, $to) {
        $config = self::getConfig();
    
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
            $mail->SMTPSecure = $config['encryption'];
            $mail->Port = $config['port'];
    
            $mail->setFrom($config['from_email'], $config['from_name']);
            $mail->addAddress($to, $userName);
    
            $mail->isHTML(true);
            $mail->Subject = "Przypomnienie o nadchodzącym wydarzeniu";
            $mail->Body = "
                <p>To przypomnienie o nadchodzącym wydarzeniu zaplanowanym na <strong>$eventDate</strong>:</p>
                <ul>
                    <li><strong>Tytuł:</strong> $title</li>
                    <li><strong>Opis:</strong> $description</li>
                </ul>
            ";
    
            return $mail->send();
        } catch (Exception $e) {
            error_log("Błąd e-maila do $to: " . $mail->ErrorInfo);
            return false;
        }
    }
    

    public static function sendFailureReport($failedLocations) {
        $config = self::getConfig();
        $adminEmail = $config['from_email'];

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
            $mail->SMTPSecure = $config['encryption'];
            $mail->Port = $config['port'];

            $mail->setFrom($config['from_email'], $config['from_name']);
            $mail->addAddress($adminEmail);

            $mail->isHTML(true);
            $mail->Subject = "Blad wysylki przypomnien o serwisie";
            $mail->Body = "
                <h3>Blad wysylki przypomnien</h3>
                <p>Nie udało się wyslac przypomnien dla nastepujących lokalizacji:</p>
                <ul>" . implode('', array_map(fn($loc) => "<li>$loc</li>", $failedLocations)) . "</ul>
            ";

            $mail->send();
        } catch (Exception $e) {
            error_log(" Błąd wysyłki raportu o błędach: " . $mail->ErrorInfo);
        }
    }
}
