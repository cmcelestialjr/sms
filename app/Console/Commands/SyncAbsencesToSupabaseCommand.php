<?php

namespace App\Console\Commands;

use App\Models\Absence;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SyncAbsencesToSupabaseCommand extends Command
{
    protected $signature = 'attendify:sync-absences 
                            {--clean : Delete existing Supabase absences before re-inserting from local}';

    protected $description = 'Update/insert all local absences from the beginning to Supabase';

    private string $stateFile = 'supabase_sync_state.json';

    public function handle()
    {
        $supabaseUrl = rtrim(env('SUPABASE_URL'), '/');
        $supabaseKey = env('SUPABASE_SERVICE_KEY');

        if (empty($supabaseUrl) || empty($supabaseKey)) {
            $this->error('Missing SUPABASE_URL or SUPABASE_SERVICE_KEY in .env');
            return Command::FAILURE;
        }

        $headers = [
            'apikey'        => $supabaseKey,
            'Authorization' => 'Bearer ' . $supabaseKey,
            'Content-Type'  => 'application/json',
        ];

        try {
            // Optional: Wipe Supabase absences first so locally deleted absences are removed
            // and re-created (student_id, date) pairs with new IDs never conflict.
            if ($this->option('clean')) {
                $this->warn('Clearing existing Supabase [absences] table...');

                $deleteResponse = Http::withHeaders($headers)
                    ->timeout(20)
                    ->delete("{$supabaseUrl}/rest/v1/absences?id=gt.0");

                if (!$deleteResponse->successful()) {
                    throw new \RuntimeException(
                        'Failed clearing Supabase [absences]: ' . $deleteResponse->body()
                    );
                }

                $this->info(' ✓ Cleared existing Supabase absences.');
            }

            // Fetch all valid absences from the very beginning
            $syncStartedAt = now()->subSeconds(5);

            $records = Absence::whereIn('student_id', Student::select('id'))
                ->orderBy('id', 'asc')
                ->get();

            if ($records->isEmpty()) {
                $this->info('No local absences found to sync.');
                return Command::SUCCESS;
            }

            $this->info("Syncing {$records->count()} total absence record(s) from the start...");

            $bar = $this->output->createProgressBar($records->count());
            $bar->start();

            foreach ($records->chunk(250) as $chunk) {
                // Deduplicate by (student_id + date) within chunk just in case local DB has duplicates
                $payload = $chunk
                    ->unique(fn($r) => $r->student_id . '_' . $r->date)
                    ->map(fn($r) => [
                        'id'         => $r->id,
                        'student_id' => $r->student_id,
                        'date'       => $r->date,
                        'updated_at' => optional($r->updated_at)->toIso8601String()
                            ?? now()->toIso8601String(),
                    ])
                    ->values()
                    ->toArray();

                // Upsert on (student_id, date) so existing dates update cleanly
                $response = Http::withHeaders(array_merge($headers, [
                    'Prefer' => 'resolution=merge-duplicates',
                ]))
                    ->timeout(25)
                    ->post("{$supabaseUrl}/rest/v1/absences?on_conflict=student_id,date", $payload);

                if (!$response->successful()) {
                    throw new \RuntimeException(
                        'Failed syncing [absences]: ' . $response->body()
                    );
                }

                $bar->advance($chunk->count());
            }

            $bar->finish();
            $this->newLine(2);

            // Update the watermark in storage/app/supabase_sync_state.json
            $this->updateAbsencesWatermark($syncStartedAt->toDateTimeString());

            $this->info(" ✓ Successfully synced all {$records->count()} row(s) to Supabase [absences].");
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('Absences sync error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function updateAbsencesWatermark(string $timestamp): void
    {
        $state = [];
        if (Storage::disk('local')->exists($this->stateFile)) {
            $state = json_decode(Storage::disk('local')->get($this->stateFile), true) ?? [];
        }

        $state['absences'] = $timestamp;
        Storage::disk('local')->put($this->stateFile, json_encode($state, JSON_PRETTY_PRINT));
    }
}