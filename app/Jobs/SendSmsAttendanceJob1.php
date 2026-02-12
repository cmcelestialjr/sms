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

class SendSmsAttendanceJob1 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $target_id;
    protected $contact_no;
    protected $message;

    public function __construct($target_id, $contact_no, $message)
    {
        $this->target_id = $target_id;
        $this->contact_no = preg_replace('/^0/', '+63', $contact_no);
        $this->message = $message;
    }

    public function handle(): void
    {
        //run php artisan queue:work database

        $pythonPath = config('app.python_path', 'python');

        $deviceSequence = DeviceSequence::first();

        $scriptPath = base_path('storage/app/private/python/send_sms_attendance_1.py');   
        

        $process = Process::run("{$pythonPath} {$scriptPath} {$this->contact_no} \"" . escapeshellarg($this->message) . "\"");
        
        $update = Attendance::find($this->target_id);
        if ($process->successful()) {
            $status = 'Success';
        } else {
            $status = 'Error';
        }
        $update->message_status = $status;
        $update->save();

        if($deviceSequence){
            if($deviceSequence->name=='COM3'){
                $deviceName = 'COM4';
            }else{
                $deviceName = 'COM3';
            }
            $updateDeviceSequence = DeviceSequence::find($deviceSequence->id);
        }else{
            $deviceName = 'COM4';
            $updateDeviceSequence = new DeviceSequence;
        }
        $updateDeviceSequence->name = $deviceName;
        $updateDeviceSequence->save();
    }
}
