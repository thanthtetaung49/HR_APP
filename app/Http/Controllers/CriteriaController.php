<?php

namespace App\Http\Controllers;

use App\DataTables\CriteriaDataTable;
use App\Helper\Reply;
use App\Models\Criteria;
use App\Models\EmployeeDetails;
use App\Models\SubCriteria;
use Illuminate\Http\Request;

class CriteriaController extends AccountBaseController
{
    public $arr = [];

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('app.menu.exitsReason');

        $this->middleware(function ($request, $next) {
            abort_403(!in_array('employees', $this->user->modules));

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index(CriteriaDataTable $dataTable)
    {
        $viewPermission = user()->permission('view_criteria');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));

        return $dataTable->render('criteria.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->data['pageTitle'] = 'Add Exit Reason';
        $this->data['subCriterias'] = SubCriteria::get();

        $employee = new EmployeeDetails();
        $getCustomFieldGroupsWithFields = $employee->getCustomFieldGroupsWithFields();

        if ($getCustomFieldGroupsWithFields) {
            $this->fields = $getCustomFieldGroupsWithFields->fields;
        }

        return view('criteria.create', $this->data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'exit_reason_id' => 'required|string',
            'sub_criteria_ids' => 'required|array',
            'sub_criteria_ids.*' => 'integer',
        ]);

        // $data = collect($request->sub_criteria_ids)->map(function ($subCriteriaId) use ($request) {
        //     return [
        //         'exit_reason_id' => $request->exit_reason_id,
        //         'sub_criteria_id' => $subCriteriaId,
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ];
        // })->toArray();

        // Criteria::insert($data);

        Criteria::create([
            'exit_reason_id' => $request->exit_reason_id,
            'sub_criteria_ids' => json_encode($request->sub_criteria_ids),
        ]);

        return redirect()->route('criteria.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $this->criteria = Criteria::findOrFail($id);
        $this->data['pageTitle'] = 'Show Exit Reason';
        $this->view = 'criteria.ajax.show';

        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        return view('criteria.show', $this->data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $this->criteria = Criteria::findOrFail($id);
        $this->data['pageTitle'] = 'Edit Exit Reason';
        $this->data['subCriterias'] = SubCriteria::get();

        $employee = new EmployeeDetails();
        $getCustomFieldGroupsWithFields = $employee->getCustomFieldGroupsWithFields();

        if ($getCustomFieldGroupsWithFields) {
            $this->fields = $getCustomFieldGroupsWithFields->fields;
        }

        return view('criteria.ajax.edit', $this->data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $criteria = Criteria::findOrFail($id);

        $request->validate([
            'exit_reason_id' => 'required|string',
            'sub_criteria_ids' => 'required|array',
            'sub_criteria_ids.*' => 'integer',
        ]);

        // $subCriteriaIds = $request->sub_criteria_ids;

        // $criteria->delete();

        // // Insert new rows
        // $data = collect($subCriteriaIds)->map(function ($subId) use ($request) {
        //     return [
        //         'exit_reason_id' => $request->exit_reason_id,
        //         'sub_criteria_id' => $subId,
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ];
        // })->toArray();

        // Criteria::insert($data);

        $criteria->update([
            'exit_reason_id' => $request->exit_reason_id,
            'sub_criteria_ids' => json_encode($request->sub_criteria_ids),
        ]);

        return redirect()->route('criteria.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $criteria = Criteria::findOrFail($id);
        $criteria->delete();

        return response()->json(['messge' => 'success', 'redirectUrl' => route('criteria.index')]);
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
            Criteria::where('id', $id)->delete();
        }
    }
}
