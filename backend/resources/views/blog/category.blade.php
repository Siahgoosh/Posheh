@extends('blog.layout')

@section('content')
<nav class="breadcrumb"><a href="/">خانه</a> / <a href="/blog">وبلاگ</a> / {{ $categoryLabel }}</nav>
<h1>{{ $categoryLabel }}</h1>

@foreach($posts as $post)
<article class="card">
    <h2><a href="/blog/{{ $post->slug }}">{{ $post->title }}</a></h2>
    @if($post->excerpt)<p>{{ $post->excerpt }}</p>@endif
</article>
@endforeach
@endsection
