<?php

namespace App\Jobs;

use Exception;
use App\Models\User;
use App\Models\Leave;
use App\Models\Attendance;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use App\Traits\ExcelImportable;
use App\Models\AttendanceSetting;
use Illuminate\Support\Facades\DB;
use App\Models\EmployeeShiftSchedule;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Carbon\Exceptions\InvalidFormatException;

class ImportAttendanceJob implements ShouldQueue
{

    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use ExcelImportable;

    private $row;
    private $columns;
    private $company;
    private $break_time_late;
    private $break_time_loop;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($row, $columns, $company = null, $break_time_late = null, $break_time_loop = null)
    {
        $this->row = $row;
        $this->columns = $columns;
        $this->company = $company;
        $this->break_time_late = $break_time_late; // break time late
        $this->break_time_loop = $break_time_loop;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->isColumnExists('clock_in_time') && $this->isColumnExists('email') && $this->isEmailValid($this->getColumnValue('email'))) {

            // user that have employee role
            $user = User::where('email', $this->getColumnValue('email'))->whereHas('roles', function ($q) {
                $q->where('name', 'employee');
            })->first();

            if (!$user) {
                $this->failJobWithMessage(__('messages.employeeNotFound'));
            } else {
                DB::beginTransaction();
                try {
                    $now = now($this->company->timezone);

                    $clock_in_time = Carbon::createFromFormat('Y-m-d H:i:s', $this->getColumnValue('clock_in_time'))->format('Y-m-d H:i:s');
                    $clock_in_ip = $this->isColumnExists('clock_in_ip') ? $this->getColumnValue('clock_in_ip') : '127.0.0.1';
                    $clock_out_time = $this->isColumnExists('clock_out_time') ? Carbon::createFromFormat('Y-m-d H:i:s', $this->getColumnValue('clock_out_time'))->format('Y-m-d H:i:s') : null;
                    $clock_out_ip = $this->isColumnExists('clock_out_ip') ? $this->getColumnValue('clock_out_ip') : '127.0.0.1';
                    $work_from_type = $this->isColumnExists('working_from') ? $this->getColumnValue('working_from') : 'office';
                    $half_day = $this->isColumnExists('half_day') && str($this->getColumnValue('half_day'))->lower() == 'yes' ? 'yes' : 'no';

                    $carbonDate = Carbon::parse($this->getColumnValue('clock_in_time'))->startOfDay();

                    $employeeShift = EmployeeShiftSchedule::with('shift')
                        ->where('user_id', $user->id)
                        ->whereDate('date', Carbon::createFromFormat('Y-m-d H:i:s', $clock_in_time)->format('Y-m-d'))
                        ->first();

                    $showClockIn = AttendanceSetting::first();

                    // $attendanceSettings = $this->attendanceShift($showClockIn);
                    $attendanceSettings = $this->attendanceShift($showClockIn, $user->id, $carbonDate, $clock_in_time);

                    if (isset($employeeShift)) {
                        $startTimestamp = now($this->company->timezone)->format('Y-m-d') . ' ' . $employeeShift->shift->office_start_time;
                        $endTimestamp = now($this->company->timezone)->format('Y-m-d') . ' ' . $employeeShift->shift->office_end_time;
                        $employeeShiftId = $employeeShift->employee_shift_id;
                    } else {
                        $startTimestamp = now($this->company->timezone)->format('Y-m-d') . ' ' . $attendanceSettings->office_start_time;
                        $endTimestamp = now($this->company->timezone)->format('Y-m-d') . ' ' . $attendanceSettings->office_end_time;
                        $employeeShiftId = $attendanceSettings->id;
                    }

                    $officeStartTime = Carbon::createFromFormat('Y-m-d H:i:s', $startTimestamp, $this->company->timezone);
                    $officeEndTime = Carbon::createFromFormat('Y-m-d H:i:s', $endTimestamp, $this->company->timezone);

                    if ($attendanceSettings->shift_type == 'strict') {
                        $clockInCount = Attendance::getTotalUserClockInWithTime($officeStartTime, $officeEndTime, $user->id);
                    } else {
                        $clockInCount = Attendance::where('user_id', $user->id)
                            ->whereDate('clock_in_time', Carbon::parse($clock_in_time)->toDateString())
                            ->count();
                    }

                    if ($clockInCount < $attendanceSettings->clockin_in_day) {
                        if ($attendanceSettings->halfday_mark_time) {
                            $halfDayTimes = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::parse($clock_in_time)->format('Y-m-d') . ' ' . $attendanceSettings->halfday_mark_time, $this->company->timezone);
                        }

                        // Check maximum attendance in a day
                        $officeStartTime = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::parse($clock_in_time)->format('Y-m-d') . ' ' . $officeStartTime->format('H:i:s'), $this->company->timezone);

                        $lateTime = $officeStartTime->addMinutes(15);

                        $attendance = new Attendance();
                        $attendance->user_id = $user->id;
                        $attendance->clock_in_time = $clock_in_time;
                        $attendance->clock_in_ip = $clock_in_ip;
                        $attendance->clock_out_time = $clock_out_time;
                        $attendance->clock_out_ip = $clock_out_ip;
                        $attendance->work_from_type = $work_from_type;
                        $attendance->location_id = $user->employeeDetail->company_address_id;

                        $isLateMarked = $this->isColumnExists('late') && str($this->getColumnValue('late'))->lower() == 'yes' && $half_day == 'no';
                        $clockInCarbon = Carbon::createFromFormat('Y-m-d H:i:s', $clock_in_time, $this->company->timezone);

                        if ($isLateMarked || $clockInCarbon->greaterThan($lateTime)) {
                            $attendance->late = 'yes';
                        } else {
                            $attendance->late = 'no';
                        }

                        $attendance->half_day = $half_day;
                        $attendance->employee_shift_id = $employeeShiftId;

                        if (isset($employeeShift)) {
                            $officeStartTime =  $employeeShift->shift->office_start_time;
                            $officeEndTime =  $employeeShift->shift->office_end_time;
                        } else {
                            $officeStartTime = $attendanceSettings->office_start_time;
                            $officeEndTime = $attendanceSettings->office_end_time;
                        }

                        $attendance->shift_start_time = $attendance->clock_in_time->format('Y-m-d') . ' ' . $officeStartTime;

                        $clockInOfficeStartTime = $attendance->clock_in_time->format('Y-m-d') . ' ' . $officeStartTime;
                        $clockInOfficeEndTime = $attendance->clock_in_time->format('Y-m-d') . ' ' . $officeEndTime;

                        if (Carbon::parse($clockInOfficeStartTime, $this->company->timezone)->gt($clockInOfficeEndTime, $this->company->timezone)) {
                            $attendance->shift_end_time = $attendance->clock_in_time->addDay()->format('Y-m-d') . ' ' . $officeEndTime;
                        } else {
                            $attendance->shift_end_time = $attendance->clock_in_time->format('Y-m-d') . ' ' . $officeEndTime;
                        }

                        $attendance->break_time_late = $this->break_time_late;

                        $attendance->save();
                    }

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


    public function attendanceShift($defaultAttendanceSettings = null, $userId = null, $date = null, $clockInTime = null)
    {
        // dd($defaultAttendanceSettings, $userId, $date, $clockInTime);

        $checkPreviousDayShift = EmployeeShiftSchedule::without('shift')->where('user_id', $userId)
            ->where('date', $date->copy()->subDay()->toDateString())
            ->first();

        $checkTodayShift = EmployeeShiftSchedule::without('shift')->where('user_id', $userId)
            ->where('date', $date->copy()->toDateString())
            ->first();

        $backDayFromDefault = Carbon::parse($date->copy()->subDay()->format('Y-m-d') . ' ' . $defaultAttendanceSettings->office_start_time);

        $backDayToDefault = Carbon::parse($date->copy()->subDay()->format('Y-m-d') . ' ' . $defaultAttendanceSettings->office_end_time);

        if ($backDayFromDefault->gt($backDayToDefault)) {
            $backDayToDefault->addDay();
        }

        $nowTime = Carbon::createFromFormat('Y-m-d H:i:s', $clockInTime, 'UTC');


        // $nowTime = Carbon::createFromFormat('Y-m-d H:i:s', $date->copy()->toDateString() . ' ' . $clockInTime, 'UTC');

        if ($checkPreviousDayShift && $nowTime->betweenIncluded($checkPreviousDayShift->shift_start_time, $checkPreviousDayShift->shift_end_time)) {
            $attendanceSettings = $checkPreviousDayShift;
        } else if ($nowTime->betweenIncluded($backDayFromDefault, $backDayToDefault)) {
            $attendanceSettings = $defaultAttendanceSettings;
        } else if (
            $checkTodayShift &&
            ($nowTime->betweenIncluded($checkTodayShift->shift_start_time, $checkTodayShift->shift_end_time) || $nowTime->gt($checkTodayShift->shift_end_time))
        ) {
            $attendanceSettings = $checkTodayShift;
        } else if ($checkTodayShift && $checkTodayShift->shift->shift_type == 'flexible') {
            $attendanceSettings = $checkTodayShift;
        } else {
            $attendanceSettings = $defaultAttendanceSettings;
        }

        return $attendanceSettings->shift;
    }
}
