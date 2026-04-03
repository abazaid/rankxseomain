<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class SmtpClient
{
    private $socket = null;

    public function send(
        string $host,
        int $port,
        string $username,
        string $password,
        string $fromEmail,
        string $fromName,
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $encryption = 'tls'
    ): void {
        $transport = ($encryption === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
        $this->socket = @stream_socket_client($transport, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);

        if (!$this->socket) {
            throw new RuntimeException('SMTP connection failed: ' . $errstr . ' (' . $errno . ')');
        }

        stream_set_timeout($this->socket, 20);

        $this->expect([220]);
        $this->command('EHLO localhost', [250]);

        if ($encryption === 'tls') {
            $this->command('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Unable to enable TLS encryption.');
            }
            $this->command('EHLO localhost', [250]);
        }

        $this->command('AUTH LOGIN', [334]);
        $this->command(base64_encode($username), [334]);
        $this->command(base64_encode($password), [235]);

        $this->command('MAIL FROM:<' . $fromEmail . '>', [250]);
        $this->command('RCPT TO:<' . $toEmail . '>', [250, 251]);
        $this->command('DATA', [354]);

        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'From: ' . $this->formatAddress($fromName, $fromEmail),
            'To: ' . $this->formatAddress($toName, $toEmail),
            'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];

        $message = implode("\r\n", $headers) . "\r\n\r\n" . $htmlBody . "\r\n.";
        fwrite($this->socket, $message . "\r\n");
        $this->expect([250]);
        $this->command('QUIT', [221]);
        fclose($this->socket);
        $this->socket = null;
    }

    private function formatAddress(string $name, string $email): string
    {
        if ($name === '') {
            return '<' . $email . '>';
        }

        return '=?UTF-8?B?' . base64_encode($name) . '?= <' . $email . '>';
    }

    private function command(string $command, array $expectedCodes): void
    {
        fwrite($this->socket, $command . "\r\n");
        $this->expect($expectedCodes);
    }

    private function expect(array $expectedCodes): string
    {
        $response = '';

        while (($line = fgets($this->socket, 515)) !== false) {
            $response .= $line;

            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }

        $code = (int) substr($response, 0, 3);

        if (!in_array($code, $expectedCodes, true)) {
            throw new RuntimeException('SMTP unexpected response: ' . trim($response));
        }

        return $response;
    }
}
