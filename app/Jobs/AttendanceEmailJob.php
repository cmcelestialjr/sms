<?php

namespace App\Jobs;

use App\Mail\AttendanceMail;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class AttendanceEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $details;

    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($details)
    {
        $this->details = $details;
    }

    public function handle()
    {
        // Set up SMTP configuration
        sleep(2);
        
        Config::set('mail.mailers.smtp.host', 'smtp.gmail.com');
        Config::set('mail.mailers.smtp.port', 587);
        Config::set('mail.mailers.smtp.username', 'sms-lnu@lnu.edu.ph');
        Config::set('mail.mailers.smtp.password', 'wzbg ijxk cffv iasb');
        Config::set('mail.mailers.smtp.encryption', 'tls');
        $id = $this->details['id'];
        $email = $this->details['email'];

        try {
            Mail::to($email)->send(new AttendanceMail($this->details));
            $this->update($id, $this->details['message'], 'Success');
        } catch (\Exception $e) {
            $this->update($id, $e->getMessage(), 'Error');
        }
    }
    private function update($id, $message, $status){
        Attendance::where('id', $id)
            ->update([
                'message1' => $message,
                'email_status' => $status,
            ]);
    }
}