<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
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

    public function index()
    {
        return view('location.index', $this->data);
    }

    public function store(Request $request) {
        $location_name = $request->location;

        Validator::make($request->all(), ['location' => 'required'])->validate();

        Location::create(['location_name' => $location_name]);

        return redirect()->route('departments.index');
    }



}
