<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard/portfolio/settings', 'pages::portfolio.settings')->name('portfolio.settings');
    Route::livewire('dashboard/portfolio/about', 'pages::portfolio.about')->name('portfolio.about');
    Route::livewire('dashboard/portfolio/profile-images', 'pages::portfolio.profile-images')->name('portfolio.profile-images');
    Route::livewire('dashboard/portfolio/experiences', 'pages::portfolio.experiences')->name('portfolio.experiences');
    Route::livewire('dashboard/portfolio/educations', 'pages::portfolio.educations')->name('portfolio.educations');
    Route::livewire('dashboard/portfolio/skills', 'pages::portfolio.skills')->name('portfolio.skills');
    Route::livewire('dashboard/portfolio/projects', 'pages::portfolio.projects')->name('portfolio.projects');
    Route::livewire('dashboard/portfolio/services', 'pages::portfolio.services')->name('portfolio.services');
    Route::livewire('dashboard/portfolio/testimonials', 'pages::portfolio.testimonials')->name('portfolio.testimonials');
    Route::livewire('dashboard/portfolio/posts', 'pages::portfolio.posts')->name('portfolio.posts');
    Route::livewire('dashboard/portfolio/posts/create', 'pages::portfolio.posts-edit')->name('portfolio.posts.create');
    Route::livewire('dashboard/portfolio/posts/{post}/edit', 'pages::portfolio.posts-edit')->name('portfolio.posts.edit');
    Route::livewire('dashboard/portfolio/visitors', 'pages::portfolio.visitors')->name('portfolio.visitors');
});
