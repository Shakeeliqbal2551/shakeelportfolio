<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\PortfolioContactController;
use App\Http\Controllers\SitemapController;
use App\Livewire\PortfolioPage;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', [SitemapController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

Route::get('/', PortfolioPage::class)->name('home');

Route::get('/portfolio/{portfolio:slug}', PortfolioPage::class)->name('portfolio.show');

Route::get('/portfolio/{portfolio:slug}/contact/log-visitor', [PortfolioContactController::class, 'logVisitor'])->name('portfolio.contact.log');
Route::post('/portfolio/{portfolio:slug}/contact/record-duration', [PortfolioContactController::class, 'recordDuration'])->name('portfolio.contact.duration');
Route::post('/portfolio/{portfolio:slug}/contact/send-email', [PortfolioContactController::class, 'sendEmail'])->name('portfolio.contact.send');

Route::get('/portfolio/{portfolio:slug}/blog', [BlogController::class, 'index'])->name('portfolio.blog.index');
Route::get('/blog', [BlogController::class, 'indexDefault'])->name('blog.index');
Route::get('/portfolio/{portfolio:slug}/blog/{post}', [BlogController::class, 'show'])->name('portfolio.blog.show');
Route::get('/blog/{post}', [BlogController::class, 'showDefault'])->name('blog.show');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/portfolio-dashboard.php';
