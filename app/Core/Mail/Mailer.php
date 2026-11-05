<?php
declare(strict_types=1);

namespace ExamCore\Core\Mail;

use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;

final class Mailer
{
    /** @param array<string, mixed> $config see config/mail.php */
    public function __construct(private readonly array $config)
    {
    }

    public function enabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? false);
    }

    /**
     * Send one HTML email with a plain-text alternative.
     *
     * @throws RuntimeException when sending fails, so the queue can retry.
     */
    public function send(string $toAddress, string $toName, string $subject, string $html, string $text): void
    {
        if (!$this->enabled()) {
            return;
        }
        if (filter_var($toAddress, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('Recipient address is invalid.');
        }

        $mail = new PHPMailer(true);
        try {
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->Timeout = max(5, (int) ($this->config['timeout'] ?? 15));
            if (($this->config['transport'] ?? 'mail') === 'smtp') {
                $mail->isSMTP();
                $mail->Host = (string) $this->config['host'];
                $mail->Port = (int) $this->config['port'];
                $username = (string) ($this->config['username'] ?? '');
                $mail->SMTPAuth = $username !== '';
                $mail->Username = $username;
                $mail->Password = (string) ($this->config['password'] ?? '');
                $encryption = (string) ($this->config['encryption'] ?? 'tls');
                $mail->SMTPSecure = match ($encryption) {
                    'ssl' => PHPMailer::ENCRYPTION_SMTPS,
                    'tls' => PHPMailer::ENCRYPTION_STARTTLS,
                    default => '',
                };
                $mail->SMTPAutoTLS = $encryption !== '';
            } else {
                $mail->isMail();
            }
            $mail->setFrom((string) $this->config['from_address'], (string) $this->config['from_name']);
            $mail->addAddress($toAddress, $toName);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $html;
            $mail->AltBody = $text;
            $mail->send();
        } catch (MailerException $exception) {
            // ErrorInfo never contains the SMTP password.
            throw new RuntimeException('Email could not be sent: ' . $mail->ErrorInfo, 0, $exception);
        }
    }
}
