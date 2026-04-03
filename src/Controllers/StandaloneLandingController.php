<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config;
use App\Support\Response;

final class StandaloneLandingController
{
    public function index(): void
    {
        $appUrl = (string) Config::get('APP_URL', 'http://localhost:8000');
        $safeAppUrl = htmlspecialchars(rtrim($appUrl, '/'), ENT_QUOTES, 'UTF-8');

        $html = <<<HTML
<!doctype html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap">
  <title>RankX Standalone</title>
  <style>
    :root{
      --primary-1:#3B82F6;
      --primary-2:#6366F1;
      --primary-3:#8B5CF6;
      --gradient-main:linear-gradient(135deg, #3B82F6 0%, #6366F1 50%, #8B5CF6 100%);
      --bg:#F8FAFC;
      --surface:#FFFFFF;
      --ink:#0F172A;
      --muted:#64748B;
      --border:#E2E8F0;
      --glow-primary:0 0 20px rgba(99, 102, 241, 0.35);
      --shadow:0 22px 60px rgba(15,23,42,.08);
    }
    *{box-sizing:border-box}
    body{margin:0;font-family:"Tajawal","Segoe UI",sans-serif;color:var(--ink);background:var(--bg);min-height:100vh}
    .wrap{width:min(1000px,100% - 28px);margin:40px auto}
    .surface{background:var(--surface);border:1px solid var(--border);border-radius:18px;box-shadow:var(--shadow);padding:28px}
    h1{margin:0 0 10px;font-size:clamp(32px,5vw,52px);line-height:1.1}
    p{margin:0;color:var(--muted);line-height:1.9;font-size:18px}
    .actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:22px}
    .btn{display:inline-flex;align-items:center;justify-content:center;text-decoration:none;border-radius:12px;padding:12px 18px;font-weight:700}
    .btn-primary{background:var(--gradient-main);color:#fff;box-shadow:var(--glow-primary)}
    .btn-secondary{background:#EEF2FF;color:#1E293B}
    .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-top:22px}
    .card{border:1px solid var(--border);border-radius:14px;padding:14px;background:#fff}
    .card strong{display:block;margin-bottom:6px}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="surface">
      <h1>RankX Standalone</h1>
      <p>Standalone SEO platform with direct client signup, manual keyword-driven generation, and no external store integration.</p>
      <div class="actions">
        <a class="btn btn-primary" href="{$safeAppUrl}/register">Create Account</a>
        <a class="btn btn-secondary" href="{$safeAppUrl}/login">Login</a>
      </div>
      <div class="grid">
        <div class="card"><strong>SEO Settings</strong><span>Central rules and writing instructions.</span></div>
        <div class="card"><strong>Products/Brands/Categories</strong><span>Generate content from keyword input.</span></div>
        <div class="card"><strong>Keywords & Domain SEO</strong><span>Research and analyze from dashboard.</span></div>
        <div class="card"><strong>Operations & Quotas</strong><span>Track usage and keep plan limits.</span></div>
      </div>
    </div>
  </div>
</body>
</html>
HTML;

        Response::html($html);
    }
}

