<?php

namespace App\Http\Controllers;

use App\DataTables\ReportPermissionDataTable;
use App\Helper\Reply;
use App\Models\Designation;
use App\Models\Location;
use App\Models\ReportPermission;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use QuickBooksOnline\API\Facades\Department;

class ReportPermissionController extends AccountBaseController
{
    public $arr = [];

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('app.menu.reportPermission');

        $this->middleware(function ($request, $next) {
            abort_403(!in_array('employees', $this->user->modules));

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index(ReportPermissionDataTable $dataTable)
    {
        $permission = user()->permission('view_report_permission');
        abort_403(!in_array($permission, ['all', 'added', 'owned', 'both']));

        return $dataTable->render('report-permission.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->data['pageTitle'] = 'Add Report Permission';
        $this->locations = Location::get();
        $this->users = User::with('roles')->select('name', 'id')
            ->get();

        return view('report-permission.create', $this->data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'location_id' => 'required',
            'team_id' => 'required',
            'designation_id' => 'required',
            'user_id' => 'required',
            'report_id' => 'required',
            'permission' => 'required',
        ]);

        ReportPermission::create([
            'location_id' => $request->location_id,
            'team_id' => $request->team_id,
            'designation_id' => $request->designation_id,
            'user_id' => $request->user_id,
            'report_id' => $request->report_id,
            'permission' => $request->permission == 0 ? 'no' : 'yes',
        ]);

        return redirect()->route('report-permission.index');
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
        $this->report = ReportPermission::findOrFail($id);
        $this->data['pageTitle'] = 'Edit Report Permission';
        $this->locations = Location::get();
        $this->departments = Team::where('location_id', $this->report->location_id)->get();
        $this->designations = Designation::whereIn('id', json_decode($this->report->team->designation_ids))->get();
        $this->users = User::where('designation_id', $this->report->desingation->id)->get();

        return view('report-permission.ajax.edit', $this->data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $report = ReportPermission::findOrFail($id);

        $request->validate([
            'location_id' => 'required',
            'team_id' => 'required',
            'designation_id' => 'required',
            'user_id' => 'required',
            'report_id' => 'required',
            'permission' => 'required',
        ]);

        $report->update([
            'location_id' => $request->location_id,
            'team_id' => $request->team_id,
            'designation_id' => $request->designation_id,
            'user_id' => $request->user_id,
            'report_id' => $request->report_id,
            'permission' => $request->permission == 0 ? 'no' : 'yes',
        ]);

        return redirect()->route('report-permission.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $report = ReportPermission::findOrFail($id);
        $report->delete();

        return response()->json(['messge' => 'success', 'redirectUrl' => route('report-permission.index')]);
    }

    public function applyQuickAction(Request $request)
    {
        if ($request->action_type == 'delete') {
            $this->deleteRecords($request);
            return Reply::success(__('messages.deleteSuccess'));
        }

        return Reply::error(__('messages.selectAction'));
    }

    protected function deleteRecords($request)
    {
        $item = explode(',', $request->row_ids);

        if (($key = array_search('on', $item)) !== false) {
            unset($item[$key]);
        }

        foreach ($item as $id) {
            ReportPermission::where('id', $id)->delete();
        }
    }
}
