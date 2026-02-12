<?php

namespace App\Jobs;

use App\Models\Attendance;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Process;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsNewStudentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected $contact_no;
    protected $message;

    public function __construct($contact_no, $message)
    {
        $this->contact_no = preg_replace('/^0/', '+63', $contact_no);
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $pythonPath = config('app.python_path', 'python');

        $scriptPath = base_path('storage/app/private/python/send_sms.py');

        $process = Process::run("{$pythonPath} {$scriptPath} {$this->contact_no} \"" . escapeshellarg($this->message) . "\"");
    }
}
