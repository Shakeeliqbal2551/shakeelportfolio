<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\PortfolioContactController;
use App\Http\Controllers\SitemapController;
use App\Livewire\PortfolioPage;
use App\Models\Portfolio;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', [SitemapController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');
Route::get('/llms.txt', [SitemapController::class, 'llmsTxt'])->name('llms-txt');

Route::get('/', PortfolioPage::class)->name('home');

// The default portfolio is served at "/"; permanently redirect its /portfolio/{slug}
// URL to avoid indexing the same content twice under two different URLs.
Route::get('/portfolio/'.Portfolio::DEFAULT_SLUG, fn () => redirect()->route('home', status: 301));

Route::get('/portfolio/{portfolio:slug}', PortfolioPage::class)->name('portfolio.show');

Route::get('/portfolio/{portfolio:slug}/contact/log-visitor', [PortfolioContactController::class, 'logVisitor'])->name('portfolio.contact.log');
Route::post('/portfolio/{portfolio:slug}/contact/record-duration', [PortfolioContactController::class, 'recordDuration'])->name('portfolio.contact.duration');
Route::post('/portfolio/{portfolio:slug}/contact/send-email', [PortfolioContactController::class, 'sendEmail'])->name('portfolio.contact.send');

Route::get('/portfolio/'.Portfolio::DEFAULT_SLUG.'/blog', fn () => redirect()->route('blog.index', status: 301));
Route::get('/portfolio/{portfolio:slug}/blog', [BlogController::class, 'index'])->name('portfolio.blog.index');
Route::get('/blog', [BlogController::class, 'indexDefault'])->name('blog.index');

Route::get('/portfolio/'.Portfolio::DEFAULT_SLUG.'/blog/{post}', fn (string $post) => redirect()->route('blog.show', $post, status: 301));
Route::get('/portfolio/{portfolio:slug}/blog/{post}', [BlogController::class, 'show'])->name('portfolio.blog.show');
Route::get('/blog/{post}', [BlogController::class, 'showDefault'])->name('blog.show');

Route::livewire('dashboard', 'pages::dashboard-home')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/portfolio-dashboard.php';
require __DIR__.'/admin.php';
