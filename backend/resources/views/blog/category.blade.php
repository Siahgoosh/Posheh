@extends('blog.layout')

@section('content')
<nav class="breadcrumb"><a href="/">خانه</a> / <a href="/blog">وبلاگ</a> / {{ $categoryLabel }}</nav>
<h1>{{ $categoryLabel }}</h1>
@if(!empty($categoryDescription))
<p class="meta">{{ $categoryDescription }}</p>
@endif
@forelse($posts as $post)
<div class="card">
    <a href="/blog/{{ $post->slug }}"><strong>{{ $post->title }}</strong></a>
    @if($post->excerpt)<p>{{ $post->excerpt }}</p>@endif
</div>
@empty
<p>هنوز مقاله‌ای در این دسته منتشر نشده.</p>
@endforelse
@if(method_exists($posts, 'links'))
<div class="meta">{{ $posts->links() }}</div>
@endif
@endsection
