<?php

declare(strict_types=1);

namespace App\Support;

use App\Config;

final class View
{
    public static function render(string $title, string $content): string
    {
        $safeAppUrl = htmlspecialchars(rtrim((string) Config::get('APP_URL', 'http://localhost:8000'), '/'), ENT_QUOTES, 'UTF-8');
        $logoSrc = 'https://rankxseo.com/logo.png';
        $faviconSrc = 'https://rankxseo.com/favicon.png';
        return <<<HTML
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/png" href="{$faviconSrc}">
  <link rel="apple-touch-icon" href="{$faviconSrc}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&display=swap">
  <title>{$title}</title>
  <style>
    :root {
      --primary-1: #3B82F6;
      --primary-2: #6366F1;
      --primary-3: #8B5CF6;
      --gradient-main: linear-gradient(135deg, #3B82F6 0%, #6366F1 50%, #8B5CF6 100%);
      --bg: #F8FAFC;
      --bg-card: #FFFFFF;
      --bg-soft: #EEF2FF;
      --surface: rgba(255, 255, 255, 0.9);
      --surface-strong: #FFFFFF;
      --surface-soft: rgba(238, 242, 255, 0.6);
      --text-primary: #0F172A;
      --text-secondary: #475569;
      --text-muted: #94A3B8;
      --ink: #0F172A;
      --muted: #64748B;
      --accent: #3B82F6;
      --accent-strong: #2563EB;
      --border-color: #E2E8F0;
      --border-soft: #EEF2FF;
      --danger: #EF4444;
      --danger-soft: #FEE2E2;
      --success: #10B981;
      --success-soft: #D1FAE5;
      --warning: #F59E0B;
      --warning-soft: #FEF3C7;
      --glow-primary: 0 0 20px rgba(99, 102, 241, 0.35);
      --glow-soft: 0 0 10px rgba(59, 130, 246, 0.2);
      --shadow: 0 24px 80px rgba(15, 23, 42, 0.08);
      --shadow-soft: 0 16px 40px rgba(15, 23, 42, 0.04);
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: "Tajawal", "Segoe UI", sans-serif;
      background: var(--bg);
      color: var(--ink);
      min-height: 100vh;
    }
    .wrap {
      width: min(100%, 1380px);
      margin: 0 auto;
      padding: 28px 20px 72px;
    }
    .card {
      background: var(--surface-strong);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      padding: 24px;
      box-shadow: var(--shadow);
    }
    .surface-soft { background: var(--surface-soft); }
    h1, h2, h3 { margin-top: 0; }
    h1 { font-size: clamp(30px, 4vw, 56px); line-height: 1.05; letter-spacing: -0.03em; }
    h2 { font-size: 24px; margin-bottom: 12px; }
    h3 { font-size: 18px; }
    p, li { line-height: 1.8; }
    .muted { color: var(--muted); }
    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 18px;
      margin-top: 20px;
    }
    .pill {
      display: inline-block;
      padding: 8px 14px;
      border-radius: 999px;
      background: var(--bg-soft);
      color: var(--primary-2);
      font-size: 13px;
      font-weight: 600;
      margin-left: 8px;
    }
    .btn,
    button.btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      text-decoration: none;
      background: var(--gradient-main);
      color: #fff;
      padding: 12px 18px;
      border-radius: 12px;
      border: none;
      cursor: pointer;
      font: inherit;
      transition: transform .16s ease, box-shadow .16s ease, opacity .16s ease;
      box-shadow: var(--glow-primary);
    }
    .btn:hover,
    button.btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 0 30px rgba(99, 102, 241, 0.45);
    }
    .btn:disabled,
    button.btn:disabled {
      opacity: .6;
      cursor: wait;
      transform: none;
    }
    .btn-secondary {
      background: var(--bg-soft);
      color: var(--text-primary);
      box-shadow: none;
    }
    .btn-secondary:hover { background: #E2E8F0; }
    .btn-sky {
      background: var(--primary-1);
      box-shadow: var(--glow-soft);
    }
    .btn-sky:hover { background: var(--accent-strong); }
    .btn-danger {
      background: var(--danger);
      box-shadow: 0 10px 24px rgba(239, 68, 68, 0.22);
    }
    .btn-danger:hover { background: #DC2626; }
    code, pre {
      font-family: Consolas, monospace;
      background: var(--bg-soft);
      border-radius: 8px;
    }
    code { padding: 2px 6px; }
    pre { padding: 14px; overflow: auto; }
    input, select, textarea {
      width: 100%;
      padding: 13px 14px;
      margin-top: 8px;
      border-radius: 12px;
      border: 1px solid var(--border-color);
      background: var(--surface-strong);
      color: var(--ink);
      font: inherit;
      outline: none;
      transition: border-color .16s ease, box-shadow .16s ease;
    }
    input:focus, select:focus, textarea:focus {
      border-color: var(--primary-1);
      box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
    }
    table {
      width: 100%;
      border-collapse: collapse;
      overflow: hidden;
      border-radius: 18px;
      background: var(--surface-strong);
    }
    th, td {
      border-bottom: 1px solid rgba(202, 177, 149, 0.32);
      padding: 12px 14px;
      vertical-align: top;
    }
    th {
      font-size: 14px;
      color: var(--text-secondary);
      background: var(--bg-soft);
    }
    tr:last-child td { border-bottom: none; }
    .hero {
      display: grid;
      grid-template-columns: 1.5fr 1fr;
      gap: 18px;
    }
    .stat {
      display: flex;
      flex-direction: column;
      gap: 8px;
      min-height: 140px;
    }
    .stat-label {
      color: var(--muted);
      font-size: 14px;
    }
    .stat-value {
      font-size: clamp(26px, 3vw, 42px);
      font-weight: 700;
      line-height: 1.05;
    }
    .panel-stack {
      display: grid;
      gap: 18px;
    }
    .dashboard-layout {
      display: grid;
      grid-template-columns: minmax(0, 1fr) 340px;
      gap: 20px;
      align-items: start;
    }
    .dashboard-shell {
      display: grid;
      grid-template-columns: 320px minmax(0, 1fr);
      gap: 20px;
      align-items: start;
    }
    .dashboard-sidebar {
      position: sticky;
      top: 20px;
      display: grid;
      gap: 16px;
      max-height: calc(100vh - 40px);
      overflow: auto;
    }
    .sidebar-nav {
      display: grid;
      gap: 8px;
    }
    .sidebar-link {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      width: 100%;
      border: 1px solid rgba(202, 177, 149, 0.55);
      border-radius: 14px;
      background: rgba(255,255,255,.85);
      color: var(--ink);
      text-align: right;
      padding: 12px 14px;
      cursor: pointer;
      transition: .16s ease;
      font: inherit;
    }
    .sidebar-link:hover {
      border-color: var(--primary-1);
      transform: translateY(-1px);
    }
    .sidebar-link.is-active {
      border-color: transparent;
      background: var(--gradient-main);
      color: #fff;
      box-shadow: var(--glow-primary);
    }
    .sidebar-link.has-note {
      display: grid;
      grid-template-columns: 1fr;
      justify-items: start;
      gap: 4px;
    }
    .sidebar-link.is-disabled {
      opacity: .65;
      cursor: not-allowed;
      transform: none !important;
      border-color: var(--border-color) !important;
      box-shadow: none !important;
    }
    .sidebar-link.is-disabled:hover {
      transform: none;
      border-color: var(--border-color);
    }
    .sidebar-lock-note {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 44px;
      padding: 2px 8px;
      border-radius: 999px;
      background: #FEE2E2;
      color: #B91C1C;
      font-size: 12px;
      font-weight: 700;
      line-height: 1.6;
      white-space: nowrap;
    }
    .sidebar-link.is-active .sidebar-lock-note {
      background: rgba(255, 255, 255, 0.2);
      color: #fff;
    }
    .section-head {
      display: flex;
      justify-content: space-between;
      gap: 16px;
      align-items: flex-start;
      flex-wrap: wrap;
      margin-bottom: 14px;
    }
    .danger-zone {
      border-color: rgba(239, 68, 68, 0.28);
      background: rgba(254, 226, 226, 0.5);
    }
    .toolbar {
      display: grid;
      gap: 14px;
    }
    .toolbar-row {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      align-items: center;
    }
    .toolbar-row > * {
      flex: 1 1 180px;
      min-width: 160px;
    }
    .toolbar-row .inline-actions {
      flex: 0 0 auto;
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }
    .chips {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }
    .chip {
      border: 1px solid var(--border-soft);
      background: var(--bg-soft);
      color: var(--text-secondary);
      padding: 10px 14px;
      border-radius: 999px;
      cursor: pointer;
      transition: .16s ease;
      font: inherit;
    }
    .chip.is-active {
      background: var(--gradient-main);
      color: #fff;
      border-color: transparent;
      box-shadow: var(--glow-soft);
    }
    .products-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
      gap: 20px;
    }
    .product-card {
      position: relative;
      display: flex;
      flex-direction: column;
      gap: 16px;
      min-height: 100%;
      background: var(--surface-strong);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      padding: 18px;
      box-shadow: var(--shadow-soft);
    }
    .product-badges {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }
    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 7px 12px;
      border-radius: 999px;
      font-size: 13px;
      font-weight: 700;
    }
    .status-badge.success {
      background: var(--success-soft);
      color: var(--success);
    }
    .status-badge.danger {
      background: var(--danger-soft);
      color: var(--danger);
    }
    .status-badge.warning {
      background: var(--warning-soft);
      color: var(--warning);
    }
    .product-thumb {
      width: 100%;
      aspect-ratio: 1 / 1;
      object-fit: cover;
      border-radius: 16px;
      background: var(--bg-soft);
      border: 1px solid var(--border-color);
    }
    .product-title {
      margin: 0;
      font-size: 22px;
      line-height: 1.35;
    }
    .meta-list {
      display: grid;
      gap: 8px;
      color: var(--muted);
      font-size: 14px;
      word-break: break-word;
      overflow-wrap: anywhere;
    }
    .meta-list code {
      display: inline-block;
      max-width: 100%;
      direction: ltr;
      unicode-bidi: plaintext;
      word-break: break-all;
      overflow-wrap: anywhere;
    }
    .product-preview {
      line-height: 1.9;
      min-height: 68px;
      overflow: hidden;
      overflow-wrap: anywhere;
      word-break: break-word;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      line-clamp: 3;
      -webkit-box-orient: vertical;
    }
    .product-actions {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 10px;
    }
    .product-actions .btn {
      width: 100%;
      padding: 12px 14px;
      font-size: 14px;
    }
    .pagination {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      align-items: center;
      justify-content: center;
    }
    .pagination button {
      min-width: 42px;
      padding: 10px 12px;
      border: 1px solid var(--border-color);
      border-radius: 10px;
      background: var(--surface-strong);
      cursor: pointer;
      font: inherit;
    }
    .pagination button.is-active {
      background: var(--gradient-main);
      color: #fff;
      border-color: transparent;
      box-shadow: var(--glow-soft);
    }
    .modal-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.4);
      display: none;
      align-items: center;
      justify-content: center;
      padding: 24px;
      z-index: 999;
      backdrop-filter: blur(6px);
    }
    .modal-backdrop.is-open { display: flex; }
    .modal {
      width: min(1120px, 100%);
      max-height: 92vh;
      overflow: auto;
      border-radius: 20px;
      padding: 24px;
      background: var(--surface-strong);
      border: 1px solid var(--border-color);
      box-shadow: 0 36px 90px rgba(15, 23, 42, 0.12);
    }
    .modal-head {
      display: flex;
      justify-content: space-between;
      gap: 16px;
      align-items: flex-start;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }
    .compare-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 16px;
    }
    .compare-card {
      background: var(--bg-soft);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      padding: 18px;
    }
    .compare-card textarea { min-height: 160px; }
    .compare-card.is-meta textarea { min-height: 104px; }
    .helper-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      color: var(--muted);
      font-size: 13px;
      margin-top: 8px;
    }
    .empty-state {
      text-align: center;
      padding: 36px 18px;
      border: 1px dashed var(--border-color);
      border-radius: 16px;
      background: var(--bg-soft);
    }
    .notice {
      margin-top: 12px;
      padding: 14px 16px;
      border-radius: 16px;
      font-size: 14px;
    }
    .notice.success {
      background: var(--success-soft);
      color: var(--success);
    }
    .notice.error {
      background: var(--danger-soft);
      color: var(--danger);
    }
    details > summary {
      list-style: none;
    }
    details > summary::-webkit-details-marker {
      display: none;
    }
    details[open] summary span {
      transform: rotate(180deg);
    }
    details > summary > div > span:last-child {
      transition: transform 0.2s ease;
    }
    @media (max-width: 980px) {
      .hero,
      .compare-grid,
      .dashboard-layout,
      .dashboard-shell {
        grid-template-columns: 1fr;
      }
      .wrap {
        padding: 18px 12px 52px;
      }
      .card {
        padding: 18px;
        border-radius: 22px;
      }
      h1 {
        font-size: clamp(28px, 8vw, 40px);
      }
      .dashboard-sidebar {
        position: static;
        max-height: none;
      }
      .toolbar-row > * {
        min-width: 100%;
      }
      .product-actions {
        grid-template-columns: 1fr;
      }
      .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
      }
      .modal {
        border-radius: 20px;
        padding: 16px;
      }
    }
    @media (max-width: 640px) {
      .products-grid {
        grid-template-columns: 1fr;
      }
      .pagination {
        justify-content: flex-start;
      }
      .section-head {
        gap: 10px;
      }
      .btn,
      button.btn {
        width: 100%;
      }
      .modal-backdrop {
        padding: 10px;
      }
      .compare-card {
        padding: 12px;
      }
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="section-head" style="margin-bottom:20px;">
      <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
        <img src="{$logoSrc}" alt="RankX SEO" width="1200" height="400" decoding="async" style="width:min(100%,280px);height:auto;display:block;filter:drop-shadow(0 0 20px rgba(99, 102, 241, 0.3))">
        <div>
          <div class="pill">RankX SEO</div>
          <div class="muted" style="margin-top:10px;">منصة تحسين محتوى منتجات سلة وربطها بلوحة خارجية احترافية.</div>
        </div>
      </div>
    </div>
    {$content}
    <div style="margin-top:26px;text-align:center;color:var(--muted);font-size:14px;padding-top:20px;border-top:1px solid var(--border-color);">
      <span style="margin:0 10px;"><a href="{$safeAppUrl}/" style="color:var(--muted);text-decoration:none;">الرئيسية</a></span>
      <span style="margin:0 10px;"><a href="{$safeAppUrl}/about" style="color:var(--muted);text-decoration:none;">من نحن</a></span>
      <span style="margin:0 10px;"><a href="{$safeAppUrl}/faq" style="color:var(--muted);text-decoration:none;">الأسئلة الشائعة</a></span>
      <span style="margin:0 10px;"><a href="{$safeAppUrl}/privacy" style="color:var(--muted);text-decoration:none;">الخصوصية</a></span>
      <span style="margin:0 10px;"><a href="{$safeAppUrl}/terms" style="color:var(--muted);text-decoration:none;">الشروط</a></span>
      <br><br>
      <span>Powered by RankX SEO</span> | <a href="mailto:seo@rankxseo.com" style="color:var(--primary-2);text-decoration:none;">seo@rankxseo.com</a>
    </div>
  </div>
</body>
</html>
HTML;
    }

    public static function renderFile(string $title, string $templatePath, array $data = []): string
    {
        extract($data, EXTR_SKIP);

        ob_start();
        require $templatePath;
        $content = (string) ob_get_clean();

        return self::render($title, $content);
    }
}
