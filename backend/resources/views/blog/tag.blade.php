@extends('blog.layout')

@section('content')
<nav class="breadcrumb"><a href="/">خانه</a> / <a href="/blog">وبلاگ</a> / برچسب: {{ $tag->name }}</nav>
<h1>{{ $tag->name }}</h1>
@if($tag->description)<p class="meta">{{ $tag->description }}</p>@endif
@foreach($posts as $post)
<div class="card">
    <a href="/blog/{{ $post->slug }}"><strong>{{ $post->title }}</strong></a>
    @if($post->excerpt)<p>{{ $post->excerpt }}</p>@endif
</div>
@endforeach
<div class="meta">{{ $posts->links() }}</div>
@endsection
