<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Location;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CriteriaReportExport;
use App\DataTables\CriteriaReportDataTable;

class CriteriaReportController extends AccountBaseController
{
    public $arr = [];

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('app.menu.criteriaReport');

        $this->middleware(function ($request, $next) {
            abort_403(!in_array('employees', $this->user->modules));

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index(CriteriaReportDataTable $dataTable)
    {
        $this->authorize('viewAny', User::class);
        $this->locations = Location::get();

        return $dataTable->render('criteria-reports.index', $this->data);
    }

    public function exportAllAttendance($location, $department, $designation)
    {
        abort_403(!canDataTableExport());

        $date = now()->format('Y-m-d');

        return Excel::download(new CriteriaReportExport($location, $department, $designation), 'Criteria_Report_' . $date . '.xlsx');
    }
}
