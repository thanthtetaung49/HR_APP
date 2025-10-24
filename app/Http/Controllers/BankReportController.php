<?php

namespace App\Http\Controllers;

use App\DataTables\BankReportDataTable;
use App\Models\Location;
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
        $this->locations = Location::get();

        return $dataTable->render('bank-reports.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
