<?php
declare(strict_types=1);

$appBasePath = (string) parse_url((string) \App\Config::get('APP_URL', ''), PHP_URL_PATH);
$appBasePath = rtrim($appBasePath, '/');
if ($appBasePath === '/') {
    $appBasePath = '';
}
?>
<div class="dashboard-shell" data-app-base-path="<?= htmlspecialchars($appBasePath, ENT_QUOTES, 'UTF-8') ?>">
  <aside class="card dashboard-sidebar">
    <div>
      <h3 style="margin:0 0 8px;">Ù„ÙˆØ­Ø© Ø§Ù„Ø¹Ù…ÙŠÙ„</h3>
      <p class="muted" style="margin:0;">Ø¥Ø¯Ø§Ø±Ø© Ø§Ù„Ø³ÙŠÙˆ Ø¨Ø§Ù„ÙƒØ§Ù…Ù„ Ù…Ù† Ù…ÙƒØ§Ù† ÙˆØ§Ø­Ø¯ Ø¨Ø¯ÙˆÙ† Ø±Ø¨Ø· Ù…Ø¹ Ø³Ù„Ø©.</p>
    </div>
    <nav class="sidebar-nav">
      <button type="button" class="sidebar-link is-active" data-section-target="home">Ø§Ù„Ø±Ø¦ÙŠØ³ÙŠØ©</button>
      <button type="button" class="sidebar-link" data-section-target="seo-settings">Ø¥Ø¹Ø¯Ø§Ø¯Ø§Øª Ø§Ù„Ø³ÙŠÙˆ Ø§Ù„Ø¹Ø§Ù…Ø©</button>
      <button type="button" class="sidebar-link" data-section-target="products-seo">Ø³ÙŠÙˆ Ø§Ù„Ù…Ù†ØªØ¬Ø§Øª</button>
      <button type="button" class="sidebar-link" data-section-target="keywords">Ø§Ù„ÙƒÙ„Ù…Ø§Øª Ø§Ù„Ù…ÙØªØ§Ø­ÙŠØ©</button>
      <button type="button" class="sidebar-link" data-section-target="domain-seo">Ø³ÙŠÙˆ Ø§Ù„Ø¯ÙˆÙ…ÙŠÙ†</button>
      <button type="button" class="sidebar-link" data-section-target="brand-seo">Ø³ÙŠÙˆ Ø§Ù„Ù…Ø§Ø±ÙƒØ§Øª</button>
      <button type="button" class="sidebar-link" data-section-target="category-seo">Ø³ÙŠÙˆ Ø§Ù„ØªØµÙ†ÙŠÙØ§Øª</button>
      <button type="button" class="sidebar-link" data-section-target="operations">Ø³Ø¬Ù„ Ø§Ù„Ø¹Ù…Ù„ÙŠØ§Øª</button>
      <button type="button" class="sidebar-link" data-section-target="account-settings">Ø§Ù„Ø­Ø³Ø§Ø¨ ÙˆØ§Ù„Ø¥Ø¹Ø¯Ø§Ø¯Ø§Øª</button>
    </nav>
  </aside>

  <main class="panel-stack">
    <section id="section-home" data-app-section="home" class="panel-stack">
      <div class="card">
        <div class="pill">Ù†Ø¸Ø±Ø© Ø¹Ø§Ù…Ø©</div>
        <h1 style="margin:12px 0 8px;">Ù…Ø±Ø­Ø¨Ù‹Ø§ <?= htmlspecialchars((string) $storeName, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="muted" style="margin:0;">Ø§Ø¨Ø¯Ø£ Ù…Ù† Ø¥Ø¹Ø¯Ø§Ø¯Ø§Øª Ø§Ù„Ø³ÙŠÙˆ Ø§Ù„Ø¹Ø§Ù…Ø© Ø«Ù… Ø§Ù†ØªÙ‚Ù„ Ù„ØªÙˆÙ„ÙŠØ¯ Ø§Ù„Ù…Ø­ØªÙˆÙ‰ ÙˆØ§Ù„ØªÙ‚Ø§Ø±ÙŠØ±.</p>
      </div>
      <div class="grid" id="home-stats"></div>
    </section>

    <section id="section-seo-settings" data-app-section="seo-settings" class="panel-stack" style="display:none;">
      <div class="card">
        <div class="section-head">
          <div>
            <div class="pill">Ø¥Ø¹Ø¯Ø§Ø¯Ø§Øª Ø§Ù„Ø³ÙŠÙˆ Ø§Ù„Ø¹Ø§Ù…Ø©</div>
            <h2 style="margin:10px 0;">ØªØ¹Ù„ÙŠÙ…Ø§Øª Ø§Ù„ØªÙˆÙ„ÙŠØ¯</h2>
          </div>
          <button id="save-settings-btn" class="btn btn-sky" type="button">Ø­ÙØ¸ Ø§Ù„Ø¥Ø¹Ø¯Ø§Ø¯Ø§Øª</button>
        </div>
        <div id="settings-alert"></div>
        <div class="grid" style="margin-top:0;">
          <div>
            <label><strong>Ù„ØºØ© Ø§Ù„Ù…Ø®Ø±Ø¬Ø§Øª</strong></label>
            <select id="setting-output-language">
              <option value="ar">Ø§Ù„Ø¹Ø±Ø¨ÙŠØ©</option>
              <option value="en">English</option>
            </select>
          </div>
          <div>
            <label><strong>Ø§Ø³Ù… Ø§Ù„Ù…ØªØ¬Ø± / Ø§Ù„Ø¨Ø±Ø§Ù†Ø¯</strong></label>
            <input id="setting-business-brand-name" type="text" placeholder="Ø§ÙƒØªØ¨ Ø§Ø³Ù… Ø§Ù„Ù…ØªØ¬Ø±">
          </div>
          <div style="grid-column:1/-1;">
            <label><strong>Ù†Ø¨Ø°Ø© Ø¹Ù† Ø§Ù„Ù†Ø´Ø§Ø· Ø§Ù„ØªØ¬Ø§Ø±ÙŠ</strong></label>
            <textarea id="setting-business-overview" rows="3" placeholder="ÙˆØ´ ØªØ¨ÙŠØ¹ØŸ Ù…ÙŠÙ† Ø¬Ù…Ù‡ÙˆØ±ÙƒØŸ"></textarea>
          </div>
          <div style="grid-column:1/-1;border:1px solid #FBBF24;background:#FEF3C7;border-radius:12px;padding:12px;">
            <p style="margin:0 0 10px;color:#92400E;font-weight:700;">Ø±Ø¨Ø· Ø§Ù„Ø³Ø§ÙŠØª Ù…Ø§Ø¨ (Ø§Ø®ØªÙŠØ§Ø±ÙŠ Ù„ÙƒÙ†Ù‡ Ù…Ù‡Ù…)</p>
            <p style="margin:0 0 12px;color:#78350F;font-size:13px;">Ø¨Ø¯ÙˆÙ† Ø§Ù„Ø³Ø§ÙŠØª Ù…Ø§Ø¨ØŒ Ù„Ù† ÙŠØªÙ… Ø¥Ø¶Ø§ÙØ© Ø±ÙˆØ§Ø¨Ø· Ø¯Ø§Ø®Ù„ÙŠØ© Ù„Ù„Ù…Ù†ØªØ¬Ø§Øª Ø£Ø«Ù†Ø§Ø¡ Ø§Ù„ØªÙˆÙ„ÙŠØ¯.</p>
            <label for="setting-sitemap-url"><strong>Ø±Ø§Ø¨Ø· Ø§Ù„Ø³Ø§ÙŠØª Ù…Ø§Ø¨</strong></label>
            <input id="setting-sitemap-url" type="url" placeholder="https://yourstore.com/sitemap.xml" style="margin-top:8px;width:100%;padding:10px;border-radius:8px;border:1px solid #D97706;">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-top:8px;color:#92400E;font-size:13px;">
              <span>Ø§Ù„Ø±ÙˆØ§Ø¨Ø· Ø§Ù„Ù…Ø­ÙÙˆØ¸Ø©: <strong id="setting-sitemap-links-count">0</strong></span>
              <span id="setting-sitemap-last-fetched">Ù„Ù… ÙŠØªÙ… Ø§Ù„Ø¬Ù„Ø¨ Ø¨Ø¹Ø¯</span>
            </div>
            <button id="save-sitemap-settings" class="btn btn-sky" type="button" style="margin-top:12px;">Ø­ÙØ¸ Ø±ÙˆØ§Ø¨Ø· Ø§Ù„Ø³Ø§ÙŠØª Ù…Ø§Ø¨</button>
            <div id="sitemap-alert" style="margin-top:12px;"></div>
          </div>
          <div style="grid-column:1/-1;">
            <label><strong>ØªØ¹Ù„ÙŠÙ…Ø§Øª Ø¹Ø§Ù…Ø©</strong></label>
            <textarea id="setting-global-instructions" rows="3"></textarea>
          </div>
          <div style="grid-column:1/-1;">
            <label><strong>ØªØ¹Ù„ÙŠÙ…Ø§Øª ÙˆØµÙ Ø§Ù„Ù…Ù†ØªØ¬</strong></label>
            <textarea id="setting-product-description-instructions" rows="3"></textarea>
          </div>
          <div>
            <label><strong>ØªØ¹Ù„ÙŠÙ…Ø§Øª Meta Title</strong></label>
            <textarea id="setting-meta-title-instructions" rows="3"></textarea>
          </div>
          <div>
            <label><strong>ØªØ¹Ù„ÙŠÙ…Ø§Øª Meta Description</strong></label>
            <textarea id="setting-meta-description-instructions" rows="3"></textarea>
          </div>
          <div>
            <label><strong>Ø±Ø§Ø¨Ø· ØµÙØ­Ø© Ø§Ù„Ù…Ù†ØªØ¬ (SEO Page URL)</strong></label>
            <textarea id="setting-seo-page-url-instructions" rows="3"></textarea>
          </div>
          <div>
            <label><strong>ØªØ¹Ù„ÙŠÙ…Ø§Øª Ø³ÙŠÙˆ Ø§Ù„Ù…Ø§Ø±ÙƒØ§Øª</strong></label>
            <textarea id="setting-brand-seo-instructions" rows="3"></textarea>
          </div>
          <div>
            <label><strong>ØªØ¹Ù„ÙŠÙ…Ø§Øª Ø³ÙŠÙˆ Ø§Ù„ØªØµÙ†ÙŠÙØ§Øª</strong></label>
            <textarea id="setting-category-seo-instructions" rows="3"></textarea>
          </div>
        </div>
      </div>
    </section>

    <section id="section-products-seo" data-app-section="products-seo" class="panel-stack" style="display:none;">
      <div class="card">
        <div class="section-head">
          <div><div class="pill">Ø³ÙŠÙˆ Ø§Ù„Ù…Ù†ØªØ¬Ø§Øª</div><h2 style="margin:10px 0;">ØªÙˆÙ„ÙŠØ¯ Ø³ÙŠÙˆ Ù…Ù†ØªØ¬</h2></div>
          <div class="action-with-cost">
            <button id="generate-product-btn" class="btn btn-sky" type="button">ØªÙˆÙ„ÙŠØ¯</button>
            <span id="generate-product-cost-badge" class="cost-badge" title="Ø³ÙŠØªÙ… Ø§Ø³ØªÙ‡Ù„Ø§Ùƒ 1 Ù†Ù‚Ø·Ø©"><span class="cost-dot"></span>1 نقطة</span>
          </div>
        </div>
        <div class="grid" style="margin-top:0;">
          <div><label><strong>Ø§Ø³Ù… Ø§Ù„Ù…Ù†ØªØ¬ / Ø§Ù„ÙƒÙ„Ù…Ø© Ø§Ù„Ù…ÙØªØ§Ø­ÙŠØ©</strong></label><input id="product-keyword" type="text" placeholder="Ù…Ø«Ø§Ù„: Ø­Ø°Ø§Ø¡ Ø¬Ø±ÙŠ Ø±Ø¬Ø§Ù„ÙŠ"></div>
          <div><label><strong>Ù…Ø¹Ù„ÙˆÙ…Ø§Øª Ø¥Ø¶Ø§ÙÙŠØ© (Ø§Ø®ØªÙŠØ§Ø±ÙŠ)</strong></label><input id="product-context" type="text" placeholder="Ø§Ù„Ø®Ø§Ù…Ø©ØŒ Ø§Ù„Ø¬Ù…Ù‡ÙˆØ±ØŒ Ø§Ù„Ø³Ø¹Ø±..."></div>
          <div style="grid-column:1/-1;">
            <label class="competitor-option-label">
              <input id="product-competitor-boost" type="checkbox">
              <strong>Ø§Ù„Ø¨Ø­Ø« Ø¹Ù† Ø£ÙˆÙ„ 10 Ù†ØªØ§Ø¦Ø¬ ÙÙŠ Ø¬ÙˆØ¬Ù„ ÙˆÙƒØªØ§Ø¨Ø© Ù…Ø­ØªÙˆÙ‰ Ø£Ù‚ÙˆÙ‰</strong>
              <span class="cost-badge is-expensive" title="Ø¹Ù†Ø¯ Ø§Ù„ØªÙØ¹ÙŠÙ„ Ø³ÙŠØªÙ… Ø§Ø³ØªÙ‡Ù„Ø§Ùƒ 5 Ù†Ù‚Ø§Ø·"><span class="cost-dot"></span>5 Ù†Ù‚Ø§Ø·</span>
            </label>
            <p class="muted" style="margin:8px 0 0;">Ø¹Ù†Ø¯ Ø§Ù„ØªÙØ¹ÙŠÙ„: ÙŠØªÙ… ØªØ­Ù„ÙŠÙ„ Ø£ÙØ¶Ù„ 10 Ù†ØªØ§Ø¦Ø¬ Ø¨Ø§Ø³ØªØ®Ø¯Ø§Ù…  Ù„Ø¨Ù†Ø§Ø¡ Ù…Ø­ØªÙˆÙ‰ ÙˆÙ…ÙŠØªØ§ Ø£Ù‚ÙˆÙ‰ Ù…Ø¹ Ø§Ù„Ø­ÙØ§Ø¸ Ø¹Ù„Ù‰ Ù‡ÙˆÙŠØ© Ù…ØªØ¬Ø±Ùƒ ÙˆØªØ¹Ù„ÙŠÙ…Ø§Øª Ø§Ù„Ø³ÙŠÙˆ.</p>
          </div>
          <div id="product-competitor-filters" style="grid-column:1/-1;display:none;">
            <div class="grid" style="margin-top:0;">
              <div><label><strong>Ø§Ù„Ø¯ÙˆÙ„Ø©</strong></label><select id="product-competitor-country"><option value="sa">Ø§Ù„Ø³Ø¹ÙˆØ¯ÙŠØ©</option></select></div>
              <div><label><strong>Ù„ØºØ© Ø§Ù„Ø¨Ø­Ø«</strong></label><select id="product-competitor-language"><option value="ar">Ø§Ù„Ø¹Ø±Ø¨ÙŠØ©</option><option value="en">English</option></select></div>
              <div><label><strong>Ù†ÙˆØ¹ Ø§Ù„Ù…ØªØµÙØ­</strong></label><select id="product-competitor-device"><option value="desktop">ÙƒÙ…Ø¨ÙŠÙˆØªØ±</option><option value="mobile">Ø¬ÙˆØ§Ù„</option></select></div>
            </div>
          </div>
        </div>
        <div id="product-alert"></div>
      </div>
      <div class="card"><h3 style="margin:0 0 10px;">Ø§Ù„Ù…Ù†ØªØ¬Ø§Øª Ø§Ù„Ù…ÙˆÙ„Ø¯Ø©</h3><div id="products-list"></div></div>
    </section>

    <section id="section-keywords" data-app-section="keywords" class="panel-stack" style="display:none;">
      <div class="card">
        <div class="section-head">
          <div>
            <div class="pill">Ø§Ù„ÙƒÙ„Ù…Ø§Øª Ø§Ù„Ù…ÙØªØ§Ø­ÙŠØ©</div>
            <h2 style="margin:10px 0;">Ø¨Ø­Ø« Ø§Ø­ØªØ±Ø§ÙÙŠ Ø¹Ù† Ø§Ù„ÙƒÙ„Ù…Ø§Øª Ø§Ù„Ù…ÙØªØ§Ø­ÙŠØ©</h2>
            <p class="muted" style="margin:0;">ØªØ­Ù„ÙŠÙ„ Ø­Ø¬Ù… Ø§Ù„Ø¨Ø­Ø« ÙˆØ§Ù„Ù…Ù†Ø§ÙØ³Ø© ÙˆØ§Ù„Ù†ØªØ§Ø¦Ø¬ Ø§Ù„Ø£ÙˆÙ„Ù‰ ÙˆØ§Ù‚ØªØ±Ø§Ø­Ø§Øª Ù…Ø±ØªØ¨Ø·Ø©.</p>
          </div>
          <div class="action-with-cost">
            <button id="keyword-search-btn" class="btn btn-sky" type="button">Ø¨Ø­Ø« Ø§Ù„ÙƒÙ„Ù…Ø§Øª Ø§Ù„Ù…ÙØªØ§Ø­ÙŠØ©</button>
            <span class="cost-badge" title="Ø³ÙŠØªÙ… Ø§Ø³ØªÙ‡Ù„Ø§Ùƒ 1 Ù†Ù‚Ø·Ø©"><span class="cost-dot"></span>1 نقطة</span>
          </div>
        </div>
        <div class="grid" style="margin-top:0;">
          <div><label><strong>Ø§Ù„ÙƒÙ„Ù…Ø© Ø§Ù„Ù…ÙØªØ§Ø­ÙŠØ©</strong></label><input id="keyword-query" type="text" placeholder="Ù…Ø«Ø§Ù„: Ø¹Ø·ÙˆØ± Ø±Ø¬Ø§Ù„ÙŠØ©"></div>
          <div><label><strong>Ø§Ù„Ø¯ÙˆÙ„Ø©</strong></label><select id="keyword-country"><option value="sa">Ø§Ù„Ø³Ø¹ÙˆØ¯ÙŠØ©</option></select></div>
          <div><label><strong>Ù„ØºØ© Ø§Ù„Ø¨Ø­Ø«</strong></label><select id="keyword-language"><option value="ar">Ø§Ù„Ø¹Ø±Ø¨ÙŠØ©</option><option value="en">English</option></select></div>
          <div><label><strong>Ù†ÙˆØ¹ Ø§Ù„Ù…ØªØµÙØ­</strong></label><select id="keyword-device"><option value="desktop">ÙƒÙ…Ø¨ÙŠÙˆØªØ±</option><option value="mobile">Ø¬ÙˆØ§Ù„</option></select></div>
        </div>
        <div id="keyword-alert"></div>
      </div>

      <div class="card">
        <div class="section-head">
          <div>
            <h2 style="margin:0 0 6px;">ØªÙ‚Ø±ÙŠØ± Ø§Ù„ÙƒÙ„Ù…Ø§Øª Ø§Ù„Ù…ÙØªØ§Ø­ÙŠØ©</h2>
            <p id="keyword-summary" class="muted" style="margin:0;">Ø£Ø¯Ø®Ù„ ÙƒÙ„Ù…Ø© Ù…ÙØªØ§Ø­ÙŠØ© Ø«Ù… Ø§Ø¶ØºØ· Ø¨Ø­Ø«.</p>
          </div>
        </div>
        <div id="keyword-results">
          <div class="empty-state"><p class="muted" style="margin:0;">Ù„Ù… ÙŠØªÙ… Ø¥Ø¬Ø±Ø§Ø¡ Ø¨Ø­Ø« Ø¨Ø¹Ø¯.</p></div>
        </div>
      </div>

      <div class="card">
        <div class="section-head">
          <div>
            <h2 style="margin:0 0 6px;">Ø³Ø¬Ù„ Ø§Ù„Ø¨Ø­Ø«</h2>
            <p class="muted" style="margin:0;">Ø§Ø³ØªØ¹Ø±Ø§Ø¶ Ù†ØªØ§Ø¦Ø¬ Ø§Ù„Ø¨Ø­Ø« Ø§Ù„Ø³Ø§Ø¨Ù‚Ø© Ø¨Ø¯ÙˆÙ† Ø§Ø³ØªÙ‡Ù„Ø§Ùƒ Ø¥Ø¶Ø§ÙÙŠ.</p>
          </div>
        </div>
        <div id="keyword-history-list" class="panel-stack">
          <div class="empty-state"><p class="muted" style="margin:0;">Ù„Ø§ ÙŠÙˆØ¬Ø¯ Ø³Ø¬Ù„ Ø¨Ø­Ø« Ø­ØªÙ‰ Ø§Ù„Ø¢Ù†.</p></div>
        </div>
      </div>
    </section>

    <section id="section-domain-seo" data-app-section="domain-seo" class="panel-stack" style="display:none;">
      <div class="card">
        <div class="section-head">
          <div>
            <div class="pill">Ø³ÙŠÙˆ Ø§Ù„Ø¯ÙˆÙ…ÙŠÙ†</div>
            <h2 style="margin:10px 0;">ØªØ­Ù„ÙŠÙ„ Ø§Ù„Ø¯ÙˆÙ…ÙŠÙ† ÙˆØ§Ù„Ù…Ù†Ø§ÙØ³ÙŠÙ†</h2>
            <p class="muted" style="margin:0;">Ø§Ø­ÙØ¸ Ø§Ù„Ø¯ÙˆÙ…ÙŠÙ† Ø«Ù… Ø­Ø¯Ù‘Ø« Ø§Ù„Ø¨ÙŠØ§Ù†Ø§Øª ÙˆÙ‚Øª Ù…Ø§ ØªØ­Ø¨ØŒ Ù…Ø¹ Ø­ÙØ¸ Ø§Ù„Ø³Ø¬Ù„ ÙƒØ§Ù…Ù„.</p>
          </div>
          <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button id="domain-seo-save-btn" class="btn btn-secondary" type="button">Ø­ÙØ¸ Ø§Ù„Ø¯ÙˆÙ…ÙŠÙ†</button>
            <div class="action-with-cost">
              <button id="domain-seo-refresh-btn" class="btn btn-sky" type="button">ØªØ­Ø¯ÙŠØ« Ø§Ù„Ø¨ÙŠØ§Ù†Ø§Øª</button>
              <span class="cost-badge is-expensive" title="Ø³ÙŠØªÙ… Ø§Ø³ØªÙ‡Ù„Ø§Ùƒ 3 Ù†Ù‚Ø§Ø·"><span class="cost-dot"></span>3 Ù†Ù‚Ø§Ø·</span>
            </div>
          </div>
        </div>
        <div class="grid" style="margin-top:0;">
          <div><label><strong>Ø§Ù„Ø¯ÙˆÙ…ÙŠÙ†</strong></label><input id="domain-seo-domain" type="text" placeholder="example.com"></div>
          <div><label><strong>Ø§Ù„Ø¯ÙˆÙ„Ø©</strong></label><select id="domain-seo-country"><option value="sa">Ø§Ù„Ø³Ø¹ÙˆØ¯ÙŠØ©</option></select></div>
          <div><label><strong>Ù†ÙˆØ¹ Ø§Ù„Ù…ØªØµÙØ­</strong></label><select id="domain-seo-device"><option value="desktop">ÙƒÙ…Ø¨ÙŠÙˆØªØ±</option><option value="mobile">Ø¬ÙˆØ§Ù„</option></select></div>
        </div>
        <div id="domain-seo-alert"></div>
      </div>

      <div class="card">
        <div class="section-head">
          <div>
            <h2 style="margin:0 0 6px;">ØªÙ‚Ø±ÙŠØ± Ø³ÙŠÙˆ Ø§Ù„Ø¯ÙˆÙ…ÙŠÙ†</h2>
            <p id="domain-seo-summary" class="muted" style="margin:0;">Ø§Ø­ÙØ¸ Ø§Ù„Ø¯ÙˆÙ…ÙŠÙ† ÙˆØ§Ø¶ØºØ· ØªØ­Ø¯ÙŠØ« Ø§Ù„Ø¨ÙŠØ§Ù†Ø§Øª.</p>
          </div>
        </div>
        <div id="domain-seo-results">
          <div class="empty-state"><p class="muted" style="margin:0;">Ù„Ø§ ØªÙˆØ¬Ø¯ Ø¨ÙŠØ§Ù†Ø§Øª Ø¯ÙˆÙ…ÙŠÙ† Ù…Ø­ÙÙˆØ¸Ø© Ø¨Ø¹Ø¯.</p></div>
        </div>
      </div>

      <div class="card">
        <div class="section-head">
          <div>
            <h2 style="margin:0 0 6px;">Ø³Ø¬Ù„ ØªØ­Ù„ÙŠÙ„ Ø§Ù„Ø¯ÙˆÙ…ÙŠÙ†</h2>
            <p class="muted" style="margin:0;">ÙƒÙ„ Ù†ØªØ§Ø¦Ø¬ Ø§Ù„ØªØ­Ø¯ÙŠØ«Ø§Øª Ø§Ù„Ø³Ø§Ø¨Ù‚Ø© Ù…Ø­ÙÙˆØ¸Ø© Ù‡Ù†Ø§.</p>
          </div>
        </div>
        <div id="domain-seo-history-list" class="panel-stack">
          <div class="empty-state"><p class="muted" style="margin:0;">Ù„Ø§ ÙŠÙˆØ¬Ø¯ Ø³Ø¬Ù„ ØªØ­Ù„ÙŠÙ„ Ù„Ù„Ø¯ÙˆÙ…ÙŠÙ† Ø­ØªÙ‰ Ø§Ù„Ø¢Ù†.</p></div>
        </div>
      </div>
    </section>

    <section id="section-brand-seo" data-app-section="brand-seo" class="panel-stack" style="display:none;">
      <div class="card">
        <div class="section-head">
          <div><div class="pill">Ø³ÙŠÙˆ Ø§Ù„Ù…Ø§Ø±ÙƒØ§Øª</div><h2 style="margin:10px 0;">ØªÙˆÙ„ÙŠØ¯ Ø³ÙŠÙˆ Ù…Ø§Ø±ÙƒØ©</h2></div>
          <div class="action-with-cost">
            <button id="generate-brand-btn" class="btn btn-sky" type="button">ØªÙˆÙ„ÙŠØ¯</button>
            <span class="cost-badge" title="Ø³ÙŠØªÙ… Ø§Ø³ØªÙ‡Ù„Ø§Ùƒ 1 Ù†Ù‚Ø·Ø©"><span class="cost-dot"></span>1 نقطة</span>
          </div>
        </div>
        <div class="grid" style="margin-top:0;">
          <div><label><strong>Ø§Ø³Ù… Ø§Ù„Ù…Ø§Ø±ÙƒØ© / Ø§Ù„ÙƒÙ„Ù…Ø© Ø§Ù„Ù…ÙØªØ§Ø­ÙŠØ©</strong></label><input id="brand-keyword" type="text"></div>
          <div><label><strong>Ù…Ø¹Ù„ÙˆÙ…Ø§Øª Ø¥Ø¶Ø§ÙÙŠØ© (Ø§Ø®ØªÙŠØ§Ø±ÙŠ)</strong></label><input id="brand-context" type="text"></div>
        </div>
        <div id="brand-alert"></div>
      </div>
      <div class="card"><h3 style="margin:0 0 10px;">Ø§Ù„Ù…Ø§Ø±ÙƒØ§Øª Ø§Ù„Ù…ÙˆÙ„Ø¯Ø©</h3><div id="brands-list"></div></div>
    </section>

    <section id="section-category-seo" data-app-section="category-seo" class="panel-stack" style="display:none;">
      <div class="card">
        <div class="section-head">
          <div><div class="pill">Ø³ÙŠÙˆ Ø§Ù„ØªØµÙ†ÙŠÙØ§Øª</div><h2 style="margin:10px 0;">ØªÙˆÙ„ÙŠØ¯ Ø³ÙŠÙˆ ØªØµÙ†ÙŠÙ</h2></div>
          <div class="action-with-cost">
            <button id="generate-category-btn" class="btn btn-sky" type="button">ØªÙˆÙ„ÙŠØ¯</button>
            <span class="cost-badge" title="Ø³ÙŠØªÙ… Ø§Ø³ØªÙ‡Ù„Ø§Ùƒ 1 Ù†Ù‚Ø·Ø©"><span class="cost-dot"></span>1 نقطة</span>
          </div>
        </div>
        <div class="grid" style="margin-top:0;">
          <div><label><strong>Ø§Ø³Ù… Ø§Ù„ØªØµÙ†ÙŠÙ / Ø§Ù„ÙƒÙ„Ù…Ø© Ø§Ù„Ù…ÙØªØ§Ø­ÙŠØ©</strong></label><input id="category-keyword" type="text"></div>
          <div><label><strong>Ù…Ø¹Ù„ÙˆÙ…Ø§Øª Ø¥Ø¶Ø§ÙÙŠØ© (Ø§Ø®ØªÙŠØ§Ø±ÙŠ)</strong></label><input id="category-context" type="text"></div>
        </div>
        <div id="category-alert"></div>
      </div>
      <div class="card"><h3 style="margin:0 0 10px;">Ø§Ù„ØªØµÙ†ÙŠÙØ§Øª Ø§Ù„Ù…ÙˆÙ„Ø¯Ø©</h3><div id="categories-list"></div></div>
    </section>

    <section id="section-operations" data-app-section="operations" class="panel-stack" style="display:none;">
      <div class="card"><h2 style="margin:0 0 10px;">Ø³Ø¬Ù„ Ø§Ù„Ø¹Ù…Ù„ÙŠØ§Øª</h2><div id="operations-list"></div></div>
    </section>

    <section id="section-account-settings" data-app-section="account-settings" class="panel-stack" style="display:none;">
      <div class="card">
        <h2 style="margin:0 0 10px;">Ø§Ù„Ø­Ø³Ø§Ø¨</h2>
        <p style="margin:0 0 8px;"><strong>Ø§Ø³Ù… Ø§Ù„Ù…ØªØ¬Ø±:</strong> <?= htmlspecialchars((string) $storeName, ENT_QUOTES, 'UTF-8') ?></p>
        <p style="margin:0 0 8px;"><strong>Merchant ID:</strong> <?= htmlspecialchars((string) $merchantId, ENT_QUOTES, 'UTF-8') ?></p>
        <p style="margin:0 0 16px;"><strong>Ø§Ù„Ø¨Ø±ÙŠØ¯:</strong> <?= htmlspecialchars((string) $ownerEmail, ENT_QUOTES, 'UTF-8') ?></p>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
          <a class="btn btn-secondary" href="/forgot-password">Ø§Ø³ØªØ±Ø¬Ø§Ø¹ ÙƒÙ„Ù…Ø© Ø§Ù„Ù…Ø±ÙˆØ±</a>
          <a class="btn" href="/logout">ØªØ³Ø¬ÙŠÙ„ Ø§Ù„Ø®Ø±ÙˆØ¬</a>
        </div>
      </div>
    </section>
  </main>

  <div id="generated-result-modal" class="modal-backdrop" aria-hidden="true">
    <div class="modal">
      <div class="modal-head">
        <div>
          <div class="pill" id="generated-result-type">Ù†ØªÙŠØ¬Ø© Ø§Ù„ØªÙˆÙ„ÙŠØ¯</div>
          <h2 id="generated-result-title" style="margin:10px 0 6px;">-</h2>
          <p id="generated-result-date" class="muted" style="margin:0;">-</p>
        </div>
        <button id="generated-result-close" class="btn btn-secondary" type="button">Ø¥ØºÙ„Ø§Ù‚</button>
      </div>
      <div class="panel-stack">
        <div id="generated-result-description-card" class="card surface-soft" style="box-shadow:none;">
          <div class="section-head" style="margin-bottom:10px;">
            <h3 id="generated-result-description-title" style="margin:0;">ÙˆØµÙ Ø§Ù„Ù…Ø­ØªÙˆÙ‰</h3>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
              <button id="generated-result-description-copy-html-btn" class="btn btn-secondary" type="button" data-copy-mode="html-description">Ù†Ø³Ø® HTML Ø¬Ø§Ù‡Ø²</button>
              <button id="generated-result-description-copy-plain-btn" class="btn btn-secondary" type="button" data-copy-target="generated-result-description-plain">Ù†Ø³Ø® Ù†Øµ Ø¹Ø§Ø¯ÙŠ</button>
            </div>
          </div>
          <input id="generated-result-description-html" type="hidden" value="">
          <input id="generated-result-description-plain" type="hidden" value="">
          <div id="generated-result-description-rendered" class="generated-result-rendered"></div>
          <div id="generated-result-copy-alert"></div>
        </div>
        <div class="grid" style="margin-top:0;">
          <div class="card surface-soft" style="box-shadow:none;">
            <div class="section-head" style="margin-bottom:10px;">
              <h3 style="margin:0;">Meta Title</h3>
              <button class="btn btn-secondary" type="button" data-copy-target="generated-result-meta-title">Ù†Ø³Ø®</button>
            </div>
            <textarea id="generated-result-meta-title" rows="4" readonly></textarea>
          </div>
          <div class="card surface-soft" style="box-shadow:none;">
            <div class="section-head" style="margin-bottom:10px;">
              <h3 style="margin:0;">Meta Description</h3>
              <button class="btn btn-secondary" type="button" data-copy-target="generated-result-meta-description">Ù†Ø³Ø®</button>
            </div>
            <textarea id="generated-result-meta-description" rows="6" readonly></textarea>
          </div>
          <div class="card surface-soft" style="box-shadow:none;">
            <div class="section-head" style="margin-bottom:10px;">
              <h3 style="margin:0;">Ø±Ø§Ø¨Ø· ØµÙØ­Ø© Ø§Ù„Ù…Ù†ØªØ¬ (SEO Page URL)</h3>
              <button class="btn btn-secondary" type="button" data-copy-target="generated-result-seo-slug">Ù†Ø³Ø®</button>
            </div>
            <textarea id="generated-result-seo-slug" rows="3" readonly></textarea>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  (function () {
    var base = (document.querySelector('.dashboard-shell')?.dataset.appBasePath || '').replace(/\/+$/, '');
    var jsVersion = <?= json_encode((string) (int) @filemtime(realpath(__DIR__ . '/../../public/assets/standalone-dashboard.js'))) ?>;
    if (!jsVersion) jsVersion = String(Date.now());
    var candidates = [
      (base || '') + '/assets/standalone-dashboard.js?v=' + jsVersion,
      (base || '') + '/public/assets/standalone-dashboard.js?v=' + jsVersion
    ];
    var index = 0;

    function loadNext() {
      if (index >= candidates.length) {
        console.error('Failed to load standalone-dashboard.js from all known paths.');
        return;
      }

      var script = document.createElement('script');
      script.defer = true;
      script.src = candidates[index];
      script.onerror = function () {
        index += 1;
        loadNext();
      };
      document.body.appendChild(script);
    }

    loadNext();
  })();
</script>


