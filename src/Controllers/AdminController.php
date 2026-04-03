<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config;
use App\Repositories\SaaSRepository;
use App\Repositories\StoreRepository;
use App\Support\Database;
use App\Support\Response;
use App\Support\View;
use App\Support\Plans;

final class AdminController
{
    public function loginForm(): void
    {
        Response::html(View::render('Admin Login', <<<HTML
<div class="card">
  <h1>Ù„ÙˆØ­Ø© Ø§Ù„Ø£Ø¯Ù…Ù†</h1>
  <p class="muted">ØªØ³Ø¬ÙŠÙ„ Ø¯Ø®ÙˆÙ„ Ù…Ø¯ÙŠØ± Ø§Ù„Ù†Ø¸Ø§Ù….</p>
  <form method="post" action="/admin/login">
    <label><strong>Ø§Ù„Ø¨Ø±ÙŠØ¯ Ø§Ù„Ø¥Ù„ÙƒØªØ±ÙˆÙ†ÙŠ</strong></label>
    <input name="email" type="email" style="width:100%;padding:12px;margin:8px 0 16px;border-radius:12px;border:1px solid #E2E8F0;" required>
    <label><strong>ÙƒÙ„Ù…Ø© Ø§Ù„Ù…Ø±ÙˆØ±</strong></label>
    <input name="password" type="password" style="width:100%;padding:12px;margin:8px 0 16px;border-radius:12px;border:1px solid #E2E8F0;" required>
    <button style="background:linear-gradient(135deg, #3B82F6, #6366F1);color:#fff;border:none;padding:12px 18px;border-radius:12px;cursor:pointer;box-shadow:0 0 20px rgba(99, 102, 241, 0.35);">Ø¯Ø®ÙˆÙ„ Ø§Ù„Ø£Ø¯Ù…Ù†</button>
  </form>
</div>
HTML));
    }

    public function loginSubmit(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $adminEmail = (string) Config::get('ADMIN_EMAIL', '');
        $adminPassword = (string) Config::get('ADMIN_PASSWORD', '');

        if ($email !== $adminEmail || $password !== $adminPassword) {
            Response::html(View::render('Admin Login', '<div class="card"><h1>ÙØ´Ù„ Ø¯Ø®ÙˆÙ„ Ø§Ù„Ø£Ø¯Ù…Ù†</h1><p class="muted">ØªØ­Ù‚Ù‚ Ù…Ù† ADMIN_EMAIL Ùˆ ADMIN_PASSWORD ÙÙŠ Ù…Ù„Ù Ø§Ù„Ø¨ÙŠØ¦Ø©.</p><p><a class="btn" href="/admin/login">Ø§Ù„Ù…Ø­Ø§ÙˆÙ„Ø© Ù…Ø¬Ø¯Ø¯Ù‹Ø§</a></p></div>'), 401);
            return;
        }

        $_SESSION['admin_logged_in'] = true;
        if (Database::isAvailable()) {
            (new SaaSRepository())->logAdminActivity($adminEmail, 'admin.login');
        }
        header('Location: /admin');
    }

    public function logout(): void
    {
        unset($_SESSION['admin_logged_in']);
        header('Location: /admin/login');
    }

    public function dashboard(): void
    {
        if (!$this->ensureAdmin()) {
            return;
        }

        if (!Database::isAvailable()) {
            Response::html(View::render('Admin Dashboard', '<div class="card"><h1>Ù‚Ø§Ø¹Ø¯Ø© Ø§Ù„Ø¨ÙŠØ§Ù†Ø§Øª ØºÙŠØ± Ù…ØªØ§Ø­Ø©</h1></div>'), 500);
            return;
        }

        $repository = new SaaSRepository();
        $stats = $repository->dashboardStats();
        $aiUsage = $repository->aiUsageSummary();
        $aiUsageByMode = $repository->aiUsageSummaryByMode();
        $aiUsageLogs = $repository->listAiUsageLogs(200);
        $dataForSeoUsage = $repository->dataForSeoUsageSummary();
        $dataForSeoUsageByMode = $repository->dataForSeoUsageSummaryByMode();
        $dataForSeoUsageLogs = $repository->listDataForSeoUsageLogs(200);
        $stores = array_slice($repository->listStores(), 0, 6);
        $rows = '';

        foreach ($stores as $store) {
            $storeName = htmlspecialchars((string) ($store['store_name'] ?? '-'), ENT_QUOTES, 'UTF-8');
            $merchantId = htmlspecialchars((string) ($store['merchant_id'] ?? '-'), ENT_QUOTES, 'UTF-8');
            $status = htmlspecialchars((string) ($store['subscription_status'] ?? '-'), ENT_QUOTES, 'UTF-8');
            $planId = (string) ($store['plan_name'] ?? Plans::BUDGET_TRIAL);
            $plan = Plans::get($planId);
            $planDisplay = $plan !== null ? $plan['icon'] . ' ' . $plan['name_ar'] : $planId;
            $used = (int) ($store['used_products'] ?? 0);
            $quota = (int) ($store['product_quota'] ?? 0);
            $rows .= "<tr><td>{$storeName}</td><td><code>{$merchantId}</code></td><td>{$planDisplay}</td><td>{$status}</td><td>{$used} / {$quota}</td><td><a href=\"/admin/stores/{$store['id']}\">ÙØªØ­</a></td></tr>";
        }

        Response::html(View::render('Admin Dashboard', <<<HTML
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
    <div>
      <h1>Ù„ÙˆØ­Ø© Ø§Ù„Ø£Ø¯Ù…Ù†</h1>
      <p class="muted">Ø¥Ø¯Ø§Ø±Ø© Ø§Ù„Ù…Ø´ØªØ±ÙƒÙŠÙ† ÙˆØ§Ù„Ù…ØªØ§Ø¬Ø± ÙˆØ§Ù„Ø§Ø³ØªÙ‡Ù„Ø§Ùƒ Ù…Ù† Ù…ÙƒØ§Ù† ÙˆØ§Ø­Ø¯.</p>
    </div>
    <a class="btn" href="/admin/logout">ØªØ³Ø¬ÙŠÙ„ Ø§Ù„Ø®Ø±ÙˆØ¬</a>
  </div>
  <div class="grid">
    <div class="card"><h2>Ø§Ù„Ù…ØªØ§Ø¬Ø±</h2><p>{$stats['stores_count']}</p></div>
    <div class="card"><h2>Ù†Ø´Ø·</h2><p>{$stats['active_subscriptions']}</p></div>
    <div class="card"><h2>ØªØ¬Ø±ÙŠØ¨ÙŠ</h2><p>{$stats['trial_subscriptions']}</p></div>
    <div class="card"><h2>Ø§Ù„Ø§Ø³ØªÙ‡Ù„Ø§Ùƒ</h2><p>{$stats['total_used']} / {$stats['total_quota']}</p></div>
    <div class="card"><h2>ØªÙƒÙ„ÙØ© OpenAI</h2><p>$ {$aiUsage['total_cost_usd']}</p></div>
    <div class="card"><h2>AI Runs</h2><p>{$aiUsage['runs_count']}</p></div>
    <div class="card"><h2>ØªÙƒÙ„ÙØ© DataForSEO</h2><p>$ {$dataForSeoUsage['total_cost_usd']}</p></div>
    <div class="card"><h2>DataForSEO Requests</h2><p>{$dataForSeoUsage['requests_count']}</p></div>
  </div>
  {$this->renderAiUsageByModeCard($aiUsageByMode, 'ØªÙƒÙ„ÙØ© OpenAI Ø­Ø³Ø¨ Ù†ÙˆØ¹ Ø§Ù„ØªÙˆÙ„ÙŠØ¯')}
  {$this->renderAiPricingTypeSummaryCard($aiUsageByMode, 'Ù…Ù„Ø®Øµ Ø§Ù„ØªØ³Ø¹ÙŠØ± Ù„ÙƒÙ„ Ù†ÙˆØ¹')}
  {$this->renderAiUsageLogsCard($aiUsageLogs, 'ØªÙØ§ØµÙŠÙ„ ØªÙƒÙ„ÙØ© ÙƒÙ„ Ø¹Ù…Ù„ÙŠØ© AI')}
  {$this->renderDataForSeoUsageByModeCard($dataForSeoUsageByMode, 'ØªÙƒÙ„ÙØ© DataForSEO Ø­Ø³Ø¨ Ù†ÙˆØ¹ Ø§Ù„Ø¹Ù…Ù„ÙŠØ©')}
  {$this->renderDataForSeoUsageLogsCard($dataForSeoUsageLogs, 'ØªÙØ§ØµÙŠÙ„ ØªÙƒÙ„ÙØ© ÙƒÙ„ Ø¹Ù…Ù„ÙŠØ© DataForSEO')}
  <div class="card" style="margin-top:16px;">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
      <h2>Ø¢Ø®Ø± Ø§Ù„Ù…ØªØ§Ø¬Ø±</h2>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <form method="post" action="/admin/email-test" style="display:inline;">
          <button class="btn" type="submit">Ø§Ø®ØªØ¨Ø§Ø± Ø§Ù„Ø¥ÙŠÙ…ÙŠÙ„</button>
        </form>
        <a class="btn" href="/admin/activity">Ø³Ø¬Ù„ Ø§Ù„Ø£Ø¯Ù…Ù†</a>
        <a class="btn" href="/admin/stores">Ø¹Ø±Ø¶ Ø¬Ù…ÙŠØ¹ Ø§Ù„Ù…ØªØ§Ø¬Ø±</a>
      </div>
    </div>
    <table style="width:100%;border-collapse:collapse;margin-top:12px;">
      <thead><tr><th style="text-align:right;padding:10px;">Ø§Ù„Ù…ØªØ¬Ø±</th><th style="text-align:right;padding:10px;">Merchant ID</th><th style="text-align:right;padding:10px;">Ø§Ù„Ø¨Ø§Ù‚Ø©</th><th style="text-align:right;padding:10px;">Ø§Ù„Ø­Ø§Ù„Ø©</th><th style="text-align:right;padding:10px;">Ø§Ù„Ø§Ø³ØªÙ‡Ù„Ø§Ùƒ</th><th style="text-align:right;padding:10px;">Ø§Ù„ØªÙØ§ØµÙŠÙ„</th></tr></thead>
      <tbody>{$rows}</tbody>
    </table>
  </div>
</div>
HTML));
    }

