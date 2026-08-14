@extends('blog.layout')

@section('content')
<nav class="breadcrumb"><a href="/">خانه</a> / وبلاگ</nav>
<h1>وبلاگ پوشه</h1>
<p class="meta">مرجع فارسی نرم‌افزار املاک، CRM و مدیریت دفتر</p>

<form method="get" action="/blog/search" style="margin:1rem 0;display:flex;gap:.5rem">
    <input type="search" name="q" placeholder="جستجو در مقالات…" style="flex:1;padding:.75rem;border-radius:10px;border:1px solid #334155;background:#1e293b;color:#e2e8f0">
    <button class="btn" type="submit">جستجو</button>
</form>

@if(!empty($featured))
<section style="margin:2rem 0">
    <p class="badge">ویژه</p>
    <h2 style="margin-top:.5rem"><a href="/blog/{{ $featured->slug }}">{{ $featured->title }}</a></h2>
    @if($featured->excerpt)<p>{{ $featured->excerpt }}</p>@endif
</section>
@endif

<section>
    <h2>آخرین مقالات</h2>
    @foreach($posts as $post)
    <div class="card">
        @if($post->category_label)<span class="badge">{{ $post->category_label }}</span>@endif
        <a href="/blog/{{ $post->slug }}"><strong>{{ $post->title }}</strong></a>
        @if($post->excerpt)<p>{{ $post->excerpt }}</p>@endif
        <p class="meta">{{ $post->reading_time }} دقیقه مطالعه</p>
    </div>
    @endforeach
    <div class="meta">{{ $posts->withQueryString()->links() }}</div>
</section>

@if(isset($popular) && $popular->isNotEmpty())
<section>
    <h2>محبوب</h2>
    @foreach($popular as $post)
    <div class="card"><a href="/blog/{{ $post->slug }}">{{ $post->title }}</a></div>
    @endforeach
</section>
@endif

<section>
    <h2>دسته‌ها</h2>
    <p>
    @foreach($categories as $slug => $label)
        <a class="badge" href="/blog/category/{{ $slug }}" style="margin:4px">{{ $label }}</a>
    @endforeach
    </p>
</section>

<div class="cta">
    <p>آماده مدیریت حرفه‌ای املاک هستید؟</p>
    <p><a href="/register" class="btn">شروع ۴۸ ساعت رایگان</a></p>
</div>
@endsection
