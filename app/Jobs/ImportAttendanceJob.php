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

        // dd($this->row, $this->columns, $this->company);
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

                    $clock_in_time = Carbon::createFromFormat('Y-m-d H:i:s', $this->getColumnValue('clock_in_time'))->timezone('UTC')->format('Y-m-d H:i:s');
                    $clock_in_ip = $this->isColumnExists('clock_in_ip') ? $this->getColumnValue('clock_in_ip') : '127.0.0.1';
                    $clock_out_time = $this->isColumnExists('clock_out_time') ? Carbon::createFromFormat('Y-m-d H:i:s', $this->getColumnValue('clock_out_time'))->timezone('UTC')->format('Y-m-d H:i:s') : null;
                    $clock_out_ip = $this->isColumnExists('clock_out_ip') ? $this->getColumnValue('clock_out_ip') : null;
                    $working_from = $this->isColumnExists('working_from') ? $this->getColumnValue('working_from') : 'office';
                    // $late = $this->isColumnExists('late') && str($this->getColumnValue('late'))->lower() == 'yes' ? 'yes' : 'no';
                    $half_day = $this->isColumnExists('half_day') && str($this->getColumnValue('half_day'))->lower() == 'yes' ? 'yes' : 'no';

                    $showClockIn = AttendanceSetting::first();

                    $attendanceSettings = $this->attendanceShift($showClockIn);

                    $startTimestamp = now($this->company->timezone)->format('Y-m-d') . ' ' . $attendanceSettings->office_start_time;
                    $endTimestamp = now($this->company->timezone)->format('Y-m-d') . ' ' . $attendanceSettings->office_end_time;
                    $officeStartTime = Carbon::createFromFormat('Y-m-d H:i:s', $startTimestamp, $this->company->timezone);
                    $officeEndTime = Carbon::createFromFormat('Y-m-d H:i:s', $endTimestamp, $this->company->timezone);


                    $clockInCount = Attendance::where('user_id', $user->id)
                        ->whereDate('clock_in_time', Carbon::parse($clock_in_time)->toDateString())
                        ->count();

                    if ($clockInCount < $attendanceSettings->clockin_in_day) {

                        if ($attendanceSettings->halfday_mark_time) {
                            $halfDayTimes = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::parse($clock_in_time)->format('Y-m-d') . ' ' . $attendanceSettings->halfday_mark_time, $this->company->timezone);
                        }

                        // Check maximum attendance in a day
                        $officeStartTime = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::parse($clock_in_time)->format('Y-m-d') . ' ' . $attendanceSettings->office_start_time, $this->company->timezone);

                        $lateTime = $officeStartTime->addMinutes($attendanceSettings->late_mark_duration);

                        $checkTodayAttendance = Attendance::where('user_id', $user->id)
                            ->where(DB::raw('DATE(attendances.clock_in_time)'), '=', Carbon::parse($clock_in_time)->format('Y-m-d'))->first();

                        $attendance = new Attendance();
                        $attendance->user_id = $user->id;
                        $attendance->clock_in_time = $clock_in_time;
                        $attendance->clock_in_ip = $clock_in_ip;
                        $attendance->clock_out_time = $clock_out_time;
                        $attendance->clock_out_ip = $clock_out_ip;

                        $attendance->working_from = $working_from;
                        $attendance->location_id = $user->employeeDetail->company_address_id;

                        if ($this->isColumnExists('late') && str($this->getColumnValue('late'))->lower() == 'yes') {
                            $attendance->late = 'yes';
                        } else {
                            if (Carbon::createFromFormat('Y-m-d H:i:s', $clock_in_time, $this->company->timezone)->greaterThan($lateTime)) {
                                $attendance->late = 'yes';
                            } else {
                                $attendance->late = 'no';
                            }
                        }

                        $attendance->half_day = $half_day;

                        if (
                            Carbon::createFromFormat('Y-m-d H:i:s', $clock_in_time, $this->company->timezone)->greaterThan($halfDayTimes)
                        ) {
                            $attendance->half_day_late = 'yes';
                        }

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


    public function attendanceShift($defaultAttendanceSettings)
    {
        $checkPreviousDayShift = EmployeeShiftSchedule::with('shift')->where('user_id', user()->id)
            ->where('date', now($this->company->timezone)->subDay()->toDateString())
            ->first();

        $checkTodayShift = EmployeeShiftSchedule::with('shift')->where('user_id', user()->id)
            ->where('date', now(company()->timezone)->toDateString())
            ->first();

        $backDayFromDefault = Carbon::parse(now($this->company->timezone)->subDay()->format('Y-m-d') . ' ' . $defaultAttendanceSettings->office_start_time, $this->company->timezone);

        $backDayToDefault = Carbon::parse(now($this->company->timezone)->subDay()->format('Y-m-d') . ' ' . $defaultAttendanceSettings->office_end_time, $this->company->timezone);

        if ($backDayFromDefault->gt($backDayToDefault)) {
            $backDayToDefault->addDay();
        }

        $nowTime = Carbon::createFromFormat('Y-m-d H:i:s', now($this->company->timezone)->toDateTimeString(), 'UTC');

        if ($checkPreviousDayShift && $nowTime->betweenIncluded($checkPreviousDayShift->shift_start_time, $checkPreviousDayShift->shift_end_time)) {
            $attendanceSettings = $checkPreviousDayShift;
        } else if ($nowTime->betweenIncluded($backDayFromDefault, $backDayToDefault)) {
            $attendanceSettings = $defaultAttendanceSettings;
        } else if (
            $checkTodayShift &&
            ($nowTime->betweenIncluded($checkTodayShift->shift_start_time, $checkTodayShift->shift_end_time)
                || $nowTime->gt($checkTodayShift->shift_end_time)
                || (!$nowTime->betweenIncluded($checkTodayShift->shift_start_time, $checkTodayShift->shift_end_time) && $defaultAttendanceSettings->show_clock_in_button == 'no'))
        ) {
            $attendanceSettings = $checkTodayShift;
        } else if ($checkTodayShift && !is_null($checkTodayShift->shift->early_clock_in)) {
            $attendanceSettings = $checkTodayShift;
        } else {
            $attendanceSettings = $defaultAttendanceSettings;
        }


        if (isset($attendanceSettings->shift)) {
            return $attendanceSettings->shift;
        }

        return $attendanceSettings;
    }
}
