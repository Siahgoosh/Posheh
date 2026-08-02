@extends('blog.layout')

@section('content')
<nav class="breadcrumb">
    <a href="/">خانه</a> / <a href="/blog">وبلاگ</a>
    @if($post->category_label) / <a href="/blog/category/{{ $post->category_slug }}">{{ $post->category_label }}</a>@endif
    / {{ $post->title }}
</nav>

@if($post->category_label)
<span class="badge"><a href="/blog/category/{{ $post->category_slug }}">{{ $post->category_label }}</a></span>
@endif

<h1>{{ $post->title }}</h1>
<p class="meta">
    @if($publishedJalali){{ $publishedJalali }}@endif
    @if($post->reading_time) · {{ $post->reading_time }} دقیقه مطالعه @endif
    @if($post->author_name) · {{ $post->author_name }} @endif
</p>

@if($post->cover_image)
@php
  $coverSrc = str_starts_with($post->cover_image, 'http') ? $post->cover_image : url($post->cover_image);
@endphp
<p><img src="{{ $coverSrc }}" alt="{{ $post->title }}" width="1200" height="630" loading="lazy" fetchpriority="high"></p>
@endif

<article>
    {!! $post->content !!}
</article>

@if(!empty($post->faq))
<section>
    <h2>سوالات متداول</h2>
    @foreach($post->faq as $item)
    <div class="card">
        <h3>{{ $item['question'] ?? '' }}</h3>
        <p>{{ $item['answer'] ?? '' }}</p>
    </div>
    @endforeach
</section>
@endif

@if($related->isNotEmpty())
<section>
    <h2>مقالات مرتبط</h2>
    @foreach($related as $r)
    <div class="card">
        <a href="/blog/{{ $r->slug }}"><strong>{{ $r->title }}</strong></a>
        @if($r->excerpt)<p>{{ $r->excerpt }}</p>@endif
    </div>
    @endforeach
</section>
@endif

<div class="cta">
    <p>{{ $post->cta_text ?: 'آماده مدیریت حرفه‌ای املاک هستید؟' }}</p>
    <p><a href="{{ $post->cta_url ?: '/register' }}" class="btn">شروع ۴۸ ساعت رایگان</a></p>
</div>
@endsection
