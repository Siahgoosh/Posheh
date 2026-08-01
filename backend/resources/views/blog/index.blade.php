@extends('blog.layout')

@section('content')
<nav class="breadcrumb"><a href="/">خانه</a> / وبلاگ</nav>
<h1>وبلاگ املاک و نرم‌افزار مدیریت دفتر</h1>
<p class="meta">مرجع فارسی نرم‌افزار املاک، CRM، فایلینگ و تحول دیجیتال دفاتر املاک</p>

@foreach($posts as $post)
<article class="card">
    @if($post->category_label)
    <span class="badge"><a href="/blog/category/{{ $post->category_slug }}">{{ $post->category_label }}</a></span>
    @endif
    <h2><a href="/blog/{{ $post->slug }}">{{ $post->title }}</a></h2>
    @if($post->excerpt)<p>{{ $post->excerpt }}</p>@endif
    <p class="meta">
        @if($post->published_at){{ \Morilog\Jalali\Jalalian::fromDateTime($post->published_at)->format('Y/m/d') }}@endif
        @if($post->reading_time) · {{ $post->reading_time }} دقیقه مطالعه @endif
    </p>
</article>
@endforeach

<div class="cta">
    <p>آماده مدیریت حرفه‌ای املاک هستید؟</p>
    <p><a href="/register" class="btn">شروع ۴۸ ساعت رایگان</a></p>
</div>
@endsection
