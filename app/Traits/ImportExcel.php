<?php

namespace App\Traits;

use Carbon\Carbon;
use ReflectionClass;
use App\Helper\Files;
use Illuminate\Support\Facades\Bus;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\HeadingRowImport;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use function Psl\Type\nullable;

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

        foreach ($groupedData as $date => $records) {
            if (key($records) == 0 || key($records) == 1) {
                $formattedData[$date] = [
                    "clock_in_time" => $records[1]["clock_in_time"],
                    "clock_out_time" => $records[0]["clock_out_time"],
                ];
            }
        }


        Session::put('leads_count', count($excelData));

        foreach ($excelData as $index => $row) {
            $date = Carbon::parse($row[1])->format('Y-m-d');

            $clockIn = Carbon::parse($formattedData[$date]['clock_in_time']);
            $clockOut = Carbon::parse($formattedData[$date]['clock_out_time'])->clone()->addMinutes(45);

            $half_day_date = "";

            if ($clockIn->greaterThan($clockOut)) {
                $half_day_date = "yes";
            } else {
                $half_day_date = "no";
            }

            $jobs[] = (new $importJobClass($row, $columns, company(), $half_day_date));
        }


        $batch = Bus::batch($jobs)->onConnection('database')->onQueue($importClassName)->name($importClassName)->dispatch();

        Files::deleteFile($request->file, Files::IMPORT_FOLDER);

        return $batch;
    }

    public function importSalaryJobProcess($request, $importClass, $importJobClass) {
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

}
