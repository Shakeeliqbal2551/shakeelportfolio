<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\ProfileImage;
use App\Models\Project;
use App\Models\Testimonial;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class CompressExistingUploads extends Command
{
    protected $signature = 'images:compress-existing
        {--min-kb=150 : Only recompress files at or above this size, in KiB}';

    protected $description = 'Recompress previously dashboard-uploaded images (profile gallery, project screenshots, testimonial avatars, blog featured images) that were stored before upload-time compression was added';

    private const MAX_DIMENSION = 1600;

    private const QUALITY = 80;

    public function handle(): int
    {
        $minBytes = (int) $this->option('min-kb') * 1024;
        $manager = new ImageManager(new Driver);

        $targets = [
            [ProfileImage::class, 'image_path'],
            [Project::class, 'image_path'],
            [Testimonial::class, 'avatar_path'],
            [Post::class, 'featured_image_path'],
        ];

        $compressed = 0;

        foreach ($targets as [$modelClass, $field]) {
            /** @var Model $model */
            foreach ($modelClass::query()->whereNotNull($field)->where($field, 'like', 'portfolios/%')->get() as $model) {
                $path = $model->{$field};

                if (! Storage::disk('public')->exists($path)) {
                    continue;
                }

                $size = Storage::disk('public')->size($path);

                if ($size < $minBytes || str_ends_with($path, '.webp')) {
                    continue;
                }

                $image = $manager->decodePath(Storage::disk('public')->path($path));
                $image->scaleDown(width: self::MAX_DIMENSION, height: self::MAX_DIMENSION);
                $encoded = $image->encode(new WebpEncoder(quality: self::QUALITY));

                $newPath = dirname($path).'/'.Str::random(40).'.webp';
                Storage::disk('public')->put($newPath, (string) $encoded);

                $newSize = Storage::disk('public')->size($newPath);

                if ($newSize >= $size) {
                    Storage::disk('public')->delete($newPath);

                    continue;
                }

                Storage::disk('public')->delete($path);
                $model->{$field} = $newPath;
                $model->save();

                $compressed++;
                $this->line(sprintf(
                    '%s#%d: %s KB -> %s KB',
                    class_basename($modelClass),
                    $model->getKey(),
                    number_format($size / 1024, 1),
                    number_format($newSize / 1024, 1),
                ));
            }
        }

        $this->info("Compressed {$compressed} image(s).");

        return self::SUCCESS;
    }
}
