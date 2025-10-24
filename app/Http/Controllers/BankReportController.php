<?php

namespace App\Http\Controllers;

use App\DataTables\BankReportDataTable;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;

class BankReportController extends AccountBaseController
{
    public $arr = [];

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('app.menu.bankReport');

        $this->middleware(function ($request, $next) {
            abort_403(!in_array('employees', $this->user->modules));

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index(BankReportDataTable $dataTable)
    {
        $this->authorize('viewAny', User::class);

        $this->locations = Location::get();
        $this->months = $this->months();

        return $dataTable->render('bank-reports.index', $this->data);
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
}
