<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Helper\Reply;
use App\Models\Skill;
use App\Models\Location;
use App\Enums\Salutation;
use App\Models\Designation;
use Illuminate\Http\Request;
use App\Models\CompanyAddress;
use App\Models\EmployeeDetails;
use App\Models\LanguageSetting;
use App\DataTables\LocationDataTable;
use Illuminate\Support\Facades\Validator;

class LocationController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('app.menu.location');

        $this->middleware(function ($request, $next) {
            abort_403(!in_array('employees', $this->user->modules));

            return $next($request);
        });
    }

    public function index(LocationDataTable $dataTable) {

        $viewPermission = user()->permission('view_department');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));


        $this->locations = Location::get();

        return $dataTable->render('location.index', $this->data);
    }

    public function create()
    {

        $this->view = 'location.ajax.create';
        $this->data['pageTitle'] = 'Add Location';

        if (request()->model == true) {
            return view('location.create_location', $this->data);
        }

        return view('location.create', $this->data);
    }

    // add location in departments
    public function store(Request $request)
    {
        $location_name = $request->location;

        Validator::make($request->all(), ['location' => 'required'])->validate();

        Location::create(['location_name' => $location_name]);

        return redirect()->route('location.index');
    }

    public function ajaxStore(Request $request) {
        $location_name = $request->location;

        Validator::make($request->all(), ['location' => 'required'])->validate();
        Location::create(['location_name' => $location_name]);

        $locations = Location::get();

        return response()->json([
            'data' => $locations,
        ]);
    }

    public function show($id)
    {
        $this->location = Location::findOrFail($id);

        $this->view = 'location.ajax.show';

        // dd($this->location->toArray());

        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        return view('location.create', $this->data);
    }

    public function edit($id)
    {
        $this->location = Location::findOrFail($id);

        return view('location.ajax.edit', $this->data);
    }

    public function update($id, Request $request) {
        $location = Location::findOrFail($id);
        $location_name = $request->location;

        Validator::make($request->all() , ['location'=> 'required'])->validate();

        $location->update(['location_name' => $location_name]);

        return redirect()->route('location.index');
    }

    public function delete($id) {
        $location = Location::findOrFail($id);
        $location->delete();

       return response()->json(['messge' => 'success', 'redirectUrl' => route('location.index')]);
    }
}
