# SEO & AI-Search Visibility Roadmap

**Prepared:** 13 Aug 2026
**Target domain:** shakeeliqbal.com
**Stack:** Laravel · Livewire · Blade
**Horizon:** 0–90 days, then ongoing

## Starting position

The foundations are better than most portfolio sites — a real `SitemapController`, dynamic `/robots.txt`, and full Open Graph/Twitter Card meta already ship on the portfolio and blog templates. But there's a dead static sitemap file competing with the real one, zero structured data (JSON-LD) anywhere on the site, and no content or authority strategy behind it. That combination — decent plumbing, no signal, no content — is exactly why a technically clean site still doesn't rank: Google has nothing to disambiguate who you are, and AI answer engines have nothing quotable to cite.

---

## Phase 1 — Fix what's actively broken

**Timeframe: Week 1**

Before any content or authority work, close the gaps that are actively confusing crawlers right now. These are small, mechanical, and non-negotiable — everything downstream depends on them being correct.

### Delete the stale static sitemap — Critical
`myportfolio/sitemap.xml` lists just two URLs — the homepage and a PDF resume — dated June 2025, and isn't wired to any route. Meanwhile `app/Http/Controllers/SitemapController.php` already generates a correct, live sitemap from the database at `/sitemap.xml`. If this static file is ever deployed into `public/` or submitted to Search Console by mistake, it tells Google your site has two pages. Delete it, and make sure only the dynamic route is ever submitted.

✓ No action needed on `SitemapController` or `/robots.txt` — both are correctly implemented and dynamically reflect published portfolios and posts.

### Verify the sitemap actually reaches Google — Critical
Confirm `APP_URL` in production is set to `https://shakeeliqbal.com` (not a `.test` or staging value — the local `.env` currently points at `shakeelportfolio.test`, and any `route()`/`url()` call bakes that host into canonical tags, OG URLs, and the sitemap itself). Then submit `https://shakeeliqbal.com/sitemap.xml` in Google Search Console and Bing Webmaster Tools.

### Add JSON-LD structured data — Critical
Nothing in `livewire/portfolio-page.blade.php` or `components/blog/layout.blade.php` emits `application/ld+json`. This is the single highest-leverage gap: structured data is how Google builds a Knowledge Panel–style understanding of you specifically, and it's the same machine-readable format AI crawlers (GPTBot, ClaudeBot, PerplexityBot) parse first when deciding what to cite. Add:

- **Person** schema on the portfolio homepage — name, job title, headshot, `sameAs` links to GitHub/LinkedIn/Twitter.
- **WebSite** schema with a `SearchAction` if you add on-site search.
- **BlogPosting** schema per post — headline, datePublished, dateModified, author, image.
- **BreadcrumbList** on blog posts (Home → Blog → Post).

### Set the real production canonical host — High
`$canonicalUrl = request()->url()` in both templates is request-derived, which is correct in principle — but it means any accidental access over `www.`, a staging subdomain, or HTTP will self-canonicalize to that variant instead of consolidating to one. Force HTTPS and the canonical apex/www choice at the web-server or middleware level so `request()->url()` can only ever resolve to the one true host.

---

## Phase 2 — Technical foundation

**Timeframe: Weeks 2–4**

With the crawl signal correct, tighten the technical layer that determines whether Google can render, index, and rank the pages fast — and whether AI crawlers can read them without a JS runtime.

### Crawlability for AI agents specifically

**Server-render is already correct — verify it stays that way.** *(Good sign)*
Because the public portfolio and blog views are server-rendered Blade/Livewire (not a client-side SPA shell), GPTBot, ClaudeBot, and PerplexityBot — none of which execute JavaScript — can already read the full content on first fetch. This is a real advantage over most React/Vue portfolio templates. Don't let future work move core content behind client-only Livewire interactions that require a round-trip to populate the initial HTML.

**Explicitly allow AI crawlers in robots.txt — High**
The current `SitemapController@robots` output uses a wildcard `User-agent: *` allow, which does cover AI crawlers by default — but add explicit blocks for `GPTBot`, `ClaudeBot`, `PerplexityBot`, and `Google-Extended` anyway. Being named explicitly (rather than caught by a wildcard) is what several of these bots' documentation recommends for reliable inclusion in training and retrieval.

### Performance & Core Web Vitals

**Audit image delivery on the portfolio and blog — High**
Profile images, project thumbnails, and blog post images should ship as WebP/AVIF with explicit `width`/`height` to prevent layout shift, and lazy-load everything below the fold. Run Lighthouse and PageSpeed Insights against the live homepage and one blog post; fix whatever scores under 90 on mobile.

**Heading hierarchy and alt text pass — High**
Every page needs exactly one `<h1>` that states who you are and what you do — not just your name. Audit `livewire/portfolio-page.blade.php` and each portfolio section partial for a clean H1→H2→H3 hierarchy, and confirm every `<img>` (profile photo, project screenshots, testimonial avatars) has descriptive, non-generic alt text — this doubles as content AI engines lift verbatim into answers.

