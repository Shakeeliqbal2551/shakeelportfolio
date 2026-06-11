<?php

use App\Http\Controllers\PortfolioContactController;
use App\Livewire\PortfolioPage;
use Illuminate\Support\Facades\Route;

// Static (perfect design) — currently active
Route::get('/', PortfolioPage::class)->name('home');

// Dynamic (DB-driven) — toggle by uncommenting this and commenting the line above
// Route::get('/', [\App\Http\Controllers\PortfolioController::class, 'index'])->name('home');

Route::get('contact/log_visitor.php', [PortfolioContactController::class, 'logVisitor']);
Route::post('contact/send-email.php', [PortfolioContactController::class, 'sendEmail']);

Route::redirect('dashboard', 'admin')
    ->middleware(['auth'])
    ->name('dashboard');

require __DIR__.'/settings.php';
