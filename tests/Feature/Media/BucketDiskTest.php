<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Throwaway model used to prove the media library writes into public/bucket.
 */
class BucketDiskSample extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'bucket_disk_samples';

    protected $guarded = [];
}

beforeEach(function () {
    Schema::create('bucket_disk_samples', function (Blueprint $table) {
        $table->id();
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('bucket_disk_samples');
});

test('the media library is configured to use the bucket disk', function () {
    expect(config('media-library.disk_name'))->toBe('bucket');
    expect(config('filesystems.disks.bucket.driver'))->toBe('local');
});

test('the bucket disk points at public/bucket', function () {
    expect(config('filesystems.disks.bucket.root'))->toBe(public_path('bucket'));
    expect(Storage::disk('bucket')->path('/'))->toBe(public_path('bucket').DIRECTORY_SEPARATOR);
});

test('files written to the bucket disk land in public/bucket', function () {
    Storage::disk('bucket')->put('probe/hello.txt', 'lakeside');

    expect(file_exists(public_path('bucket/probe/hello.txt')))->toBeTrue();
    expect(Storage::disk('bucket')->get('probe/hello.txt'))->toBe('lakeside');
    expect(Storage::disk('bucket')->url('probe/hello.txt'))->toBe('/bucket/probe/hello.txt');

    Storage::disk('bucket')->deleteDirectory('probe');

    expect(file_exists(public_path('bucket/probe/hello.txt')))->toBeFalse();
});

test('the media library stores uploads inside public/bucket and serves them from /bucket', function () {
    $sample = BucketDiskSample::create();

    $media = $sample
        ->addMedia(UploadedFile::fake()->image('lake-malawi.jpg', 1200, 800))
        ->toMediaCollection('images');

    expect($media->disk)->toBe('bucket');
    expect(file_exists(public_path("bucket/{$media->id}/lake-malawi.jpg")))->toBeTrue();
    expect($media->getUrl())->toBe("/bucket/{$media->id}/lake-malawi.jpg");

    $media->delete();

    expect(file_exists(public_path("bucket/{$media->id}/lake-malawi.jpg")))->toBeFalse();
});
