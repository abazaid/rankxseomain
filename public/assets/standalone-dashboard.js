(function () {
  const shell = document.querySelector('.dashboard-shell');
  if (!shell) return;

  const appBasePath = (shell.dataset.appBasePath || '').replace(/\/+$/, '');

  const state = {
    keywordLastResult: null,
    keywordHistory: [],
    domainSeo: null,
    domainHistory: [],
  };

  function escapeHtml(value) {
    return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function formatNumber(value) {
    const n = Number(value || 0);
    return Number.isFinite(n) ? n.toLocaleString('en-US') : '0';
  }

  function formatUsd(value) {
    const n = Number(value || 0);
    if (!Number.isFinite(n)) return '$ 0.000000';
    return `$ ${n.toFixed(6)}`;
  }

  function formatDate(value) {
    if (!value) return '-';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return String(value);
    return `${date.toLocaleDateString('ar-SA')} ${date.toLocaleTimeString('ar-SA')}`;
  }

  async function apiFetch(path, options = {}) {
    const response = await fetch(`${appBasePath}/api${path}`, options);
    const data = await response.json().catch(() => ({}));
    if (!response.ok || data.success === false) {
      throw new Error(data.message || `فشل الطلب (${response.status})`);
    }
    return data;
  }

  function setNotice(rootId, message, kind = 'success') {
    const root = document.getElementById(rootId);
    if (!root) return;
    if (!message) {
      root.innerHTML = '';
      return;
    }
    root.innerHTML = `<div class="notice ${kind === 'error' ? 'error' : 'success'}">${escapeHtml(message)}</div>`;
  }

  function showSection(key) {
    document.querySelectorAll('[data-app-section]').forEach((section) => {
      section.style.display = section.dataset.appSection === key ? '' : 'none';
    });
    document.querySelectorAll('.sidebar-link').forEach((button) => {
      button.classList.toggle('is-active', button.dataset.sectionTarget === key);
    });
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

  function renderItems(rootId, items, emptyText) {
    const root = document.getElementById(rootId);
    if (!root) return;

    if (!Array.isArray(items) || items.length === 0) {
      root.innerHTML = `<div class="empty-state">${escapeHtml(emptyText)}</div>`;
      return;
    }

    root.innerHTML = items.map((item) => `
      <div class="card surface-soft" style="box-shadow:none;margin-bottom:12px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;">
          <strong>${escapeHtml(item.title || item.keyword || '-')}</strong>
          <span class="muted">${escapeHtml(formatDate(item.created_at || ''))}</span>
        </div>
        <p style="margin:10px 0 6px;line-height:1.9;">${escapeHtml(item.description || '')}</p>
        <p style="margin:0 0 4px;"><strong>Meta Title:</strong> ${escapeHtml(item.meta_title || '-')}</p>
        <p style="margin:0;"><strong>Meta Description:</strong> ${escapeHtml(item.meta_description || '-')}</p>
      </div>
    `).join('');
  }

  function renderOperations(rows) {
    const root = document.getElementById('operations-list');
    if (!root) return;

    if (!Array.isArray(rows) || rows.length === 0) {
      root.innerHTML = '<div class="empty-state">لا يوجد عمليات مسجلة حتى الآن.</div>';
      return;
    }

    root.innerHTML = `
      <div style="overflow:auto;">
        <table>
          <thead>
            <tr>
              <th>الوقت</th>
              <th>النوع</th>
              <th>الهدف</th>
              <th>الحالة</th>
            </tr>
          </thead>
          <tbody>
            ${rows.map((row) => `
              <tr>
                <td>${escapeHtml(formatDate(row.used_at || row.created_at || ''))}</td>
                <td>${escapeHtml(row.mode || '-')}</td>
                <td>${escapeHtml(row.product_name || row.product_id || '-')}</td>
                <td>${escapeHtml(row.status || '-')}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    `;
  }

  function renderKeywordResults(result) {
    const root = document.getElementById('keyword-results');
    const summary = document.getElementById('keyword-summary');
    if (!root || !summary) return;

    if (!result) {
      summary.textContent = 'أدخل كلمة مفتاحية ثم اضغط بحث.';
      root.innerHTML = '<div class="empty-state"><p class="muted" style="margin:0;">لم يتم إجراء بحث بعد.</p></div>';
      return;
    }

    const metrics = result.metrics || {};
    const trend = Array.isArray(result.trend) ? result.trend : [];
    const serpItems = Array.isArray(result.serp?.items) ? result.serp.items : [];
    const related = Array.isArray(result.related_keywords) ? result.related_keywords : [];
    const suggestions = Array.isArray(result.keyword_suggestions) ? result.keyword_suggestions : [];

    summary.textContent = `الكلمة: ${result.keyword || '-'} • ${result.country_name || '-'} • ${result.language_name || '-'} • ${result.device || '-'}`;

    const trendRows = trend.length
      ? trend.map((row) => `<tr><td>${escapeHtml(String(row.year || '-'))}/${escapeHtml(String(row.month || '-'))}</td><td>${escapeHtml(formatNumber(row.search_volume || 0))}</td></tr>`).join('')
      : '<tr><td colspan="2" class="muted">لا يوجد ترند متاح.</td></tr>';

    const serpRows = serpItems.length
      ? serpItems.map((row) => `
          <tr>
            <td>${escapeHtml(row.rank_group || '-')}</td>
            <td style="min-width:260px;white-space:normal;">${escapeHtml(row.title || '-')}</td>
            <td>${escapeHtml(row.domain || '-')}</td>
          </tr>
        `).join('')
      : '<tr><td colspan="3" class="muted">لا توجد نتائج SERP متاحة.</td></tr>';

    const relatedRows = related.slice(0, 30).map((row, idx) => `
      <tr>
        <td>${idx + 1}</td>
        <td style="min-width:240px;white-space:normal;">${escapeHtml(row.keyword || '-')}</td>
        <td>${escapeHtml(formatNumber(row.search_volume || 0))}</td>
        <td>${escapeHtml(String(row.competition_level || row.competition || '-'))}</td>
        <td>${escapeHtml(formatUsd(row.cpc || 0))}</td>
      </tr>
    `).join('') || '<tr><td colspan="5" class="muted">لا توجد كلمات مرتبطة.</td></tr>';

    const suggestionRows = suggestions.slice(0, 30).map((row, idx) => `
      <tr>
        <td>${idx + 1}</td>
        <td style="min-width:240px;white-space:normal;">${escapeHtml(row.keyword || '-')}</td>
        <td>${escapeHtml(formatNumber(row.search_volume || 0))}</td>
        <td>${escapeHtml(String(row.competition_level || row.competition || '-'))}</td>
        <td>${escapeHtml(formatUsd(row.cpc || 0))}</td>
      </tr>
    `).join('') || '<tr><td colspan="5" class="muted">لا توجد اقتراحات متاحة.</td></tr>';

    root.innerHTML = `
      <div class="grid" style="margin-top:0;">
        <div class="card surface-soft stat" style="min-height:auto;box-shadow:none;">
          <span class="stat-label">حجم البحث</span>
          <span class="stat-value">${escapeHtml(formatNumber(metrics.search_volume || 0))}</span>
        </div>
        <div class="card surface-soft stat" style="min-height:auto;box-shadow:none;">
          <span class="stat-label">المنافسة</span>
          <span class="stat-value">${escapeHtml(String(metrics.competition_level || metrics.competition || 0))}</span>
        </div>
        <div class="card surface-soft stat" style="min-height:auto;box-shadow:none;">
          <span class="stat-label">CPC</span>
          <span class="stat-value">${escapeHtml(formatUsd(metrics.cpc || 0))}</span>
        </div>
      </div>

      <div class="grid" style="margin-top:14px;">
        <div class="card surface-soft" style="box-shadow:none;">
          <h3 style="margin:0 0 10px;">ترند البحث الشهري</h3>
          <div style="overflow:auto;max-height:330px;">
            <table style="min-width:360px;">
              <thead><tr><th>الشهر</th><th>حجم البحث</th></tr></thead>
              <tbody>${trendRows}</tbody>
            </table>
          </div>
        </div>
        <div class="card surface-soft" style="box-shadow:none;">
          <h3 style="margin:0 0 10px;">أعلى نتائج البحث (SERP)</h3>
          <div style="overflow:auto;max-height:330px;">
            <table style="min-width:720px;">
              <thead><tr><th>الترتيب</th><th>العنوان</th><th>الدومين</th></tr></thead>
              <tbody>${serpRows}</tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="card surface-soft" style="box-shadow:none;margin-top:14px;">
        <h3 style="margin:0 0 10px;">الكلمات المرتبطة</h3>
        <div style="overflow:auto;max-height:360px;">
          <table style="min-width:820px;">
            <thead><tr><th>#</th><th>الكلمة</th><th>الحجم</th><th>المنافسة</th><th>CPC</th></tr></thead>
            <tbody>${relatedRows}</tbody>
          </table>
        </div>
      </div>

      <div class="card surface-soft" style="box-shadow:none;margin-top:14px;">
        <h3 style="margin:0 0 10px;">اقتراحات كلمات إضافية</h3>
        <div style="overflow:auto;max-height:360px;">
          <table style="min-width:820px;">
            <thead><tr><th>#</th><th>الكلمة</th><th>الحجم</th><th>المنافسة</th><th>CPC</th></tr></thead>
            <tbody>${suggestionRows}</tbody>
          </table>
        </div>
      </div>
    `;
  }

  function renderKeywordHistory(rows) {
    const root = document.getElementById('keyword-history-list');
    if (!root) return;

    if (!Array.isArray(rows) || rows.length === 0) {
      root.innerHTML = '<div class="empty-state"><p class="muted" style="margin:0;">لا يوجد سجل بحث حتى الآن.</p></div>';
      return;
    }

    root.innerHTML = rows.slice(0, 30).map((row, index) => `
      <details class="card surface-soft" style="box-shadow:none;">
        <summary style="cursor:pointer;display:flex;justify-content:space-between;gap:10px;align-items:center;">
          <strong>${escapeHtml(row.keyword || '-')}</strong>
          <span class="muted">${escapeHtml(formatDate(row.created_at || ''))}</span>
        </summary>
        <div style="margin-top:10px;">
          <p class="muted" style="margin:0 0 10px;">${escapeHtml(row.country || '-')} • ${escapeHtml(row.device || '-')}</p>
          <button class="btn btn-secondary" type="button" data-keyword-history-index="${index}">عرض التقرير</button>
        </div>
      </details>
    `).join('');

    root.querySelectorAll('[data-keyword-history-index]').forEach((button) => {
      button.addEventListener('click', () => {
        const idx = Number(button.dataset.keywordHistoryIndex || -1);
        if (idx >= 0 && rows[idx] && rows[idx].result) {
          state.keywordLastResult = rows[idx].result;
          renderKeywordResults(state.keywordLastResult);
          setNotice('keyword-alert', 'تم تحميل التقرير من السجل.');
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }
      });
    });
  }

  function renderDomainResults(payload) {
    const root = document.getElementById('domain-seo-results');
    const summary = document.getElementById('domain-seo-summary');
    if (!root || !summary) return;

    if (!payload || !payload.last_data) {
      summary.textContent = 'احفظ الدومين واضغط تحديث البيانات.';
      root.innerHTML = '<div class="empty-state"><p class="muted" style="margin:0;">لا توجد بيانات دومين محفوظة بعد.</p></div>';
      return;
    }

    const data = payload.last_data || {};
    const overview = data.overview || {};
    const organic = overview.organic || {};
    const paid = overview.paid || {};
    const competitors = Array.isArray(data.competitors) ? data.competitors : [];
    const topKeywords = Array.isArray(data.top_keywords) ? data.top_keywords : [];
    const allKeywords = Array.isArray(data.all_keywords) ? data.all_keywords : [];
    const refreshedAt = payload.refreshed_at || data.fetched_at || '';

    summary.textContent = `الدومين: ${payload.domain || data.domain || '-'} • ${payload.device || data.device || '-'} • آخر تحديث: ${formatDate(refreshedAt)}`;

    const competitorsRows = competitors.map((row, idx) => `
      <tr>
        <td>${idx + 1}</td>
        <td>${escapeHtml(row.domain || '-')}</td>
        <td>${escapeHtml(formatNumber(row.intersections || 0))}</td>
        <td>${escapeHtml(formatNumber(row.avg_position || 0))}</td>
        <td>${escapeHtml(formatNumber(row.organic_keywords || 0))}</td>
      </tr>
    `).join('') || '<tr><td colspan="5" class="muted">لا توجد بيانات منافسين.</td></tr>';

    const topKeywordRows = topKeywords.map((row, idx) => `
      <tr>
        <td>${idx + 1}</td>
        <td style="min-width:220px;white-space:normal;">${escapeHtml(row.keyword || '-')}</td>
        <td>${escapeHtml(formatNumber(row.position || 0))}</td>
        <td>${escapeHtml(formatNumber(row.search_volume || 0))}</td>
        <td>${escapeHtml(formatUsd(row.cpc || 0))}</td>
        <td>${escapeHtml(row.intent || '-')}</td>
      </tr>
    `).join('') || '<tr><td colspan="6" class="muted">لا توجد كلمات مرتبة.</td></tr>';

    const allKeywordRows = allKeywords.map((row, idx) => `
      <tr>
        <td>${idx + 1}</td>
        <td style="min-width:220px;white-space:normal;">${escapeHtml(row.keyword || '-')}</td>
        <td>${escapeHtml(formatNumber(row.position || 0))}</td>
        <td>${escapeHtml(formatNumber(row.search_volume || 0))}</td>
        <td>${escapeHtml(formatUsd(row.cpc || 0))}</td>
        <td>${escapeHtml(row.intent || '-')}</td>
      </tr>
    `).join('') || '<tr><td colspan="6" class="muted">لا توجد بيانات إضافية.</td></tr>';

    root.innerHTML = `
      <div class="grid" style="margin-top:0;">
        <div class="card surface-soft stat" style="min-height:auto;box-shadow:none;">
          <span class="stat-label">Organic Keywords</span>
          <span class="stat-value">${escapeHtml(formatNumber(organic.keywords_count || 0))}</span>
        </div>
        <div class="card surface-soft stat" style="min-height:auto;box-shadow:none;">
          <span class="stat-label">Organic Traffic</span>
          <span class="stat-value">${escapeHtml(formatNumber(organic.traffic || 0))}</span>
        </div>
        <div class="card surface-soft stat" style="min-height:auto;box-shadow:none;">
          <span class="stat-label">Organic Cost</span>
          <span class="stat-value">${escapeHtml(formatUsd(organic.traffic_cost || 0))}</span>
        </div>
        <div class="card surface-soft stat" style="min-height:auto;box-shadow:none;">
          <span class="stat-label">Paid Keywords</span>
          <span class="stat-value">${escapeHtml(formatNumber(paid.keywords_count || 0))}</span>
        </div>
        <div class="card surface-soft stat" style="min-height:auto;box-shadow:none;">
          <span class="stat-label">Paid Traffic</span>
          <span class="stat-value">${escapeHtml(formatNumber(paid.traffic || 0))}</span>
        </div>
        <div class="card surface-soft stat" style="min-height:auto;box-shadow:none;">
          <span class="stat-label">Paid Cost</span>
          <span class="stat-value">${escapeHtml(formatUsd(paid.traffic_cost || 0))}</span>
        </div>
      </div>

      <div class="card surface-soft" style="box-shadow:none;margin-top:14px;">
        <h3 style="margin:0 0 10px;">أهم الكلمات المرتبة</h3>
        <div style="overflow:auto;max-height:360px;">
          <table style="min-width:860px;">
            <thead><tr><th>#</th><th>الكلمة</th><th>الترتيب</th><th>الحجم</th><th>CPC</th><th>النية</th></tr></thead>
            <tbody>${topKeywordRows}</tbody>
          </table>
        </div>
        <details style="margin-top:12px;">
          <summary style="cursor:pointer;">استعراض كل الكلمات (${escapeHtml(formatNumber(allKeywords.length))})</summary>
          <div style="overflow:auto;max-height:360px;margin-top:8px;">
            <table style="min-width:860px;">
              <thead><tr><th>#</th><th>الكلمة</th><th>الترتيب</th><th>الحجم</th><th>CPC</th><th>النية</th></tr></thead>
              <tbody>${allKeywordRows}</tbody>
            </table>
          </div>
        </details>
      </div>

      <div class="card surface-soft" style="box-shadow:none;margin-top:14px;">
        <h3 style="margin:0 0 10px;">أهم المنافسين</h3>
        <div style="overflow:auto;max-height:360px;">
          <table style="min-width:760px;">
            <thead><tr><th>#</th><th>الدومين</th><th>تقاطع الكلمات</th><th>متوسط الترتيب</th><th>Organic Keywords</th></tr></thead>
            <tbody>${competitorsRows}</tbody>
          </table>
        </div>
      </div>
    `;
  }

  function renderDomainHistory(rows) {
    const root = document.getElementById('domain-seo-history-list');
    if (!root) return;

    if (!Array.isArray(rows) || rows.length === 0) {
      root.innerHTML = '<div class="empty-state"><p class="muted" style="margin:0;">لا يوجد سجل تحليل للدومين حتى الآن.</p></div>';
      return;
    }

    root.innerHTML = rows.slice(0, 20).map((row, index) => `
      <details class="card surface-soft" style="box-shadow:none;">
        <summary style="cursor:pointer;display:flex;justify-content:space-between;gap:10px;align-items:center;">
          <strong>${escapeHtml(row.domain || '-')}</strong>
          <span class="muted">${escapeHtml(formatDate(row.created_at || ''))}</span>
        </summary>
        <div style="margin-top:10px;">
          <p class="muted" style="margin:0 0 10px;">الجهاز: ${escapeHtml(row.device || '-')}</p>
          <button class="btn btn-secondary" type="button" data-domain-history-index="${index}">عرض التقرير</button>
        </div>
      </details>
    `).join('');

    root.querySelectorAll('[data-domain-history-index]').forEach((button) => {
      button.addEventListener('click', () => {
        const idx = Number(button.dataset.domainHistoryIndex || -1);
        if (idx >= 0 && rows[idx]) {
          const selected = rows[idx];
          const payload = {
            domain: selected.domain || '',
            device: selected.device || 'desktop',
            refreshed_at: selected.created_at || '',
            last_data: selected.result || null,
          };
          renderDomainResults(payload);
          setNotice('domain-seo-alert', 'تم تحميل تقرير من السجل.');
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }
      });
    });
  }

  async function loadHome() {
    const root = document.getElementById('home-stats');
    if (!root) return;
    try {
      const data = await apiFetch('/subscription');
      const sub = data.subscription || {};
      root.innerHTML = `
        <div class="card surface-soft stat">
          <span class="stat-label">الباقة</span>
          <span class="stat-value" style="font-size:26px;">${escapeHtml(sub.plan_name || '-')}</span>
        </div>
        <div class="card surface-soft stat">
          <span class="stat-label">الحالة</span>
          <span class="stat-value" style="font-size:26px;">${escapeHtml(sub.status || '-')}</span>
        </div>
        <div class="card surface-soft stat">
          <span class="stat-label">الاستهلاك</span>
          <span class="stat-value">${escapeHtml(formatNumber(sub.used_products || 0))} / ${escapeHtml(formatNumber(sub.product_quota || 0))}</span>
        </div>
      `;
    } catch (error) {
      root.innerHTML = `<div class="notice error">${escapeHtml(error.message)}</div>`;
    }
  }

  async function loadSettings() {
    try {
      const data = await apiFetch('/settings');
      fillSettings(data.settings || {});
    } catch (error) {
      setNotice('settings-alert', error.message, 'error');
    }
  }

  async function saveSettings() {
    try {
      const payload = readSettings();
      const data = await apiFetch('/settings/save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      fillSettings(data.settings || payload);
      setNotice('settings-alert', data.message || 'تم حفظ إعدادات السيو بنجاح.');
    } catch (error) {
      setNotice('settings-alert', error.message, 'error');
    }
  }

  async function loadItems(type, rootId) {
    try {
      const data = await apiFetch(`/items?type=${encodeURIComponent(type)}`);
      const mapEmpty = {
        product: 'لا يوجد منتجات مولدة حتى الآن.',
        brand: 'لا يوجد ماركات مولدة حتى الآن.',
        category: 'لا يوجد تصنيفات مولدة حتى الآن.',
      };
      renderItems(rootId, data.items || [], mapEmpty[type] || 'لا يوجد عناصر.');
    } catch (error) {
      renderItems(rootId, [], 'تعذر تحميل البيانات.');
    }
  }

  async function generateItem(type, keywordId, contextId, rootId) {
    const keyword = (document.getElementById(keywordId)?.value || '').trim();
    const context = (document.getElementById(contextId)?.value || '').trim();
    if (!keyword) {
      alert('يرجى إدخال الكلمة المفتاحية أولًا.');
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

  async function searchKeywordResearch() {
    const keyword = (document.getElementById('keyword-query')?.value || '').trim();
    const country = document.getElementById('keyword-country')?.value || 'sa';
    const language = document.getElementById('keyword-language')?.value || 'ar';
    const device = document.getElementById('keyword-device')?.value || 'desktop';

    if (!keyword) {
      setNotice('keyword-alert', 'يرجى كتابة كلمة مفتاحية أولًا.', 'error');
      return;
    }

    setNotice('keyword-alert', 'جاري تحليل الكلمة المفتاحية...');
    try {
      const data = await apiFetch('/keywords/research', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ keyword, country, language, device }),
      });
      state.keywordLastResult = data.result || null;
      renderKeywordResults(state.keywordLastResult);
      await loadKeywordHistory();
      await loadHome();
      setNotice('keyword-alert', 'تم تحليل الكلمة المفتاحية بنجاح.');
    } catch (error) {
      setNotice('keyword-alert', error.message, 'error');
    }
  }

  async function loadKeywordHistory() {
    try {
      const data = await apiFetch('/keywords/history');
      state.keywordHistory = Array.isArray(data.history) ? data.history : [];
      renderKeywordHistory(state.keywordHistory);
    } catch (error) {
      renderKeywordHistory([]);
    }
  }

  async function loadDomainSeo() {
    try {
      const data = await apiFetch('/domain-seo');
      state.domainSeo = data.domain_seo || null;
      const seo = state.domainSeo || {};
      const domainInput = document.getElementById('domain-seo-domain');
      const countryInput = document.getElementById('domain-seo-country');
      const deviceInput = document.getElementById('domain-seo-device');
      if (domainInput) domainInput.value = seo.domain || '';
      if (countryInput) countryInput.value = seo.country || 'sa';
      if (deviceInput) deviceInput.value = seo.device || 'desktop';
      renderDomainResults(seo);
    } catch (error) {
      renderDomainResults(null);
    }
  }

  async function saveDomainSeo() {
    const domain = (document.getElementById('domain-seo-domain')?.value || '').trim();
    const country = document.getElementById('domain-seo-country')?.value || 'sa';
    const device = document.getElementById('domain-seo-device')?.value || 'desktop';

    if (!domain) {
      setNotice('domain-seo-alert', 'يرجى إدخال الدومين أولًا.', 'error');
      return;
    }

    setNotice('domain-seo-alert', 'جاري حفظ بيانات الدومين...');
    try {
      const data = await apiFetch('/domain-seo/save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ domain, country, device }),
      });
      state.domainSeo = data.domain_seo || null;
      renderDomainResults(state.domainSeo);
      setNotice('domain-seo-alert', 'تم حفظ الدومين بنجاح.');
    } catch (error) {
      setNotice('domain-seo-alert', error.message, 'error');
    }
  }

  async function refreshDomainSeo() {
    const domain = (document.getElementById('domain-seo-domain')?.value || '').trim();
    const device = document.getElementById('domain-seo-device')?.value || 'desktop';
    if (!domain) {
      setNotice('domain-seo-alert', 'يرجى إدخال الدومين أولًا.', 'error');
      return;
    }

    setNotice('domain-seo-alert', 'جاري تحديث بيانات الدومين...');
    try {
      const data = await apiFetch('/domain-seo/refresh', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ domain, device }),
      });
      state.domainSeo = data.domain_seo || null;
      renderDomainResults(state.domainSeo);
      await loadDomainHistory();
      await loadHome();
      setNotice('domain-seo-alert', 'تم تحديث بيانات الدومين بنجاح.');
    } catch (error) {
      setNotice('domain-seo-alert', error.message, 'error');
    }
  }

  async function loadDomainHistory() {
    try {
      const data = await apiFetch('/domain-seo/history');
      state.domainHistory = Array.isArray(data.history) ? data.history : [];
      renderDomainHistory(state.domainHistory);
    } catch (error) {
      renderDomainHistory([]);
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

  function bindEvents() {
    document.querySelectorAll('.sidebar-link').forEach((button) => {
      button.addEventListener('click', () => showSection(button.dataset.sectionTarget || 'home'));
    });

    document.getElementById('save-settings-btn')?.addEventListener('click', saveSettings);
    document.getElementById('generate-product-btn')?.addEventListener('click', () => generateItem('product', 'product-keyword', 'product-context', 'products-list'));
    document.getElementById('generate-brand-btn')?.addEventListener('click', () => generateItem('brand', 'brand-keyword', 'brand-context', 'brands-list'));
    document.getElementById('generate-category-btn')?.addEventListener('click', () => generateItem('category', 'category-keyword', 'category-context', 'categories-list'));

    document.getElementById('keyword-search-btn')?.addEventListener('click', searchKeywordResearch);
    document.getElementById('keyword-query')?.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') {
        event.preventDefault();
        searchKeywordResearch();
      }
    });

    document.getElementById('domain-seo-save-btn')?.addEventListener('click', saveDomainSeo);
    document.getElementById('domain-seo-refresh-btn')?.addEventListener('click', refreshDomainSeo);
    document.getElementById('domain-seo-domain')?.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') {
        event.preventDefault();
        saveDomainSeo();
      }
    });
  }

  bindEvents();
  showSection('home');

  loadHome();
  loadSettings();
  loadItems('product', 'products-list');
  loadItems('brand', 'brands-list');
  loadItems('category', 'categories-list');
  loadOperations();
  loadKeywordHistory();
  loadDomainSeo();
  loadDomainHistory();
  renderKeywordResults(null);
})();
