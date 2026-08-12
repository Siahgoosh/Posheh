@extends('blog.layout')

@section('content')
<h1>مقاله یافت نشد</h1>
<p class="meta">این آدرس موجود نیست یا حذف شده. می‌توانید جستجو کنید یا یکی از مطالب محبوب را ببینید.</p>
<form method="get" action="/blog/search" style="margin:1rem 0;display:flex;gap:.5rem">
    <input type="search" name="q" placeholder="جستجو…" style="flex:1;padding:.75rem;border-radius:10px;border:1px solid #334155;background:#1e293b;color:#e2e8f0">
    <button class="btn" type="submit">جستجو</button>
</form>
<p><a href="/blog" class="btn">بازگشت به وبلاگ</a></p>
@if(!empty($popular) && count($popular))
<section>
    <h2>مقالات محبوب</h2>
    @foreach($popular as $post)
    <div class="card"><a href="/blog/{{ $post->slug }}">{{ $post->title }}</a></div>
    @endforeach
</section>
@endif
@endsection
