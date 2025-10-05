<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Helper\Reply;
use App\Models\Location;
use Illuminate\Http\Request;
use App\Models\ManPowerReport;
use Illuminate\Support\Facades\Validator;
use App\DataTables\ManPowerReportDataTable;

class ManPowerReportController extends AccountBaseController
{
    public $arr = [];

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('app.menu.manPowerReport');

        $this->middleware(function ($request, $next) {
            abort_403(!in_array('employees', $this->user->modules));

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index(ManPowerReportDataTable $dataTable)
    {
        $viewPermission = user()->permission('view_department');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));

        $this->reports = ManPowerReport::get();
        $this->data['departments'] = Team::get();
        $this->data['locations'] = Location::get();
        $this->data['budgetYears'] = ManPowerReport::select('budget_year')
            ->distinct()
            ->orderBy('budget_year', 'desc')
            ->get()
            ->pluck('budget_year');


        return $dataTable->render('man-power-reports.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->view = 'man-power-reports.ajax.create';
        $this->data['pageTitle'] = 'Add Man Power';
        $this->data['departments'] = Team::get();

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

        Validator::make($request->all(), [
            'man_power_setup' => 'required',
            'man_power_basic_salary' => 'required',
            'team_id' => 'required',
            'budget_year' => 'required|date_format:Y',
        ])->validate();

        ManPowerReport::create([
            'man_power_setup' => $man_power_setup,
            'man_power_basic_salary' => $man_power_basic_salary,
            'team_id' => $team_id,
            'budget_year' => $budget_year
        ]);

        return redirect()->route('man-power-reports.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $this->reports = ManPowerReport::findOrFail($id);
        $this->data['pageTitle'] = 'Show Man Power';
        $this->data['departments'] = Team::get();

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
        $this->reports = ManPowerReport::findOrFail($id);
        $this->data['pageTitle'] = 'Edit Man Power';
        $this->data['departments'] = Team::get();

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
        ])->validate();

        $reports->update([
            'man_power_setup' => $request->man_power_setup,
            'man_power_basic_salary' => $request->man_power_basic_salary,
            'team_id' => $request->team_id,
            'budget_year' => $request->budget_year
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
        $deletePermission = user()->permission('delete_department');
        abort_403($deletePermission != 'all');

        $item = explode(',', $request->row_ids);

        if (($key = array_search('on', $item)) !== false) {
            unset($item[$key]);
        }

        foreach ($item as $id) {
            ManPowerReport::where('id', $id)->delete();
        }
    }
}
