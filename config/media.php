<?php

/*
|--------------------------------------------------------------------------
| Media Configuration
|--------------------------------------------------------------------------
|
| Centralised media handling. Switch storage providers (public -> s3 etc.)
| by changing MEDIA_DISK in .env. No application code needs to change.
|
*/

return [

    // Disk used by App\Services\MediaService for all uploads.
    // Must be a key in config/filesystems.php "disks".
    'disk' => env('MEDIA_DISK', 'public'),

    // Sub-folders inside the disk root, by domain.
    'paths' => [
        'profile_photos'  => 'portfolio/profile-photos',
        'project_images'  => 'portfolio/projects',
        'service_icons'   => 'portfolio/services',
        'testimonials'    => 'portfolio/testimonials',
        'blog_featured'   => 'blog/featured',
        'blog_inline'     => 'blog/inline',
        'documents'       => 'portfolio/documents',
        'misc'            => 'portfolio/misc',
    ],

    // Max upload size in KB (Livewire will respect this on validation).
    'max_upload_kb' => env('MEDIA_MAX_UPLOAD_KB', 8192), // 8 MB

    // Allowed mime patterns for image uploads.
    'image_mimes' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'],

    // Allowed mime patterns for document uploads (CV / resume etc.).
    'document_mimes' => ['pdf', 'doc', 'docx'],

];
