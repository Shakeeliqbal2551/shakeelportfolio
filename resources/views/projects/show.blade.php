@php
    $seoTitle = $project->seo_title;
    $seoDescription = $project->seo_description;
    $seoImage = $project->image_url ?: ($portfolio->og_image_url ?: asset('img/og-default.jpg'));
    $canonicalUrl = request()->url();
    $isDefaultPortfolio = $portfolio->isDefault() || request()->attributes->has('resolvedPortfolio');
    $homeUrl = $isDefaultPortfolio ? url('/') : route('portfolio.show', $portfolio);
    $workIndexUrl = $homeUrl.'#portfolio';

    $jsonLd = [
        array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'CreativeWork',
            'name' => $project->title,
            'headline' => $project->seo_title,
            'description' => $project->description,
            'image' => $project->image_url,
            'url' => $canonicalUrl,
            'creator' => [
                '@type' => 'Person',
                'name' => $portfolio->user?->name,
            ],
            'keywords' => $project->tags ? implode(', ', $project->tags) : null,
            'dateModified' => $project->updated_at?->toAtomString(),
        ]),
        [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $homeUrl],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Work', 'item' => $workIndexUrl],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $project->title, 'item' => $canonicalUrl],
            ],
        ],
    ];
@endphp

<x-blog.layout :portfolio="$portfolio" :seoTitle="$seoTitle" :seoDescription="$seoDescription" :seoImage="$seoImage" :canonicalUrl="$canonicalUrl" ogType="article" :jsonLd="$jsonLd" activeNav="work">
    <article class="blog-wrap">
        <a href="{{ $workIndexUrl }}" class="back-link">&larr; Back to Work</a>

        @if ($project->category_label)
            <p style="color: var(--gold); font-size: 13px; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 12px;">{{ $project->category_label }} Development</p>
        @endif

        <h1 class="post-detail-title">{{ $project->title }}</h1>

        @if ($project->company_name || $project->your_title)
            <div class="post-detail-meta">
                {{ collect([$project->your_title, $project->company_name])->filter()->join(' · ') }}
            </div>
        @endif

        @if ($project->tags)
            <div class="case-tags">
                @foreach ($project->tags as $tag)
                    <span class="case-tag">{{ ucfirst($tag) }}</span>
                @endforeach
            </div>
        @endif

        @if ($project->image_url)
            <img src="{{ $project->image_url }}" alt="{{ $project->image_alt ?: $project->title.' — screenshot' }}" class="post-detail-image" loading="lazy">
        @endif

        <div class="post-body">
            <p>{{ $project->description }}</p>

            {!! $project->details_html !!}
        </div>

        @if ($project->external_link)
            <a rel="nofollow" target="_blank" href="{{ $project->external_link }}" class="case-visit-cta">Visit {{ $project->title }} &nbsp;→</a>
        @endif

        <div class="case-cta-box">
            <h3>Need something similar built?</h3>
            <p>I design and build custom {{ $project->category_label ? strtolower($project->category_label).' ' : '' }}systems and web applications for businesses — from management platforms to full SaaS products.</p>
            <a href="{{ $homeUrl }}#contact">Book a Free Consultation</a>
        </div>
    </article>
</x-blog.layout>
