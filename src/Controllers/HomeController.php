<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config;
use App\Support\Response;

final class HomeController
{
    public function index(): void
    {
        $appUrl = (string) Config::get('APP_URL', 'http://localhost:8000');
        $safeAppUrl = htmlspecialchars(rtrim($appUrl, '/'), ENT_QUOTES, 'UTF-8');
        $loginHref = $safeAppUrl . '/login';
        $logoSrc = 'https://rankxseo.com/logo.png';
        $faviconSrc = 'https://rankxseo.com/favicon.png';

        $html = <<<HTML
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/png" href="{$faviconSrc}">
  <link rel="apple-touch-icon" href="{$faviconSrc}">
  <meta name="theme-color" content="#3B82F6">
  <meta name="author" content="RankX SEO">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://rankxseo.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&display=swap">
  <title>تحسين محركات البحث سلة | تحسين محركات البحث SEO للمتجر الإلكتروني - RankX SEO</title>
  <meta name="description" content="RankX SEO هو تطبيق سيو سلة يساعدك على تحسين محركات البحث SEO للمتجر الإلكتروني عبر تحسين وصف المنتجات، سيو المتجر، ALT الصور، تحليل الكلمات المفتاحية والدومين، وكتابة مقالات مدونة متوافقة مع SEO.">
  <meta name="keywords" content="تحسين محركات البحث, تحسين محركات البحث seo, تحسين محركات البحث سلة, سيو سلة, تطبيق سيو, تطبيق سيو سلة, تحسين محركات البحث seo للمتجر الإلكتروني, كتابة مقالات مدونة, كتابة مقالات متوافقة مع seo, مدونة سلة seo, خدمات تحسين محركات البحث, خدمة تحسين محركات البحث, جوجل سلة, قوقل سلة">
  <meta name="robots" content="index,follow,max-image-preview:large">
  <link rel="canonical" href="{$safeAppUrl}/">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="ar_SA">
  <meta property="og:title" content="تحسين محركات البحث سلة | تحسين محركات البحث SEO - RankX SEO">
  <meta property="og:description" content="تطبيق سيو سلة عملي لتحسين محركات البحث SEO للمتجر الإلكتروني: سيو المنتجات، سيو المتجر، ALT الصور، تحليل الكلمات المفتاحية والدومين، وكتابة مقالات مدونة متوافقة مع SEO.">
  <meta property="og:url" content="{$safeAppUrl}/">
  <meta property="og:image" content="{$logoSrc}">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="تحسين محركات البحث سلة | سيو سلة - RankX SEO">
  <meta name="twitter:description" content="ابدأ تحسين محركات البحث SEO في سلة بخطوات عملية: تحسين المنتجات، سيو المتجر، ALT الصور، الكلمات المفتاحية، وكتابة مقالات المدونة.">
  <meta name="twitter:image" content="{$logoSrc}">
  <script type="application/ld+json">
  {
    "@context":"https://schema.org",
    "@type":"SoftwareApplication",
    "name":"RankX SEO",
    "applicationCategory":"BusinessApplication",
    "operatingSystem":"Web",
    "url":"{$safeAppUrl}/",
    "description":"تطبيق SEO لمتاجر سلة لتحسين وصف المنتجات، سيو المتجر، الصور والكلمات المفتاحية.",
    "inLanguage":"ar",
    "offers":[
      {"@type":"Offer","price":"5","priceCurrency":"SAR"},
      {"@type":"Offer","price":"29","priceCurrency":"SAR"},
      {"@type":"Offer","price":"79","priceCurrency":"SAR"},
      {"@type":"Offer","price":"149","priceCurrency":"SAR"}
    ]
  }
  </script>
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
      --success:#10B981;
      --success-soft:#D1FAE5;
      --danger:#EF4444;
      --danger-soft:#FEE2E2;
      --glow-primary:0 0 20px rgba(99, 102, 241, 0.35);
      --shadow:0 22px 60px rgba(15,23,42,.08);
      --shadow-soft:0 12px 28px rgba(15,23,42,.04);
    }
    *{box-sizing:border-box}
    body{
      margin:0;
      font-family:"Tajawal","Segoe UI",sans-serif;
      color:var(--ink);
      background:var(--bg);
      min-height:100vh;
    }
    .wrap{width:min(1180px,100% - 28px);margin:22px auto 42px}
    .surface{
      background:var(--surface);
      border:1px solid var(--border);
      border-radius:16px;
      box-shadow:var(--shadow);
      padding:24px;
    }
    .top{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:14px;
      margin-bottom:14px;
      flex-wrap:wrap;
    }
    .brand{
      display:flex;
      align-items:center;
      gap:14px;
      flex-wrap:wrap;
    }
    .brand img{width:min(280px,70vw);height:auto;display:block;filter:drop-shadow(0 0 20px rgba(99, 102, 241, 0.3))}
    .pill{
      display:inline-flex;
      align-items:center;
      padding:7px 12px;
      border-radius:999px;
      background:#EEF2FF;
      color:var(--primary-2);
      font-size:13px;
      font-weight:700;
      white-space:nowrap;
    }
    .hero{padding:32px 22px}
    h1{
      margin:0 0 14px;
      font-size:clamp(34px,5vw,62px);
      line-height:1.04;
      letter-spacing:-.02em;
    }
    .lead{
      margin:0;
      color:var(--muted);
      font-size:clamp(18px,2.2vw,24px);
      line-height:1.8;
      max-width:980px;
    }
    .hero-actions{
      display:flex;
      gap:12px;
      flex-wrap:wrap;
      margin-top:28px;
    }
    .btn{
      display:inline-flex;
      justify-content:center;
      align-items:center;
      min-width:170px;
      border:none;
      border-radius:12px;
      padding:13px 20px;
      font:700 18px/1 "Tajawal","Segoe UI",sans-serif;
      text-decoration:none;
      cursor:pointer;
      transition:.16s ease;
    }
    .btn-primary{
      color:#fff;
      background:var(--gradient-main);
      box-shadow:var(--glow-primary);
    }
    .btn-primary:hover{transform:translateY(-1px);box-shadow:0 0 30px rgba(99, 102, 241, 0.45)}
    .btn-secondary{
      color:#fff;
      background:var(--primary-1);
      box-shadow:0 0 10px rgba(59, 130, 246, 0.2);
    }
    .btn-secondary:hover{background:#2563EB;transform:translateY(-1px)}
    .btn-disabled{
      background:#E2E8F0;
      color:#94A3B8;
      cursor:not-allowed;
      box-shadow:none;
      pointer-events:none;
    }
    .section{margin-top:18px}
    .section h2{margin:0 0 10px;font-size:34px}
    .section p{margin:0;color:var(--muted);line-height:1.9;font-size:18px}
    .grid{
      display:grid;
      grid-template-columns:repeat(2,minmax(0,1fr));
      gap:14px;
      margin-top:18px;
    }
    .card{
      border:1px solid var(--border);
      background:var(--surface);
      border-radius:16px;
      padding:18px;
      box-shadow:var(--shadow-soft);
    }
    .card-head{
      display:flex;
      align-items:center;
      gap:10px;
      margin-bottom:10px;
    }
    .icon-badge{
      width:42px;
      height:42px;
      border-radius:12px;
      background:linear-gradient(180deg,#EEF2FF 0%, #E0E7FF 100%);
      border:1px solid #CFD8FF;
      display:grid;
      place-items:center;
      flex:0 0 auto;
    }
    .icon-badge svg{
      width:22px;
      height:22px;
      stroke:#4F46E5;
      fill:none;
      stroke-width:1.8;
      stroke-linecap:round;
      stroke-linejoin:round;
    }
    .card h3{margin:0;font-size:25px}
    .list{
      margin:12px 0 0;
      padding:0 18px 0 0;
      color:#475569;
      line-height:2;
      font-size:17px;
    }
    .kpis{
      display:grid;
      grid-template-columns:repeat(4,minmax(0,1fr));
      gap:12px;
      margin-top:18px;
    }
    .kpi{
      text-align:center;
      border:1px solid var(--border);
      border-radius:16px;
      background:var(--surface);
      padding:14px 10px;
    }
    .kpi strong{
      display:block;
      margin-top:6px;
      font-size:30px;
      background:var(--gradient-main);
      -webkit-background-clip:text;
      -webkit-text-fill-color:transparent;
      line-height:1;
    }
    .steps{
      counter-reset:step;
      display:grid;
      grid-template-columns:repeat(4,minmax(0,1fr));
      gap:12px;
      margin-top:16px;
    }
    .step{
      border:1px solid var(--border);
      border-radius:16px;
      background:var(--surface);
      padding:14px;
      position:relative;
      min-height:142px;
    }
    .step::before{
      counter-increment:step;
      content:counter(step);
      position:absolute;
      top:10px;left:10px;
      width:34px;height:34px;
      border-radius:50%;
      display:grid;place-items:center;
      font-weight:800;
      color:#fff;
      background:var(--gradient-main);
    }
    .step h4{margin:36px 0 8px;font-size:21px}
    .step p{margin:0;color:var(--muted);font-size:16px;line-height:1.8}
    .faq{
      display:grid;
      gap:10px;
      margin-top:16px;
    }
    details{
      border:1px solid var(--border);
      background:var(--surface);
      border-radius:14px;
      padding:12px 14px;
    }
    summary{
      cursor:pointer;
      font-size:19px;
      font-weight:700;
      color:#0F172A;
    }
    details p{margin:8px 0 0;color:var(--muted);font-size:16px;line-height:1.9}
    .foot{
      margin-top:32px;
      text-align:center;
      color:var(--muted);
      font-size:14px;
      padding-top:20px;
      border-top:1px solid var(--border);
    }
    .foot a{color:var(--muted);text-decoration:none;transition:color .2s}
    .foot a:hover{color:var(--primary-2)}
    @media (max-width:1040px){
      .grid,.kpis,.steps{grid-template-columns:repeat(2,minmax(0,1fr))}
    }
    @media (max-width:700px){
      .wrap{width:min(100% - 14px,1180px);margin:12px auto 28px}
      .surface{border-radius:16px;padding:14px}
      .hero{padding:12px 6px}
      h1{font-size:clamp(30px,9vw,44px)}
      .lead{font-size:17px}
      .hero-actions .btn{width:100%}
      .grid,.kpis,.steps{grid-template-columns:1fr}
      .section h2{font-size:29px}
      .card h3{font-size:22px}
    }
  </style>
</head>
<body>
  <svg aria-hidden="true" width="0" height="0" style="position:absolute;left:-9999px;overflow:hidden">
    <symbol id="i-doc" viewBox="0 0 24 24"><path d="M8 4h7l4 4v12H8z"></path><path d="M15 4v4h4"></path><path d="M10 13h7M10 17h5"></path></symbol>
    <symbol id="i-meta" viewBox="0 0 24 24"><path d="M4 12a8 8 0 1 0 16 0a8 8 0 1 0-16 0"></path><path d="M12 8v4l3 2"></path></symbol>
    <symbol id="i-brand" viewBox="0 0 24 24"><path d="M4 7h16v10H4z"></path><path d="M9 7V5h6v2"></path><path d="M8 12h8"></path></symbol>
    <symbol id="i-image" viewBox="0 0 24 24"><path d="M4 6h16v12H4z"></path><path d="M8 14l3-3l4 4l2-2l3 3"></path><path d="M9 9h.01"></path></symbol>
    <symbol id="i-keyword" viewBox="0 0 24 24"><path d="M10 14a4 4 0 1 1 2.6-7l7.4 7.4l-2 2l-1.5-1.5l-1.5 1.5l-1.8-1.8"></path><path d="M8.5 10.5h.01"></path></symbol>
    <symbol id="i-domain" viewBox="0 0 24 24"><path d="M3 12h18"></path><path d="M12 3a9 9 0 0 0 0 18"></path><path d="M12 3a9 9 0 0 1 0 18"></path><path d="M6 7.5c1.8 1 4 1.5 6 1.5s4.2-.5 6-1.5"></path><path d="M6 16.5c1.8-1 4-1.5 6-1.5s4.2.5 6 1.5"></path></symbol>
    <symbol id="i-store" viewBox="0 0 24 24"><path d="M4 8l1.5-4h13L20 8"></path><path d="M5 8h14v11H5z"></path><path d="M9 19v-5h6v5"></path></symbol>
    <symbol id="i-blog" viewBox="0 0 24 24"><path d="M5 4h14v16H5z"></path><path d="M8 8h8"></path><path d="M8 12h8"></path><path d="M8 16h5"></path></symbol>
    <symbol id="i-edit" viewBox="0 0 24 24"><path d="M4 20h4l10-10l-4-4L4 16z"></path><path d="M13.5 6.5l4 4"></path></symbol>
  </svg>
  <div class="wrap">
    <div class="surface">
      <div class="top">
        <div class="brand">
          <img src="{$logoSrc}" alt="RankX SEO" width="1200" height="400" decoding="async" fetchpriority="high">
          <span class="pill">منصة متخصصة لمتاجر سلة</span>
        </div>
      </div>

      <section class="hero">
        <h1>تحسين محركات البحث سلة لرفع الزيارات وزيادة التحويل</h1>
        <p class="lead">
          RankX SEO هو تطبيق سيو سلة يجمع أدوات تحسين محركات البحث SEO في لوحة واحدة: كتابة وصف منتجات متوافق مع SEO،
          تحسين سيو المتجر، إنشاء ALT احترافي للصور، تحليل الكلمات المفتاحية، كتابة مقالات مدونة متوافقة مع SEO، ومتابعة المنافسين
          بخطوات عملية واضحة تناسب المتاجر التي تريد نتائج حقيقية في جوجل سلة وقوقل سلة.
        </p>
        <div class="hero-actions">
          <a class="btn btn-primary" href="{$loginHref}">تسجيل الدخول</a>
          <button class="btn btn-secondary btn-disabled" type="button" aria-disabled="true">التسجيل قريبًا</button>
        </div>
      </section>
    </div>

    <section class="surface section">
      <h2>تحسين محركات البحث SEO سلة: كيف يزيد الزيارات مع الوقت؟</h2>
      <p>
        تحسين محركات البحث في سلة ليس إجراءً لحظيًا، بل عملية تراكمية تُبنى خطوة بخطوة. كل تحسين في جودة المحتوى، وضوح نية البحث، وتنظيم الصفحة
        يضيف أثرًا فوق الأثر السابق. أهم عوامل النمو: محتوى مميز ومفيد، صفحات واضحة، ووصف منتجات يخدم قرار الشراء.
        لهذا صممنا RankX SEO كتطبيق سيو سلة يربط التوليد الذكي بالتنفيذ المباشر داخل سلة، حتى يتحول تحسين محركات البحث SEO
        إلى نظام مستمر
        يرفع فرص الظهور في نتائج البحث مع كل تحديث جديد.
      </p>
      <div class="card surface-soft" style="box-shadow:none;margin-top:12px;">
        <img src="https://rankxseo.com/wp-content/uploads/2024/07/باك-لينك-سيو-خدمات-backlinks.gif" alt="مخطط يوضح نمو الزيارات تدريجيًا مع تطبيق تحسينات السيو التراكمية" loading="lazy" style="display:block;width:100%;max-width:860px;height:auto;margin:0 auto;border-radius:14px;border:1px solid var(--border);">
      </div>
      <div class="grid" style="margin-top:14px;">
        <article class="card">
          <h3>سيو سلة يبدأ من المحتوى المميز</h3>
          <p class="muted" style="margin:0;">عندما يكون المحتوى مفيدًا وواضحًا للعميل، تزيد الثقة ويقوى ترتيب الصفحات تدريجيًا في نتائج البحث.</p>
        </article>
        <article class="card">
          <h3>تطبيق سيو سلة للتنفيذ المستمر</h3>
          <p class="muted" style="margin:0;">بدل العمل المتقطع، تحصل على نظام تحسين متكامل: توليد، مراجعة، ثم حفظ مباشر داخل المتجر.</p>
        </article>
        <article class="card">
          <h3>الظهور في قوقل سلة وجوجل سلة</h3>
          <p class="muted" style="margin:0;">استهداف الكلمات الصحيحة + تحسين الصفحات الأساسية = فرص أعلى لالتقاط زيارات بحث جاهزة للشراء.</p>
        </article>
      </div>
    </section>

    <section class="surface section">
      <h2>تحسين محركات البحث SEO للمتجر الإلكتروني على سلة</h2>
      <p>
        إذا كان هدفك الوصول لزيارات أعلى من الباحثين الجاهزين للشراء، فأنت تحتاج خطة واضحة في تحسين محركات البحث seo للمتجر الإلكتروني،
        وليس فقط تعديلًا سريعًا في عنوان الصفحة. عبر RankX SEO نغطي الأساسيات المؤثرة: بنية المحتوى، جودة العبارات التسويقية،
        مواءمة نية البحث، وربط الكلمات الرئيسية بالكلمات الطويلة داخل صفحات المنتجات والمتجر.
      </p>
      <div class="grid" style="margin-top:14px;">
        <article class="card">
          <div class="card-head"><span class="icon-badge"><svg><use href="#i-meta"></use></svg></span><h3>بحث الكلمات عالي النية</h3></div>
          <ul class="list">
            <li>اختيار الكلمات التي تعبّر عن نية شراء حقيقية.</li>
            <li>موازنة حجم البحث مع مستوى المنافسة.</li>
            <li>توجيه الكلمات المناسبة لكل صفحة داخل المتجر.</li>
          </ul>
        </article>
        <article class="card">
          <div class="card-head"><span class="icon-badge"><svg><use href="#i-keyword"></use></svg></span><h3>توزيع ذكي داخل المحتوى</h3></div>
          <ul class="list">
            <li>إدخال الكلمات في عناوين H2/H3 بشكل طبيعي.</li>
            <li>تحسين Meta Title و Meta Description بما يخدم البحث.</li>
            <li>تجنب الحشو مع الحفاظ على وضوح الرسالة التسويقية.</li>
          </ul>
        </article>
        <article class="card">
          <div class="card-head"><span class="icon-badge"><svg><use href="#i-doc"></use></svg></span><h3>تحسين تراكمي مستمر</h3></div>
          <ul class="list">
            <li>كل تحديث يضيف أثرًا فوق السابق لرفع الظهور تدريجيًا.</li>
            <li>ربط داخلي بين المنتجات والأقسام لدعم الصفحات المهمة.</li>
            <li>تطوير المحتوى مع البيانات الفعلية بدل التخمين.</li>
          </ul>
        </article>
      </div>
    </section>

    <section class="surface section">
      <h2>الخدمات الأساسية داخل RankX SEO</h2>
      <p>كل خدمة مصممة لتدعم قرارًا تجاريًا واضحًا: جذب زيارات أعلى، رفع جودة الصفحة، وتحويل الزائر إلى عميل.</p>
      <div class="grid">
        <article class="card">
          <div class="card-head"><span class="icon-badge"><svg><use href="#i-doc"></use></svg></span><h3>تحسين وصف المنتجات</h3></div>
          <ul class="list">
            <li>صياغة وصف احترافي منظم بعناوين وقوائم واضحة وسهل القراءة.</li>
            <li>تحسين متوافق مع نية البحث والكلمات المفتاحية داخل سياق طبيعي.</li>
            <li>إمكانية التعديل اليدوي قبل الحفظ المباشر داخل سلة.</li>
          </ul>
        </article>
        <article class="card">
          <div class="card-head"><span class="icon-badge"><svg><use href="#i-meta"></use></svg></span><h3>تحسين SEO المنتجات</h3></div>
          <ul class="list">
            <li>Meta Title و Meta Description محسّنة لكل منتج.</li>
            <li>تحسين متوافق مع محركات البحث لرفع ظهور منتجاتك.</li>
            <li>حفظ مباشر في متجرك على سلة.</li>
          </ul>
        </article>
        <article class="card">
          <div class="card-head"><span class="icon-badge"><svg><use href="#i-brand"></use></svg></span><h3>تحسين SEO الماركات التجارية</h3></div>
          <ul class="list">
            <li>تحسين وصف و Meta Tags للماركات التجارية.</li>
            <li>رفع ظهور الماركات في نتائج البحث.</li>
            <li>حفظ مباشر في سلة.</li>
          </ul>
        </article>
        <article class="card">
          <div class="card-head"><span class="icon-badge"><svg><use href="#i-image"></use></svg></span><h3>كاتب ALT للصور</h3></div>
          <ul class="list">
            <li>إنشاء ALT واضح ودقيق لكل صورة بما يخدم تحسين الظهور في البحث المرئي.</li>
            <li>تحسين صورة واحدة، مجموعة صور، أو جميع صور المنتج بضغطة واحدة.</li>
            <li>تمييز الصور المحسنة وغير المحسنة مع سجل عمليات واضح.</li>
          </ul>
        </article>
        <article class="card">
          <div class="card-head"><span class="icon-badge"><svg><use href="#i-keyword"></use></svg></span><h3>تحليل الكلمات المفتاحية</h3></div>
          <ul class="list">
            <li>تحليل الكلمة المفتاحية: حجم البحث، المنافسة، CPC، واقتراحات مرتبطة.</li>
            <li>حفظ نتائج البحث السابقة لتقليل الهدر وتسريع العمل.</li>
          </ul>
        </article>
        <article class="card">
          <div class="card-head"><span class="icon-badge"><svg><use href="#i-domain"></use></svg></span><h3>تحليل سيو الدومين</h3></div>
          <ul class="list">
            <li>تحليل شامل لموقعك مع مؤشرات ترتيب الكلمات العضوية.</li>
            <li>مقارنة مع المنافسين في نفس المجال.</li>
          </ul>
        </article>
        <article class="card">
          <div class="card-head"><span class="icon-badge"><svg><use href="#i-store"></use></svg></span><h3>سيو المتجر</h3></div>
          <ul class="list">
            <li>توليد صياغات قوية لصفحة المتجر الرئيسية.</li>
            <li>تحسين Meta Title / Description / Keywords للمتجر.</li>
            <li>تحديث فوري من اللوحة إلى إعدادات SEO في سلة.</li>
          </ul>
        </article>
        <article class="card">
          <div class="card-head"><span class="icon-badge"><svg><use href="#i-blog"></use></svg></span><h3>كتابة مقالات المدونة (قريبًا)</h3></div>
          <ul class="list">
            <li>إنشاء مقالات متوافقة مع تحسين محركات البحث وجاهزة للنشر في مدونة سلة.</li>
            <li>التركيز على كلمة مفتاحية رئيسية وكلمات طويلة داعمة.</li>
            <li>ربط داخلي ذكي مع المنتجات والأقسام لتحسين فرص التصدر في قوقل.</li>
          </ul>
        </article>
        <article class="card">
          <div class="card-head"><span class="icon-badge"><svg><use href="#i-edit"></use></svg></span><h3>تحرير يدوي بدون AI</h3></div>
          <ul class="list">
            <li>تعديل الوصف و Meta Tags يدويًا مباشرة.</li>
            <li>لا يستهلك من رصيدك لأنه لا يستخدم الذكاء الاصطناعي.</li>
            <li>حفظ مباشر في متجرك.</li>
          </ul>
        </article>
      </div>
    </section>

    <section class="surface section">
      <h2>الباقات والأسعار</h2>
      <p>اختر الباقة المناسبة لمتجرك وابدأ بتحسين محتواك اليوم.</p>
      <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(250px,1fr));">
        <article class="card" style="border:2px solid #10B981;">
          <h3 style="color:#10B981;">🟢 التجربة الاقتصادية</h3>
          <div style="font-size:32px;font-weight:800;margin:10px 0;">5 ر.س<span style="font-size:16px;color:var(--muted);font-weight:400;">/شهر</span></div>
          <p style="color:var(--muted);font-size:14px;">جرّب المنصة بسعر رمزي</p>
          <ul class="list" style="font-size:15px;">
            <li>10 تحسين وصف منتج</li>
            <li>10 تحسين SEO منتج</li>
            <li>10 تحسين ALT صور</li>
            <li>5 كلمات مفتاحية</li>
            <li>1 تحليل دومين</li>
            <li>5 تحسين ماركات</li>
            <li>5 تحسين الأقسام</li>
          </ul>
          <a class="btn btn-primary" href="{$loginHref}" style="margin-top:12px;background:#10B981;">ابدأ الآن</a>
        </article>
        <article class="card" style="border:2px solid #3B82F6;">
          <h3 style="color:#3B82F6;">🔵 الخطة الأساسية</h3>
          <div style="font-size:32px;font-weight:800;margin:10px 0;">29 ر.س<span style="font-size:16px;color:var(--muted);font-weight:400;">/شهر</span></div>
          <p style="color:var(--muted);font-size:14px;">للمتاجر الصغيرة والمتوسطة</p>
          <ul class="list" style="font-size:15px;">
            <li>80 تحسين وصف منتج</li>
            <li>80 تحسين SEO منتج</li>
            <li>30 تحسين ALT صور</li>
            <li>10 كلمات مفتاحية</li>
            <li>3 تحليل دومين</li>
            <li>تحسين الماركات غير مفعّل (رقّي الاشتراك)</li>
            <li>تحسين الأقسام غير مفعّل (رقّي الاشتراك)</li>
          </ul>
          <a class="btn btn-primary" href="{$loginHref}" style="margin-top:12px;background:#3B82F6;">ابدأ الآن</a>
        </article>
        <article class="card" style="border:2px solid #8B5CF6;position:relative;">
          <div style="position:absolute;top:-12px;right:20px;background:var(--gradient-main);color:#fff;padding:4px 12px;border-radius:999px;font-size:12px;font-weight:700;">الأكثر شعبية ⭐</div>
          <h3 style="color:#8B5CF6;">🟣 الخطة المتقدمة</h3>
          <div style="font-size:32px;font-weight:800;margin:10px 0;">79 ر.س<span style="font-size:16px;color:var(--muted);font-weight:400;">/شهر</span></div>
          <p style="color:var(--muted);font-size:14px;">للمتاجر المتنامية</p>
          <ul class="list" style="font-size:15px;">
            <li>260 تحسين وصف منتج</li>
            <li>140 تحسين SEO منتج</li>
            <li>260 تحسين ALT صور</li>
            <li>40 كلمة مفتاحية</li>
            <li>12 تحليل دومين</li>
            <li>50 تحسين ماركات</li>
            <li>50 تحسين الأقسام</li>
            <li>+ سجل النشاطات</li>
            <li>+ تصدير البيانات</li>
            <li>+ أداء أسرع</li>
          </ul>
          <a class="btn btn-primary" href="{$loginHref}" style="margin-top:12px;background:var(--gradient-main);">ابدأ الآن</a>
        </article>
        <article class="card" style="border:2px solid #EF4444;">
          <h3 style="color:#EF4444;">🔴 الخطة الاحترافية</h3>
          <div style="font-size:32px;font-weight:800;margin:10px 0;">149 ر.س<span style="font-size:16px;color:var(--muted);font-weight:400;">/شهر</span></div>
          <p style="color:var(--muted);font-size:14px;">للمتاجر الكبيرة والوكالات</p>
          <ul class="list" style="font-size:15px;">
            <li>700 تحسين وصف منتج</li>
            <li>700 تحسين SEO منتج</li>
            <li>700 تحسين ALT صور</li>
            <li>120 كلمة مفتاحية</li>
            <li>35 تحليل دومين</li>
            <li>150 تحسين ماركات</li>
            <li>100 تحسين الأقسام</li>
            <li>+ دعم أولوي</li>
            <li>+ حدود أعلى</li>
            <li>+ كل مميزات المتقدمة</li>
          </ul>
          <a class="btn btn-primary" href="{$loginHref}" style="margin-top:12px;background:#EF4444;">ابدأ الآن</a>
        </article>
      </div>
    </section>

    <section class="surface section">
      <h2>لماذا هذه المنصة تفرق فعليًا؟</h2>
      <p>الهدف ليس فقط "إنشاء نص"، بل بناء نظام محتوى كامل يساعد متجرك على النمو بشكل مستمر.</p>
      <div class="kpis">
        <div class="kpi">واجهة موحدة<strong>9</strong>أقسام (منها قسم قريبًا)</div>
        <div class="kpi">تحسين قابل للقياس<strong>100%</strong>سجل عمليات</div>
        <div class="kpi">تحكم كامل<strong>يدوي + AI</strong>قبل الحفظ</div>
        <div class="kpi">جاهز للنشر<strong>مباشر</strong>داخل سلة</div>
      </div>
    </section>

    <section class="surface section">
      <h2>كيف تبدأ خلال دقائق؟</h2>
      <p>التدفق واضح وبسيط حتى لو فريقك غير تقني.</p>
      <div class="steps">
        <article class="step">
          <h4>دخول اللوحة</h4>
          <p>سجّل الدخول بحسابك ثم اختر القسم المناسب حسب هدفك اليومي.</p>
        </article>
        <article class="step">
          <h4>توليد التحسين</h4>
          <p>استخدم الذكاء الاصطناعي مع تعليمات متجرك للحصول على مخرجات أدق.</p>
        </article>
        <article class="step">
          <h4>مراجعة وتعديل</h4>
          <p>راجع قبل/بعد، عدّل يدويًا إذا رغبت، ثم ثبّت النسخة النهائية.</p>
        </article>
        <article class="step">
          <h4>حفظ داخل سلة</h4>
          <p>احفظ التحديث مباشرة داخل متجرك وتابع أثره من نفس اللوحة.</p>
        </article>
      </div>
    </section>

    <section class="surface section">
      <h2>أسئلة شائعة</h2>
      <div class="faq">
        <details>
          <summary>هل المنصة مناسبة لمتجر صغير أو جديد؟</summary>
          <p>نعم، لأنها تقلل وقت كتابة المحتوى وتمنحك خطوات واضحة للبدء دون تعقيد تقني. ابدأ بالخطة الاقتصادية بـ 5 ر.س فقط.</p>
        </details>
        <details>
          <summary>كيف أبدأ تحسين محركات البحث SEO للمتجر الإلكتروني في سلة؟</summary>
          <p>ابدأ بضبط سيو المتجر ثم انتقل إلى تحسين وصف المنتجات وMeta لكل صفحة. بعدها فعّل ALT الصور وتحليل الكلمات المفتاحية لبناء نتائج تراكمية.</p>
        </details>
        <details>
          <summary>ما الفرق بين تطبيق سيو سلة والعمل اليدوي التقليدي؟</summary>
          <p>العمل اليدوي غالبًا متقطع، بينما تطبيق سيو سلة يجمع التوليد والمراجعة والحفظ المباشر في نفس التدفق، فيعطي اتساقًا أعلى وسرعة تنفيذ أفضل.</p>
        </details>
        <details>
          <summary>هل RankX SEO مناسب كخدمة تحسين محركات البحث لمتاجر سلة؟</summary>
          <p>نعم، لأنه مصمم خصيصًا لمتاجر سلة ويغطي سيو المنتجات، سيو الماركات، سيو الأقسام، سيو المتجر، وتحليل الكلمات المفتاحية والدومين.</p>
        </details>
        <details>
          <summary>متى يتوفر قسم كتابة مقالات المدونة؟</summary>
          <p>القسم قيد الإطلاق قريبًا، وسيقدم كتابة مقالات متوافقة مع SEO تركز على كلمة مفتاحية رئيسية مع ربط داخلي بالمنتجات والأقسام داخل سلة.</p>
        </details>
        <details>
          <summary>هل أقدر أعدّل النص قبل الحفظ؟</summary>
          <p>أكيد. كل مخرجات AI تمر بمرحلة مراجعة كاملة قبل اعتمادها داخل سلة. كما يمكنك استخدام التحرير اليدوي بدون AI.</p>
        </details>
        <details>
          <summary>هل التحسين يشمل المنتجات فقط؟</summary>
          <p>لا، يشمل أيضًا SEO الماركات، سيو المتجر، ALT الصور، تحليل الكلمات المفتاحية، وتحليل سيو الدومين.</p>
        </details>
        <details>
          <summary>ماذا لو استهلكت رصيدي؟</summary>
          <p>ستظهر لك رسالة تنبيه. يمكنك ترقية اشتراكك للحصول على رصيد أكبر، أو الانتظار حتى تجديد الفترة التالية.</p>
        </details>
        <details>
          <summary>هل أحتاج حساب OpenAI؟</summary>
          <p>لا، التكلفة مشمولة في اشتراكك. لا حاجة لإنشاء حساب OpenAI أو شراء رصيد. نحن نتولى كل شيء.</p>
        </details>
      </div>
      <div class="hero-actions" style="margin-top:18px">
        <a class="btn btn-primary" href="{$loginHref}">ابدأ الآن عبر تسجيل الدخول</a>
        <button class="btn btn-secondary btn-disabled" type="button" aria-disabled="true">التسجيل سيتاح قريبًا</button>
      </div>
    </section>

    <div class="foot">
      <p style="margin:0 0 10px;">
        <a href="{$safeAppUrl}/" style="color:var(--muted);text-decoration:none;margin:0 8px;">الرئيسية</a>
        <a href="{$safeAppUrl}/pricing" style="color:var(--muted);text-decoration:none;margin:0 8px;">الباقات</a>
        <a href="{$safeAppUrl}/about" style="color:var(--muted);text-decoration:none;margin:0 8px;">من نحن</a>
        <a href="{$safeAppUrl}/faq" style="color:var(--muted);text-decoration:none;margin:0 8px;">الأسئلة الشائعة</a>
        <a href="{$safeAppUrl}/privacy" style="color:var(--muted);text-decoration:none;margin:0 8px;">الخصوصية</a>
        <a href="{$safeAppUrl}/terms" style="color:var(--muted);text-decoration:none;margin:0 8px;">الشروط</a>
      </p>
      <p style="margin:0;">Powered by RankX SEO | <a href="mailto:seo@rankxseo.com" style="color:var(--primary-2);">seo@rankxseo.com</a></p>
    </div>
  </div>
</body>
</html>
HTML;

        Response::html($html);
    }
}

