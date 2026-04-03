<?php

declare(strict_types=1);

namespace App\Services;

use App\Config;
use RuntimeException;

final class Mailer
{
    public function sendPasswordReset(string $toEmail, string $toName, string $resetUrl): bool
    {
        $subject = (string) Config::get('MAIL_RESET_SUBJECT', 'تعيين كلمة المرور');
        $safeName = htmlspecialchars($toName !== '' ? $toName : $toEmail, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');

        $html = <<<HTML
<html lang="ar" dir="rtl">
  <body style="margin:0;font-family:Tajawal,Tahoma,Arial,sans-serif;background:#f6efe3;padding:28px;color:#1d1b18;">
    <div style="max-width:680px;margin:0 auto;background:#fffdf9;border-radius:24px;padding:32px;border:1px solid #eadccc;box-shadow:0 18px 40px rgba(50,35,18,0.08);">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;">
        <div>
          <div style="display:inline-block;padding:8px 14px;border-radius:999px;background:#f1e5d7;color:#7a4e1f;font-size:13px;font-weight:700;">RankX SEO</div>
          <h1 style="margin:16px 0 8px;font-size:34px;line-height:1.1;">مرحبًا {$safeName}</h1>
          <p style="margin:0;color:#675f56;line-height:1.9;">تم ربط متجرك بنجاح، ويمكنك الآن تفعيل حسابك للدخول إلى لوحة التحكم الخارجية وإدارة تحسينات المنتجات.</p>
        </div>
      </div>
      <div style="margin-top:24px;padding:20px;border-radius:20px;background:#fbf6ef;border:1px solid #eee1d0;">
        <p style="margin:0 0 12px;line-height:1.9;">اضغط الزر التالي لتعيين كلمة المرور:</p>
        <p style="margin:0;"><a href="{$safeUrl}" style="display:inline-block;background:#0f7b66;color:#ffffff;text-decoration:none;padding:14px 20px;border-radius:14px;font-weight:700;">تعيين كلمة المرور</a></p>
      </div>
      <p style="margin-top:24px;line-height:1.9;color:#675f56;">إذا لم يعمل الزر، انسخ الرابط التالي وافتحه في المتصفح:</p>
      <p style="word-break:break-all;color:#7a4e1f;line-height:1.9;">{$safeUrl}</p>
      <hr style="border:none;border-top:1px solid #eee1d0;margin:28px 0;">
      <p style="margin:0;color:#8a7d70;line-height:1.8;">تم إرسال هذه الرسالة من منصة RankX SEO لإدارة وتحسين محتوى منتجات سلة.</p>
    </div>
  </body>
</html>
HTML;

        return $this->sendHtml($toEmail, $toName, $subject, $html);
    }

    public function sendHtml(string $toEmail, string $toName, string $subject, string $html): bool
    {
        $host = (string) Config::get('SMTP_HOST', '');
        $port = (int) Config::get('SMTP_PORT', 587);
        $username = (string) Config::get('SMTP_USERNAME', '');
        $password = (string) Config::get('SMTP_PASSWORD', '');
        $encryption = (string) Config::get('SMTP_ENCRYPTION', 'tls');
        $fromEmail = (string) Config::get('MAIL_FROM_ADDRESS', '');
        $fromName = (string) Config::get('MAIL_FROM_NAME', 'RankX SEO');

        if ($host === '' || $username === '' || $password === '' || $fromEmail === '') {
            throw new RuntimeException('SMTP settings are incomplete.');
        }

        (new SmtpClient())->send(
            $host,
            $port,
            $username,
            $password,
            $fromEmail,
            $fromName,
            $toEmail,
            $toName,
            $subject,
            $html,
            $encryption
        );

        return true;
    }

    public function sendTestEmail(string $toEmail): bool
    {
        $safeEmail = htmlspecialchars($toEmail, ENT_QUOTES, 'UTF-8');
        $html = <<<HTML
<html lang="ar" dir="rtl">
  <body style="font-family:Tajawal,Tahoma,Arial,sans-serif;background:#f6efe3;padding:28px;">
    <div style="max-width:640px;margin:0 auto;background:#ffffff;border-radius:18px;padding:28px;border:1px solid #eadccc;">
      <h2 style="margin-top:0;color:#1d1b18;">اختبار إرسال البريد</h2>
      <p style="line-height:1.8;color:#4f463d;">إذا وصلك هذا البريد على <strong>{$safeEmail}</strong> فهذا يعني أن إعدادات SMTP الخاصة بـ RankX SEO تعمل بنجاح.</p>
      <p style="line-height:1.8;color:#6c645a;">يمكنك الآن الاعتماد على نفس الإعدادات في رسائل تفعيل الحساب واسترجاع كلمة المرور.</p>
    </div>
  </body>
</html>
HTML;

        return $this->sendHtml($toEmail, $toEmail, 'اختبار بريد RankX SEO', $html);
    }
}
