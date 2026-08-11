<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::livewire('/', 'pages::admin.dashboard')->name('dashboard');

    // Site
    Route::livewire('site-settings',  'pages::admin.site-settings')->name('site-settings');
    Route::livewire('profile-photos', 'pages::admin.profile-photos')->name('profile-photos');

    // Projects
    Route::livewire('projects',                  'pages::admin.projects.index')->name('projects.index');
    Route::livewire('projects/categories',       'pages::admin.projects.categories')->name('projects.categories');
    Route::livewire('projects/{project}/edit',   'pages::admin.projects.edit')->name('projects.edit');
    Route::livewire('projects/create',           'pages::admin.projects.create')->name('projects.create');

    // Skills
    Route::livewire('skills',            'pages::admin.skills.index')->name('skills.index');
    Route::livewire('skills/categories', 'pages::admin.skills.categories')->name('skills.categories');

    // Experience / Education
    Route::livewire('experiences', 'pages::admin.experiences')->name('experiences');
    Route::livewire('educations',  'pages::admin.educations')->name('educations');

    // Services / Testimonials / Why Hire Me
    Route::livewire('services',     'pages::admin.services')->name('services');
    Route::livewire('testimonials', 'pages::admin.testimonials')->name('testimonials');
    Route::livewire('why-points',   'pages::admin.why-points')->name('why-points');

    // Blog
    Route::livewire('blog/posts',          'pages::admin.blog.posts')->name('blog.posts');
    Route::livewire('blog/posts/create',   'pages::admin.blog.post-editor')->name('blog.posts.create');
    Route::livewire('blog/posts/{post}/edit', 'pages::admin.blog.post-editor')->name('blog.posts.edit');
    Route::livewire('blog/categories',     'pages::admin.blog.categories')->name('blog.categories');
    Route::livewire('blog/tags',           'pages::admin.blog.tags')->name('blog.tags');

    // Inbox
    Route::livewire('contact-messages', 'pages::admin.contact-messages')->name('contact-messages');
    Route::livewire('visitor-logs',     'pages::admin.visitor-logs')->name('visitor-logs');
});
