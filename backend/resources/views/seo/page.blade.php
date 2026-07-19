<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:type" content="{{ $type ?? 'website' }}">
    <meta property="og:locale" content="fa_IR">
    @if(!empty($image))
    <meta property="og:image" content="{{ $image }}">
    @endif
    @if(!empty($jsonLd))
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @endif
    <style>
        body { font-family: Tahoma, sans-serif; max-width: 48rem; margin: 2rem auto; padding: 0 1rem; line-height: 1.8; color: #111; }
        a { color: #2563eb; }
        img { max-width: 100%; height: auto; }
    </style>
</head>
<body>
    {!! $body !!}
    <hr>
    <p><a href="{{ $canonical }}">مشاهده نسخه کامل سایت</a> | <a href="/">صفحه اصلی پوشه</a></p>
</body>
</html>
