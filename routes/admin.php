<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('tenants', 'pages::admin.tenants')->name('tenants');
    Route::livewire('tenants/create', 'pages::admin.tenants-create')->name('tenants.create');
    Route::livewire('tenants/{user}/credentials', 'pages::admin.tenant-credentials')->name('tenants.credentials');
    Route::livewire('tenants/{user}/domains', 'pages::admin.tenant-domains')->name('tenants.domains');
});
