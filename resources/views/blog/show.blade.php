@php
    $seoTitle = $post->meta_title ?: $post->title;
    $seoDescription = $post->meta_description ?: $post->excerpt;
    $seoImage = $post->featured_image_url;
    $canonicalUrl = request()->url();
@endphp

<x-blog.layout :portfolio="$portfolio" :seoTitle="$seoTitle" :seoDescription="$seoDescription" :seoImage="$seoImage" :canonicalUrl="$canonicalUrl" ogType="article">
    <article class="blog-wrap">
        <a href="{{ route('portfolio.blog.index', $portfolio->slug) }}" class="back-link">&larr; Back to Blog</a>

        @if (! $post->isPublished())
            <p style="color: var(--gold); font-size: 13px; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 16px;">Draft preview — not publicly visible</p>
        @endif

        <h1 class="post-detail-title">{{ $post->title }}</h1>
        <div class="post-detail-meta">
            {{ $post->isPublished() ? $post->published_at->format('F j, Y') : 'Unpublished' }}
        </div>

        @if ($post->featured_image_url)
            <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}" class="post-detail-image" loading="lazy">
        @endif

        <div class="post-body">
            {!! $post->body !!}
        </div>
    </article>
</x-blog.layout>
