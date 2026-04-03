(function () {
  const shell = document.querySelector('.dashboard-shell');
  if (!shell) return;

  const appBasePath = (shell.dataset.appBasePath || '').replace(/\/+$/, '');

  function escapeHtml(value) {
    return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  async function apiFetch(path, options = {}) {
    const url = `${appBasePath}/api${path}`;
    const response = await fetch(url, options);
    const data = await response.json().catch(() => ({}));
    if (!response.ok || data.success === false) {
      const message = data.message || `Request failed (${response.status})`;
      throw new Error(message);
    }
    return data;
  }

  function showSection(key) {
    document.querySelectorAll('[data-app-section]').forEach((section) => {
      section.style.display = section.dataset.appSection === key ? '' : 'none';
    });
    document.querySelectorAll('.sidebar-link').forEach((btn) => {
      btn.classList.toggle('is-active', btn.dataset.sectionTarget === key);
    });
  }

  function notice(rootId, text, kind = 'success') {
    const root = document.getElementById(rootId);
    if (!root) return;
    root.innerHTML = `<div class="notice ${kind === 'error' ? 'error' : 'success'}">${escapeHtml(text)}</div>`;
  }

  function fillSettings(settings) {
    const map = {
      'setting-output-language': settings.output_language || 'ar',
      'setting-business-brand-name': settings.business_brand_name || '',
      'setting-business-overview': settings.business_overview || '',
      'setting-global-instructions': settings.global_instructions || '',
      'setting-product-description-instructions': settings.product_description_instructions || '',
      'setting-meta-title-instructions': settings.meta_title_instructions || '',
      'setting-meta-description-instructions': settings.meta_description_instructions || '',
      'setting-brand-seo-instructions': settings.brand_seo_instructions || '',
      'setting-category-seo-instructions': settings.category_seo_instructions || '',
    };
    Object.entries(map).forEach(([id, value]) => {
      const el = document.getElementById(id);
      if (el) el.value = value;
    });
  }

  function readSettings() {
    return {
      output_language: document.getElementById('setting-output-language')?.value || 'ar',
      business_brand_name: document.getElementById('setting-business-brand-name')?.value || '',
      business_overview: document.getElementById('setting-business-overview')?.value || '',
      global_instructions: document.getElementById('setting-global-instructions')?.value || '',
      product_description_instructions: document.getElementById('setting-product-description-instructions')?.value || '',
      meta_title_instructions: document.getElementById('setting-meta-title-instructions')?.value || '',
      meta_description_instructions: document.getElementById('setting-meta-description-instructions')?.value || '',
      brand_seo_instructions: document.getElementById('setting-brand-seo-instructions')?.value || '',
      category_seo_instructions: document.getElementById('setting-category-seo-instructions')?.value || '',
    };
  }

  function renderItems(rootId, items) {
    const root = document.getElementById(rootId);
    if (!root) return;
    if (!items.length) {
      root.innerHTML = '<div class="empty-state">No generated items yet.</div>';
      return;
    }

    root.innerHTML = items.map((item) => `
      <div class="card surface-soft" style="box-shadow:none;margin-bottom:12px;">
        <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;">
          <strong>${escapeHtml(item.title || item.keyword)}</strong>
          <span class="muted">${escapeHtml(String(item.created_at || ''))}</span>
        </div>
        <p style="margin:10px 0 6px;line-height:1.8;">${escapeHtml(item.description || '')}</p>
        <p style="margin:0 0 4px;"><strong>Meta Title:</strong> ${escapeHtml(item.meta_title || '')}</p>
        <p style="margin:0;"><strong>Meta Description:</strong> ${escapeHtml(item.meta_description || '')}</p>
      </div>
    `).join('');
  }

  function renderOperations(rows) {
    const root = document.getElementById('operations-list');
    if (!root) return;
    if (!rows.length) {
      root.innerHTML = '<div class="empty-state">No operations yet.</div>';
      return;
    }
    root.innerHTML = `
      <div style="overflow:auto;">
        <table>
          <thead>
            <tr><th>Time</th><th>Mode</th><th>Target</th><th>Status</th></tr>
          </thead>
          <tbody>
            ${rows.map((row) => `
              <tr>
                <td>${escapeHtml(String(row.used_at || ''))}</td>
                <td>${escapeHtml(String(row.mode || ''))}</td>
                <td>${escapeHtml(String(row.product_name || row.product_id || '-'))}</td>
                <td>${escapeHtml(String(row.status || ''))}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>`;
  }

  function renderKeywordHistory(rows) {
    const root = document.getElementById('keywords-history');
    if (!root) return;
    if (!rows.length) {
      root.innerHTML = '<div class="empty-state">No keyword history yet.</div>';
      return;
    }
    root.innerHTML = `
      <div style="overflow:auto;">
        <table>
          <thead>
            <tr><th>Date</th><th>Keyword</th><th>Country</th><th>Device</th></tr>
          </thead>
          <tbody>
            ${rows.map((row) => `
              <tr>
                <td>${escapeHtml(String(row.created_at || ''))}</td>
                <td>${escapeHtml(String(row.keyword || ''))}</td>
                <td>${escapeHtml(String(row.country || ''))}</td>
                <td>${escapeHtml(String(row.device || ''))}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>`;
  }

  function renderKeywordResult(result) {
    const root = document.getElementById('keywords-result');
    if (!root) return;
    if (!result) {
      root.innerHTML = '<div class="empty-state">Run a keyword analysis.</div>';
      return;
    }
    const metrics = result.metrics || {};
    const related = Array.isArray(result.related_keywords) ? result.related_keywords.slice(0, 20) : [];
    root.innerHTML = `
      <div class="grid" style="margin-top:0;">
        <div class="card surface-soft stat" style="min-height:auto;box-shadow:none;"><span class="stat-label">Search Volume</span><span class="stat-value">${escapeHtml(String(metrics.search_volume || 0))}</span></div>
        <div class="card surface-soft stat" style="min-height:auto;box-shadow:none;"><span class="stat-label">Competition</span><span class="stat-value">${escapeHtml(String(metrics.competition || 0))}</span></div>
      </div>
      <div style="margin-top:12px;">
        <strong>Top Related Keywords</strong>
        <p style="margin:8px 0 0;line-height:1.9;">${escapeHtml(related.map((k) => k.keyword || '').filter(Boolean).join(' | '))}</p>
      </div>`;
  }

  function renderDomainResult(domainSeo) {
    const root = document.getElementById('domain-result');
    if (!root) return;
    const data = domainSeo?.last_data;
    if (!data) {
      root.innerHTML = '<div class="empty-state">Save a domain then refresh to fetch data.</div>';
      return;
    }
    const topKeywords = Array.isArray(data.top_keywords) ? data.top_keywords.slice(0, 10) : [];
    root.innerHTML = `
      <p style="margin:0 0 10px;"><strong>Domain:</strong> ${escapeHtml(String(data.domain || domainSeo.domain || ''))}</p>
      <p style="margin:0 0 10px;"><strong>Last refresh:</strong> ${escapeHtml(String(domainSeo.refreshed_at || ''))}</p>
      <div style="overflow:auto;">
        <table>
          <thead><tr><th>Keyword</th><th>Position</th><th>Volume</th></tr></thead>
          <tbody>
            ${topKeywords.map((row) => `
              <tr>
                <td>${escapeHtml(String(row.keyword || ''))}</td>
                <td>${escapeHtml(String(row.rank_position || '-'))}</td>
                <td>${escapeHtml(String(row.search_volume || 0))}</td>
              </tr>`).join('')}
          </tbody>
        </table>
      </div>`;
  }

  async function loadHome() {
    const statRoot = document.getElementById('home-stats');
    if (!statRoot) return;
    try {
      const sub = await apiFetch('/subscription');
      const s = sub.subscription || {};
      statRoot.innerHTML = `
        <div class="card surface-soft stat"><span class="stat-label">Plan</span><span class="stat-value" style="font-size:26px;">${escapeHtml(String(s.plan_name || '-'))}</span></div>
        <div class="card surface-soft stat"><span class="stat-label">Status</span><span class="stat-value" style="font-size:26px;">${escapeHtml(String(s.status || '-'))}</span></div>
        <div class="card surface-soft stat"><span class="stat-label">Remaining</span><span class="stat-value">${escapeHtml(String(s.remaining_products || 0))}</span></div>
      `;
    } catch (error) {
      statRoot.innerHTML = `<div class="notice error">${escapeHtml(error.message)}</div>`;
    }
  }

  async function loadSettings() {
    try {
      const data = await apiFetch('/settings');
      fillSettings(data.settings || {});
    } catch (error) {
      notice('settings-alert', error.message, 'error');
    }
  }

  async function saveSettings() {
    const payload = readSettings();
    try {
      const data = await apiFetch('/settings/save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      fillSettings(data.settings || payload);
      notice('settings-alert', data.message || 'Settings saved.');
    } catch (error) {
      notice('settings-alert', error.message, 'error');
    }
  }

  async function loadItems(type, rootId) {
    try {
      const data = await apiFetch(`/items?type=${encodeURIComponent(type)}`);
      renderItems(rootId, data.items || []);
    } catch (error) {
      renderItems(rootId, []);
    }
  }

  async function generateItem(type, keywordId, contextId, rootId) {
    const keyword = (document.getElementById(keywordId)?.value || '').trim();
    const context = (document.getElementById(contextId)?.value || '').trim();
    if (!keyword) {
      alert('Please enter a keyword/name first.');
      return;
    }
    try {
      await apiFetch('/items/generate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ type, keyword, context }),
      });
      await loadItems(type, rootId);
      await loadHome();
    } catch (error) {
      alert(error.message);
    }
  }

  async function keywordResearch() {
    const keyword = (document.getElementById('keyword-input')?.value || '').trim();
    const device = document.getElementById('keyword-device')?.value || 'desktop';
    if (!keyword) {
      alert('Please enter a keyword.');
      return;
    }
    try {
      const data = await apiFetch('/keywords/research', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ keyword, device, country: 'sa' }),
      });
      renderKeywordResult(data.result || null);
      await loadKeywordHistory();
      await loadHome();
    } catch (error) {
      alert(error.message);
    }
  }

  async function loadKeywordHistory() {
    try {
      const data = await apiFetch('/keywords/history');
      renderKeywordHistory(data.history || []);
    } catch (error) {
      renderKeywordHistory([]);
    }
  }

  async function loadDomainSeo() {
    try {
      const data = await apiFetch('/domain-seo');
      const seo = data.domain_seo || {};
      const domainInput = document.getElementById('domain-input');
      const deviceInput = document.getElementById('domain-device');
      if (domainInput) domainInput.value = seo.domain || '';
      if (deviceInput) deviceInput.value = seo.device || 'desktop';
      renderDomainResult(seo);
    } catch (error) {
      renderDomainResult(null);
    }
  }

  async function saveDomainSeo() {
    const domain = (document.getElementById('domain-input')?.value || '').trim();
    const device = document.getElementById('domain-device')?.value || 'desktop';
    try {
      await apiFetch('/domain-seo/save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ domain, device, country: 'sa' }),
      });
      await loadDomainSeo();
    } catch (error) {
      alert(error.message);
    }
  }

  async function refreshDomainSeo() {
    const domain = (document.getElementById('domain-input')?.value || '').trim();
    const device = document.getElementById('domain-device')?.value || 'desktop';
    try {
      await apiFetch('/domain-seo/refresh', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ domain, device }),
      });
      await loadDomainSeo();
      await loadHome();
    } catch (error) {
      alert(error.message);
    }
  }

  async function loadOperations() {
    try {
      const data = await apiFetch('/operations');
      renderOperations(data.operations || []);
    } catch (error) {
      renderOperations([]);
    }
  }

  document.querySelectorAll('.sidebar-link').forEach((btn) => {
    btn.addEventListener('click', () => showSection(btn.dataset.sectionTarget || 'home'));
  });

  document.getElementById('save-settings-btn')?.addEventListener('click', saveSettings);
  document.getElementById('generate-product-btn')?.addEventListener('click', () => generateItem('product', 'product-keyword', 'product-context', 'products-list'));
  document.getElementById('generate-brand-btn')?.addEventListener('click', () => generateItem('brand', 'brand-keyword', 'brand-context', 'brands-list'));
  document.getElementById('generate-category-btn')?.addEventListener('click', () => generateItem('category', 'category-keyword', 'category-context', 'categories-list'));
  document.getElementById('research-keyword-btn')?.addEventListener('click', keywordResearch);
  document.getElementById('save-domain-btn')?.addEventListener('click', saveDomainSeo);
  document.getElementById('refresh-domain-btn')?.addEventListener('click', refreshDomainSeo);

  showSection('home');
  loadHome();
  loadSettings();
  loadItems('product', 'products-list');
  loadItems('brand', 'brands-list');
  loadItems('category', 'categories-list');
  loadKeywordHistory();
  loadDomainSeo();
  loadOperations();
})();

