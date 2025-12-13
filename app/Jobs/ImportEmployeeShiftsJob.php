<?php

namespace App\Jobs;

use App\Models\Allowance;
use Exception;
use App\Models\User;
use App\Models\Leave;
use App\Models\Attendance;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use App\Traits\ExcelImportable;
use App\Models\AttendanceSetting;
use App\Models\Detection;
use App\Models\EmployeeShift;
use Illuminate\Support\Facades\DB;
use App\Models\EmployeeShiftSchedule;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Facades\Log;

class ImportEmployeeShiftsJob implements ShouldQueue
{

    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use ExcelImportable;

    private $row;
    private $columns;
    private $company;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($row, $columns, $company = null)
    {
        $this->row = $row;
        $this->columns = $columns;
        $this->company = $company;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (
            $this->isColumnExists('email') &&
            $this->isColumnExists('date') &&
            $this->isColumnExists('employee_shift_id')
        ) {

            // user that have employee role
            $user = User::where('email', $this->getColumnValue('email'))->whereHas('roles', function ($q) {
                $q->where('name', 'employee');
            })->first();

            if (!$user) {
                $this->failJobWithMessage(__('messages.employeeNotFound'));
            } else {
                DB::beginTransaction();
                try {
                    $userId = $user->id;
                    $date = $this->isColumnExists('date') ? $this->getColumnValue('date') : null;
                    $employeeShiftId = $this->isColumnExists('employee_shift_id') ? $this->getColumnValue('employee_shift_id') : null;

                    $shift = EmployeeShift::where('id', $employeeShiftId)->first();

                    Log::info('employee_shift_data', [
                        'user_id' => $userId,
                        'date' => $date,
                        'employee_shift_id' => $employeeShiftId,
                        'added_by' => auth()->user()->id,
                        'last_updated_by' => auth()->user()->id,
                        'shift_start_time' => $shift->office_start_time,
                        'shift_end_time' => $shift->office_end_time
                    ]);

                    EmployeeShiftSchedule::firstOrCreate([
                        'user_id' => $userId,
                        'date' => $date,
                        'employee_shift_id' => $employeeShiftId,
                        'added_by' => auth()->user()->id,
                        'last_updated_by' => auth()->user()->id,
                        'shift_start_time' => $date . ' ' . $shift->office_start_time,
                        'shift_end_time' =>  $date . ' ' . $shift->office_end_time
                    ]);

                    Log::info('success');

                    DB::commit();
                } catch (InvalidFormatException $e) {
                    DB::rollBack();
                    $this->failJob(__('messages.invalidDate'));
                } catch (Exception $e) {
                    DB::rollBack();
                    $this->failJobWithMessage($e->getMessage());
                }
            }
        } else {
            $this->failJob(__('messages.invalidData'));
        }
    }
}
