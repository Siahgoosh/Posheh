@extends('blog.layout')

@section('content')
<nav class="breadcrumb"><a href="/">خانه</a> / <a href="/blog">وبلاگ</a> / نویسنده: {{ $author->name }}</nav>
<h1>{{ $author->name }}</h1>
@if($author->role)<p class="badge">{{ $author->role }}</p>@endif
@if($author->bio)<p class="meta">{{ $author->bio }}</p>@endif
@foreach($posts as $post)
<div class="card">
    <a href="/blog/{{ $post->slug }}"><strong>{{ $post->title }}</strong></a>
    @if($post->excerpt)<p>{{ $post->excerpt }}</p>@endif
</div>
@endforeach
@endsection
