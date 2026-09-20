<?php

namespace App\Http\Controllers;

use App\DataTables\LateReportDataTable;
use App\Exports\LateReportExport;
use App\Models\Attendance;
use App\Models\Location;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class LateReportController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('app.menu.lateReport');

        $this->middleware(function ($request, $next) {
            abort_403(!in_array('employees', $this->user->modules));

            return $next($request);
        });
    }

    public function index(LateReportDataTable $dataTable)
    {
        $this->authorize('lateReportPermission', User::class);

        $this->locations = Location::get();
        $this->months = $this->months();

        return $dataTable->render('late-reports.index', $this->data);
    }

    protected function months()
    {
        return  [
            1 => "Jan",
            2 => "Feb",
            3 => "Mar",
            4 => "Apr",
            5 => "May",
            6 => "Jun",
            7 => "Jul",
            8 => "Aug",
            9 => "Sep",
            10 => "Oct",
            11 => "Nov",
            12 => "Dec"
        ];
    }

    public function exportAllAttendance($location = null, $month = null, $year = null)
    {
        abort_403(!canDataTableExport());

        $date = now()->format('Y-m-d');

        return Excel::download(new LateReportExport($location, $month, $year), 'Late_Report_' . $date . '.xlsx');
    }
}
