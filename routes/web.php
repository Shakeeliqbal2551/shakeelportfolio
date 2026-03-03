<?php

use App\Http\Controllers\PortfolioContactController;
use App\Livewire\PortfolioPage;
use Illuminate\Support\Facades\Route;

Route::get('/', PortfolioPage::class)->name('home');

Route::get('contact/log_visitor.php', [PortfolioContactController::class, 'logVisitor']);
Route::post('contact/send-email.php', [PortfolioContactController::class, 'sendEmail']);

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
