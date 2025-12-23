<?php

namespace App\Http\Controllers;

use App\DataTables\SubCriteriaDataTable;
use App\Helper\Reply;
use App\Models\Criteria;
use App\Models\SubCriteria;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubCriteriaController extends AccountBaseController
{
    public $arr = [];

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('app.menu.subCriteria');

        $this->middleware(function ($request, $next) {
            abort_403(!in_array('employees', $this->user->modules));

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index(SubCriteriaDataTable $dataTable)
    {
        $this->authorize('viewAny', User::class);

        $this->subCriteria = SubCriteria::get();

        return $dataTable->render('sub-criteria.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->data['pageTitle'] =__('app.menu.subCriteria');

        return view('sub-criteria.create', $this->data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'sub_criteria' => 'required|unique:sub_criterias,sub_criteria',
            'responsible_person' => 'required|string',
            'accountability' => 'required|string',
            'action_taken' => 'required|string'
        ]);

        SubCriteria::create([
            'sub_criteria' => $request->sub_criteria,
            'responsible_person' => $request->responsible_person,
            'accountability' => $request->accountability,
            'action_taken' => $request->action_taken,
        ]);

        return redirect()->route('sub-criteria.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $this->subCriteria = SubCriteria::findOrFail($id);
        $this->data['pageTitle'] = __('app.menu.subCriteria');
        $this->view = 'sub-criteria.ajax.show';

        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        return view('sub-criteria.show', $this->data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $this->subCriteria = SubCriteria::findOrFail($id);
        $this->data['pageTitle'] = __('app.menu.subCriteria');

        return view('sub-criteria.ajax.edit', $this->data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $subCriteria = SubCriteria::findOrFail($id);

        $request->validate([
            'sub_criteria' => ['required', 'unique:sub_criterias,sub_criteria,' . $subCriteria->id],
            'responsible_person' => 'required|string',
            'accountability' => 'required|string',
            'action_taken' => 'required|string'
        ]);

        $subCriteria->update([
            'sub_criteria' => $request->sub_criteria,
            'responsible_person' => $request->responsible_person,
            'accountability' => $request->accountability,
            'action_taken' => $request->action_taken,
        ]);

        return redirect()->route('sub-criteria.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $subCriteria = SubCriteria::findOrFail($id);
        $subCriteria->delete();

        return response()->json(['messge' => 'success', 'redirectUrl' => route('sub-criteria.index')]);
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
            SubCriteria::where('id', $id)->delete();
        }
    }
}
