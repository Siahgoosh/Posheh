<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $seo['title'] }}</title>
    <meta name="description" content="{{ $seo['description'] }}">
    @if(!empty($seo['keywords'] ?? null))
    <meta name="keywords" content="{{ $seo['keywords'] }}">
    @endif
    <link rel="canonical" href="{{ $seo['url'] }}">
    @if(!empty($seo['noindex']))
    <meta name="robots" content="noindex, nofollow">
    @else
    <meta name="robots" content="index, follow, max-image-preview:large">
    @endif
    <meta property="og:title" content="{{ $seo['title'] }}">
    <meta property="og:description" content="{{ $seo['description'] }}">
    <meta property="og:url" content="{{ $seo['url'] }}">
    <meta property="og:type" content="{{ $seo['type'] ?? 'website' }}">
    <meta property="og:image" content="{{ $seo['ogImage'] }}">
    <meta property="og:locale" content="fa_IR">
    <meta property="og:site_name" content="پوشه">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seo['title'] }}">
    <meta name="twitter:description" content="{{ $seo['description'] }}">
    <meta name="twitter:image" content="{{ $seo['ogImage'] }}">
    @if(!empty($jsonLd))
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @endif
    <style>
        :root { --bg:#0f172a; --fg:#e2e8f0; --muted:#94a3b8; --primary:#6366f1; --card:#1e293b; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: Tahoma, Arial, sans-serif; background:var(--bg); color:var(--fg); line-height:1.8; }
        a { color: var(--primary); text-decoration: none; }
        a:hover { text-decoration: underline; }
        .container { max-width: 760px; margin: 0 auto; padding: 1.5rem; }
        header { border-bottom: 1px solid #334155; padding: 1rem 0; margin-bottom: 2rem; }
        .logo { font-weight: bold; font-size: 1.25rem; background: linear-gradient(135deg,#6366f1,#a855f7); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
        h1 { font-size: 2rem; line-height: 1.4; margin: 0 0 1rem; }
        h2 { font-size: 1.35rem; margin-top: 2rem; }
        .meta { color: var(--muted); font-size: 0.9rem; margin-bottom: 2rem; }
        .card { background: var(--card); border: 1px solid #334155; border-radius: 12px; padding: 1rem; margin-bottom: 1rem; }
        .badge { display:inline-block; padding:2px 10px; border-radius:999px; border:1px solid #475569; font-size:12px; margin-bottom:8px; }
        article img { max-width:100%; border-radius:12px; }
        article p { color: var(--muted); }
        footer { margin-top:3rem; padding-top:1.5rem; border-top:1px solid #334155; color:var(--muted); font-size:0.85rem; text-align:center; }
        .cta { background: rgba(99,102,241,.15); border:1px solid rgba(99,102,241,.3); border-radius:12px; padding:1.5rem; text-align:center; margin:2rem 0; }
        .btn { display:inline-block; background:var(--primary); color:#fff; padding:.75rem 1.5rem; border-radius:10px; font-weight:bold; }
        .breadcrumb { font-size:.85rem; color:var(--muted); margin-bottom:1rem; }
        .breadcrumb a { color:var(--muted); }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <a href="/" class="logo">پوشه</a>
        </header>
        @yield('content')
        <footer>
            <p>© {{ date('Y') }} پوشه — سامانه مدیریت املاک</p>
            <p><a href="/register">شروع رایگان</a> · <a href="/blog">وبلاگ</a> · <a href="/download">دانلود اپ</a></p>
        </footer>
    </div>
</body>
</html>
