<?php

namespace App\Traits;

use Carbon\Carbon;
use App\Models\User;
use ReflectionClass;
use App\Helper\Files;
use function Psl\Type\nullable;
use App\Models\AttendanceSetting;
use Illuminate\Support\Facades\Bus;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\EmployeeShiftSchedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\HeadingRowImport;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

trait ImportExcel
{

    public function importFileProcess($request, $importClass)
    {
        // get class name from $importClass
        $this->importClassName = (new ReflectionClass($importClass))->getShortName();

        $this->file = Files::upload($request->import_file, Files::IMPORT_FOLDER);
        $excelData = Excel::toArray(new $importClass, public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $this->file))[0];

        if ($request->has('heading')) {
            array_shift($excelData);
        }

        $isDataNull = true;

        foreach ($excelData as $rowitem) {
            if (array_filter($rowitem)) {
                $isDataNull = false;
                break;
            }
        }

        if ($isDataNull) {
            return 'abort';
        }


        $this->hasHeading = $request->has('heading');
        $this->heading = array();
        $this->fileHeading = array();

        $this->columns = $importClass::fields();
        $this->importMatchedColumns = array();
        $this->matchedColumns = array();

        if ($this->hasHeading) {
            $this->heading = (new HeadingRowImport)->toArray(public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $this->file))[0][0];

            // Excel Format None for get Heading Row Without Format and after change back to config
            HeadingRowFormatter::default('none');
            $this->fileHeading = (new HeadingRowImport)->toArray(public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $this->file))[0][0];
            HeadingRowFormatter::default(config('excel.imports.heading_row.formatter'));

            array_shift($excelData);
            $this->matchedColumns = collect($this->columns)->whereIn('id', $this->heading)->pluck('id');
            $importMatchedColumns = array();

            foreach ($this->matchedColumns as $matchedColumn) {
                $importMatchedColumns[$matchedColumn] = 1;
            }

            $this->importMatchedColumns = $importMatchedColumns;
        }

        $this->importSample = array_slice($excelData, 0, 5);
    }

    public function importJobProcess($request, $importClass, $importJobClass)
    {
        // get class name from $importClass
        $importClassName = (new ReflectionClass($importClass))->getShortName();

        // clear previous import
        Artisan::call('queue:clear database --queue=' . $importClassName);
        Artisan::call('queue:flush');
        // Get index of an array not null value with key
        $columns = array_filter($request->columns, function ($value) {
            return $value !== null;
        });

        $excelData = Excel::toArray(new $importClass, public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $request->file))[0];

        if ($request->has_heading) {
            array_shift($excelData);
        }

        $jobs = [];

        $groupedData = [];

        foreach ($excelData as $row) {
            $mappedRow = array_combine($columns, $row);
            $date = Carbon::parse($mappedRow["clock_in_time"])->format('Y-m-d');

            $groupedData[$date][] = $mappedRow;
        }

        $formattedData = [];

        $finalData = [];

        foreach ($groupedData as $dailyRecords) {
            foreach ($dailyRecords as $record) {
                $email = $record['email'];
                $date = date('Y-m-d', strtotime($record['clock_in_time'] ?? $record['clock_out_time']));

                $formattedData[$email][$date][] = $record;
            }
        }

        foreach ($formattedData as $email => $dates) {
            foreach ($dates as $date => $records) {
                usort($records, function ($a, $b) {
                    return strtotime($a['clock_in_time'] ?? $a['clock_out_time']) <=> strtotime($b['clock_in_time'] ?? $b['clock_out_time']);
                });

                $finalData[] = [
                    'email' => $email,
                    'clock_out_time' => $records[0]['clock_out_time'] ?? null,
                    'clock_in_time' => $records[1]['clock_in_time'] ?? null
                ];
            }
        }

        Session::put('leads_count', count($excelData));

        foreach ($excelData as $index => $row) {
            $email = $row[0];

            $user = User::where('email', $email)->whereHas('roles', function ($q) {
                $q->where('name', 'employee');
            })->first();

            $date = Carbon::parse($row[1])->format('Y-m-d');

            foreach ($finalData as $data) {
                $finalEmail = $data['email'];
                $finalDate = Carbon::parse($data['clock_out_time'])->format('Y-m-d');

                $checkEmailDate = ($email == $finalEmail) && ($date == $finalDate);

                if (!empty($user) && $checkEmailDate) {

                    $clockIn = Carbon::parse($data['clock_in_time']);
                    $breakTimeStartTime = Carbon::parse($data['clock_out_time'])
                        ->copy()
                        ->addMinutes(45);

                    $breakTimeEndTime = Carbon::parse($data['clock_out_time'])
                        ->copy()
                        ->addMinutes(60);

                    $employeeShift = EmployeeShiftSchedule::with('shift')
                        ->where('user_id', $user->id)
                        ->whereDate('date', $clockIn)
                        ->first();

                    $showClockIn = AttendanceSetting::first();

                    $attendanceSettings = $this->attendanceShiftData($showClockIn);

                    if (isset($employeeShift)) {
                        $halfDayMarkTime = $clockIn->format('Y-m-d') . ' ' . $employeeShift->shift->halfday_mark_time;
                    } else {
                        $halfDayMarkTime = $clockIn->format('Y-m-d') . ' ' . $attendanceSettings->halfday_mark_time;
                    }

                    $halfDayMarkTime = Carbon::parse($halfDayMarkTime);

                    $break_time_late = "no";
                    $breaktime_late_between = "no";

                    if ($clockIn->lt($halfDayMarkTime) && $clockIn->gt($breakTimeEndTime)) {
                        $break_time_late = 'yes';
                    } elseif ($clockIn->between($breakTimeStartTime, $breakTimeEndTime)) {
                        $breaktime_late_between = 'yes';
                    } elseif ($clockIn->gt($halfDayMarkTime)) {
                        $break_time_late = 'yes';
                    } else {
                        $break_time_late = 'no';
                        $breaktime_late_between = 'no';
                    }

                    $jobs[] = (new $importJobClass($row, $columns, company(), $break_time_late, $breaktime_late_between));
                }
            }
        }

        $batch = Bus::batch($jobs)->onConnection('database')->onQueue($importClassName)->name($importClassName)->dispatch();

        Files::deleteFile($request->file, Files::IMPORT_FOLDER);

        return $batch;
    }

    public function importSalaryJobProcess($request, $importClass, $importJobClass)
    {
        // get class name from $importClass
        $importClassName = (new ReflectionClass($importClass))->getShortName();

        // clear previous import
        Artisan::call('queue:clear database --queue=' . $importClassName);
        Artisan::call('queue:flush');
        // Get index of an array not null value with key
        $columns = array_filter($request->columns, function ($value) {
            return $value !== null;
        });

        $excelData = Excel::toArray(new $importClass, public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $request->file))[0];

        if ($request->has_heading) {
            array_shift($excelData);
        }

        $jobs = [];

        Session::put('leads_count', count($excelData));

        foreach ($excelData as $row) {
            $jobs[] = (new $importJobClass($row, $columns, company()));
        }

        $batch = Bus::batch($jobs)->onConnection('database')->onQueue($importClassName)->name($importClassName)->dispatch();

        Files::deleteFile($request->file, Files::IMPORT_FOLDER);

        return $batch;
    }

    public function attendanceShiftData($defaultAttendanceSettings)
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
