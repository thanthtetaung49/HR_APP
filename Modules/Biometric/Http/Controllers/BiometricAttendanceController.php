<?php

namespace Modules\Biometric\Http\Controllers;

use stdClass;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\AccountBaseController;
use Carbon\Carbon;
use Modules\Biometric\Entities\BiometricEmployee;
use Modules\Biometric\DataTables\BiometricAttendanceDataTable;

class BiometricAttendanceController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'biometric::app.menu.deviceEmployees';

        $this->middleware(function ($request, $next) {
            abort_403(!in_array('biometric', $this->user->modules) && user()->permission('manage_biometric_settings') != 'none');
            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index(BiometricAttendanceDataTable $dataTable)
    {
        $this->pageTitle = 'biometric::app.menu.attendance';
        $this->employees = User::allEmployees();
        $this->viewAttendancePermission = user()->permission('view_attendance');

        $now = now();
        $this->year = $now->format('Y');
        $this->month = $now->format('m');

        return $dataTable->render('biometric::attendance.index', $this->data);
    }

    public function test()
    {
        $arrays = [
            ["SLTTGI0100017\t2026-01-14 07:51:35\t0\t1\t\t0\t0",""]
            // ["CIS2MDY0100082\t2026-01-13 09:00:23\t0\t1\t0\t0\t0\t0\t0\t0\t", ""],
            // ["CIS2MDY0100082\t2026-01-13 12:01:33\t0\t1\t0\t0\t0\t0\t0\t0\t", ""],

            // ["1\t2026-01-14 09:05:34\t0\t1\t0\t0\t0\t0\t0\t0\t", ""],
            // ["1\t2026-01-14 10:50:34\t0\t1\t0\t0\t0\t0\t0\t0\t", ""],
            // ["1\t2026-01-14 12:30:34\t0\t1\t0\t0\t0\t0\t0\t0\t", ""],
            // ["1\t2026-01-14 14:00:34\t0\t1\t0\t0\t0\t0\t0\t0\t", ""],

            // ["1\t2026-01-15 09:20:34\t0\t1\t0\t0\t0\t0\t0\t0\t", ""],
            // ["1\t2026-01-15 10:50:34\t0\t1\t0\t0\t0\t0\t0\t0\t", ""],
            // ["1\t2026-01-15 12:30:34\t0\t1\t0\t0\t0\t0\t0\t0\t", ""],
            // ["1\t2026-01-15 13:20:34\t0\t1\t0\t0\t0\t0\t0\t0\t", ""],

            // ["1\t2026-01-16 09:20:34\t0\t1\t0\t0\t0\t0\t0\t0\t", ""],
            // ["1\t2026-01-16 10:50:34\t0\t1\t0\t0\t0\t0\t0\t0\t", ""],
            // ["1\t2026-01-16 11:40:34\t0\t1\t0\t0\t0\t0\t0\t0\t", ""],
            // ["1\t2026-01-16 13:20:34\t0\t1\t0\t0\t0\t0\t0\t0\t", ""],
        ];

        foreach ($arrays as $rows) {
            $device = new stdClass();
            $device->id = 1;
            $device->company_id = 1;
            $device->device_name = "CIS2_01";
            $device->serial_number = "GED7252100001";
            $device->device_ip = "160.30.213.134";
            $device->status = "online";
            $device->last_online = "2025-11-11T13:58:39.000000Z";
            $device->created_at = "2025-11-09T05:27:55.000000Z";
            $device->updated_at = "2025-11-11T13:58:39.000000Z";

            $device->company = new stdClass();
            $device->company->timezone = 'Asia/Yangon';
            $device->company->id = 1;

            $request = new \Illuminate\Http\Request([
                'table' => 'ATTLOG',
                'Stamp' => '9999'
            ]);
            BiometricEmployee::markAttendanceToDeviceAndApplication($rows, $device, $request);
        }

        return "Done";
    }
}
