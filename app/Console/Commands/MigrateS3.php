<?php

namespace App\Console\Commands;

use App\Providers\AppServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Codec\OrderedTimeCodec;
use Ramsey\Uuid\Exception\UnsupportedOperationException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MigrateS3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:s3';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This migrates folders identified by uuid to folders identified by id';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // $folders = Storage::disk('s3')->directories();

        $this->ensureDirectoriesExist();

        $medias = Media::all();
        $bar = $this->output->createProgressBar(count($medias));
        foreach ($medias as $media) {
            $this->ensureMediaIsMoved($media);
            $bar->advance();
        }
        $bar->finish();

        return 0;
    }

    private function ensureMediaIsMoved(Media $media)
    {
        $factory = new UuidFactory();
        $uuid = $factory->fromString($media->uuid);
        $codec = new OrderedTimeCodec($factory->getUuidBuilder());
        $factory->setCodec($codec);

        try {
            $uuid = $factory->fromBytes($uuid->getBytes());
        } catch (UnsupportedOperationException $exception) {
            try {
                $uuid = Uuid::fromBytes($uuid->getBytes());
            } catch (\Exception $exception) {
                $message = $exception->getMessage();
                $this->error("Failed processing the following media object $message :");
                dump($media);
                return;
            }
        }

        if (! Storage::disk('s3')->exists($uuid)) {
            // SKIP - Didn't find it in s3
            return;
        }

        $oldPath = "$uuid/$media->file_name";
        $newPath = "$media->id/$media->file_name";

        if (Storage::disk('s3')->exists($newPath)) {
            // SKIP - Already moved
            return;
        }

        Storage::disk('s3')->copy($oldPath, $newPath);
    }

    private function ensureDirectoriesExist()
    {
        $this->info("Ensuring model directories exist");

        $models = array_keys(AppServiceProvider::MORPH_MAP);
        foreach ($models as $model) {
            Storage::makeDirectory($model);
        }
    }
}