**Fix mixed metadata sourcing — High**
In `portfolio-page.blade.php`, `$seoDescription` falls back to `$portfolio->about?->bio` when `meta_description` is empty — a bio written for humans rarely reads well as a 155-character search snippet. Fill in `meta_description` and `site_title` explicitly for the default portfolio and every published post rather than relying on the fallback.

---

## Phase 3 — Entity & authority signals

**Timeframe: Weeks 3–6, ongoing**

Google and AI engines both rank *entities*, not URLs. Before content volume matters, the web needs to agree, consistently, on who "Shakeel Iqbal" is and what he's known for.

| Model | Approach |
|---|---|
| Google's model — **E-E-A-T** | Experience, Expertise, Authoritativeness, Trust — scored partly via off-site corroboration |
| AI answer engines' model — **Citation frequency** | LLMs surface names/claims repeated consistently across indexed sources |

- **Claim and complete Google Business Profile** as a freelance developer/consultant if you take client work — even service-area-only profiles help local + entity signals.
- **Consistent NAP-equivalent across profiles** — same name, title, and one-line positioning on GitHub, LinkedIn, Twitter/X, and any dev directories (dev.to, Hashnode, Stack Overflow). The `sameAs` array in your new Person schema should list all of them.
- **Get listed on developer directories** — Clutch, GoodFirms, or niche Laravel/PHP directories if relevant to the services you offer. These are exactly the kind of third-party corroboration E-E-A-T rewards.
- **Guest content and backlinks** — one well-placed guest post or interview on a mid-authority dev publication (dev.to, freeCodeCamp, a Laravel-focused newsletter) outweighs a dozen low-quality directory links.
- **Testimonials with schema markup** — the portfolio already has a testimonials section (`pages/portfolio/⚡testimonials.blade.php`); wrap it in `Review`/`AggregateRating` schema so it's eligible for rich results.

---

## Phase 4 — Content strategy (the part that actually compounds)

**Timeframe: Month 2 onward, ongoing**

Technical SEO gets you indexed. Content is what gets you ranked and cited. The blog infrastructure already exists (`BlogController`, `blog/index.blade.php`, `blog/show.blade.php`) — it just needs a publishing cadence and a topic strategy built for how both Google and AI answer engines actually retrieve content in 2026.

### What to write

- **Problem-first, not topic-first titles.** "How I fixed N+1 queries in a Livewire component" beats "Laravel Performance Tips" — it matches the specific phrasing people actually type into Google and ask ChatGPT/Claude/Perplexity.
- **Case studies of your own projects.** A written breakdown of a real build — decisions, trade-offs, what broke — is the single best-performing content type for both rankings and AI citation, because it's the one thing generic AI-written content can't fabricate: specific, verifiable experience.
- **Answer-shaped structure.** Open each post with a direct 2–3 sentence answer to the implied question, then expand. This is the paragraph shape both Google's featured snippets and AI answer engines extract verbatim.
- **Update, don't just publish.** Revisit and refresh your best 3–5 posts every 6 months with a genuinely updated `dateModified` — freshness is a ranking factor and a citation-recency factor for AI engines alike.

### Cadence

| Cadence | Format | Goal |
|---|---|---|
| 1 post / 2 weeks | Technical how-to or case study, 1,200–2,000 words | Long-tail ranking + AI citation surface area |
| Quarterly | Deep-dive project case study with metrics/screenshots | Backlink magnet, portfolio proof |
| Ongoing | Refresh top 5 posts by traffic (Search Console) | Defend rankings, signal freshness |

---

## Phase 5 — Measurement

**Timeframe: Ongoing from week 1**

Track leading indicators weekly, lagging indicators monthly. Don't judge this plan on traffic alone in month one — indexation and impressions move first, rankings and clicks follow weeks later.

| Metric | Where | Check |
|---|---|---|
| Pages indexed | Search Console → Pages | Weekly |
| Impressions & avg. position | Search Console → Performance | Weekly |
| Core Web Vitals | PageSpeed Insights / CrUX | Monthly |
| Referral traffic from AI engines | Analytics, filter referrers: chat.openai.com, perplexity.ai, claude.ai | Monthly |
| Brand + name citation | Manually query ChatGPT/Claude/Perplexity: "who is Shakeel Iqbal developer" | Monthly |
| Backlink count & referring domains | Search Console → Links, or Ahrefs/Ubersuggest free tier | Monthly |

---

## If you only do five things

In order. Each one unlocks or de-risks the next — don't skip ahead to content while the foundation is still wrong.

1. **This week** — Delete `myportfolio/sitemap.xml` and confirm production `APP_URL` is the real domain. *(Removes confusion)*
2. **This week** — Submit the real sitemap to Search Console and Bing Webmaster Tools. *(Enables indexing)*
3. **Week 2** — Ship Person + BlogPosting JSON-LD across the portfolio and blog templates. *(Highest leverage)*
4. **Weeks 2–4** — Fill in `meta_description`/`site_title` for the portfolio and every post; fix image performance. *(Snippet quality)*
5. **Month 2+** — Publish one case-study-style post every two weeks, answer-shaped, about real project work. *(Compounds over time)*

---

*Grounded against current shakeelportfolio codebase, Aug 2026.*