    public function stores(): void
    {
        if (!$this->ensureAdmin()) {
            return;
        }

        if (!Database::isAvailable()) {
            Response::html(View::render('Admin Stores', '<div class="card"><h1>Ù‚Ø§Ø¹Ø¯Ø© Ø§Ù„Ø¨ÙŠØ§Ù†Ø§Øª ØºÙŠØ± Ù…ØªØ§Ø­Ø©</h1></div>'), 500);
            return;
        }

        $repository = new SaaSRepository();
        $stores = $repository->listStores();
        $cards = '';

        foreach ($stores as $store) {
            $storeName = htmlspecialchars((string) ($store['store_name'] ?? '-'), ENT_QUOTES, 'UTF-8');
            $ownerEmail = htmlspecialchars((string) ($store['owner_email'] ?? '-'), ENT_QUOTES, 'UTF-8');
            $merchantId = htmlspecialchars((string) ($store['merchant_id'] ?? '-'), ENT_QUOTES, 'UTF-8');
            $status = htmlspecialchars((string) ($store['subscription_status'] ?? '-'), ENT_QUOTES, 'UTF-8');
            $used = (int) ($store['used_products'] ?? 0);
            $quota = (int) ($store['product_quota'] ?? 0);

            $cards .= <<<HTML
<div class="card">
  <h2>{$storeName}</h2>
  <p class="muted">{$ownerEmail}</p>
  <p>Merchant ID: <code>{$merchantId}</code></p>
  <p>Ø§Ù„Ø§Ø´ØªØ±Ø§Ùƒ: <strong>{$status}</strong></p>
  <p>Ø§Ù„Ø§Ø³ØªÙ‡Ù„Ø§Ùƒ: <strong>{$used} / {$quota}</strong></p>
  <p><a class="btn" href="/admin/stores/{$store['id']}">Ø¥Ø¯Ø§Ø±Ø© Ø§Ù„Ù…ØªØ¬Ø±</a></p>
</div>
HTML;
        }

        Response::html(View::render('Admin Stores', <<<HTML
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
    <div>
      <h1>Ø§Ù„Ù…Ø´ØªØ±ÙƒÙŠÙ† ÙˆØ§Ù„Ù…ØªØ§Ø¬Ø±</h1>
      <p class="muted">Ø¹Ø±Ø¶ Ø³Ø±ÙŠØ¹ Ù„ÙƒÙ„ Ø§Ù„Ù…Ø´ØªØ±ÙƒÙŠÙ† ÙˆØ§Ù„Ø§Ø´ØªØ±Ø§ÙƒØ§Øª.</p>
    </div>
    <a class="btn" href="/admin">Ø§Ù„Ø¹ÙˆØ¯Ø© Ù„Ù„ÙˆØ­Ø© Ø§Ù„Ø£Ø¯Ù…Ù†</a>
  </div>
  <div class="grid">{$cards}</div>
</div>
HTML));
    }

