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
        // $rows = ["1\t2025-11-12 09:30:34\t0\t1\t0\t0\t0\t0\t0\t0\t", ""];
        // $rows = ["1\t2025-11-12 10:50:34\t0\t1\t0\t0\t0\t0\t0\t0\t", ""];
        // $rows = ["1\t2025-11-12 12:30:34\t0\t1\t0\t0\t0\t0\t0\t0\t", ""];
        // $rows = ["1\t2025-11-12 15:10:34\t0\t1\t0\t0\t0\t0\t0\t0\t", ""];
        // $rows = ["1\t2025-11-12 15:30:34\t0\t1\t0\t0\t0\t0\t0\t0\t", ""];
        $rows = ["1\t2025-11-12 17:10:34\t0\t1\t0\t0\t0\t0\t0\t0\t", ""];

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
}
