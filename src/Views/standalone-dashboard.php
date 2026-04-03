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
      <h3 style="margin:0 0 8px;">Standalone Dashboard</h3>
      <p class="muted" style="margin:0;">Standalone mode. Generate SEO content directly from keywords.</p>
    </div>
    <nav class="sidebar-nav">
      <button type="button" class="sidebar-link is-active" data-section-target="home">Home</button>
      <button type="button" class="sidebar-link" data-section-target="seo-settings">SEO Settings</button>
      <button type="button" class="sidebar-link" data-section-target="products-seo">Products SEO</button>
      <button type="button" class="sidebar-link" data-section-target="keywords">Keywords</button>
      <button type="button" class="sidebar-link" data-section-target="domain-seo">Domain SEO</button>
      <button type="button" class="sidebar-link" data-section-target="brand-seo">Brands SEO</button>
      <button type="button" class="sidebar-link" data-section-target="category-seo">Categories SEO</button>
      <button type="button" class="sidebar-link" data-section-target="operations">Operations</button>
      <button type="button" class="sidebar-link" data-section-target="account-settings">Account</button>
    </nav>
  </aside>

  <main class="panel-stack">
    <section id="section-home" data-app-section="home" class="panel-stack">
      <div class="card">
        <div class="pill">Overview</div>
        <h1 style="margin:12px 0 8px;">Welcome <?= htmlspecialchars((string) $storeName, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="muted" style="margin:0;">Use each section to generate and manage SEO content manually using keywords.</p>
      </div>
      <div class="grid" id="home-stats"></div>
    </section>

    <section id="section-seo-settings" data-app-section="seo-settings" class="panel-stack" style="display:none;">
      <div class="card">
        <div class="section-head">
          <div>
            <div class="pill">General SEO Settings</div>
            <h2 style="margin:10px 0;">Global Instructions</h2>
          </div>
          <button id="save-settings-btn" class="btn btn-sky" type="button">Save Settings</button>
        </div>
        <div id="settings-alert"></div>
        <div class="grid" style="margin-top:0;">
          <div>
            <label><strong>Output Language</strong></label>
            <select id="setting-output-language">
              <option value="ar">Arabic</option>
              <option value="en">English</option>
            </select>
          </div>
          <div>
            <label><strong>Brand/Store Name</strong></label>
            <input id="setting-business-brand-name" type="text" placeholder="Brand name">
          </div>
          <div style="grid-column:1/-1;">
            <label><strong>Business Overview</strong></label>
            <textarea id="setting-business-overview" rows="3" placeholder="What do you sell? who is your audience?"></textarea>
          </div>
          <div style="grid-column:1/-1;">
            <label><strong>Global Instructions</strong></label>
            <textarea id="setting-global-instructions" rows="3"></textarea>
          </div>
          <div style="grid-column:1/-1;">
            <label><strong>Product Description Instructions</strong></label>
            <textarea id="setting-product-description-instructions" rows="3"></textarea>
          </div>
          <div>
            <label><strong>Meta Title Instructions</strong></label>
            <textarea id="setting-meta-title-instructions" rows="3"></textarea>
          </div>
          <div>
            <label><strong>Meta Description Instructions</strong></label>
            <textarea id="setting-meta-description-instructions" rows="3"></textarea>
          </div>
          <div>
            <label><strong>Brand SEO Instructions</strong></label>
            <textarea id="setting-brand-seo-instructions" rows="3"></textarea>
          </div>
          <div>
            <label><strong>Category SEO Instructions</strong></label>
            <textarea id="setting-category-seo-instructions" rows="3"></textarea>
          </div>
        </div>
      </div>
    </section>

    <section id="section-products-seo" data-app-section="products-seo" class="panel-stack" style="display:none;">
      <div class="card">
        <div class="section-head">
          <div><div class="pill">Products SEO</div><h2 style="margin:10px 0;">Generate Product SEO</h2></div>
          <button id="generate-product-btn" class="btn btn-sky" type="button">Generate</button>
        </div>
        <div class="grid" style="margin-top:0;">
          <div><label><strong>Product Keyword/Name</strong></label><input id="product-keyword" type="text" placeholder="Example: Running shoes for men"></div>
          <div><label><strong>Extra Context (Optional)</strong></label><input id="product-context" type="text" placeholder="Material, audience, price range..."></div>
        </div>
      </div>
      <div class="card"><h3 style="margin:0 0 10px;">Generated Products</h3><div id="products-list"></div></div>
    </section>

    <section id="section-keywords" data-app-section="keywords" class="panel-stack" style="display:none;">
      <div class="card">
        <div class="section-head">
          <div><div class="pill">Keywords</div><h2 style="margin:10px 0;">Keyword Research</h2></div>
          <button id="research-keyword-btn" class="btn btn-sky" type="button">Analyze</button>
        </div>
        <div class="grid" style="margin-top:0;">
          <div><label><strong>Keyword</strong></label><input id="keyword-input" type="text"></div>
          <div><label><strong>Device</strong></label><select id="keyword-device"><option value="desktop">Desktop</option><option value="mobile">Mobile</option></select></div>
        </div>
      </div>
      <div class="card"><h3 style="margin:0 0 10px;">Result</h3><div id="keywords-result"></div></div>
      <div class="card"><h3 style="margin:0 0 10px;">History</h3><div id="keywords-history"></div></div>
    </section>

    <section id="section-domain-seo" data-app-section="domain-seo" class="panel-stack" style="display:none;">
      <div class="card">
        <div class="section-head">
          <div><div class="pill">Domain SEO</div><h2 style="margin:10px 0;">Domain Overview</h2></div>
          <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button id="save-domain-btn" class="btn btn-secondary" type="button">Save Domain</button>
            <button id="refresh-domain-btn" class="btn btn-sky" type="button">Refresh Data</button>
          </div>
        </div>
        <div class="grid" style="margin-top:0;">
          <div><label><strong>Domain</strong></label><input id="domain-input" type="text" placeholder="example.com"></div>
          <div><label><strong>Device</strong></label><select id="domain-device"><option value="desktop">Desktop</option><option value="mobile">Mobile</option></select></div>
        </div>
      </div>
      <div class="card"><h3 style="margin:0 0 10px;">Domain Data</h3><div id="domain-result"></div></div>
    </section>

    <section id="section-brand-seo" data-app-section="brand-seo" class="panel-stack" style="display:none;">
      <div class="card">
        <div class="section-head">
          <div><div class="pill">Brands SEO</div><h2 style="margin:10px 0;">Generate Brand SEO</h2></div>
          <button id="generate-brand-btn" class="btn btn-sky" type="button">Generate</button>
        </div>
        <div class="grid" style="margin-top:0;">
          <div><label><strong>Brand Name / Keyword</strong></label><input id="brand-keyword" type="text"></div>
          <div><label><strong>Brand Context (Optional)</strong></label><input id="brand-context" type="text"></div>
        </div>
      </div>
      <div class="card"><h3 style="margin:0 0 10px;">Generated Brands</h3><div id="brands-list"></div></div>
    </section>

    <section id="section-category-seo" data-app-section="category-seo" class="panel-stack" style="display:none;">
      <div class="card">
        <div class="section-head">
          <div><div class="pill">Categories SEO</div><h2 style="margin:10px 0;">Generate Category SEO</h2></div>
          <button id="generate-category-btn" class="btn btn-sky" type="button">Generate</button>
        </div>
        <div class="grid" style="margin-top:0;">
          <div><label><strong>Category Name / Keyword</strong></label><input id="category-keyword" type="text"></div>
          <div><label><strong>Category Context (Optional)</strong></label><input id="category-context" type="text"></div>
        </div>
      </div>
      <div class="card"><h3 style="margin:0 0 10px;">Generated Categories</h3><div id="categories-list"></div></div>
    </section>

    <section id="section-operations" data-app-section="operations" class="panel-stack" style="display:none;">
      <div class="card"><h2 style="margin:0 0 10px;">Operations Log</h2><div id="operations-list"></div></div>
    </section>

    <section id="section-account-settings" data-app-section="account-settings" class="panel-stack" style="display:none;">
      <div class="card">
        <h2 style="margin:0 0 10px;">Account</h2>
        <p style="margin:0 0 8px;"><strong>Store:</strong> <?= htmlspecialchars((string) $storeName, ENT_QUOTES, 'UTF-8') ?></p>
        <p style="margin:0 0 8px;"><strong>Merchant ID:</strong> <?= htmlspecialchars((string) $merchantId, ENT_QUOTES, 'UTF-8') ?></p>
        <p style="margin:0 0 16px;"><strong>Email:</strong> <?= htmlspecialchars((string) $ownerEmail, ENT_QUOTES, 'UTF-8') ?></p>
        <a class="btn btn-secondary" href="/forgot-password">Reset Password</a>
      </div>
    </section>
  </main>
</div>

<script>
  (function () {
    var base = (document.querySelector('.dashboard-shell')?.dataset.appBasePath || '').replace(/\/+$/, '');
    var jsVersion = <?= json_encode((string) (int) @filemtime(realpath(__DIR__ . '/../../public/assets/standalone-dashboard.js'))) ?>;
    var script = document.createElement('script');
    script.src = (base || '') + '/public/assets/standalone-dashboard.js?v=' + jsVersion;
    script.async = false;
    document.body.appendChild(script);
  })();
</script>