    public function store(array $params): void
    {
        if (!$this->ensureAdmin()) {
            return;
        }

        $storeId = (int) ($params['id'] ?? 0);
        $repository = new SaaSRepository();
        $store = $repository->findStoreById($storeId);

        if (!$store) {
            Response::html(View::render('Store Not Found', '<div class="card"><h1>Ø§Ù„Ù…ØªØ¬Ø± ØºÙŠØ± Ù…ÙˆØ¬ÙˆØ¯</h1></div>'), 404);
            return;
        }

        $storeName = htmlspecialchars((string) ($store['store_name'] ?? '-'), ENT_QUOTES, 'UTF-8');
        $ownerEmail = htmlspecialchars((string) ($store['owner_email'] ?? '-'), ENT_QUOTES, 'UTF-8');
        $merchantId = htmlspecialchars((string) ($store['merchant_id'] ?? '-'), ENT_QUOTES, 'UTF-8');
        $status = htmlspecialchars((string) ($store['subscription_status'] ?? 'trial'), ENT_QUOTES, 'UTF-8');
        $planId = (string) ($store['plan_name'] ?? Plans::BUDGET_TRIAL);
        $currentPlan = Plans::get($planId) ?? Plans::get(Plans::BUDGET_TRIAL);
        $periodStart = htmlspecialchars((string) ($store['period_started_at'] ?? date('Y-m-d H:i:s')), ENT_QUOTES, 'UTF-8');
        $periodEnd = htmlspecialchars((string) ($store['period_ends_at'] ?? date('Y-m-d H:i:s', strtotime('+30 days'))), ENT_QUOTES, 'UTF-8');
        $used = (int) ($store['used_products'] ?? 0);
        $quota = (int) ($store['product_quota'] ?? 0);
        $jsonStore = (new StoreRepository())->find((string) ($store['merchant_id'] ?? '')) ?? [];

        $planOptions = '';
        foreach (Plans::all() as $plan) {
            $selected = $plan['id'] === $planId ? 'selected' : '';
            $planOptions .= '<option value="' . $plan['id'] . '" ' . $selected . '>' . $plan['icon'] . ' ' . $plan['name_ar'] . ' - ' . $plan['price_sar'] . ' Ø±.Ø³</option>';
        }

        $statusOptions = '';
        $statuses = ['trial' => 'ØªØ¬Ø±Ø¨Ø©', 'active' => 'Ù†Ø´Ø·', 'inactive' => 'Ù…ØªÙˆÙ‚Ù', 'expired' => 'Ù…Ù†ØªÙ‡ÙŠ'];
        foreach ($statuses as $key => $label) {
            $selected = $key === $status ? 'selected' : '';
            $statusOptions .= '<option value="' . $key . '" ' . $selected . '>' . $label . '</option>';
        }

        $planQuotasJson = json_encode(array_map(fn($p) => $p['quotas'], Plans::all()));

        $quotaKeys = ['product_description', 'product_seo', 'image_alt', 'keyword_research', 'domain_seo', 'brand_seo', 'category_seo'];
        $currentUsed = [];
        $currentQuota = [];
        foreach ($quotaKeys as $key) {
            $usedKey = 'used_' . $key;
            $quotaKey = 'quota_' . $key;
            $defaultQuota = $currentPlan['quotas'][$key] ?? 0;
            $currentUsed[$key] = (int) ($store[$usedKey] ?? 0);
            $currentQuota[$key] = (int) ($store[$quotaKey] ?? $defaultQuota);
        }
        $currentUsedJson = json_encode($currentUsed);
        $currentQuotaJson = json_encode($currentQuota);
        $planName = $currentPlan !== null
            ? ($currentPlan['icon'] . ' ' . $currentPlan['name_ar'])
            : htmlspecialchars($planId, ENT_QUOTES, 'UTF-8');
        $aiUsage = $repository->storeAiUsageSummary($storeId);
        $aiUsageByMode = $repository->storeAiUsageSummaryByMode($storeId);
        $aiUsageLogs = $repository->listStoreAiUsageLogs($storeId, 200);
        $dataForSeoUsage = $repository->storeDataForSeoUsageSummary($storeId);
        $dataForSeoUsageByMode = $repository->storeDataForSeoUsageSummaryByMode($storeId);
        $dataForSeoUsageLogs = $repository->listStoreDataForSeoUsageLogs($storeId, 200);

        Response::html(View::render('Admin Store', <<<HTML
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
    <div>
      <h1>Ø¥Ø¯Ø§Ø±Ø© Ù…ØªØ¬Ø±: {$storeName}</h1>
      <p class="muted">{$ownerEmail} | <code>{$merchantId}</code></p>
    </div>
    <a class="btn" href="/admin/stores">Ø§Ù„Ø¹ÙˆØ¯Ø© Ù„Ù„Ù…ØªØ§Ø¬Ø±</a>
  </div>
  <div class="grid">
    <div class="card"><h2>Ø§Ù„Ø­Ø§Ù„Ø©</h2><p>{$status}</p></div>
    <div class="card"><h2>Ø§Ù„Ø¨Ø§Ù‚Ø©</h2><p>{$planName}</p></div>
    <div class="card"><h2>Ø§Ù„Ø§Ø³ØªÙ‡Ù„Ø§Ùƒ</h2><p>{$used} / {$quota}</p></div>
    <div class="card"><h2>ØªÙƒÙ„ÙØ© OpenAI</h2><p>$ {$aiUsage['total_cost_usd']}</p></div>
    <div class="card"><h2>ØªÙƒÙ„ÙØ© DataForSEO</h2><p>$ {$dataForSeoUsage['total_cost_usd']}</p></div>
  </div>
  {$this->renderAiUsageByModeCard($aiUsageByMode, 'ØªÙƒÙ„ÙØ© OpenAI Ù„Ù‡Ø°Ø§ Ø§Ù„Ù…ØªØ¬Ø± Ø­Ø³Ø¨ Ø§Ù„Ù†ÙˆØ¹')}
  {$this->renderAiPricingTypeSummaryCard($aiUsageByMode, 'Ù…Ù„Ø®Øµ Ø§Ù„ØªØ³Ø¹ÙŠØ± Ù„Ù‡Ø°Ø§ Ø§Ù„Ù…ØªØ¬Ø± Ø­Ø³Ø¨ Ø§Ù„Ù†ÙˆØ¹')}
  {$this->renderAiUsageLogsCard($aiUsageLogs, 'ØªÙØ§ØµÙŠÙ„ ØªÙƒÙ„ÙØ© ÙƒÙ„ Ø¹Ù…Ù„ÙŠØ© Ù„Ù‡Ø°Ø§ Ø§Ù„Ù…ØªØ¬Ø±')}
  {$this->renderDataForSeoUsageByModeCard($dataForSeoUsageByMode, 'ØªÙƒÙ„ÙØ© DataForSEO Ù„Ù‡Ø°Ø§ Ø§Ù„Ù…ØªØ¬Ø± Ø­Ø³Ø¨ Ø§Ù„Ù†ÙˆØ¹')}
  {$this->renderDataForSeoUsageLogsCard($dataForSeoUsageLogs, 'ØªÙØ§ØµÙŠÙ„ ØªÙƒÙ„ÙØ© DataForSEO Ù„Ù‡Ø°Ø§ Ø§Ù„Ù…ØªØ¬Ø±')}
    <div class="card" style="margin-top:16px;">
    <h2>ØªØ¹Ø¯ÙŠÙ„ Ø§Ù„Ø§Ø´ØªØ±Ø§Ùƒ</h2>
    <form method="post" id="subscription-form">
      <div class="grid">
        <div>
          <label>Ø§Ù„Ø­Ø§Ù„Ø©</label>
          <select name="status" style="width:100%;padding:12px;margin-top:8px;border-radius:12px;border:1px solid #E2E8F0;">
            {$statusOptions}
          </select>
        </div>
        <div>
          <label>Ø§Ù„Ø¨Ø§Ù‚Ø©</label>
          <select name="plan_id" id="plan-select" onchange="updatePlanQuotas()" style="width:100%;padding:12px;margin-top:8px;border-radius:12px;border:1px solid #E2E8F0;">
            {$planOptions}
          </select>
        </div>
        <div>
          <label>Ø¥Ø¬Ù…Ø§Ù„ÙŠ Ø§Ù„Ø­ØµØ©</label>
          <input name="product_quota" type="number" value="{$quota}" id="product-quota" style="width:100%;padding:12px;margin-top:8px;border-radius:12px;border:1px solid #E2E8F0;">
        </div>
        <div>
          <label>Ø§Ù„Ù…Ø³ØªØ®Ø¯Ù…</label>
          <input name="used_products" type="number" value="{$used}" style="width:100%;padding:12px;margin-top:8px;border-radius:12px;border:1px solid #E2E8F0;">
        </div>
        <div>
          <label>Ø¨Ø¯Ø§ÙŠØ© Ø§Ù„ÙØªØ±Ø©</label>
          <input name="period_started_at" value="{$periodStart}" type="datetime-local" style="width:100%;padding:12px;margin-top:8px;border-radius:12px;border:1px solid #E2E8F0;">
        </div>
        <div>
          <label>Ù†Ù‡Ø§ÙŠØ© Ø§Ù„ÙØªØ±Ø©</label>
          <input name="period_ends_at" value="{$periodEnd}" type="datetime-local" style="width:100%;padding:12px;margin-top:8px;border-radius:12px;border:1px solid #E2E8F0;">
        </div>
      </div>
      <div id="plan-details" class="card" style="margin-top:16px;background:#EEF2FF;border:none;">
        <h3 style="margin:0 0 12px;">ØªÙØ§ØµÙŠÙ„ Ø§Ù„Ø¨Ø§Ù‚Ø©: {$currentPlan['icon']} {$currentPlan['name_ar']}</h3>
        <p style="margin:0;color:#64748B;font-size:14px;">{$currentPlan['description_ar']}</p>
      </div>
      <button type="submit" formaction="/admin/stores/{$store['id']}/subscription" style="background:linear-gradient(135deg, #3B82F6, #6366F1);color:#fff;border:none;padding:12px 18px;border-radius:12px;cursor:pointer;margin-top:12px;box-shadow:0 0 20px rgba(99, 102, 241, 0.35);">Ø­ÙØ¸ Ø§Ù„ØªØ¹Ø¯ÙŠÙ„Ø§Øª</button>
    </form>
  </div>

  <div class="card" style="margin-top:16px;background:#FEF3C7;border:1px solid #F59E0B;">
    <h2 style="margin-top:0;">ØªØ¹Ø¯ÙŠÙ„ Ø§Ù„Ø­ØµØµ Ø§Ù„ÙØ±Ø¯ÙŠØ©</h2>
    <p class="muted">Ø£Ø¶Ù Ø£Ùˆ Ø§Ù†Ù‚Øµ Ù…Ù† Ø­ØµØ© ÙƒÙ„ Ø¹Ù…Ù„ÙŠØ© Ø¹Ù„Ù‰ Ø­Ø¯Ø©. Ø§Ù„Ù‚ÙŠÙ… Ø§Ù„Ø³Ø§Ù„Ø¨Ø© ØªÙ†Ù‚Øµ Ù…Ù† Ø§Ù„Ù…Ø³ØªØ®Ø¯Ù….</p>
    <form method="post" action="/admin/stores/{$store['id']}/adjust-quotas">
      <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));">
        <div>
          <label>ØªØ­Ø³ÙŠÙ† ÙˆØµÙ Ù…Ù†ØªØ¬</label>
          <input name="quota_product_description" type="number" value="0" placeholder="0" style="width:100%;padding:12px;margin-top:8px;border-radius:12px;border:1px solid #E2E8F0;">
          <small class="muted">Ø§Ù„Ø­Ø§Ù„ÙŠ: {$currentUsed['product_description']} / {$currentQuota['product_description']}</small>
        </div>
        <div>
          <label>ØªØ­Ø³ÙŠÙ† SEO Ù…Ù†ØªØ¬</label>
          <input name="quota_product_seo" type="number" value="0" placeholder="0" style="width:100%;padding:12px;margin-top:8px;border-radius:12px;border:1px solid #E2E8F0;">
          <small class="muted">Ø§Ù„Ø­Ø§Ù„ÙŠ: {$currentUsed['product_seo']} / {$currentQuota['product_seo']}</small>
        </div>
        <div>
          <label>ØªØ­Ø³ÙŠÙ† ALT ØµÙˆØ±</label>
          <input name="quota_image_alt" type="number" value="0" placeholder="0" style="width:100%;padding:12px;margin-top:8px;border-radius:12px;border:1px solid #E2E8F0;">
          <small class="muted">Ø§Ù„Ø­Ø§Ù„ÙŠ: {$currentUsed['image_alt']} / {$currentQuota['image_alt']}</small>
        </div>
        <div>
          <label>ÙƒÙ„Ù…Ø§Øª Ù…ÙØªØ§Ø­ÙŠØ©</label>
          <input name="quota_keyword_research" type="number" value="0" placeholder="0" style="width:100%;padding:12px;margin-top:8px;border-radius:12px;border:1px solid #E2E8F0;">
          <small class="muted">Ø§Ù„Ø­Ø§Ù„ÙŠ: {$currentUsed['keyword_research']} / {$currentQuota['keyword_research']}</small>
        </div>
        <div>
          <label>ØªØ­Ù„ÙŠÙ„ Ø³ÙŠÙˆ Ø¯ÙˆÙ…ÙŠÙ†</label>
          <input name="quota_domain_seo" type="number" value="0" placeholder="0" style="width:100%;padding:12px;margin-top:8px;border-radius:12px;border:1px solid #E2E8F0;">
          <small class="muted">Ø§Ù„Ø­Ø§Ù„ÙŠ: {$currentUsed['domain_seo']} / {$currentQuota['domain_seo']}</small>
        </div>
        <div>
          <label>ØªØ­Ø³ÙŠÙ† SEO Ù…Ø§Ø±ÙƒØ©</label>
          <input name="quota_brand_seo" type="number" value="0" placeholder="0" style="width:100%;padding:12px;margin-top:8px;border-radius:12px;border:1px solid #E2E8F0;">
          <small class="muted">Ø§Ù„Ø­Ø§Ù„ÙŠ: {$currentUsed['brand_seo']} / {$currentQuota['brand_seo']}</small>
        </div>
      </div>
      <p class="muted" style="margin-top:12px;font-size:13px;">ðŸ’¡ Ø£Ø¯Ø®Ù„ Ù‚ÙŠÙ…Ø© Ù…ÙˆØ¬Ø¨Ø© (+) Ù„Ø¥Ø¶Ø§ÙØ© Ø£Ùˆ Ø³Ø§Ù„Ø¨Ø© (-) Ù„Ø¥Ù†Ù‚Ø§Øµ Ù…Ù† Ø§Ù„Ø­ØµØ© Ø§Ù„Ø­Ø§Ù„ÙŠØ©.</p>
      <button type="submit" style="background:linear-gradient(135deg, #F59E0B, #D97706);color:#fff;border:none;padding:12px 18px;border-radius:12px;cursor:pointer;margin-top:8px;">ØªØ·Ø¨ÙŠÙ‚ Ø§Ù„ØªØ¹Ø¯ÙŠÙ„Ø§Øª</button>
    </form>
  </div>

  <script>
    const planQuotas = {$planQuotasJson};
    
    function updatePlanQuotas() {
      const planId = document.getElementById('plan-select').value;
      const quota = planQuotas[planId];
      if (quota) {
        const total = Object.values(quota).reduce((a, b) => a + b, 0);
        document.getElementById('product-quota').value = total;
      }
    }
  </script>
  <div class="card danger-zone" style="margin-top:16px;">
    <h2>Ù…Ù†Ø·Ù‚Ø© Ø®Ø·Ø±Ø©</h2>
    <p class="muted">Ø­Ø°Ù Ø§Ù„Ù…ØªØ¬Ø± Ø³ÙŠØ²ÙŠÙ„Ù‡ Ù…Ù† Ù‚Ø§Ø¹Ø¯Ø© Ø§Ù„Ø¨ÙŠØ§Ù†Ø§Øª ÙˆÙ„ÙˆØ­Ø© Ø§Ù„Ø£Ø¯Ù…Ù†. Ù„Ø§ ÙŠØªÙ… Ø¥Ù„ØºØ§Ø¡ ØªØ«Ø¨ÙŠØªÙ‡ ØªÙ„Ù‚Ø§Ø¦ÙŠÙ‹Ø§ Ù…Ù† Ø³Ù„Ø©.</p>
    <form method="post" action="/admin/stores/{$store['id']}/delete" onsubmit="return confirm('Ù‡Ù„ Ø£Ù†Øª Ù…ØªØ£ÙƒØ¯ Ù…Ù† Ø­Ø°Ù Ù‡Ø°Ø§ Ø§Ù„Ù…ØªØ¬Ø±ØŸ');">
      <button style="background:#EF4444;color:#fff;border:none;padding:12px 18px;border-radius:12px;cursor:pointer;box-shadow:0 10px 24px rgba(239, 68, 68, 0.22);">Ø­Ø°Ù Ø§Ù„Ù…ØªØ¬Ø±</button>
    </form>
  </div>
  <div class="card" style="margin-top:16px;">
    <h2>Ø³Ø¬Ù„ Ø§Ù„Ø§Ø³ØªØ®Ø¯Ø§Ù…</h2>
    <table style="width:100%;border-collapse:collapse;">
      <thead><tr><th style="text-align:right;padding:10px;">Ø§Ù„Ù…Ù†ØªØ¬</th><th style="text-align:right;padding:10px;">ÙˆÙ‚Øª Ø§Ù„Ø§Ø³ØªØ®Ø¯Ø§Ù…</th></tr></thead>
      <tbody>{$logHtml}</tbody>
    </table>
  </div>
</div>
HTML));
    }

    public function updateSubscription(array $params): void
    {
        if (!$this->ensureAdmin()) {
            return;
        }

        $storeId = (int) ($params['id'] ?? 0);
        $repository = new SaaSRepository();
        $store = $repository->findStoreById($storeId);

        if (!$store) {
            Response::html(View::render('Store Not Found', '<div class="card"><h1>Ø§Ù„Ù…ØªØ¬Ø± ØºÙŠØ± Ù…ÙˆØ¬ÙˆØ¯</h1></div>'), 404);
            return;
        }

        $planId = trim((string) ($_POST['plan_id'] ?? Plans::BUDGET_TRIAL));
        $plan = Plans::get($planId);
        if ($plan === null) {
            $plan = Plans::get(Plans::BUDGET_TRIAL);
            $planId = Plans::BUDGET_TRIAL;
        }

        $periodStartedAt = trim((string) ($_POST['period_started_at'] ?? ''));
        $periodEndsAt = trim((string) ($_POST['period_ends_at'] ?? ''));
        if ($periodStartedAt !== '') {
            $periodStartedAt = date('Y-m-d H:i:s', strtotime($periodStartedAt));
        }
        if ($periodEndsAt !== '') {
            $periodEndsAt = date('Y-m-d H:i:s', strtotime($periodEndsAt));
        }

        $payload = [
            'status' => trim((string) ($_POST['status'] ?? 'trial')),
            'plan_name' => $planId,
            'product_quota' => (int) ($_POST['product_quota'] ?? array_sum($plan['quotas'])),
            'used_products' => (int) ($_POST['used_products'] ?? 0),
            'period_started_at' => $periodStartedAt,
            'period_ends_at' => $periodEndsAt,
        ];

        foreach ($plan['quotas'] as $key => $value) {
            $payload['quota_' . $key] = $value;
        }

        $repository->updateStoreSubscription($storeId, $payload);
        $repository->logAdminActivity(
            (string) Config::get('ADMIN_EMAIL', 'admin'),
            'subscription.updated',
            'store',
            (string) $storeId,
            $payload
        );

        $jsonStore = (new StoreRepository())->find((string) $store['merchant_id']) ?? [];
        (new StoreRepository())->save((string) $store['merchant_id'], [
            'subscription' => array_merge($jsonStore['subscription'] ?? [], $payload),
        ]);

        header('Location: /admin/stores/' . $storeId);
    }

    public function adjustQuotas(array $params): void
    {
        if (!$this->ensureAdmin()) {
            return;
        }

        $storeId = (int) ($params['id'] ?? 0);
        $repository = new SaaSRepository();
        $store = $repository->findStoreById($storeId);

        if (!$store) {
            header('Location: /admin/stores');
            return;
        }

        $quotaKeys = ['product_description', 'product_seo', 'image_alt', 'keyword_research', 'domain_seo', 'brand_seo', 'category_seo'];
        $updates = [];

        foreach ($quotaKeys as $key) {
            $usedKey = 'used_' . $key;
            $quotaKey = 'quota_' . $key;
            $adjustValue = (int) ($_POST['quota_' . $key] ?? 0);
            
            if ($adjustValue !== 0) {
                $currentUsed = (int) ($store[$usedKey] ?? 0);
                $currentQuota = (int) ($store[$quotaKey] ?? 0);
                
                $updates[$usedKey] = max(0, $currentUsed + $adjustValue);
                $updates[$quotaKey] = max(0, $currentQuota + $adjustValue);
            }
        }

        if (!empty($updates)) {
            $repository->updateStoreSubscription($storeId, $updates);
            $repository->logAdminActivity(
                (string) Config::get('ADMIN_EMAIL', 'admin'),
                'quotas.adjusted',
                'store',
                (string) $storeId,
                $updates
            );

            $jsonStore = (new StoreRepository())->find((string) $store['merchant_id']) ?? [];
            $currentSub = $jsonStore['subscription'] ?? [];
            foreach ($updates as $key => $value) {
                $currentSub[$key] = $value;
            }
            (new StoreRepository())->save((string) $store['merchant_id'], [
                'subscription' => $currentSub,
            ]);
        }

        header('Location: /admin/stores/' . $storeId);
    }

    public function deleteStore(array $params): void
    {
        if (!$this->ensureAdmin()) {
            return;
        }

        $storeId = (int) ($params['id'] ?? 0);
        $repository = new SaaSRepository();
        $store = $repository->findStoreById($storeId);

        if ($store) {
            $merchantId = (string) ($store['merchant_id'] ?? '');
            $repository->deleteStore($storeId);
            $repository->logAdminActivity(
                (string) Config::get('ADMIN_EMAIL', 'admin'),
                'store.deleted',
                'store',
                (string) $storeId,
                ['merchant_id' => $merchantId, 'store_name' => $store['store_name'] ?? null]
            );

            if ($merchantId !== '') {
                (new StoreRepository())->delete($merchantId);
            }
        }

        header('Location: /admin/stores');
    }

    public function activity(): void
    {
        if (!$this->ensureAdmin()) {
            return;
        }

        $logs = Database::isAvailable() ? (new SaaSRepository())->listAdminActivityLogs(200) : [];
        $rows = '';

        foreach ($logs as $log) {
            $details = htmlspecialchars((string) ($log['details_json'] ?? ''), ENT_QUOTES, 'UTF-8');
            $rows .= '<tr>'
                . '<td style="padding:10px;">' . htmlspecialchars((string) $log['created_at'], ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="padding:10px;">' . htmlspecialchars((string) $log['admin_email'], ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="padding:10px;">' . htmlspecialchars((string) $log['action'], ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="padding:10px;">' . htmlspecialchars((string) ($log['target_type'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="padding:10px;">' . htmlspecialchars((string) ($log['target_id'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="padding:10px;max-width:340px;word-break:break-word;">' . $details . '</td>'
                . '</tr>';
        }

        if ($rows === '') {
            $rows = '<tr><td colspan="6" style="padding:10px;">Ù„Ø§ ØªÙˆØ¬Ø¯ Ù†Ø´Ø§Ø·Ø§Øª Ù…Ø³Ø¬Ù„Ø© Ø¨Ø¹Ø¯.</td></tr>';
        }

        Response::html(View::render('Admin Activity', <<<HTML
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
    <div>
      <h1>Ø³Ø¬Ù„ Ù†Ø´Ø§Ø· Ø§Ù„Ø£Ø¯Ù…Ù†</h1>
      <p class="muted">ÙƒÙ„ Ø§Ù„ØªØ¹Ø¯ÙŠÙ„Ø§Øª Ø§Ù„Ø¥Ø¯Ø§Ø±ÙŠØ© Ø§Ù„Ù…Ù‡Ù…Ø© ØªØ¸Ù‡Ø± Ù‡Ù†Ø§.</p>
    </div>
    <a class="btn" href="/admin">Ø§Ù„Ø¹ÙˆØ¯Ø© Ù„Ù„ÙˆØ­Ø© Ø§Ù„Ø£Ø¯Ù…Ù†</a>
  </div>
  <table style="width:100%;border-collapse:collapse;margin-top:12px;">
    <thead><tr><th style="text-align:right;padding:10px;">Ø§Ù„ÙˆÙ‚Øª</th><th style="text-align:right;padding:10px;">Ø§Ù„Ø£Ø¯Ù…Ù†</th><th style="text-align:right;padding:10px;">Ø§Ù„Ø¥Ø¬Ø±Ø§Ø¡</th><th style="text-align:right;padding:10px;">Ø§Ù„Ù†ÙˆØ¹</th><th style="text-align:right;padding:10px;">Ø§Ù„Ù…Ø¹Ø±Ù</th><th style="text-align:right;padding:10px;">Ø§Ù„ØªÙØ§ØµÙŠÙ„</th></tr></thead>
    <tbody>{$rows}</tbody>
  </table>
</div>
HTML));
    }

    public function sendTestEmail(): void
    {
        if (!$this->ensureAdmin()) {
            return;
        }

        $adminEmail = (string) Config::get('ADMIN_EMAIL', '');
        $targetEmail = (string) Config::get('MAIL_FROM_ADDRESS', $adminEmail);

        try {
            (new \App\Services\Mailer())->sendTestEmail($targetEmail);

            if (Database::isAvailable()) {
                (new SaaSRepository())->logAdminActivity(
                    $adminEmail,
                    'email.test.sent',
                    'mail',
                    $targetEmail
                );
            }

            Response::html(View::render('Email Test', '<div class="card"><h1>ØªÙ… Ø¥Ø±Ø³Ø§Ù„ Ø¨Ø±ÙŠØ¯ Ø§Ù„Ø§Ø®ØªØ¨Ø§Ø±</h1><p class="muted">ØªØ­Ù‚Ù‚ Ù…Ù† ØµÙ†Ø¯ÙˆÙ‚ Ø§Ù„Ø¨Ø±ÙŠØ¯: <strong>' . htmlspecialchars($targetEmail, ENT_QUOTES, 'UTF-8') . '</strong></p><p><a class="btn" href="/admin">Ø§Ù„Ø¹ÙˆØ¯Ø© Ù„Ù„ÙˆØ­Ø© Ø§Ù„Ø£Ø¯Ù…Ù†</a></p></div>'));
        } catch (\Throwable $exception) {
            Response::html(View::render('Email Test Failed', '<div class="card"><h1>ÙØ´Ù„ Ø¥Ø±Ø³Ø§Ù„ Ø¨Ø±ÙŠØ¯ Ø§Ù„Ø§Ø®ØªØ¨Ø§Ø±</h1><p class="muted">' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') . '</p><p><a class="btn" href="/admin">Ø§Ù„Ø¹ÙˆØ¯Ø©</a></p></div>'), 500);
        }
    }

    private function renderAiUsageByModeCard(array $rows, string $title): string
    {
        if ($rows === []) {
            return '<div class="card" style="margin-top:16px;"><h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2><p class="muted">Ù„Ø§ ØªÙˆØ¬Ø¯ Ø¨ÙŠØ§Ù†Ø§Øª ØªÙƒÙ„ÙØ© Ø¨Ø¹Ø¯.</p></div>';
        }

        $tableRows = '';
        $totalRuns = 0;
        $totalCost = 0.0;

        foreach ($rows as $row) {
            $label = htmlspecialchars((string) ($row['label'] ?? $row['mode'] ?? '-'), ENT_QUOTES, 'UTF-8');
            $runs = (int) ($row['runs_count'] ?? 0);
            $cost = (float) ($row['total_cost_usd'] ?? 0);
            $inputTokens = number_format((int) ($row['input_tokens'] ?? 0));
            $outputTokens = number_format((int) ($row['output_tokens'] ?? 0));
            $totalRuns += $runs;
            $totalCost += $cost;

            $tableRows .= '<tr>'
                . '<td style="padding:10px;">' . $label . '</td>'
                . '<td style="padding:10px;">' . number_format($runs) . '</td>'
                . '<td style="padding:10px;">$ ' . $this->formatUsd($cost) . '</td>'
                . '<td style="padding:10px;">' . $inputTokens . '</td>'
                . '<td style="padding:10px;">' . $outputTokens . '</td>'
                . '</tr>';
        }

        $footer = '<tr style="font-weight:700;background:#EEF2FF;">'
            . '<td style="padding:10px;">Ø§Ù„Ø¥Ø¬Ù…Ø§Ù„ÙŠ</td>'
            . '<td style="padding:10px;">' . number_format($totalRuns) . '</td>'
            . '<td style="padding:10px;">$ ' . $this->formatUsd($totalCost) . '</td>'
            . '<td style="padding:10px;">-</td>'
            . '<td style="padding:10px;">-</td>'
            . '</tr>';

        return '<div class="card" style="margin-top:16px;">'
            . '<h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>'
            . '<table style="width:100%;border-collapse:collapse;margin-top:12px;">'
            . '<thead><tr>'
            . '<th style="text-align:right;padding:10px;">Ø§Ù„Ù†ÙˆØ¹</th>'
            . '<th style="text-align:right;padding:10px;">Ø¹Ø¯Ø¯ Ø§Ù„Ø¹Ù…Ù„ÙŠØ§Øª</th>'
            . '<th style="text-align:right;padding:10px;">Ø§Ù„ØªÙƒÙ„ÙØ© (USD)</th>'
            . '<th style="text-align:right;padding:10px;">Input Tokens</th>'
            . '<th style="text-align:right;padding:10px;">Output Tokens</th>'
            . '</tr></thead>'
            . '<tbody>' . $tableRows . $footer . '</tbody>'
            . '</table>'
            . '</div>';
    }

    private function renderAiUsageLogsCard(array $rows, string $title): string
    {
        if ($rows === []) {
            return '<div class="card" style="margin-top:16px;"><h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2><p class="muted">Ù„Ø§ ØªÙˆØ¬Ø¯ Ø¹Ù…Ù„ÙŠØ§Øª AI Ù…Ø³Ø¬Ù„Ø© Ø¨Ø¹Ø¯.</p></div>';
        }

        $modeLabels = [
            'description' => 'ÙˆØµÙ Ø§Ù„Ù…Ù†ØªØ¬',
            'seo' => 'Ø³ÙŠÙˆ Ø§Ù„Ù…Ù†ØªØ¬',
            'all' => 'ÙˆØµÙ + Ø³ÙŠÙˆ Ø§Ù„Ù…Ù†ØªØ¬',
            'image_alt' => 'ALT Ø§Ù„ØµÙˆØ±',
            'image_alt_bulk' => 'ALT Ø§Ù„ØµÙˆØ± (Ø¬Ù…Ù„Ø©)',
            'store_seo' => 'Ø³ÙŠÙˆ Ø§Ù„Ù…ØªØ¬Ø±',
            'unknown' => 'ØºÙŠØ± Ù…ØµÙ†Ù',
        ];

        $tableRows = '';
        foreach ($rows as $row) {
            $mode = (string) ($row['mode'] ?? 'unknown');
            $label = $modeLabels[$mode] ?? $mode;
            $storeName = (string) ($row['store_name'] ?? '-');
            $merchantId = (string) ($row['merchant_id'] ?? '-');
            $productId = (string) ($row['product_id'] ?? '-');
            $inputTokens = number_format((int) ($row['input_tokens'] ?? 0));
            $outputTokens = number_format((int) ($row['output_tokens'] ?? 0));
            $totalTokens = number_format((int) ($row['total_tokens'] ?? 0));
            $cost = (float) ($row['total_cost_usd'] ?? 0);
            $createdAt = (string) ($row['created_at'] ?? '-');

            $tableRows .= '<tr>'
                . '<td style="padding:10px;">' . htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="padding:10px;">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="padding:10px;">' . htmlspecialchars($storeName, ENT_QUOTES, 'UTF-8') . '<br><code>' . htmlspecialchars($merchantId, ENT_QUOTES, 'UTF-8') . '</code></td>'
                . '<td style="padding:10px;"><code>' . htmlspecialchars($productId, ENT_QUOTES, 'UTF-8') . '</code></td>'
                . '<td style="padding:10px;">' . $inputTokens . ' / ' . $outputTokens . ' / ' . $totalTokens . '</td>'
                . '<td style="padding:10px;">$ ' . $this->formatUsd($cost) . '</td>'
                . '</tr>';
        }

        return '<div class="card" style="margin-top:16px;">'
            . '<h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>'
            . '<table style="width:100%;border-collapse:collapse;margin-top:12px;">'
            . '<thead><tr>'
            . '<th style="text-align:right;padding:10px;">Ø§Ù„ÙˆÙ‚Øª</th>'
            . '<th style="text-align:right;padding:10px;">Ù†ÙˆØ¹ Ø§Ù„Ø¹Ù…Ù„ÙŠØ©</th>'
            . '<th style="text-align:right;padding:10px;">Ø§Ù„Ù…ØªØ¬Ø±</th>'
            . '<th style="text-align:right;padding:10px;">Product ID</th>'
            . '<th style="text-align:right;padding:10px;">Input/Output/Total Tokens</th>'
            . '<th style="text-align:right;padding:10px;">Ø§Ù„ØªÙƒÙ„ÙØ© (USD)</th>'
            . '</tr></thead>'
            . '<tbody>' . $tableRows . '</tbody>'
            . '</table>'
            . '</div>';
    }

    private function renderAiPricingTypeSummaryCard(array $rows, string $title): string
    {
        if ($rows === []) {
            return '<div class="card" style="margin-top:16px;"><h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2><p class="muted">Ù„Ø§ ØªÙˆØ¬Ø¯ Ø¨ÙŠØ§Ù†Ø§Øª ÙƒØ§ÙÙŠØ© Ù„Ù„ØªØ³Ø¹ÙŠØ± Ø¨Ø¹Ø¯.</p></div>';
        }

        usort($rows, static function (array $a, array $b): int {
            return ((float) ($b['total_cost_usd'] ?? 0)) <=> ((float) ($a['total_cost_usd'] ?? 0));
        });

        $cards = '';
        foreach ($rows as $row) {
            $runs = (int) ($row['runs_count'] ?? 0);
            if ($runs <= 0) {
                continue;
            }

            $label = htmlspecialchars((string) ($row['label'] ?? $row['mode'] ?? '-'), ENT_QUOTES, 'UTF-8');
            $totalCost = (float) ($row['total_cost_usd'] ?? 0);
            $avgCost = $totalCost / $runs;

            $cards .= '<div class="card" style="margin:0;">'
                . '<h3 style="margin:0 0 10px 0;">' . $label . '</h3>'
                . '<p style="margin:0 0 6px 0;">Ø¹Ø¯Ø¯ Ø§Ù„Ø¹Ù…Ù„ÙŠØ§Øª: <strong>' . number_format($runs) . '</strong></p>'
                . '<p style="margin:0 0 6px 0;">Ø¥Ø¬Ù…Ø§Ù„ÙŠ Ø§Ù„ØªÙƒÙ„ÙØ©: <strong>$ ' . $this->formatUsd($totalCost) . '</strong></p>'
                . '<p style="margin:0;">Ù…ØªÙˆØ³Ø· ØªÙƒÙ„ÙØ© Ø§Ù„Ø¹Ù…Ù„ÙŠØ©: <strong>$ ' . $this->formatUsd($avgCost) . '</strong></p>'
                . '</div>';
        }

        if ($cards === '') {
            return '<div class="card" style="margin-top:16px;"><h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2><p class="muted">Ù„Ø§ ØªÙˆØ¬Ø¯ Ø¹Ù…Ù„ÙŠØ§Øª Ù…ÙˆÙ„Ø¯Ø© Ø­ØªÙ‰ Ø§Ù„Ø¢Ù†.</p></div>';
        }

        return '<div class="card" style="margin-top:16px;">'
            . '<h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>'
            . '<p class="muted">Ù‡Ø°Ø§ Ø§Ù„Ù…Ù„Ø®Øµ ÙŠØ³Ø§Ø¹Ø¯Ùƒ ÙÙŠ Ø§Ù„ØªØ³Ø¹ÙŠØ± Ù„Ø§Ø­Ù‚Ù‹Ø§ Ø¨Ø­Ø³Ø¨ ÙƒÙ„ Ù†ÙˆØ¹ ØªÙˆÙ„ÙŠØ¯.</p>'
            . '<div class="grid" style="margin-top:12px;">' . $cards . '</div>'
            . '</div>';
    }

    private function renderDataForSeoUsageByModeCard(array $rows, string $title): string
    {
        if ($rows === []) {
            return '<div class="card" style="margin-top:16px;"><h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2><p class="muted">Ù„Ø§ ØªÙˆØ¬Ø¯ Ø¨ÙŠØ§Ù†Ø§Øª ØªÙƒÙ„ÙØ© DataForSEO Ø¨Ø¹Ø¯.</p></div>';
        }

        $tableRows = '';
        $totalRuns = 0;
        $totalRequests = 0;
        $totalCost = 0.0;

        foreach ($rows as $row) {
            $label = htmlspecialchars((string) ($row['label'] ?? $row['mode'] ?? '-'), ENT_QUOTES, 'UTF-8');
            $runs = (int) ($row['runs_count'] ?? 0);
            $requestsCount = (int) ($row['requests_count'] ?? 0);
            $cost = (float) ($row['total_cost_usd'] ?? 0);

            $totalRuns += $runs;
            $totalRequests += $requestsCount;
            $totalCost += $cost;

            $tableRows .= '<tr>'
                . '<td style="padding:10px;">' . $label . '</td>'
                . '<td style="padding:10px;">' . number_format($runs) . '</td>'
                . '<td style="padding:10px;">' . number_format($requestsCount) . '</td>'
                . '<td style="padding:10px;">$ ' . $this->formatUsd($cost) . '</td>'
                . '</tr>';
        }

        $footer = '<tr style="font-weight:700;background:#EEF2FF;">'
            . '<td style="padding:10px;">Ø§Ù„Ø¥Ø¬Ù…Ø§Ù„ÙŠ</td>'
            . '<td style="padding:10px;">' . number_format($totalRuns) . '</td>'
            . '<td style="padding:10px;">' . number_format($totalRequests) . '</td>'
            . '<td style="padding:10px;">$ ' . $this->formatUsd($totalCost) . '</td>'
            . '</tr>';

        return '<div class="card" style="margin-top:16px;">'
            . '<h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>'
            . '<table style="width:100%;border-collapse:collapse;margin-top:12px;">'
            . '<thead><tr>'
            . '<th style="text-align:right;padding:10px;">Ø§Ù„Ù†ÙˆØ¹</th>'
            . '<th style="text-align:right;padding:10px;">Ø¹Ø¯Ø¯ Ø§Ù„Ø¹Ù…Ù„ÙŠØ§Øª</th>'
            . '<th style="text-align:right;padding:10px;">Ø¹Ø¯Ø¯ Ø·Ù„Ø¨Ø§Øª API</th>'
            . '<th style="text-align:right;padding:10px;">Ø§Ù„ØªÙƒÙ„ÙØ© (USD)</th>'
            . '</tr></thead>'
            . '<tbody>' . $tableRows . $footer . '</tbody>'
            . '</table>'
            . '</div>';
    }

    private function renderDataForSeoUsageLogsCard(array $rows, string $title): string
    {
        if ($rows === []) {
            return '<div class="card" style="margin-top:16px;"><h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2><p class="muted">Ù„Ø§ ØªÙˆØ¬Ø¯ Ø¹Ù…Ù„ÙŠØ§Øª DataForSEO Ù…Ø³Ø¬Ù„Ø© Ø¨Ø¹Ø¯.</p></div>';
        }

        $modeLabels = [
            'keyword_research' => 'Ø¨Ø­Ø« Ø§Ù„ÙƒÙ„Ù…Ø§Øª Ø§Ù„Ù…ÙØªØ§Ø­ÙŠØ©',
            'domain_seo' => 'ØªØ­Ù„ÙŠÙ„ Ø³ÙŠÙˆ Ø¯ÙˆÙ…ÙŠÙ†',
            'unknown' => 'ØºÙŠØ± Ù…ØµÙ†Ù',
        ];

        $tableRows = '';
        foreach ($rows as $row) {
            $mode = (string) ($row['mode'] ?? 'unknown');
            $label = $modeLabels[$mode] ?? $mode;
            $storeName = (string) ($row['store_name'] ?? '-');
            $merchantId = (string) ($row['merchant_id'] ?? '-');
            $target = (string) ($row['target'] ?? '-');
            $requestsCount = number_format((int) ($row['requests_count'] ?? 0));
            $cost = (float) ($row['total_cost_usd'] ?? 0);
            $createdAt = (string) ($row['created_at'] ?? '-');

            $tableRows .= '<tr>'
                . '<td style="padding:10px;">' . htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="padding:10px;">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="padding:10px;">' . htmlspecialchars($storeName, ENT_QUOTES, 'UTF-8') . '<br><code>' . htmlspecialchars($merchantId, ENT_QUOTES, 'UTF-8') . '</code></td>'
                . '<td style="padding:10px;"><code>' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '</code></td>'
                . '<td style="padding:10px;">' . $requestsCount . '</td>'
                . '<td style="padding:10px;">$ ' . $this->formatUsd($cost) . '</td>'
                . '</tr>';
        }

        return '<div class="card" style="margin-top:16px;">'
            . '<h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>'
            . '<table style="width:100%;border-collapse:collapse;margin-top:12px;">'
            . '<thead><tr>'
            . '<th style="text-align:right;padding:10px;">Ø§Ù„ÙˆÙ‚Øª</th>'
            . '<th style="text-align:right;padding:10px;">Ù†ÙˆØ¹ Ø§Ù„Ø¹Ù…Ù„ÙŠØ©</th>'
            . '<th style="text-align:right;padding:10px;">Ø§Ù„Ù…ØªØ¬Ø±</th>'
            . '<th style="text-align:right;padding:10px;">Ø§Ù„Ù‚ÙŠÙ…Ø© Ø§Ù„Ù…Ø¯Ø®Ù„Ø©</th>'
            . '<th style="text-align:right;padding:10px;">Ø¹Ø¯Ø¯ Ø·Ù„Ø¨Ø§Øª API</th>'
            . '<th style="text-align:right;padding:10px;">Ø§Ù„ØªÙƒÙ„ÙØ© (USD)</th>'
            . '</tr></thead>'
            . '<tbody>' . $tableRows . '</tbody>'
            . '</table>'
            . '</div>';
    }

    private function formatUsd(float $value): string
    {
        return number_format($value, 6, '.', '');
    }

    private function ensureAdmin(): bool
    {
        if (!($_SESSION['admin_logged_in'] ?? false)) {
            header('Location: /admin/login');
            return false;
        }

        return true;
    }
}

