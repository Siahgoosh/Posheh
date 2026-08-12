@extends('blog.layout')

@section('content')
<nav class="breadcrumb"><a href="/">خانه</a> / <a href="/blog">وبلاگ</a> / جستجو</nav>
<h1>جستجو در وبلاگ</h1>
<form method="get" action="/blog/search" style="margin:1rem 0;display:flex;gap:.5rem;flex-wrap:wrap">
    <input type="search" name="q" value="{{ $query }}" placeholder="جستجو…" style="flex:1;min-width:200px;padding:.75rem;border-radius:10px;border:1px solid #334155;background:#1e293b;color:#e2e8f0">
    <button type="submit" class="btn">جستجو</button>
</form>
<p class="meta">{{ $meta['total'] ?? 0 }} نتیجه برای «{{ $query }}»</p>
@forelse($results as $item)
<div class="card">
    <a href="/blog/{{ $item['slug'] }}"><strong>{{ $item['title'] }}</strong></a>
    @if(!empty($item['excerpt']))<p>{{ $item['excerpt'] }}</p>@endif
</div>
@empty
<p>نتیجه‌ای پیدا نشد. عبارتی دیگر را امتحان کنید یا به <a href="/blog">وبلاگ</a> برگردید.</p>
@endforelse
@endsection
