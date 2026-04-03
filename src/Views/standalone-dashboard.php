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
      <h3 style="margin:0 0 8px;">لوحة العميل</h3>
      <p class="muted" style="margin:0;">إدارة السيو بالكامل من مكان واحد بدون ربط مع سلة.</p>
    </div>
    <nav class="sidebar-nav">
      <button type="button" class="sidebar-link is-active" data-section-target="home">الرئيسية</button>
      <button type="button" class="sidebar-link" data-section-target="seo-settings">إعدادات السيو العامة</button>
      <button type="button" class="sidebar-link" data-section-target="products-seo">سيو المنتجات</button>
      <button type="button" class="sidebar-link" data-section-target="keywords">الكلمات المفتاحية</button>
      <button type="button" class="sidebar-link" data-section-target="domain-seo">سيو الدومين</button>
      <button type="button" class="sidebar-link" data-section-target="brand-seo">سيو الماركات</button>
      <button type="button" class="sidebar-link" data-section-target="category-seo">سيو التصنيفات</button>
      <button type="button" class="sidebar-link" data-section-target="operations">سجل العمليات</button>
      <button type="button" class="sidebar-link" data-section-target="account-settings">الحساب والإعدادات</button>
    </nav>
  </aside>

  <main class="panel-stack">
    <section id="section-home" data-app-section="home" class="panel-stack">
      <div class="card">
        <div class="pill">نظرة عامة</div>
        <h1 style="margin:12px 0 8px;">مرحبًا <?= htmlspecialchars((string) $storeName, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="muted" style="margin:0;">ابدأ من إعدادات السيو العامة ثم انتقل لتوليد المحتوى والتقارير.</p>
      </div>
      <div class="grid" id="home-stats"></div>
    </section>

    <section id="section-seo-settings" data-app-section="seo-settings" class="panel-stack" style="display:none;">
      <div class="card">
        <div class="section-head">
          <div>
            <div class="pill">إعدادات السيو العامة</div>
            <h2 style="margin:10px 0;">تعليمات التوليد</h2>
          </div>
          <button id="save-settings-btn" class="btn btn-sky" type="button">حفظ الإعدادات</button>
        </div>
        <div id="settings-alert"></div>
        <div class="grid" style="margin-top:0;">
          <div>
            <label><strong>لغة المخرجات</strong></label>
            <select id="setting-output-language">
              <option value="ar">العربية</option>
              <option value="en">English</option>
            </select>
          </div>
          <div>
            <label><strong>اسم المتجر / البراند</strong></label>
            <input id="setting-business-brand-name" type="text" placeholder="اكتب اسم المتجر">
          </div>
          <div style="grid-column:1/-1;">
            <label><strong>نبذة عن النشاط التجاري</strong></label>
            <textarea id="setting-business-overview" rows="3" placeholder="وش تبيع؟ مين جمهورك؟"></textarea>
          </div>
          <div style="grid-column:1/-1;border:1px solid #FBBF24;background:#FEF3C7;border-radius:12px;padding:12px;">
            <p style="margin:0 0 10px;color:#92400E;font-weight:700;">ربط السايت ماب (اختياري لكنه مهم)</p>
            <p style="margin:0 0 12px;color:#78350F;font-size:13px;">بدون السايت ماب، لن يتم إضافة روابط داخلية للمنتجات أثناء التوليد.</p>
            <label for="setting-sitemap-url"><strong>رابط السايت ماب</strong></label>
            <input id="setting-sitemap-url" type="url" placeholder="https://yourstore.com/sitemap.xml" style="margin-top:8px;width:100%;padding:10px;border-radius:8px;border:1px solid #D97706;">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-top:8px;color:#92400E;font-size:13px;">
              <span>الروابط المحفوظة: <strong id="setting-sitemap-links-count">0</strong></span>
              <span id="setting-sitemap-last-fetched">لم يتم الجلب بعد</span>
            </div>
            <button id="save-sitemap-settings" class="btn btn-sky" type="button" style="margin-top:12px;">حفظ روابط السايت ماب</button>
            <div id="sitemap-alert" style="margin-top:12px;"></div>
          </div>
          <div style="grid-column:1/-1;">
            <label><strong>تعليمات عامة</strong></label>
            <textarea id="setting-global-instructions" rows="3"></textarea>
          </div>
          <div style="grid-column:1/-1;">
            <label><strong>تعليمات وصف المنتج</strong></label>
            <textarea id="setting-product-description-instructions" rows="3"></textarea>
          </div>
          <div>
            <label><strong>تعليمات Meta Title</strong></label>
            <textarea id="setting-meta-title-instructions" rows="3"></textarea>
          </div>
          <div>
            <label><strong>تعليمات Meta Description</strong></label>
            <textarea id="setting-meta-description-instructions" rows="3"></textarea>
          </div>
          <div>
            <label><strong>تعليمات سيو الماركات</strong></label>
            <textarea id="setting-brand-seo-instructions" rows="3"></textarea>
          </div>
          <div>
            <label><strong>تعليمات سيو التصنيفات</strong></label>
            <textarea id="setting-category-seo-instructions" rows="3"></textarea>
          </div>
        </div>
      </div>
    </section>

    <section id="section-products-seo" data-app-section="products-seo" class="panel-stack" style="display:none;">
      <div class="card">
        <div class="section-head">
          <div><div class="pill">سيو المنتجات</div><h2 style="margin:10px 0;">توليد سيو منتج</h2></div>
          <button id="generate-product-btn" class="btn btn-sky" type="button">توليد</button>
        </div>
        <div class="grid" style="margin-top:0;">
          <div><label><strong>اسم المنتج / الكلمة المفتاحية</strong></label><input id="product-keyword" type="text" placeholder="مثال: حذاء جري رجالي"></div>
          <div><label><strong>معلومات إضافية (اختياري)</strong></label><input id="product-context" type="text" placeholder="الخامة، الجمهور، السعر..."></div>
        </div>
        <div id="product-alert"></div>
      </div>
      <div class="card"><h3 style="margin:0 0 10px;">المنتجات المولدة</h3><div id="products-list"></div></div>
    </section>

    <section id="section-keywords" data-app-section="keywords" class="panel-stack" style="display:none;">
      <div class="card">
        <div class="section-head">
          <div>
            <div class="pill">الكلمات المفتاحية</div>
            <h2 style="margin:10px 0;">بحث احترافي عن الكلمات المفتاحية</h2>
            <p class="muted" style="margin:0;">تحليل حجم البحث والمنافسة والنتائج الأولى واقتراحات مرتبطة.</p>
          </div>
          <button id="keyword-search-btn" class="btn btn-sky" type="button">بحث الكلمات المفتاحية</button>
        </div>
        <div class="grid" style="margin-top:0;">
          <div><label><strong>الكلمة المفتاحية</strong></label><input id="keyword-query" type="text" placeholder="مثال: عطور رجالية"></div>
          <div><label><strong>الدولة</strong></label><select id="keyword-country"><option value="sa">السعودية</option></select></div>
          <div><label><strong>لغة البحث</strong></label><select id="keyword-language"><option value="ar">العربية</option><option value="en">English</option></select></div>
          <div><label><strong>نوع المتصفح</strong></label><select id="keyword-device"><option value="desktop">كمبيوتر</option><option value="mobile">جوال</option></select></div>
        </div>
        <div id="keyword-alert"></div>
      </div>

      <div class="card">
        <div class="section-head">
          <div>
            <h2 style="margin:0 0 6px;">تقرير الكلمات المفتاحية</h2>
            <p id="keyword-summary" class="muted" style="margin:0;">أدخل كلمة مفتاحية ثم اضغط بحث.</p>
          </div>
        </div>
        <div id="keyword-results">
          <div class="empty-state"><p class="muted" style="margin:0;">لم يتم إجراء بحث بعد.</p></div>
        </div>
      </div>

      <div class="card">
        <div class="section-head">
          <div>
            <h2 style="margin:0 0 6px;">سجل البحث</h2>
            <p class="muted" style="margin:0;">استعراض نتائج البحث السابقة بدون استهلاك إضافي.</p>
          </div>
        </div>
        <div id="keyword-history-list" class="panel-stack">
          <div class="empty-state"><p class="muted" style="margin:0;">لا يوجد سجل بحث حتى الآن.</p></div>
        </div>
      </div>
    </section>

    <section id="section-domain-seo" data-app-section="domain-seo" class="panel-stack" style="display:none;">
      <div class="card">
        <div class="section-head">
          <div>
            <div class="pill">سيو الدومين</div>
            <h2 style="margin:10px 0;">تحليل الدومين والمنافسين</h2>
            <p class="muted" style="margin:0;">احفظ الدومين ثم حدّث البيانات وقت ما تحب، مع حفظ السجل كامل.</p>
          </div>
          <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button id="domain-seo-save-btn" class="btn btn-secondary" type="button">حفظ الدومين</button>
            <button id="domain-seo-refresh-btn" class="btn btn-sky" type="button">تحديث البيانات</button>
          </div>
        </div>
        <div class="grid" style="margin-top:0;">
          <div><label><strong>الدومين</strong></label><input id="domain-seo-domain" type="text" placeholder="example.com"></div>
          <div><label><strong>الدولة</strong></label><select id="domain-seo-country"><option value="sa">السعودية</option></select></div>
          <div><label><strong>نوع المتصفح</strong></label><select id="domain-seo-device"><option value="desktop">كمبيوتر</option><option value="mobile">جوال</option></select></div>
        </div>
        <div id="domain-seo-alert"></div>
      </div>

      <div class="card">
        <div class="section-head">
          <div>
            <h2 style="margin:0 0 6px;">تقرير سيو الدومين</h2>
            <p id="domain-seo-summary" class="muted" style="margin:0;">احفظ الدومين واضغط تحديث البيانات.</p>
          </div>
        </div>
        <div id="domain-seo-results">
          <div class="empty-state"><p class="muted" style="margin:0;">لا توجد بيانات دومين محفوظة بعد.</p></div>
        </div>
      </div>

      <div class="card">
        <div class="section-head">
          <div>
            <h2 style="margin:0 0 6px;">سجل تحليل الدومين</h2>
            <p class="muted" style="margin:0;">كل نتائج التحديثات السابقة محفوظة هنا.</p>
          </div>
        </div>
        <div id="domain-seo-history-list" class="panel-stack">
          <div class="empty-state"><p class="muted" style="margin:0;">لا يوجد سجل تحليل للدومين حتى الآن.</p></div>
        </div>
      </div>
    </section>

    <section id="section-brand-seo" data-app-section="brand-seo" class="panel-stack" style="display:none;">
      <div class="card">
        <div class="section-head">
          <div><div class="pill">سيو الماركات</div><h2 style="margin:10px 0;">توليد سيو ماركة</h2></div>
          <button id="generate-brand-btn" class="btn btn-sky" type="button">توليد</button>
        </div>
        <div class="grid" style="margin-top:0;">
          <div><label><strong>اسم الماركة / الكلمة المفتاحية</strong></label><input id="brand-keyword" type="text"></div>
          <div><label><strong>معلومات إضافية (اختياري)</strong></label><input id="brand-context" type="text"></div>
        </div>
        <div id="brand-alert"></div>
      </div>
      <div class="card"><h3 style="margin:0 0 10px;">الماركات المولدة</h3><div id="brands-list"></div></div>
    </section>

    <section id="section-category-seo" data-app-section="category-seo" class="panel-stack" style="display:none;">
      <div class="card">
        <div class="section-head">
          <div><div class="pill">سيو التصنيفات</div><h2 style="margin:10px 0;">توليد سيو تصنيف</h2></div>
          <button id="generate-category-btn" class="btn btn-sky" type="button">توليد</button>
        </div>
        <div class="grid" style="margin-top:0;">
          <div><label><strong>اسم التصنيف / الكلمة المفتاحية</strong></label><input id="category-keyword" type="text"></div>
          <div><label><strong>معلومات إضافية (اختياري)</strong></label><input id="category-context" type="text"></div>
        </div>
        <div id="category-alert"></div>
      </div>
      <div class="card"><h3 style="margin:0 0 10px;">التصنيفات المولدة</h3><div id="categories-list"></div></div>
    </section>

    <section id="section-operations" data-app-section="operations" class="panel-stack" style="display:none;">
      <div class="card"><h2 style="margin:0 0 10px;">سجل العمليات</h2><div id="operations-list"></div></div>
    </section>

    <section id="section-account-settings" data-app-section="account-settings" class="panel-stack" style="display:none;">
      <div class="card">
        <h2 style="margin:0 0 10px;">الحساب</h2>
        <p style="margin:0 0 8px;"><strong>اسم المتجر:</strong> <?= htmlspecialchars((string) $storeName, ENT_QUOTES, 'UTF-8') ?></p>
        <p style="margin:0 0 8px;"><strong>Merchant ID:</strong> <?= htmlspecialchars((string) $merchantId, ENT_QUOTES, 'UTF-8') ?></p>
        <p style="margin:0 0 16px;"><strong>البريد:</strong> <?= htmlspecialchars((string) $ownerEmail, ENT_QUOTES, 'UTF-8') ?></p>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
          <a class="btn btn-secondary" href="/forgot-password">استرجاع كلمة المرور</a>
          <a class="btn" href="/logout">تسجيل الخروج</a>
        </div>
      </div>
    </section>
  </main>

  <div id="generated-result-modal" class="modal-backdrop" aria-hidden="true">
    <div class="modal">
      <div class="modal-head">
        <div>
          <div class="pill" id="generated-result-type">نتيجة التوليد</div>
          <h2 id="generated-result-title" style="margin:10px 0 6px;">-</h2>
          <p id="generated-result-date" class="muted" style="margin:0;">-</p>
        </div>
        <button id="generated-result-close" class="btn btn-secondary" type="button">إغلاق</button>
      </div>
      <div class="panel-stack">
        <div class="card surface-soft" style="box-shadow:none;">
          <div class="section-head" style="margin-bottom:10px;">
            <h3 style="margin:0;">وصف المحتوى</h3>
            <button class="btn btn-secondary" type="button" data-copy-target="generated-result-description">نسخ الوصف</button>
          </div>
          <textarea id="generated-result-description" rows="10" readonly></textarea>
        </div>
        <div class="grid" style="margin-top:0;">
          <div class="card surface-soft" style="box-shadow:none;">
            <div class="section-head" style="margin-bottom:10px;">
              <h3 style="margin:0;">Meta Title</h3>
              <button class="btn btn-secondary" type="button" data-copy-target="generated-result-meta-title">نسخ</button>
            </div>
            <textarea id="generated-result-meta-title" rows="4" readonly></textarea>
          </div>
          <div class="card surface-soft" style="box-shadow:none;">
            <div class="section-head" style="margin-bottom:10px;">
              <h3 style="margin:0;">Meta Description</h3>
              <button class="btn btn-secondary" type="button" data-copy-target="generated-result-meta-description">نسخ</button>
            </div>
            <textarea id="generated-result-meta-description" rows="6" readonly></textarea>
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
