@php
    $seoTitle = $post->meta_title ?: $post->title;
    $seoDescription = $post->meta_description ?: $post->excerpt;
    $seoImage = $post->featured_image_url ?: ($portfolio->og_image_url ?: asset('img/og-default.jpg'));
    $canonicalUrl = request()->url();
    $publishedTime = $post->isPublished() ? $post->published_at->toAtomString() : null;
    $isDefaultPortfolio = $portfolio->isDefault() || request()->attributes->has('resolvedPortfolio');
    $homeUrl = $isDefaultPortfolio ? url('/') : route('portfolio.show', $portfolio);
    $blogIndexUrl = $isDefaultPortfolio ? route('blog.index') : route('portfolio.blog.index', $portfolio);

    $jsonLd = [
        array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post->title,
            'description' => $seoDescription,
            'image' => $seoImage,
            'url' => $canonicalUrl,
            'datePublished' => $publishedTime,
            'dateModified' => $post->updated_at?->toAtomString(),
            'author' => [
                '@type' => 'Person',
                'name' => $portfolio->user?->name,
            ],
            'mainEntityOfPage' => $canonicalUrl,
        ]),
        [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $homeUrl],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => $blogIndexUrl],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $post->title, 'item' => $canonicalUrl],
            ],
        ],
    ];
@endphp

<x-blog.layout :portfolio="$portfolio" :seoTitle="$seoTitle" :seoDescription="$seoDescription" :seoImage="$seoImage" :canonicalUrl="$canonicalUrl" ogType="article" :publishedTime="$publishedTime" :jsonLd="$jsonLd">
    <article class="blog-wrap">
        <a href="{{ $blogIndexUrl }}" class="back-link">&larr; Back to Blog</a>

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
