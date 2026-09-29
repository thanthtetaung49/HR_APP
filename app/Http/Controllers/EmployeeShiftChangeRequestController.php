<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Helper\Reply;
use Illuminate\Http\Request;
use App\Models\EmployeeShift;
use App\Models\AttendanceSetting;
use App\Models\EmployeeDetails;
use App\Models\EmployeeShiftSchedule;
use App\Models\EmployeeShiftChangeRequest;
use App\DataTables\ShiftChangeRequestDataTable;
use App\Http\Requests\EmployeeShiftChange\UpdateRequest;

class EmployeeShiftChangeRequestController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.shiftRoster';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('attendance', $this->user->modules));

            return $next($request);
        });
    }

    public function index(ShiftChangeRequestDataTable $dataTable)
    {
        abort_403(user()->permission('manage_employee_shifts') !== 'all');

        // $this->manageEmployeeShifts = user()->permission('manage_employee_shifts');
        $this->canFinalApprove = $this->isFinalApprover();
        $hasDirectReports = EmployeeDetails::where('reporting_to', user()->id)->exists();

        abort_403(!$this->canFinalApprove && !$hasDirectReports);

        if (!request()->ajax()) {
            $this->employees = User::allEmployees(null, true, 'all');
            // $this->employeeShifts = EmployeeShift::where('shift_name', '<>', 'Day Off')->get();
            $this->employeeShifts = EmployeeShift::get();
        }

        // dd($this->employeeShifts);

        return $dataTable->render('shift-change.index', $this->data);
    }

    public function edit(Request $request, $id)
    {
        $shiftId = $request->shift_id;
        $this->day = Carbon::createFromFormat($this->company->date_format, $request->date)->dayOfWeek;
        $this->shift = EmployeeShiftSchedule::with('requestChange', 'requestChange.shift')->findOrFail($id);
        // $this->employeeShifts = EmployeeShift::where('shift_name', '<>', 'Day Off')
        //     ->where('id', '!=', $shiftId )
        //     ->where('office_open_days', 'like', '%"'.$this->day.'"%')
        //     ->get();
        $this->employeeShifts = EmployeeShift::where('id', '!=', $shiftId)
            ->where('office_open_days', 'like', '%"' . $this->day . '"%')
            ->get();

        return view('shift-rosters.ajax.request-change', $this->data);
    }

    public function update(UpdateRequest $request, $id)
    {
        $requestChange = EmployeeShiftChangeRequest::firstOrNew([
            'shift_schedule_id' => $id,
            'status' => 'waiting'
        ]);

        $requestChange->employee_shift_id = $request->employee_shift_id;
        $requestChange->reason = $request->reason;
        $requestChange->save();

        return Reply::success(__('messages.requestSubmitSuccess'));
    }

    public function destroy($id)
    {
        EmployeeShiftChangeRequest::destroy($id);

        return Reply::success(__('messages.deleteSuccess'));
    }

    public function approveRequest($id)
    {
        return $this->updateRequestStatus($id, 'accepted');
    }

    public function declineRequest($id)
    {
        return $this->updateRequestStatus($id, 'rejected');
    }

    public function preApprove($id)
    {
        $changeRequest = EmployeeShiftChangeRequest::with('shiftSchedule')->findOrFail($id);

        $managerPermission = AttendanceSetting::where('company_id', company()->id)
            ->value('shift_manager_permission') ?? 'cannot-approve';

        $isReportingManager = EmployeeDetails::where('user_id', $changeRequest->shiftSchedule->user_id)
            ->where('reporting_to', user()->id)
            ->exists();

        abort_403($managerPermission !== 'pre-approve');
        abort_403(!$isReportingManager);

        if ($changeRequest->status !== 'waiting') {
            return Reply::error(__('modules.attendance.onlyWaitingShiftAction'));
        }

        if ($changeRequest->manager_status_permission === 'pre-approve') {
            return Reply::error(__('modules.attendance.shiftAlreadyPreApproved'));
        }

        $changeRequest->manager_status_permission = 'pre-approve';
        $changeRequest->action_by = user()->id;
        $changeRequest->save();

        return Reply::success(__('modules.attendance.shiftPreApproved'));
    }

    public function changeManagerPermission(Request $request)
    {
        // dd($request->all());
        abort_403(user()->permission('manage_attendance_setting') !== 'all');

        $validated = $request->validate([
            'shift_manager_permission' => [
                'required',
                'in:cannot-approve,approved,pre-approve',
            ],
        ]);

        $setting = AttendanceSetting::firstOrFail();
        $setting->shift_manager_permission = $validated['shift_manager_permission'];
        $setting->save();

        session()->forget(['attendance_setting', 'company']);

        return Reply::success(__('messages.updateSuccess'));
    }

    private function updateRequestStatus(int $id, string $status)
    {
        $changeRequest = $this->findRequest($id);

        if ($changeRequest->status !== 'waiting') {
            return Reply::error(__('modules.attendance.onlyWaitingShiftAction'));
        }

        $managerPermission = attendance_setting()->shift_manager_permission ?? 'cannot-approve';
        $isReportingManager = $this->isReportingManager($changeRequest);
        $isFinalApprover = $this->isFinalApprover();

        if (
            $isReportingManager
            && !$isFinalApprover
            && $changeRequest->manager_status_permission === 'pre-approve'
        ) {
            abort_403();
        }

        $canPerformFinalAction = $isFinalApprover
            || ($isReportingManager && $managerPermission === 'approved')
            || ($isReportingManager && $managerPermission === 'pre-approve' && $status === 'rejected');

        abort_403(!$canPerformFinalAction);

        $changeRequest->status = $status;
        $changeRequest->action_by = user()->id;
        $changeRequest->save();

        return Reply::dataOnly(['status' => 'success']);
    }

    public function applyQuickAction(Request $request)
    {
        abort_403(!$this->isFinalApprover());

        $request->validate([
            'action_type' => ['required', 'in:change-status'],
            'status' => ['required', 'in:accepted,rejected'],
            'row_ids' => ['required', 'string'],
        ]);

        switch ($request->action_type) {
            case 'change-status':
                $this->changeBulkStatus($request);

                return Reply::success(__('messages.updateSuccess'));
            default:
                return Reply::error(__('messages.selectAction'));
        }
    }

    protected function changeBulkStatus($request)
    {
        $shiftRequests = EmployeeShiftChangeRequest::whereIn('id', explode(',', $request->row_ids))->get();

        foreach ($shiftRequests as $key => $changeRequest) {
            if ($changeRequest->status !== 'waiting') {
                continue;
            }

            $changeRequest->status = $request->status;
            $changeRequest->action_by = user()->id;
            $changeRequest->save();
        }
    }

    private function findRequest(int $id): EmployeeShiftChangeRequest
    {
        return EmployeeShiftChangeRequest::with([
            'shiftSchedule.user.employeeDetails',
        ])->findOrFail($id);
    }

    private function isReportingManager(EmployeeShiftChangeRequest $changeRequest): bool
    {
        $employee = optional($changeRequest->shiftSchedule)->user;
        $reportingManagerId = optional(optional($employee)->employeeDetails)->reporting_to;

        return (int) $reportingManagerId === (int) user()->id
            && (int) optional($employee)->id !== (int) user()->id;
    }

    private function isFinalApprover(): bool
    {
        return user()->hasRole('admin') || user()->hasRole('hr-manager');
    }
}
