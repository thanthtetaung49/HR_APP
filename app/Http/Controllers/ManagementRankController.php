<?php

namespace App\Http\Controllers;

use App\DataTables\ManagementRankDataTable;
use App\Helper\Reply;
use App\Models\ManagementRank;
use App\Models\User;
use Illuminate\Http\Request;

class ManagementRankController extends AccountBaseController
{
    public $arr = [];

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('app.menu.managementRanks');

        $this->middleware(function ($request, $next) {
            abort_403(!in_array('employees', $this->user->modules));

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index(ManagementRankDataTable $dataTable)
    {
        $this->authorize('viewAny', User::class);

        return $dataTable->render('management-ranks.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->data['pageTitle'] = 'Add Management Rank';

        return view('management-ranks.create', $this->data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'rank' => 'required',
        ]);

        // dd($request->all());

        ManagementRank::create([
            'name' => $request->name,
            'rank' => json_encode($request->rank),
        ]);

        return redirect()->route('management-ranks.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $this->managementRank = ManagementRank::findOrFail($id);
        $this->data['pageTitle'] = 'Show Management Rank';
        $this->view = 'management-ranks.ajax.show';

        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        return view('management-ranks.show', $this->data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $this->managementRank = ManagementRank::findOrFail($id);
        $this->data['pageTitle'] = 'Edit Management Rank';

        return view('management-ranks.ajax.edit', $this->data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $managementRank = ManagementRank::findOrFail($id);

        $request->validate([
            'name' => 'required',
            'rank' => 'required',
        ]);

        $managementRank->update([
            'name' => $request->name,
            'rank' => json_encode($request->rank),
        ]);

        return redirect()->route('management-ranks.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $managementRank = ManagementRank::findOrFail($id);
        $managementRank->delete();

        return response()->json(['messge' => 'success', 'redirectUrl' => route('management-ranks.index')]);
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
            ManagementRank::where('id', $id)->delete();
        }
    }
}
