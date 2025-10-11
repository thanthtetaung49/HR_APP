<?php

namespace App\Http\Controllers;

use App\DataTables\CauseDataTable;
use App\Helper\Reply;
use App\Models\Cause;
use App\Models\Criteria;
use App\Models\EmployeeDetails;
use Illuminate\Http\Request;

class CauseController extends AccountBaseController
{
    public $arr = [];

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('app.menu.cause');

        $this->middleware(function ($request, $next) {
            abort_403(!in_array('employees', $this->user->modules));

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index(CauseDataTable $dataTable)
    {
        $viewPermission = user()->permission('view_department');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));

        return $dataTable->render('cause.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->data['pageTitle'] = 'Add Cause';
        $this->data['criterias'] = Criteria::get();

        $employee = new EmployeeDetails();
        $getCustomFieldGroupsWithFields = $employee->getCustomFieldGroupsWithFields();

         if ($getCustomFieldGroupsWithFields) {
            $this->fields = $getCustomFieldGroupsWithFields->fields;
        }

        return view('cause.create', $this->data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'exit_reason_id' => 'required|string',
            'action_taken' => 'required|string',
            'criteria_id' => 'required|integer',
        ]);

        Cause::create([
            'exit_reason_id' => $request->exit_reason_id,
            'criteria_id' => $request->criteria_id,
            'action_taken' => $request->action_taken,
        ]);

        return redirect()->route('causes.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $this->cause = Cause::findOrFail($id);
        $this->data['pageTitle'] = 'Show Cause';
        $this->view = 'cause.ajax.show';

        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        return view('cause.show', $this->data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $this->cause = Cause::findOrFail($id);
        $this->data['pageTitle'] = 'Edit Cause';
        $this->data['criterias'] = Criteria::get();

        return view('cause.ajax.edit', $this->data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $cause = Cause::findOrFail($id);

        $request->validate([
            'exit_reason_id' => 'required|string',
            'action_taken' => 'required|string',
            'criteria_id' => 'required|integer',
        ]);

        $cause->update([
            'exit_reason_id' => $request->exit_reason_id,
            'criteria_id' => $request->criteria_id,
            'action_taken' => $request->action_taken,
        ]);

        return redirect()->route('causes.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $cause = Cause::findOrFail($id);
        $cause->delete();

        return response()->json(['messge' => 'success', 'redirectUrl' => route('causes.index')]);
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
            Cause::where('id', $id)->delete();
        }
    }
}
