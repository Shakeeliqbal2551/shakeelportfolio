@php
    $isDefaultPortfolio = $portfolio->isDefault() || request()->attributes->has('resolvedPortfolio');
    $seoTitle = 'Blog — '.($portfolio->site_title ?: $portfolio->user?->name);
    $seoDescription = $portfolio->blog_meta_description ?: 'Articles and write-ups from '.$portfolio->user?->name;
    $seoImage = asset('img/og-default.jpg');
    $canonicalUrl = $isDefaultPortfolio ? route('blog.index') : route('portfolio.blog.index', $portfolio);
    $homeUrl = $isDefaultPortfolio ? url('/') : route('portfolio.show', $portfolio);

    $jsonLd = [
        [
            '@context' => 'https://schema.org',
            '@type' => 'Blog',
            'name' => $seoTitle,
            'description' => $seoDescription,
            'url' => $canonicalUrl,
            'author' => [
                '@type' => 'Person',
                'name' => $portfolio->user?->name,
            ],
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $homeUrl],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => $canonicalUrl],
            ],
        ],
    ];
@endphp

<x-blog.layout :portfolio="$portfolio" :seoTitle="$seoTitle" :seoDescription="$seoDescription" :seoImage="$seoImage" :canonicalUrl="$canonicalUrl" :jsonLd="$jsonLd">
    <div class="blog-list">
        <h1 class="blog-hero-title">Blog</h1>
        <p class="blog-hero-sub">Thoughts, write-ups, and lessons from building web apps.</p>

        @forelse ($posts as $post)
            <a href="{{ $isDefaultPortfolio ? route('blog.show', $post->slug) : route('portfolio.blog.show', [$portfolio->slug, $post->slug]) }}" class="post-card">
                @if ($post->featured_image_url)
                    <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}" loading="lazy">
                @endif
                <span class="post-date">{{ $post->published_at->format('F j, Y') }}</span>
                <h2>{{ $post->title }}</h2>
                @if ($post->excerpt)
                    <p>{{ $post->excerpt }}</p>
                @endif
            </a>
        @empty
            <p>No posts published yet — check back soon.</p>
        @endforelse
    </div>
</x-blog.layout>
