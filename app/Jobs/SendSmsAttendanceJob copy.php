<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Models\DeviceSequence;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Process;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsAttendanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $target_id;
    protected $contact_no;
    protected $message;
    protected $send_sms_attendance;

    public function __construct($target_id, $contact_no, $message, $send_sms_attendance)
    {
        $this->target_id = $target_id;
        $this->contact_no = preg_replace('/^0/', '+63', $contact_no);
        $this->message = $message;
        $this->send_sms_attendance = $send_sms_attendance;
    }

    public function handle(): void
    {
        //run php artisan queue:work database

        $pythonPath = 'C:\laragon\bin\python\python-3.13\python.exe';        

        //$pythonPath = config('app.python_path', 'python');

        if($this->send_sms_attendance == 'COM3') {
            $scriptPath = base_path('storage/app/private/python/send_sms_attendance.py');        
        } else if($this->send_sms_attendance == 'COM4') {
            $scriptPath = base_path('storage/app/private/python/send_sms_attendance_1.py');    
        } else if($this->send_sms_attendance == 'COM5') {
            $scriptPath = base_path('storage/app/private/python/send_sms_attendance_2.py');    
        } else if($this->send_sms_attendance == 'COM6') {
            $scriptPath = base_path('storage/app/private/python/send_sms_attendance_3.py');    
        } else {
            Log::error("Invalid COM port specified for SendSmsAttendanceJob: {$this->send_sms_attendance}");
            return;
        }

        $command = "{$pythonPath} {$scriptPath} {$this->contact_no} \"{$this->message}\"";

        $process = Process::run($command);
        
        $output = $process->output();
        $result = json_decode($output, true);

        $update = Attendance::find($this->target_id);

        if ($result && isset($result['success']) && $result['success']) {
            $update->message_status = 'Success';
        } else {
            $errorMessage = $result['error'] ?? 'Unknown error';
            $update->message_status = "Error: {$errorMessage}";
        }

        $update->save();
    }
}
