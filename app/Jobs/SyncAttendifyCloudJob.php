<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class SyncAttendifyCloudJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Retry up to 3 times if campus internet briefly drops.
     */
    public int $tries = 3;

    /**
     * Wait 10s, then 30s, then 60s between retries.
     */
    public array $backoff = [10, 30, 60];

    /**
     * Max seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Release uniqueness lock after 60s if queue worker isn't running.
     */
    public int $uniqueFor = 60;

    /**
     * Ensure two sync jobs never write to supabase_sync_state.json at the same time.
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('supabase-cloud-sync-lock'))
                ->releaseAfter(5)
                ->expireAfter(120),
        ];
    }

    public function handle(): void
    {
        $exitCode = Artisan::call('attendify:sync-cloud');

        if ($exitCode !== 0) {
            throw new \RuntimeException('attendify:sync-cloud returned non-zero exit code.');
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('Queued Supabase cloud sync failed after retries: ' . $e->getMessage());
    }
}