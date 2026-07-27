<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Models\DeviceSequence;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SendSmsAttendanceJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $target_id;
    public $contact_no;
    public $message;
	public $send_sms_attendance;

    public function __construct($target_id, $contact_no, $message, $send_sms_attendance)
    {
        $this->target_id = $target_id;
        $this->contact_no = preg_replace('/^0/', '+63', $contact_no);
        $this->message = $message;
		$this->send_sms_attendance = $send_sms_attendance;
    }

    public function handle()
    {
        //$queueFile3 = storage_path('app/private/sms_queue3.txt');
		//$queueFile4 = storage_path('app/private/sms_queue4.txt');
		
		DB::table('message_sents')->insert([
			'target_id' => $this->target_id,
			'contact_no' => $this->contact_no,
			'message' => $this->message,
			'table' => 'attendances',
			'column1' => 'message_status',
			'column2' => 'message1',
			'status' => 0,
			'assigned_port' => null,
			'retries' => 0,
			'error_message' => null,
			'processed_at' => null,
			'created_at' => now(),
			'updated_at' => now(),
		]);
		
        //$payload = json_encode([
        //    'target_id' => $this->target_id,
        //    'contact_no' => $this->contact_no,
        //    'message' => $this->message,
		//	'table' => 'attendances',
		//	'column1' => 'message_status',
		//	'column2' => 'message1',
        //]);
		
		//if($this->send_sms_attendance == 'COM3') {
		//	file_put_contents($queueFile3, $payload . PHP_EOL, FILE_APPEND);
		//}else if($this->send_sms_attendance == 'COM4') {
		//	file_put_contents($queueFile4, $payload . PHP_EOL, FILE_APPEND);
		//}
        
    }
}