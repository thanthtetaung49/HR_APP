<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Helper\Reply;
use App\Models\Location;
use App\Models\Designation;
use Illuminate\Http\Request;
use App\Models\ManPowerReport;
use App\Models\ReportPermission;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ManPowerReportExport;
use App\Models\ManPowerReportHistory;
use Illuminate\Support\Facades\Validator;
use App\DataTables\ManPowerReportDataTable;
use App\DataTables\ManPowerReportHistoryDataTable;

class ManPowerReportController extends AccountBaseController
{
    public $arr = [];

    public function __construct()
    {

        parent::__construct();
        $this->pageTitle = __('app.menu.manPowerReport');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(ManPowerReportDataTable $dataTable)
    {
        $this->authorize('viewAny', ReportPermission::class);

        $this->reports = ManPowerReport::get();
        $this->data['departments'] = Team::get();
        $this->data['locations'] = Location::get();
        $this->data['budgetYears'] = ManPowerReport::select('budget_year')
            ->distinct()
            ->orderBy('budget_year', 'desc')
            ->get()
            ->pluck('budget_year');

        $this->data['designations'] = Designation::select('id', 'name')
            ->get();

        return $dataTable->render('man-power-reports.index', $this->data);
    }

    public function history(Request $request, $id)
    {
        $this->authorize('viewAny', ReportPermission::class);

        $dataTable = new ManPowerReportHistoryDataTable($id);
        $this->reports = ManPowerReport::get();

        return $dataTable->render('man-power-reports.history', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('viewAny', ReportPermission::class);

        $this->data['pageTitle'] = __('app.menu.manPowerReport');

        $roles = auth()->user()->roles;

        $isAdmin = $roles->contains(function ($role) {
            return $role->name === 'admin';
        });

        $isHRmanager = $roles->contains(function ($role) {
            return $role->name === 'hr-manager';
        });

        $isEmployee = $roles->contains(function ($role) {
            return $role->name === 'employee';
        });


        if ($isAdmin || $isHRmanager) {
            $this->data['departments'] = Team::get();
        } elseif ($isEmployee) {
            $this->data['departments'] = Team::where('id', auth()->user()->department_id)->get();
        }

        $this->data['designations'] = Designation::select('id', 'name')
            ->get();

        return view('man-power-reports.create', $this->data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $man_power_setup = $request->man_power_setup;
        $man_power_basic_salary = $request->man_power_basic_salary;
        $team_id = $request->team_id;
        $budget_year = $request->budget_year;
        $quarter = $request->quarter;
        $position_id = $request->position_id;
        $status = $request->status ? $request->status : 'pending';
        $remark_from = $request->remark_from;
        $remark_to = $request->remark_to;

        Validator::make($request->all(), [
            'man_power_setup' => 'required',
            'man_power_basic_salary' => 'required',
            'team_id' => 'required',
            'budget_year' => 'required|date_format:Y',
            'position_id' => 'required',
        ])->validate();

        $manPowerReport = ManPowerReport::create([
            'man_power_setup' => $man_power_setup,
            'man_power_basic_salary' => $man_power_basic_salary,
            'team_id' => $team_id,
            'budget_year' => $budget_year,
            'quarter' => $quarter,
            'position_id' => $position_id,
            'status' => $status,
            'remark_from' => $remark_from,
            'remark_to' => $remark_to,
            'created_by' => user()->id,
            'approved_date' => $status == 'approved' ? now() : null,
        ]);

        ManPowerReportHistory::create([
            'man_power_report_id' => $manPowerReport->id,
            'man_power_setup' => $manPowerReport->man_power_setup,
            'man_power_basic_salary' => $manPowerReport->man_power_basic_salary,
            'team_id' => $manPowerReport->team_id,
            'budget_year' => $manPowerReport->budget_year,
            'quarter' => $manPowerReport->quarter,
            'position_id' => $manPowerReport->position_id,
            'status' => $manPowerReport->status,
            'remark_from' => $manPowerReport->remark_from,
            'remark_to' => $manPowerReport->remark_to,
            'created_by' => user()->id,
            'approved_date' => $manPowerReport->approved_date,
            'updated_date' => now(),
        ]);

        return redirect()->route('man-power-reports.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $this->authorize('viewAny', ReportPermission::class);

        $this->reports = ManPowerReport::findOrFail($id);
        $this->data['pageTitle'] = __('app.menu.manPowerReport');

        $roles = auth()->user()->roles;

        $isAdmin = $roles->contains(function ($role) {
            return $role->name === 'admin';
        });

        $isHRmanager = $roles->contains(function ($role) {
            return $role->name === 'hr-manager';
        });

        $isEmployee = $roles->contains(function ($role) {
            return $role->name === 'employee';
        });


        if ($isAdmin || $isHRmanager) {
            $this->data['departments'] = Team::get();
        } elseif ($isEmployee) {
            $this->data['departments'] = Team::where('id', auth()->user()->department_id)->get();
        }

        $this->view = 'man-power-reports.ajax.show';

        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        return view('man-power-reports.show', $this->data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $this->authorize('viewAny', ReportPermission::class);

        $this->reports = ManPowerReport::findOrFail($id);
        $this->data['pageTitle'] = __('app.menu.manPowerReport');
        $roles = auth()->user()->roles;

        $isAdmin = $roles->contains(function ($role) {
            return $role->name === 'admin';
        });

        $isHRmanager = $roles->contains(function ($role) {
            return $role->name === 'hr-manager';
        });

        $isEmployee = $roles->contains(function ($role) {
            return $role->name === 'employee';
        });


        if ($isAdmin || $isHRmanager) {
            $this->data['departments'] = Team::get();
        } elseif ($isEmployee) {
            $this->data['departments'] = Team::where('id', auth()->user()->department_id)->get();
        }

        $teams = Team::where('id', $this->reports->team_id)->first();

        // dd($teams->toArray());

        $this->data['designations'] = Designation::select('id', 'name')
            ->whereIn('id', json_decode($teams->designation_ids))
            ->get();

        return view('man-power-reports.ajax.edit', $this->data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(string $id, Request $request)
    {
        $reports = ManPowerReport::findOrFail($id);

        Validator::make($request->all(), [
            'man_power_setup' => 'required',
            'man_power_basic_salary' => 'required',
            'team_id' => 'required',
            'budget_year' => 'required|date_format:Y',
            'position_id' => 'required',
        ])->validate();

        $reports->update([
            'man_power_setup' => $request->man_power_setup,
            'man_power_basic_salary' => $request->man_power_basic_salary,
            'team_id' => $request->team_id,
            'budget_year' => $request->budget_year,
            'quarter' => $request->quarter,
            'position_id' => $request->position_id,
            'status' => $request->status ? $request->status : 'pending',
            'remark_from' => $request->remark_from,
            'remark_to' => $request->remark_to,
            'approved_date' => $request->status == 'approved' ? now() : null,
        ]);

        ManPowerReportHistory::create([
            'man_power_report_id' => $reports->id,
            'man_power_setup' => $reports->man_power_setup,
            'man_power_basic_salary' => $reports->man_power_basic_salary,
            'team_id' => $reports->team_id,
            'budget_year' => $reports->budget_year,
            'quarter' => $reports->quarter,
            'position_id' => $reports->position_id,
            'status' => $reports->status,
            'remark_from' => $request->remark_from,
            'remark_to' => $request->remark_to,
            'created_by' => user()->id,
            'approved_date' => $reports->approved_date,
            'updated_date' => now(),
        ]);

        return redirect()->route('man-power-reports.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $reports = ManPowerReport::findOrFail($id);
        $reports->delete();

        return response()->json(['messge' => 'success', 'redirectUrl' => route('man-power-reports.index')]);
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
            ManPowerReport::where('id', $id)->delete();
        }
    }

    public function applyDepartmentFilter(Request $request)
    {
        $team = Team::where('id', $request->teamId)->first();

        $designations = Designation::whereIn('id', json_decode($team->designation_ids))->get();

        return response()->json(['designations' => $designations]);
    }

    public function exportAllAttendance($location, $team, $position, $budgetYear, $quarter)
    {
        abort_403(!canDataTableExport());

        $date = now()->format('Y-m-d');
        $companyDateFormat = $this->company->date_format;

        return Excel::download(new ManPowerReportExport($location, $team, $position, $budgetYear, $quarter, $companyDateFormat), 'Man_Power_Report_' . $date . '.xlsx');
    }
}
