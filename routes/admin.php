<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('admin/tenants', 'pages::admin.tenants')->name('tenants');
    Route::livewire('admin/tenants/create', 'pages::admin.tenants-create')->name('tenants.create');
    Route::livewire('admin/tenants/{user}/domains', 'pages::admin.tenant-domains')->name('tenants.domains');
});
