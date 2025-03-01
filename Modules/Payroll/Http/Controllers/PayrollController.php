<?php

namespace Modules\Payroll\Http\Controllers;

use Carbon\Carbon;
use App\Models\Team;
use App\Models\User;
use App\Helper\Reply;
use App\Models\Leave;
use App\Models\Expense;
use App\Models\Holiday;
use App\Models\Allowance;
use App\Models\Attendance;
use App\Models\Designation;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\ProjectTimeLog;
use App\Models\EmployeeDetails;
use App\Models\ExpensesCategory;
use App\Models\AttendanceSetting;
use Illuminate\Support\Facades\DB;
use Modules\Payroll\Entities\SalaryTds;
use Modules\Payroll\Entities\SalarySlip;
use Modules\Payroll\Entities\PayrollCycle;
use Modules\Payroll\Entities\PayrollSetting;
use Modules\Payroll\Entities\OvertimeRequest;
use App\Http\Controllers\AccountBaseController;
use App\Models\Detection;
use Modules\Payroll\DataTables\PayrollDataTable;
use Modules\Payroll\Entities\EmployeeSalaryGroup;
use Modules\Payroll\Entities\SalaryPaymentMethod;
use Modules\Payroll\Entities\EmployeeMonthlySalary;
use Modules\Payroll\Notifications\SalaryStatusEmail;
use Modules\Payroll\Entities\EmployeeVariableComponent;

class PayrollController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('payroll::app.menu.payroll');
        $this->middleware(function ($request, $next) {
            abort_403(!in_array(PayrollSetting::MODULE_NAME, $this->user->modules));

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */

    public function index(PayrollDataTable $dataTable)
    {
        $viewPermission = user()->permission('view_payroll');

        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));

        $this->departments = Team::all();
        $this->designations = Designation::all();
        $this->payrollCycles = PayrollCycle::all();

        $now = now();
        $this->year = $now->format('Y');
        $this->month = $now->format('m');

        if (!in_array('admin', user_roles())) {
            $this->month = 'all';
        }

        $this->teams = Team::all();

        $payrollCycle = PayrollCycle::where('cycle', 'monthly')->first();

        $this->employees = User::join('employee_details', 'employee_details.user_id', '=', 'users.id')
            ->join('employee_payroll_cycles', 'employee_payroll_cycles.user_id', '=', 'users.id')
            ->where('employee_payroll_cycles.payroll_cycle_id', $payrollCycle->id)
            ->select('users.*')->get();

        $this->salaryPaymentMethods = SalaryPaymentMethod::all();

        return $dataTable->render('payroll::payroll.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        $viewPermission = user()->permission('view_payroll');

        $this->salarySlip = SalarySlip::with('user', 'user.employeeDetail', 'salary_group', 'salary_payment_method', 'payroll_cycle', 'user.userAllowances')
            ->findOrFail($id);

        abort_403(!($viewPermission == 'all'
            || ($viewPermission == 'owned' && $this->salarySlip->user_id == user()->id)
            || ($viewPermission == 'added' && $this->salarySlip->added_by == user()->id)
            || ($viewPermission == 'both' && ($this->salarySlip->user_id == user()->id || $this->salarySlip->added_by == user()->id))
        ));

        $this->salaryPaymentMethods = SalaryPaymentMethod::all();

        // $salaryJson = json_decode($this->salarySlip->salary_json, true);
        // $this->earnings = $salaryJson['earnings'];
        // $this->deductions = $salaryJson['deductions'];
        // $extraJson = json_decode($this->salarySlip->extra_json, true);
        // $additionalEarnings = json_decode($this->salarySlip->additional_earning_json, true);

        if ($this->salarySlip->payroll_cycle->cycle == 'monthly') {
            $this->basicSalary = (float) $this->salarySlip->user->userAllowances->basic_salary;
        } elseif ($this->salarySlip->payroll_cycle->cycle == 'weekly') {
            $this->basicSalary = ((float) $this->salarySlip->user->userAllowances->basic_salary / 4);
        } elseif ($this->salarySlip->payroll_cycle->cycle == 'semimonthly') {
            $this->basicSalary = ((float) $this->salarySlip->user->userAllowances->basic_salary / 2);
        } elseif ($this->salarySlip->payroll_cycle->cycle == 'biweekly') {
            $perday = ((float) $this->salarySlip->user->userAllowances->basic_salary / 30);
            $this->basicSalary = $perday * 14;
        }

        $this->technicalAllowance = $this->salarySlip->user->userAllowances->technical_allowance;
        $this->livingCostAllowance = $this->salarySlip->user->userAllowances->living_cost_allowance;
        $this->specialAllowance = $this->salarySlip->user->userAllowances->special_allowance;


        $this->totalAllowance = $this->basicSalary + $this->technicalAllowance + $this->livingCostAllowance + $this->specialAllowance;

        $this->monthlySalary = Allowance::where('user_id',  $this->salarySlip->user_id)->first();
        $this->monthlyOtherDetection = Detection::where('user_id', $this->salarySlip->user_id)->first();

        $startDate = Carbon::parse($this->salarySlip->salary_from);
        $endDate = $startDate->clone()->parse($this->salarySlip->salary_to);


        $subQuery = Attendance::select(
            'clock_in_time',
            DB::raw('ROW_NUMBER() OVER (PARTITION BY DATE(clock_in_time) ORDER BY clock_in_time ASC) as row_num')
        )
            ->where('user_id', $this->salarySlip->user_id)
            ->whereDate('clock_in_time', '>=', $startDate)
            ->whereDate('clock_in_time', '<=', $endDate);

        $attendanceLateInMonth = Attendance::select(
            DB::raw("DATE(clock_in_time) as presentDate"),
            "late",
            "clock_in_time"
        )
            ->where('user_id', $this->salarySlip->user_id)
            ->whereDate('clock_in_time', '>=', "2025-01-26")
            ->whereDate('clock_in_time', '<=', "2025-02-25")
            ->whereIn('clock_in_time', function ($query) use ($subQuery) {
                $query->select('clock_in_time')
                    ->fromSub($subQuery, 'ranked')
                    ->where('row_num', 1);
            })
            ->get();


        $breakTimeLateMonth = Attendance::select(
            DB::raw("DATE(clock_in_time) as presentDate"),
            "half_day_late",
            "clock_in_time"
        )
            ->where('user_id', $this->salarySlip->user_id)
            ->whereDate('clock_in_time', '>=', $startDate)
            ->whereDate('clock_in_time', '<=', $endDate)
            ->whereIn('clock_in_time', function ($query) use ($subQuery) {
                $query->select('clock_in_time')
                    ->fromSub($subQuery, 'ranked')
                    ->where('row_num', 2);
            })
            ->where('half_day_late', 'yes')
            ->get();

        $leaveInMonth = Leave::where('user_id', $this->salarySlip->user_id)
            ->whereDate('leave_date', '>=', $startDate)
            ->whereDate('leave_date', '<=', $endDate)
            ->get();


        $totalLeaveWithoutPay = 0;

        $attendanceSetting = AttendanceSetting::first();
        $attLateBeforeFifteenMinutes = 0;
        $attLateAfterFifteenMinutes = 0;
        $attBreakTime = $breakTimeLateMonth->count();
        $leaveWithoutPayInMonth = $leaveInMonth->count();

        foreach ($attendanceLateInMonth as $key => $attendanceLate) {
            $clock_in_time = $attendanceLate->clock_in_time;

            $officeStartTime = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::parse($clock_in_time)->format('Y-m-d') . ' ' . $attendanceSetting->office_start_time);


            $lateTime = $officeStartTime->clone()->addMinutes(15);

            if ($clock_in_time->greaterThan($officeStartTime) && $clock_in_time->lessThan($lateTime)) {
                $attLateBeforeFifteenMinutes += 1;
            }

            if ($clock_in_time->greaterThan($lateTime)) {
                $attLateAfterFifteenMinutes += 1;
            }
        }

        $totalLeaveWithoutPay = (($attLateBeforeFifteenMinutes + $attBreakTime) / 3) + ($attLateAfterFifteenMinutes) + $leaveWithoutPayInMonth;

        $lastDayOfMonth = $startDate->clone()->lastOfMonth();
        $daysInMonth = (int) abs($lastDayOfMonth->diffInDays($startDate) + 25);

        $this->perDaySalary = $this->monthlySalary?->basic_salary / $daysInMonth;

        $this->beforeLateDetection = ($attLateBeforeFifteenMinutes / 3) * $this->perDaySalary;
        $this->afterLateDetection = $attLateAfterFifteenMinutes * $this->perDaySalary;
        $this->breakTimeLateDetection = ($attBreakTime / 3) * $this->perDaySalary;
        $this->leaveWithoutPayDetection = $leaveWithoutPayInMonth * $this->perDaySalary;

        // dd([
        //     'beforeLate' => $this->attLateBeforeFifteenMinutes,
        //     'afterLate' => $this->attLateAfterFifteenMinutes,
        //     'breakTimeLate' => $this->attBreakTime,
        //     'lwp' => $this->leaveWithoutPayInMonth,
        //     'payDays' => $this->perDaySalary,
        //     'otherDetection' =>  $this->monthlyOtherDetection?->other_detection
        // ]);

        $this->totalDetection = ($totalLeaveWithoutPay * $this->perDaySalary) + $this->monthlyOtherDetection?->other_detection;

        // dd($this->totalDetection, $attLateBeforeFifteenMinutes);

        // $earn = [];

        // foreach ($this->earnings as $key => $value) {
        //     if ($key != 'Total Hours') {
        //         $earn[] = $value;
        //     }
        // }

        // $earn = array_sum($earn);

        // $this->fixedAllowance = $this->salarySlip->gross_salary - ($this->basicSalary + $earn);

        // if ($this->fixedAllowance < 0) {
        //     $this->fixedAllowance = 0;
        // }

        // if (!is_null($extraJson)) {

        //     $this->earningsExtra = $extraJson['earnings'];
        //     $this->deductionsExtra = $extraJson['deductions'];
        // } else {
        //     $this->earningsExtra = '';
        //     $this->deductionsExtra = '';
        // }

        // if (!is_null($additionalEarnings)) {
        //     $this->earningsAdditional = $additionalEarnings['earnings'];
        // } else {
        //     $this->earningsAdditional = '';
        // }

        // if ($this->earningsAdditional == '') {
        //     $this->earningsAdditional = array();
        // }

        // if ($this->earningsExtra == '') {
        //     $this->earningsExtra = array();
        // }


        // if ($this->deductionsExtra == '') {
        //     $this->deductionsExtra = array();
        // }

        $this->payrollSetting = PayrollSetting::first();
        $this->extraFields = [];

        if ($this->payrollSetting->extra_fields) {
            $this->extraFields = json_decode($this->payrollSetting->extra_fields);
        }

        $this->employeeDetail = EmployeeDetails::where('user_id', '=', $this->salarySlip->user->id)->first()->withCustomFields();
        $this->currency = PayrollSetting::with('currency')->first();


        if (!is_null($this->employeeDetail) && $this->employeeDetail->getCustomFieldGroupsWithFields()) {
            $this->fieldsData = $this->employeeDetail->getCustomFieldGroupsWithFields()->fields;
            $this->fields = $this->fieldsData->filter(function ($value, $key) {
                return in_array($value->id, $this->extraFields);
            })->all();
        }

        // if ($this->fixedAllowance < 1 && $this->fixedAllowance > -1) {
        //     $this->fixedAllowance = 0;
        // }

        if (request()->ajax()) {
            $html = view('payroll::payroll.ajax.show-modal', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'payroll::payroll.ajax.show-modal';

        return view('payroll::payroll.create', $this->data);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        $this->salarySlip = SalarySlip::with('user', 'user.employeeDetail', 'salary_group', 'salary_payment_method', 'user.userAllowances', 'user.userDetection')
            ->findOrFail($id);

        // dd($this->salarySlip->user->toArray());

        $editPermission = user()->permission('edit_payroll');

        abort_403(!($editPermission == 'all'
            || ($editPermission == 'owned' && $this->salarySlip->user_id == user()->id)
            || ($editPermission == 'added' && $this->salarySlip->added_by == user()->id)
            || ($editPermission == 'both' && ($this->salarySlip->user_id == user()->id || $this->salarySlip->added_by == user()->id))
        ));

        // $salaryJson = json_decode($this->salarySlip->salary_json, true);
        // $this->earnings = $salaryJson['earnings'];
        // $this->deductions = $salaryJson['deductions'];
        // $extraJson = json_decode($this->salarySlip->extra_json, true);
        // $additionalEarnings = json_decode($this->salarySlip->additional_earning_json, true);

        // if (!is_null($extraJson)) {
        //     $this->earningsExtra = $extraJson['earnings'];
        //     $this->deductionsExtra = $extraJson['deductions'];
        // } else {
        //     $this->earningsExtra = '';
        //     $this->deductionsExtra = '';
        // }

        // if (!is_null($additionalEarnings)) {
        //     $this->earningsAdditional = $additionalEarnings['earnings'];
        // } else {
        //     $this->earningsAdditional = '';
        // }

        // if ($this->earningsAdditional == '') {
        //     $this->earningsAdditional = array();
        // }

        // if ($this->earningsExtra == '') {
        //     $this->earningsExtra = array();
        // }

        // if ($this->deductionsExtra == '') {
        //     $this->deductionsExtra = array();
        // }

        // if ($this->salarySlip->payroll_cycle->cycle == 'monthly') {
        //     $this->basicSalary = $this->salarySlip->basic_salary;
        // } elseif ($this->salarySlip->payroll_cycle->cycle == 'weekly') {
        //     $this->basicSalary = ((float) $this->salarySlip->basic_salary / 4);
        // } elseif ($this->salarySlip->payroll_cycle->cycle == 'semimonthly') {
        //     $this->basicSalary = ((float) $this->salarySlip->basic_salary / 2);
        // } elseif ($this->salarySlip->payroll_cycle->cycle == 'biweekly') {
        //     $perday = (float) $this->salarySlip->basic_salary / 30;
        //     $this->basicSalary = $perday * 14;
        // }

        // $earn = [];
        // $extraEarn = [];

        // $this->earningsAdditionalTotal = isset($this->earningsAdditional) ? array_sum($this->earningsAdditional) : 0;

        // foreach ($this->earnings as $key => $value) {
        //     if ($key != 'Total Hours') {
        //         $earn[] = $value;
        //     }
        // }

        // foreach ($this->earningsExtra as $key => $value) {
        //     $extraEarn[] = $value;
        // }

        // $earn = array_sum($earn);

        // $extraEarn = array_sum($extraEarn);


        // $this->fixedAllowance = $this->salarySlip->gross_salary - ($this->basicSalary + $earn + $extraEarn);

        // if ($this->fixedAllowance < 0) {
        //     $this->fixedAllowance = 0;
        // }

        // $this->currency = PayrollSetting::with('currency')->first();
        $this->salaryPaymentMethods = SalaryPaymentMethod::all();


        if (request()->ajax()) {
            $html = view('payroll::payroll.ajax.edit-modal', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'payroll::payroll.ajax.edit-modal';

        return view('payroll::payroll.create', $this->data);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        // $grossEarning = $request->basic_salary;
        // $totalDeductions = 0;
        // $reimbursement = $request->expense_claims;
        // $earningsName = $request->earnings_name;
        // $earnings = $request->earnings;
        // $deductionsName = $request->deductions_name;
        // $deductions = $request->deductions ? $request->deductions : array();
        // $extraEarningsName = $request->extra_earnings_name;
        // $extraEarnings = $request->extra_earnings;
        // $extraDeductionsName = $request->extra_deductions_name;
        // $extraDeductions = $request->extra_deductions;
        // $additionalEarnings = $request->additional_earnings;
        // $additionalEarningsName = $request->additional_name;
        // $additionalEarningTotal = 0;

        // $earningsArray = array();
        // $deductionsArray = array();
        // $extraEarningsArray = array();
        // $extraDeductionsArray = array();
        // $additionalEarningsArray = array();

        // if ($earnings != '') {
        //     foreach ($earnings as $key => $value) {
        //         $earningsArray[$earningsName[$key]] = floatval($value);
        //         $grossEarning = $grossEarning + $earningsArray[$earningsName[$key]];
        //     }
        // }

        // foreach ($deductions as $key => $value) {
        //     if ($value != 0 && $value != '') {
        //         $deductionsArray[$deductionsName[$key]] = floatval($value);
        //         $totalDeductions = $totalDeductions + $deductionsArray[$deductionsName[$key]];
        //     }
        // }

        // $salaryComponents = [
        //     'earnings' => $earningsArray,
        //     'deductions' => $deductionsArray
        // ];
        // $salaryComponentsJson = json_encode($salaryComponents);

        // if ($extraEarnings != '') {
        //     foreach ($extraEarnings as $key => $value) {
        //         if ($value != 0 && $value != '') {
        //             $extraEarningsArray[$extraEarningsName[$key]] = floatval($value);
        //             $grossEarning = $grossEarning + $extraEarningsArray[$extraEarningsName[$key]];
        //         }
        //     }
        // }

        // if ($additionalEarnings != '') {
        //     foreach ($additionalEarnings as $key => $value) {
        //         if ($value != 0 && $value != '') {
        //             $additionalEarningsArray[$additionalEarningsName[$key]] = floatval($value);
        //             $additionalEarningTotal = $additionalEarningTotal + $additionalEarningsArray[$additionalEarningsName[$key]];
        //         }
        //     }
        // }

        // if ($extraDeductions != '') {
        //     foreach ($extraDeductions as $key => $value) {
        //         if ($value != 0 && $value != '') {
        //             $extraDeductionsArray[$extraDeductionsName[$key]] = floatval($value);
        //             $totalDeductions = $totalDeductions + $extraDeductionsArray[$extraDeductionsName[$key]];
        //         }
        //     }
        // }

        // $extraSalaryComponents = [
        //     'earnings' => $extraEarningsArray,
        //     'deductions' => $extraDeductionsArray
        // ];

        // $extraSalaryComponentsJson = json_encode($extraSalaryComponents);

        // $additionalEarningComponents = [
        //     'earnings' => $additionalEarningsArray,
        // ];

        // $additionalEarningComponentJson = json_encode($additionalEarningComponents);

        // $netSalary = $grossEarning - $totalDeductions + $reimbursement + $additionalEarningTotal;

        $salarySlip = SalarySlip::findOrFail($id);
        $userAllowance = Allowance::findOrFail($request->userId);
        $userDetection = Detection::findOrFail($request->userId);

        if ($request->paid_on != '') {
            $salarySlip->paid_on = Carbon::createFromFormat($this->company->date_format, $request->paid_on)->format('Y-m-d');
        }

        if ($request->salary_payment_method_id != '') {
            $salarySlip->salary_payment_method_id = $request->salary_payment_method_id;
        }

        // allowance
        $userAllowance->basic_salary = $request->basic_salary;
        $userAllowance->living_cost_allowance = $request->living_cost_allowance;
        $userAllowance->technical_allowance = $request->technical_allowance;
        $userAllowance->special_allowance = $request->special_allowance;

        // detection
        $userDetection->other_detection = $request->other_detection;

        // $grossEarning = $grossEarning + $request->fixed_allowance_input + $additionalEarningTotal;
        // $netSalary = $netSalary + $request->fixed_allowance_input;

        // // dd(round(($grossEarning), 2), $netSalary, $request->fixed_allowance_input);
        // $salarySlip->status = $request->status;
        // $salarySlip->expense_claims = $request->expense_claims;
        // $salarySlip->basic_salary = $request->basic_salary;
        // $salarySlip->salary_json = $salaryComponentsJson;
        // $salarySlip->extra_json = $extraSalaryComponentsJson;
        // $salarySlip->additional_earning_json = $additionalEarningComponentJson;
        // $salarySlip->tds = isset($deductionsArray['TDS']) ? $deductionsArray['TDS'] : 0;
        // $salarySlip->total_deductions = round(($totalDeductions), 2);
        // $salarySlip->net_salary = round(($netSalary), 2);
        // $salarySlip->gross_salary = round(($grossEarning), 2);
        // $salarySlip->last_updated_by = user()->id;
        // $salarySlip->fixed_allowance = $request->fixed_allowance_input;


        $salarySlip->save();
        $userAllowance->save();
        $userDetection->save();

        return Reply::redirect(route('payroll.show', $salarySlip->id), __('messages.updateSuccess'));
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $this->salarySlip = SalarySlip::with('user', 'user.employeeDetail', 'salary_group', 'salary_payment_method')->findOrFail($id);

        $editPermission = user()->permission('delete_payroll');

        abort_403(!($editPermission == 'all'
            || ($editPermission == 'owned' && $this->salarySlip->user_id == user()->id)
            || ($editPermission == 'added' && $this->salarySlip->added_by == user()->id)
            || ($editPermission == 'both' && ($this->salarySlip->user_id == user()->id || $this->salarySlip->added_by == user()->id))
        ));

        SalarySlip::destroy($id);

        return Reply::success(__('messages.deleteSuccess'));
    }

    public function generatePaySlip(Request $request)
    {
        $this->addPermission = user()->permission('add_payroll');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $month = explode(' ', $request->month);
        $payrollCycle = $request->cycle;
        $year = $request->year;
        $useAttendance = $request->useAttendance;
        $markApprovedLeavesPaid = $request->markLeavesPaid;
        $markAbsentUnpaid = $request->markAbsentUnpaid;
        $includeExpenseClaims = $request->includeExpenseClaims;
        $addTimelogs = $request->addTimelogs;
        $payrollCycleData = PayrollCycle::find($payrollCycle);
        $startDate = CarbonImmutable::parse($month[0])->subMonth()->setDay(26);
        $endDate = CarbonImmutable::parse($month[1])->setDay(25);

        $lastDayCheck = $endDate;
        // $daysInMonth = $startDate->diffInDays($lastDayCheck->addDay()); // Days by start and end date

        $lastDayOfMonth = $startDate->lastOfMonth();
        $daysInMonth = (int) abs($lastDayOfMonth->diffInDays($startDate) + 25);

        if ($request->userIds || $request->employee_id) {
            $users = User::with('employeeDetail')
                ->join('employee_payroll_cycles', 'employee_payroll_cycles.user_id', '=', 'users.id')
                ->join('employee_monthly_salaries', 'employee_monthly_salaries.user_id', '=', 'users.id')
                ->where('employee_payroll_cycles.payroll_cycle_id', $payrollCycle)
                ->where('employee_monthly_salaries.allow_generate_payroll', 'yes')
                ->select('users.id', 'users.name', 'users.email', 'users.status', 'users.email_notifications', 'users.created_at', 'users.image', 'users.mobile', 'users.country_id', 'employee_monthly_salaries.id as salary_id');

            if ($request->userIds) {
                $users = $users->whereIn('users.id', $request->userIds);
            } else {
                $users = $users->whereIn('users.id', $request->employee_id);
            }
            $users = $users->get();
        } else if ($request->department) {
            $users = User::with('employeeDetail')
                ->join('employee_payroll_cycles', 'employee_payroll_cycles.user_id', '=', 'users.id')
                ->join('employee_monthly_salaries', 'employee_monthly_salaries.user_id', '=', 'users.id')
                ->where('employee_payroll_cycles.payroll_cycle_id', $payrollCycle)
                ->where('employee_monthly_salaries.allow_generate_payroll', 'yes')
                ->join('employee_details', 'employee_details.user_id', '=', 'users.id')
                ->select('users.id', 'users.name', 'users.email', 'users.status', 'users.email_notifications', 'users.created_at', 'users.image', 'users.mobile', 'users.country_id', 'employee_monthly_salaries.id as salary_id')
                ->where('employee_details.department_id', $request->department)->get();
        } else {
            $users = User::with('employeeDetail')->leftJoin('employee_details', 'employee_details.user_id', '=', 'users.id')
                ->join('role_user', 'role_user.user_id', '=', 'users.id')
                ->join('roles', 'roles.id', '=', 'role_user.role_id')
                ->select('users.id', 'users.name', 'users.email', 'users.status', 'users.email_notifications', 'users.created_at', 'users.image', 'users.mobile', 'users.country_id', 'employee_monthly_salaries.id as salary_id')
                ->join('employee_payroll_cycles', 'employee_payroll_cycles.user_id', '=', 'users.id')
                ->join('employee_monthly_salaries', 'employee_monthly_salaries.user_id', '=', 'users.id')
                ->where('employee_payroll_cycles.payroll_cycle_id', $payrollCycle)
                ->where('roles.name', '<>', 'client')
                ->where('employee_monthly_salaries.allow_generate_payroll', 'yes')
                ->orderBy('users.name', 'asc')
                ->where(function ($query) use ($startDate) {
                    $query->whereDate('employee_details.last_date', '>', $startDate->format('Y-m-d'))
                        ->orWhereNull('employee_details.last_date');
                })
                ->groupBy('users.id')
                ->get();
        }

        $subQuery = Attendance::select(
            'clock_in_time',
            DB::raw('ROW_NUMBER() OVER (PARTITION BY DATE(clock_in_time) ORDER BY clock_in_time ASC) as row_num')
        )
            ->whereIn('user_id', $request->employee_id)
            ->whereDate('clock_in_time', '>=', $startDate)
            ->whereDate('clock_in_time', '<=', $endDate);

        $attendanceLateInMonth = Attendance::select(
            DB::raw("DATE(clock_in_time) as presentDate"),
            "late",
            "clock_in_time"
        )
            ->whereIn('user_id', $request->employee_id)
            ->whereDate('clock_in_time', '>=', $startDate)
            ->whereDate('clock_in_time', '<=', $endDate)
            ->whereIn('clock_in_time', function ($query) use ($subQuery) {
                $query->select('clock_in_time')
                    ->fromSub($subQuery, 'ranked')
                    ->where('row_num', 1);
            })
            ->get();

        $breakTimeLateMonth = Attendance::select(
            DB::raw("DATE(clock_in_time) as presentDate"),
            "half_day_late",
            "clock_in_time"
        )
            ->whereIn('user_id', $request->employee_id)
            ->whereDate('clock_in_time', '>=', $startDate)
            ->whereDate('clock_in_time', '<=', $endDate)
            ->whereIn('clock_in_time', function ($query) use ($subQuery) {
                $query->select('clock_in_time')
                    ->fromSub($subQuery, 'ranked')
                    ->where('row_num', 2);
            })
            ->where('half_day_late', 'yes')
            ->get();

        $leaveInMonth = Leave::where('user_id', $request->employee_id)
            ->whereDate('leave_date', '>=', $startDate)
            ->whereDate('leave_date', '<=', $endDate)
            ->get();

        $totalLeaveWithoutPay = 0;

        $attendanceSetting = AttendanceSetting::first();
        $attLateBeforeFifteenMinutes = 0;
        $attLateAfterFifteenMinutes = 0;
        $attBreakTime = $breakTimeLateMonth->count();
        $leaveWithoutPayInMonth = $leaveInMonth->count();

        foreach ($attendanceLateInMonth as $key => $attendanceLate) {
            $clock_in_time = $attendanceLate->clock_in_time;

            $officeStartTime = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::parse($clock_in_time)->format('Y-m-d') . ' ' . $attendanceSetting->office_start_time);


            $lateTime = $officeStartTime->clone()->addMinutes(15);

            if ($clock_in_time->greaterThan($officeStartTime) && $clock_in_time->lessThan($lateTime)) {
                $attLateBeforeFifteenMinutes += 1;
            }

            if ($clock_in_time->greaterThan($lateTime)) {
                $attLateAfterFifteenMinutes += 1;
            }
        }

        $totalLeaveWithoutPay = (($attLateBeforeFifteenMinutes + $attBreakTime) / 3) + ($attLateAfterFifteenMinutes) + $leaveWithoutPayInMonth;

        foreach ($users as $user) {
            $userId = $user->id;
            $employeeDetails = EmployeeDetails::where('user_id', $userId)->first();
            $joiningDate = Carbon::parse($employeeDetails->joining_date)->setTimezone($this->company->timezone);
            $exitDate = (!is_null($employeeDetails->last_date)) ? Carbon::parse($employeeDetails->last_date)->setTimezone($this->company->timezone) : null;

            $payDays = $daysInMonth;

            $HolidayStartDate = ($joiningDate->greaterThan($startDate)) ? $joiningDate : $startDate;
            $HolidayEndDate = (!is_null($exitDate) && $endDate->greaterThan($exitDate)) ? $exitDate : $endDate;

            $holidayData = $this->getHolidayByDates($HolidayStartDate->toDateString(), $HolidayEndDate->toDateString(), $userId)
                ->pluck('holiday_date')
                ->values(); // Getting Holiday Data

            $gazattedPresentCount = $this->countGazattedPresentByUser($startDate, $endDate, $userId, $holidayData); // Getting Attendance Data
            $holidayPresentCount = $this->countHolidayPresentByUser($startDate, $endDate, $userId, $holidayData); // Getting Attendance Data
            $eveningShiftPresentCout = $this->countEveningShiftPresentByUser($startDate, $endDate, $userId, $holidayData); // Getting Attendance Data

            if ($endDate->greaterThan($joiningDate)) {
                $payDays = (int) $this->countAttendace($startDate, $endDate, $userId, $daysInMonth, $useAttendance, $joiningDate, $exitDate);

                // Check Joining date of the employee
                if (!$useAttendance && $joiningDate->greaterThan($startDate)) {
                    $daysDifference = $joiningDate->diffInDays($startDate);
                    $payDays = ($payDays - $daysDifference);
                }

                // Check Exit date of the employee
                if (!$useAttendance && (!is_null($exitDate) && $endDate->greaterThan($exitDate))) {
                    $daysDifference = $endDate->diffInDays($exitDate);
                    $payDays = ($payDays - $daysDifference);
                }


                $monthCur = $endDate->month;
                $curMonthDays = Carbon::parse('01-' . $monthCur . '-' . $year);
                // $monthlySalary = EmployeeMonthlySalary::employeeNetSalary($userId, $endDate);

                $monthlySalary = Allowance::where('user_id', $userId)->first();
                $monthlyOtherDetection = Detection::where('user_id', $userId)->first();

                $technicalAllowance = $monthlySalary?->technical_allowance;
                $livingCostAllowance = $monthlySalary?->living_cost_allowance;
                $specialAllowance = $monthlySalary?->special_allowance;

                $daysInMonth = ($daysInMonth != 30 && $payrollCycleData->cycle == 'semimonthly') ? 30 : $daysInMonth;

                $perDaySalary = $monthlySalary?->basic_salary / $daysInMonth;
                $payableSalary = $perDaySalary * $payDays;

                $basicSalary = $payableSalary;

                if ($gazattedPresentCount > 0) {
                    $basicSalary = $basicSalary + ($gazattedPresentCount * 3000);
                }

                if ($holidayPresentCount > 0) {
                    $basicSalary = $basicSalary + ($holidayPresentCount * 3000);
                }

                if ($eveningShiftPresentCout > 0) {
                    $basicSalary = $basicSalary + ($eveningShiftPresentCout * 500);
                }

                $totalDetection = ($totalLeaveWithoutPay * $perDaySalary) + $monthlyOtherDetection?->other_detection;
                $totalBasicSalary = $basicSalary + $technicalAllowance + $livingCostAllowance + $specialAllowance; // allowance calculation
                $netSalary = $totalBasicSalary - $totalDetection;

                $payrollSetting = PayrollSetting::first();

                $data = [
                    'user_id' => $userId,
                    'currency_id' => $payrollSetting->currency_id,
                    'salary_group_id' => 1, // null
                    'basic_salary' => round(($monthlySalary?->basic_salary), 2),
                    'monthly_salary' => round($monthlySalary?->basic_salary, 2),
                    'net_salary' => (round(($netSalary), 2) < 0) ? 0.00 : round(($netSalary), 2),
                    'gross_salary' => round($totalBasicSalary, 2), // null
                    'total_deductions' => round(($totalDetection), 2),
                    'month' => $startDate->month,
                    'payroll_cycle_id' => $payrollCycle,
                    'salary_from' => $startDate->format('Y-m-d'),
                    'salary_to' => $endDate->format('Y-m-d'),
                    'year' => $request->year,
                    // 'salary_json' => null, // null
                    // 'expense_claims' => null, // null
                    'pay_days' => $payDays,
                    'added_by' => user()->id,
                ];

                SalarySlip::create($data);

                // dd(
                //     [
                //         "monthlySalaryBasic" => $monthlySalary->basic_salary,
                //         "daysInMonth" => $daysInMonth,
                //         "payDays" => $payDays,
                //         "gazattedCount" => $gazattedPresentCount,
                //         "holidayCount" => $holidayPresentCount,
                //         "eveningShiftCount" => $eveningShiftPresentCout,
                //         "technicalAllowance" => $technicalAllowance,
                //         "livingCost" => $livingCostAllowance,
                //         "specialCost" => $specialAllowance,
                //         "totalBasicSalary" => $totalBasicSalary
                //     ]
                // );

                // $salaryGroup = EmployeeSalaryGroup::with('salary_group.components', 'salary_group.components.component')
                //     ->where('user_id', $userId)
                //     ->first();

                // $totalBasicSalary = [];
                // $employeeBasicSalary = EmployeeMonthlySalary::where('user_id', $userId)->where('type', 'initial')->first();

                // if ($employeeBasicSalary->basic_value_type == 'fixed') {
                //     $totalBasicSalary[] = $employeeBasicSalary->basic_salary;
                // } else {
                //     $totalBasicSalary[] = $employeeBasicSalary->effective_monthly_salary / 100 *
                //         $employeeBasicSalary->basic_salary;
                // }

                // $totalBasicSalary = array_sum($totalBasicSalary);
                // $earnings = array();
                // $earningsTotal = 0;
                // $deductions = array();
                // $deductionsTotal = 0;

                // if (!is_null($salaryGroup)) {

                //     $earnings = [];
                //     $deductions = [];

                //     foreach ($salaryGroup->salary_group->components as $key => $components) {
                //         $componentValueAmount = ($payrollCycleData->cycle != 'monthly') ? $components->component->{$payrollCycleData->cycle . '_value'} : $components->component->component_value;

                //         $componentCalculation = $this->componentCalculation($components, $basicSalary, $componentValueAmount, $payableSalary, $totalBasicSalary, $earningsTotal, $deductionsTotal, $earnings, $deductions, $user->salary_id);

                //         $earningsTotal = $componentCalculation['earningsTotal'];
                //         $deductionsTotal = $componentCalculation['deductionsTotal'];
                //         $earnings = $componentCalculation['earnings'];
                //         $deductions = $componentCalculation['deductions'];
                //     }
                // }

                // $salaryTdsTotal = 0;
                // $payrollSetting = PayrollSetting::first();

                // $today = now()->timezone($this->company->timezone);

                // $year = $today->year;

                // $financialyearStart = Carbon::parse($year . '-' . $payrollSetting->finance_month . '-01')->setTimezone($this->company->timezone);
                // $financialyearEnd = Carbon::parse($today->year . '-' . $payrollSetting->finance_month . '-01')->addYear()->subDays(1)->setTimezone($this->company->timezone);


                // if ($startDate->format('m') < $payrollSetting->finance_month) {
                //     $startPayrollDate = clone $startDate;
                //     $financialYear = $startPayrollDate->subYear()->year;

                //     $financialyearStart = Carbon::parse($financialYear . '-' . $payrollSetting->finance_month . '-01')->setTimezone($this->company->timezone);
                //     $financialyearEnd = Carbon::parse($today->year . '-' . $payrollSetting->finance_month . '-01')->subDays(1)->setTimezone($this->company->timezone);
                // }

                // $userSlip = SalarySlip::where('user_id', $userId)
                //     ->where(function ($query) use ($startDate, $endDate) {
                //         $query->whereBetween('salary_from', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                //             ->orWhereBetween('salary_to', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
                //     })
                //     ->where('year', $year)->first();


                // if ($payrollSetting->tds_status) {
                //     $deductions['TDS'] = 0;

                //     $annualSalary = $this->calculateTdsSalary($userId, $joiningDate, $financialyearStart, $financialyearEnd, $endDate, $startDate);

                //     if ($payrollSetting->tds_salary < $annualSalary) {

                //         $salaryTds = SalaryTds::orderBy('salary_from', 'asc')->get();

                //         $taxableSalary = $annualSalary;

                //         $previousLimit = 0;
                //         $salaryTdsTotal = $this->calculateTds($taxableSalary, $salaryTds, $previousLimit, $annualSalary, $salaryTdsTotal);


                //         $tdsAlreadyPaid = SalarySlip::where('user_id', $userId)->sum('tds');

                //         if (!is_null($userSlip)) {
                //             $tdsAlreadyPaid = ($tdsAlreadyPaid - $userSlip->tds);
                //         }

                //         $tdsToBePaid = $salaryTdsTotal - $tdsAlreadyPaid;

                //         $monthDiffFromFinYrEnd = $financialyearEnd->diffInMonths($startDate, true) + 1;

                //         $deductions['TDS'] = floatval($tdsToBePaid) / $monthDiffFromFinYrEnd;

                //         $deductionsTotal = $deductionsTotal + $deductions['TDS'];
                //         $deductions['TDS'] = round($deductions['TDS'], 2);
                //     }
                // }

                // $expenseTotal = 0;

                // if ($includeExpenseClaims) {
                //     $expenseTotal = Expense::where(DB::raw('DATE(purchase_date)'), '>=', $startDate)
                //         ->where(DB::raw('DATE(purchase_date)'), '<=', $endDate)
                //         ->where('user_id', $userId)
                //         ->where('status', 'approved')
                //         ->where('can_claim', 1)
                //         ->sum('price');
                //     $payableSalary = $payableSalary + $expenseTotal;
                // }

                // if ($addTimelogs) {
                //     $timeLogs = ProjectTimeLog::where(DB::raw('DATE(start_time)'), '>=', $startDate)
                //         ->where(DB::raw('DATE(start_time)'), '<=', $endDate)
                //         ->where('user_id', $userId)->get();

                //     $totalHours = 0;

                //     foreach ($timeLogs as $timeLog) {
                //         $totalHours = $totalHours + $timeLog->total_hours;
                //     }

                //     $earnings['Time Logs'] = $timeLogs->sum('earnings');
                //     $payableSalary = $payableSalary + $earnings['Time Logs'];
                //     $earnings['Time Logs'] = round($earnings['Time Logs'], 2);
                //     $earnings['Total Hours'] = $totalHours;
                // }

                // $overtimeRequest = OvertimeRequest::where('user_id', $userId)
                //     ->where('status', 'accept')
                //     ->whereDate('date', '>=', $startDate)
                //     ->whereDate('date', '<=', $endDate)->sum('amount');

                // if ($overtimeRequest > 0) {
                //     $earnings['Overtime'] = $overtimeRequest;
                //     $payableSalary = $payableSalary + $earnings['Overtime'];
                // }

                // $unpaidDaysAmount = 0;

                // if ($useAttendance) {
                //     $unpaidDayCount = $daysInMonth - $payDays;
                //     $unPaidAmount = round(($unpaidDayCount * $perDaySalary), 2);

                //     if ($unPaidAmount > 0) {
                //         $deductions['Unpaid Days Amount'] = $unPaidAmount;
                //         $unpaidDaysAmount = $deductions['Unpaid Days Amount'];
                //     }
                // }

                // $salaryComponents = [
                //     'earnings' => $earnings,
                //     'deductions' => $deductions
                // ];

                // $salaryComponentsJson = json_encode($salaryComponents);

                // $data = [
                //     'user_id' => $userId,
                //     'currency_id' => $payrollSetting->currency_id,
                //     'salary_group_id' => 1, // null
                //     'basic_salary' => round(($monthlySalary->basic_salary), 2),
                //     'monthly_salary' => round($monthlySalary->basic_salary, 2),
                //     'net_salary' => (round(($netSalary), 2) < 0) ? 0.00 : round(($netSalary)),
                //     'gross_salary' => 5, // null
                //     'total_deductions' => round(($totalDetection), 2),
                //     'month' => $startDate->month,
                //     'payroll_cycle_id' => $payrollCycle,
                //     'salary_from' => $startDate->format('Y-m-d'),
                //     'salary_to' => $endDate->format('Y-m-d'),
                //     'year' => $request->year,
                //     // 'salary_json' => null, // null
                //     // 'expense_claims' => null, // null
                //     'pay_days' => $payDays,
                //     'added_by' => user()->id,
                // ];


                // if ($payrollSetting->tds_status) {
                //     $data['tds'] = $deductions['TDS'];
                // }

                // if (!is_null($userSlip) && $userSlip->status != 'paid') {
                //     $userSlip->delete();
                // }

                // if (is_null($userSlip) || (!is_null($userSlip) && $userSlip->status != 'paid')) {
                //     SalarySlip::create($data);
                // }
            }
        }

        return Reply::dataOnly(['status' => 'success']);
    }

    protected function calculateTdsSalary($userId, $joiningDate, $financialyearStart, $financialyearEnd, $payrollMonthEndDate, $payrollMonthStartDate)
    {

        $totalEarning = 0;

        if ($joiningDate->greaterThan($financialyearStart)) {
            $monthlySalary = EmployeeMonthlySalary::employeeNetSalary($userId);
            $currentSalary = $initialSalary = $monthlySalary['initialSalary'];
        } else {
            $monthlySalary = EmployeeMonthlySalary::employeeNetSalary($userId, $financialyearStart);
            $currentSalary = $initialSalary = $monthlySalary['netSalary'];
        }

        $increments = EmployeeMonthlySalary::employeeIncrements($userId);
        $lastIncrement = null;

        foreach ($increments as $increment) {
            $incrementDate = Carbon::parse($increment->date);

            if ($payrollMonthEndDate->greaterThan($incrementDate)) {
                if (is_null($lastIncrement)) {
                    $payDays = $incrementDate->diffInDays($joiningDate, true);
                    $perDaySalary = ($initialSalary / 30); /*30 is taken as no of days in a month*/
                    $totalEarning = $payDays * $perDaySalary;
                    $lastIncrement = $incrementDate;
                    $currentSalary = $increment->amount + $initialSalary;
                } else {
                    $payDays = $incrementDate->diffInDays($lastIncrement, true);
                    $perDaySalary = ($currentSalary / 30);
                    $totalEarning = $totalEarning + ($payDays * $perDaySalary);
                    $lastIncrement = $incrementDate;
                    $currentSalary = $increment->amount + $currentSalary;
                }
            }
        }

        if (!is_null($lastIncrement)) {
            $payDays = $financialyearEnd->diffInDays($lastIncrement, true);
            $perDaySalary = ($currentSalary / 30);
            $totalEarning = $totalEarning + ($payDays * $perDaySalary);
        } else {

            if ($joiningDate->greaterThan($financialyearStart)) {
                $startFinanceDate = $joiningDate;
            } else {
                $startFinanceDate = $financialyearStart;
            }

            $totalPaidSalary = SalarySlip::where('user_id', $userId)
                ->whereDate('salary_from', '>=', $startFinanceDate->format('Y-m-d'))
                ->whereDate('salary_to', '<=', $financialyearEnd->format('Y-m-d'))
                ->where('status', 'paid')->get();

            $totalDaysSalary = $totalPaidSalary->sum('gross_salary');

            if ($totalDaysSalary != 0) {
                $payDays = $financialyearEnd->diffInDays($payrollMonthStartDate, true);
            } elseif ($joiningDate->greaterThan($financialyearStart)) {
                $payDays = $financialyearEnd->diffInDays($joiningDate, true);
            } else {
                $payDays = ($financialyearEnd->diffInDays($financialyearStart, true));
            }

            $slry = EmployeeMonthlySalary::where('user_id', $userId)->where('type', 'initial')->first();

            $perDaySalary = ($slry->annual_salary / 365); /*365 is taken as no of days in a year*/

            $totalEarning = $payDays * $perDaySalary;
        }

        return $totalEarning;
    }

    public function getStatus(Request $request)
    {
        $this->paymentMethods = SalaryPaymentMethod::all();
        $this->expenseCategory = ExpensesCategory::all();

        return view('payroll::payroll.ajax.status-modal', $this->data);
    }

    public function updateStatus(Request $request)
    {
        $salarySlips = SalarySlip::whereIn('id', $request->salaryIds)->get();
        $salarySlipsTotal = SalarySlip::whereIn('id', $request->salaryIds)->sum('gross_salary');

        $data = [
            'status' => $request->status
        ];

        if ($request->status == 'paid') {
            $data['salary_payment_method_id'] = $request->paymentMethod;
            $data['paid_on'] = Carbon::createFromFormat($this->company->date_format, $request->paidOn)->toDateString();
        } else {
            $data['salary_payment_method_id'] = null;
            $data['paid_on'] = null;
        }

        if ($request->add_expenses == 'yes') {
            $expense = new Expense();
            $expenseTitle = null;

            if ($request->expense_title == null) {
                if (isset($salarySlips[0])) {
                    $firstSalary = $salarySlips[0];
                    $payrollCycle = PayrollCycle::find($firstSalary->payroll_cycle_id);

                    if (!is_null($payrollCycle) && $payrollCycle->cycle != 'monthly') {
                        $expenseTitle = __('payroll::modules.payroll.salaryExpenseHeadingWithoutMonth') . ' ' . $firstSalary->salary_from->format($this->company->date_format) . ' - ' . $firstSalary->salary_to->format($this->company->date_format);
                    }
                }

                if (is_null($expenseTitle)) {
                    $expenseTitle = __('payroll::modules.payroll.salaryExpenseHeading') . ' ' . $request->month . ' ' . $request->year;
                }
            }

            $expense->item_name = ($request->expense_title != null) ? $request->expense_title : $expenseTitle;
            $expense->category_id = $request->category_id;
            $expense->purchase_date = Carbon::createFromFormat($this->company->date_format, $request->paidOn)->toDateString();
            $expense->purchase_from = Carbon::createFromFormat($this->company->date_format, $request->paidOn)->format('F Y');
            $expense->price = $salarySlipsTotal;
            $expense->currency_id = $this->company->currency_id;
            $expense->default_currency_id = $this->company->currency_id;
            $expense->exchange_rate = $this->company->currency->exchange_rate;
            $expense->user_id = user()->id;
            $expense->status = 'approved';
            $expense->can_claim = 0;
            $expense->save();
        }

        foreach ($salarySlips as $key => $value) {
            $salary = SalarySlip::find($value->id);
            $salary->update($data);

            if ($request->add_expenses == 'yes') {
                $salary->expenses_created = 1;
                $salary->expense_id = $expense->id;
                $salary->save();
            }

            if ($request->status != 'generated') {
                $notifyUser = User::find($salary->user_id);
                $notifyUser->notify(new SalaryStatusEmail($salary));
            }
        }

        return Reply::dataOnly(['status' => 'success']);
    }

    public function getExpenseTitle(Request $request)
    {
        if ($request->status == 'yes') {
            $expense = new Expense();
            $expenseTitle = null;

            if (isset($salarySlips[0])) {
                $firstSalary = $salarySlips[0];
                $payrollCycle = PayrollCycle::find($firstSalary->payroll_cycle_id);

                if (!is_null($payrollCycle) && $payrollCycle->cycle != 'monthly') {
                    $expenseTitle = __('payroll::modules.payroll.salaryExpenseHeadingWithoutMonth') . ' ' . $firstSalary->salary_from->format($this->company->date_format) . ' - ' . $firstSalary->salary_to->format($this->company->date_format);
                }
            }

            if (is_null($expenseTitle)) {
                $expenseTitle = __('payroll::modules.payroll.salaryExpenseHeading') . ' ' . $request->month . ' ' . $request->year;
            }
        } else {
            $expenseTitle = '';
        }

        return Reply::dataOnly(['status' => 'success', 'expenseTitle' => $expenseTitle]);
    }

    public function downloadPdf($id)
    {
        $viewPermission = user()->permission('view_payroll');
        abort_403($viewPermission == 'none');

        $this->salarySlip = SalarySlip::with('user', 'user.employeeDetail', 'salary_group', 'salary_payment_method')->whereRaw('md5(id) = ?', $id)->firstOrFail();
        $this->payrollSetting = PayrollSetting::with('currency')->first();

        if (
            $viewPermission == 'all'
            || ($viewPermission == 'added' && $this->salarySlip->added_by == user()->id)
            || $this->salarySlip->user_id == $this->user->id
        ) {

            $pdfOption = $this->domPdfObjectForDownload($this->salarySlip->id);
            $pdf = $pdfOption['pdf'];
            $filename = $pdfOption['fileName'];

            return request()->view ? $pdf->stream($filename . '.pdf') : $pdf->download($filename . '.pdf');
        }
    }

    public function domPdfObjectForDownload($id)
    {
        // $this->salarySlip = SalarySlip::with('user', 'user.employeeDetail', 'salary_group', 'salary_payment_method')->find($id);
        // $this->payrollSetting = PayrollSetting::with('currency')->first();

        // $this->company = $this->salarySlip->company;

        // $salaryJson = json_decode($this->salarySlip->salary_json, true);
        // $this->earnings = $salaryJson['earnings'];
        // $this->deductions = $salaryJson['deductions'];
        // $extraJson = json_decode($this->salarySlip->extra_json, true);
        // $additionalEarnings = json_decode($this->salarySlip->additional_earning_json, true);

        // if ($this->salarySlip->payroll_cycle->cycle == 'monthly') {
        //     $this->basicSalary = $this->salarySlip->basic_salary;
        // } elseif ($this->salarySlip->payroll_cycle->cycle == 'weekly') {
        //     $this->basicSalary = $this->salarySlip->basic_salary / 4;
        // } elseif ($this->salarySlip->payroll_cycle->cycle == 'semimonthly') {
        //     $this->basicSalary = $this->salarySlip->basic_salary / 2;
        // } elseif ($this->salarySlip->payroll_cycle->cycle == 'biweekly') {
        //     $perday = $this->salarySlip->basic_salary / 30;
        //     $this->basicSalary = $perday * 14;
        // }

        // if (!is_null($extraJson)) {
        //     $this->earningsExtra = $extraJson['earnings'];
        //     $this->deductionsExtra = $extraJson['deductions'];
        // } else {
        //     $this->earningsExtra = '';
        //     $this->deductionsExtra = '';
        // }

        // if (!is_null($additionalEarnings)) {
        //     $this->earningsAdditional = $additionalEarnings['earnings'];
        // } else {
        //     $this->earningsAdditional = '';
        // }

        // if ($this->earningsAdditional == '') {
        //     $this->earningsAdditional = array();
        // }

        // if ($this->earningsExtra == '') {
        //     $this->earningsExtra = array();
        // }

        // if ($this->deductionsExtra == '') {
        //     $this->deductionsExtra = array();
        // }

        // $earn = [];
        // $extraEarn = [];

        // foreach ($this->earnings as $key => $value) {
        //     if ($key != 'Total Hours') {
        //         $earn[] = $value;
        //     }
        // }

        // foreach ($this->earningsExtra as $key => $value) {
        //     if ($key != 'Total Hours') {
        //         $extraEarn[] = $value;
        //     }
        // }

        // $earn = array_sum($earn);

        // $extraEarn = array_sum($extraEarn);

        // if ($this->basicSalary == '' || is_null($this->basicSalary)) {
        //     $this->basicSalary = 0.0;
        // }

        // $this->fixedAllowance = $this->salarySlip->gross_salary - ($this->basicSalary + $earn + $extraEarn);

        // $this->fixedAllowance = ($this->fixedAllowance < 0) ? 0 : round(floatval($this->fixedAllowance), 2);

        // $this->payrollSetting = PayrollSetting::first();

        // $this->extraFields = [];

        // if ($this->payrollSetting->extra_fields) {
        //     $this->extraFields = json_decode($this->payrollSetting->extra_fields);
        // }

        // $this->employeeDetail = EmployeeDetails::where('user_id', '=', $this->salarySlip->user->id)->first()->withCustomFields();
        // $this->currency = PayrollSetting::with('currency')->first();

        // if (!is_null($this->employeeDetail) && $this->employeeDetail->getCustomFieldGroupsWithFields()) {
        //     $this->fieldsData = $this->employeeDetail->getCustomFieldGroupsWithFields()->fields;
        //     $this->fields = $this->fieldsData->filter(function ($value, $key) {
        //         return in_array($value->id, $this->extraFields);
        //     })->all();
        // }

        $this->salarySlip = SalarySlip::with('user', 'user.employeeDetail', 'salary_group', 'salary_payment_method', 'payroll_cycle', 'user.userAllowances')
            ->findOrFail($id);
        $this->company = $this->salarySlip->company;
        // dd($this->company->toArray());

        $this->salaryPaymentMethods = SalaryPaymentMethod::all();

        if ($this->salarySlip->payroll_cycle->cycle == 'monthly') {
            $this->basicSalary = (float) $this->salarySlip->user->userAllowances->basic_salary;
        } elseif ($this->salarySlip->payroll_cycle->cycle == 'weekly') {
            $this->basicSalary = ((float) $this->salarySlip->user->userAllowances->basic_salary / 4);
        } elseif ($this->salarySlip->payroll_cycle->cycle == 'semimonthly') {
            $this->basicSalary = ((float) $this->salarySlip->user->userAllowances->basic_salary / 2);
        } elseif ($this->salarySlip->payroll_cycle->cycle == 'biweekly') {
            $perday = ((float) $this->salarySlip->user->userAllowances->basic_salary / 30);
            $this->basicSalary = $perday * 14;
        }

        $this->technicalAllowance = $this->salarySlip->user->userAllowances->technical_allowance;
        $this->livingCostAllowance = $this->salarySlip->user->userAllowances->living_cost_allowance;
        $this->specialAllowance = $this->salarySlip->user->userAllowances->special_allowance;


        $this->totalAllowance = $this->basicSalary + $this->technicalAllowance + $this->livingCostAllowance + $this->specialAllowance;

        $this->monthlySalary = Allowance::where('user_id',  $this->salarySlip->user_id)->first();
        $this->monthlyOtherDetection = Detection::where('user_id', $this->salarySlip->user_id)->first();

        $startDate = Carbon::parse($this->salarySlip->salary_from);
        $endDate = $startDate->clone()->parse($this->salarySlip->salary_to);


        $subQuery = Attendance::select(
            'clock_in_time',
            DB::raw('ROW_NUMBER() OVER (PARTITION BY DATE(clock_in_time) ORDER BY clock_in_time ASC) as row_num')
        )
            ->where('user_id', $this->salarySlip->user_id)
            ->whereDate('clock_in_time', '>=', $startDate)
            ->whereDate('clock_in_time', '<=', $endDate);

        $attendanceLateInMonth = Attendance::select(
            DB::raw("DATE(clock_in_time) as presentDate"),
            "late",
            "clock_in_time"
        )
            ->where('user_id', $this->salarySlip->user_id)
            ->whereDate('clock_in_time', '>=', "2025-01-26")
            ->whereDate('clock_in_time', '<=', "2025-02-25")
            ->whereIn('clock_in_time', function ($query) use ($subQuery) {
                $query->select('clock_in_time')
                    ->fromSub($subQuery, 'ranked')
                    ->where('row_num', 1);
            })
            ->get();


        $breakTimeLateMonth = Attendance::select(
            DB::raw("DATE(clock_in_time) as presentDate"),
            "half_day_late",
            "clock_in_time"
        )
            ->where('user_id', $this->salarySlip->user_id)
            ->whereDate('clock_in_time', '>=', $startDate)
            ->whereDate('clock_in_time', '<=', $endDate)
            ->whereIn('clock_in_time', function ($query) use ($subQuery) {
                $query->select('clock_in_time')
                    ->fromSub($subQuery, 'ranked')
                    ->where('row_num', 2);
            })
            ->where('half_day_late', 'yes')
            ->get();

        $leaveInMonth = Leave::where('user_id', $this->salarySlip->user_id)
            ->whereDate('leave_date', '>=', $startDate)
            ->whereDate('leave_date', '<=', $endDate)
            ->get();


        $totalLeaveWithoutPay = 0;

        $attendanceSetting = AttendanceSetting::first();
        $attLateBeforeFifteenMinutes = 0;
        $attLateAfterFifteenMinutes = 0;
        $attBreakTime = $breakTimeLateMonth->count();
        $leaveWithoutPayInMonth = $leaveInMonth->count();

        foreach ($attendanceLateInMonth as $key => $attendanceLate) {
            $clock_in_time = $attendanceLate->clock_in_time;

            $officeStartTime = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::parse($clock_in_time)->format('Y-m-d') . ' ' . $attendanceSetting->office_start_time);


            $lateTime = $officeStartTime->clone()->addMinutes(15);

            if ($clock_in_time->greaterThan($officeStartTime) && $clock_in_time->lessThan($lateTime)) {
                $attLateBeforeFifteenMinutes += 1;
            }

            if ($clock_in_time->greaterThan($lateTime)) {
                $attLateAfterFifteenMinutes += 1;
            }
        }

        $totalLeaveWithoutPay = (($attLateBeforeFifteenMinutes + $attBreakTime) / 3) + ($attLateAfterFifteenMinutes) + $leaveWithoutPayInMonth;

        $lastDayOfMonth = $startDate->clone()->lastOfMonth();
        $daysInMonth = (int) abs($lastDayOfMonth->diffInDays($startDate) + 25);

        $this->perDaySalary = $this->monthlySalary?->basic_salary / $daysInMonth;

        $this->beforeLateDetection = ($attLateBeforeFifteenMinutes / 3) * $this->perDaySalary;
        $this->afterLateDetection = $attLateAfterFifteenMinutes * $this->perDaySalary;
        $this->breakTimeLateDetection = ($attBreakTime / 3) * $this->perDaySalary;
        $this->leaveWithoutPayDetection = $leaveWithoutPayInMonth * $this->perDaySalary;

        $this->totalDetection = ($totalLeaveWithoutPay * $this->perDaySalary) + $this->monthlyOtherDetection?->other_detection;

        $this->payrollSetting = PayrollSetting::first();
        $this->extraFields = [];

        if ($this->payrollSetting->extra_fields) {
            $this->extraFields = json_decode($this->payrollSetting->extra_fields);
        }

        $this->employeeDetail = EmployeeDetails::where('user_id', '=', $this->salarySlip->user->id)->first()->withCustomFields();
        $this->payrollSetting = PayrollSetting::with('currency')->first();


        if (!is_null($this->employeeDetail) && $this->employeeDetail->getCustomFieldGroupsWithFields()) {
            $this->fieldsData = $this->employeeDetail->getCustomFieldGroupsWithFields()->fields;
            $this->fields = $this->fieldsData->filter(function ($value, $key) {
                return in_array($value->id, $this->extraFields);
            })->all();
        }

        // dd($this->monthlyOtherDetection->toArray());



        $pdf = app('dompdf.wrapper');
        $pdf->setOption('enable_php', true);
        $pdf->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

        $month = Carbon::createFromFormat('m', $this->salarySlip->month)->translatedFormat('F');


        $pdf->loadView('payroll::payroll.pdfview', $this->data);
        $filename = $this->salarySlip->user->employeeDetail->employee_id . '-' . $month . '-' . $this->salarySlip->year;

        return [
            'pdf' => $pdf,
            'fileName' => $filename
        ];
    }

    public function getCycleData(Request $request)
    {
        $payrollCycle = PayrollCycle::find($request->payrollCycle);
        $currentDate = now();
        $this->current = 0;

        if ($payrollCycle->cycle == 'weekly') {
            $year = $request->year;
            $dateData = [];
            $weeks = 52;
            $carbonFirst = new Carbon('first Monday of January ' . $year);

            for ($i = 1; $i <= $weeks; $i++) {
                $dateData['start_date'][] = $carbonFirst->toDateString();
                $endDate = $carbonFirst->addWeek();
                $dateData['end_date'][] = $endDate->subDay()->toDateString();
                $index = ($i > 1) ? ($i - 1) : 0;
                $startDateData = Carbon::parse($dateData['start_date'][$index]);

                if ($currentDate->between($startDateData, $endDate)) {
                    $this->current = $index;
                }

                $carbonFirst = $endDate->addDay();
            }

            if ($request->has('with_view')) {
                $this->results = $dateData;
                $this->cycle = 'weekly';
                $this->month = now()->month;

                $view = view('payroll::payroll.cycle', $this->data)->render();

                return Reply::dataOnly(['view' => $view]);
            }

            return $dateData;
        }

        if ($payrollCycle->cycle == 'biweekly') {
            $year = $request->year;
            $dateData = [];
            $weeks = 26;
            $carbonFirst = new Carbon('first Monday of January ' . $year);

            $this->current = 0;
            $index = 0;

            for ($i = 1; $i <= $weeks; $i++) {
                $dateData['start_date'][] = $carbonFirst->format('Y-m-d');
                $endDate = $carbonFirst->addWeeks(2);
                $dateData['end_date'][] = $endDate->subDay()->toDateString();
                $index = ($i > 1) ? ($i - 1) : 0;
                $startDateData = Carbon::parse($dateData['start_date'][$index]);

                if ($currentDate->between($startDateData, $endDate)) {
                    $this->current = $index;
                }

                $carbonFirst = $endDate->addDay();
            }

            if ($request->has('with_view')) {
                $this->results = $dateData;
                $this->cycle = 'biweekly';
                $this->month = now()->month;

                $view = view('payroll::payroll.cycle', $this->data)->render();

                return Reply::dataOnly(['view' => $view]);
            }

            return $dateData;
        }

        if ($payrollCycle->cycle == 'semimonthly') {
            $startDay = 1;
            $endDay = 15;
            $startSecondDay = 16;
            $endSecondDay = 30;
            $year = $request->year;
            $dateData = [];
            $months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
            $i = 0;

            foreach ($months as $index => $month) {
                $date = Carbon::createFromDate($year, $month);
                $daysInMonth = $date->daysInMonth;

                $dateData['start_date'][] = $startDateData = Carbon::createFromDate($year, $month, $startDay)->toDateString();

                $dateData['end_date'][] = $endDateData = Carbon::createFromDate($year, $month, $endDay)->toDateString();

                if ($currentDate->between($startDateData, $endDateData)) {
                    $this->current = $i;
                }

                $i++;
                $dateData['start_date'][] = $startDateDataNew = Carbon::createFromDate($year, $month, $startSecondDay)->toDateString();

                if ($endSecondDay > $daysInMonth) {
                    $dateData['end_date'][] = $endDateDataNew = Carbon::createFromDate($year, $month, $daysInMonth)->toDateString();
                } else {
                    $dateData['end_date'][] = $endDateDataNew = Carbon::createFromDate($year, $month, $endSecondDay)->toDateString();
                }

                if ($currentDate->between($startDateDataNew, $endDateDataNew)) {
                    $this->current = $i;
                }

                $i++;
            }

            if ($request->has('with_view')) {
                $this->results = $dateData;
                $this->cycle = 'semimonthly';
                $this->month = now()->month;

                $view = view('payroll::payroll.cycle', $this->data)->render();

                return Reply::dataOnly(['view' => $view]);
            }

            return $dateData;
        }

        if ($payrollCycle->cycle == 'monthly') {
            $this->months = ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'];
            $year = $request->year;
            $dateData = [];
            $months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];

            foreach ($months as $month) {
                $date = Carbon::createFromDate($year, $month);
                $dateData['start_date'][] = Carbon::parse(Carbon::parse('01-' . $month . '-' . $year))->startOfMonth()->toDateString();
                $dateData['end_date'][] = Carbon::parse(Carbon::parse('01-' . $month . '-' . $year))->endOfMonth()->toDateString();
            }

            if ($request->has('with_view')) {
                $this->results = $dateData;
                $this->cycle = 'monthly';
                $this->month = now()->month;
                $view = view('payroll::payroll.cycle', $this->data)->render();

                return Reply::dataOnly(['view' => $view]);
            }

            return $dateData;
        }
    }

    public function countAttendace($startDate, $endDate, $userId, $daysInMonth, $useAttendance, $joiningDate, $exitDate)
    {
        if ($useAttendance) {

            $markApprovedLeavesPaid = true;

            $HolidayStartDate = ($joiningDate->greaterThan($startDate)) ? $joiningDate : $startDate;
            $HolidayEndDate = (!is_null($exitDate) && $endDate->greaterThan($exitDate)) ? $exitDate : $endDate;

            $holidayData = $this->getHolidayByDates($HolidayStartDate->toDateString(), $HolidayEndDate->toDateString(), $userId)->pluck('holiday_date')->values(); // Getting Holiday Data
            $holidays = $holidayData->count();

            $totalWorkingDays = $daysInMonth - $holidays;

            $fullDayPresentCount = $this->countFullDaysPresentByUser($startDate, $endDate, $userId, $holidayData); // Getting Attendance Data
            $halfDayPresentCount = $this->countHalfDaysPresentByUser($startDate, $endDate, $userId, $holidayData); // Getting Attendance Data

            // dd($fullDayPresentCount, $halfDayPresentCount);

            $presentCount = $fullDayPresentCount + $halfDayPresentCount;

            $leaveCount = Leave::join('leave_types', 'leave_types.id', 'leaves.leave_type_id')->where('user_id', $userId)
                ->where('leave_date', '>=', $startDate)
                ->where('leave_date', '<=', $endDate)
                ->where('status', 'approved')->get();

            $unpaidHaldDayCount = $leaveCount->filter(function ($value, $key) {
                return $value->duration == "half day" && $value->paid == 0;
            })->count();

            $unpaidFullDayCount = $leaveCount->filter(function ($value, $key) {
                return $value->duration <> 'half day' && $value->paid == 0;
            })->count();

            $halfDayCountUnpaid = ($unpaidHaldDayCount / 2);

            $UnpaidleaveCount = $unpaidFullDayCount + $halfDayCountUnpaid;

            $PaidLeaveHalfDayCount = $leaveCount->filter(function ($value, $key) {
                return $value->duration == 'half day' && $value->paid == 1;
            })->count();

            $PaidLeaveFullDayCount = $leaveCount->filter(function ($value, $key) {
                return $value->duration <> 'half day' && $value->paid == 1;
            })->count();

            $halfDayCountPaid = ($PaidLeaveHalfDayCount / 2);

            $PaidLeaveCount = $PaidLeaveFullDayCount + $halfDayCountPaid;

            $absentCount = ($totalWorkingDays - $presentCount) - ($UnpaidleaveCount + $PaidLeaveCount);

            if ($markApprovedLeavesPaid) {
                $presentCount = $presentCount + $PaidLeaveCount;
            }

            $payDays = $presentCount + $holidays;
            $payDays = ($payDays > $daysInMonth) ? $daysInMonth : $payDays;

            return $payDays;
        }

        return $daysInMonth;
    }

    public static function getHolidayByDates($startDate, $endDate, $userId = null)
    {

        $holiday = Holiday::select(DB::raw('DATE_FORMAT(date, "%Y-%m-%d") as holiday_date'), 'occassion')
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate);

        if (is_null($userId)) {
            return $holiday->groupBy('date')->get();
        }

        $user = User::find($userId);

        $holiday = $holiday->where(function ($query) use ($user) {
            $query->where(function ($subquery) use ($user) {
                $subquery->where(function ($q) use ($user) {
                    $q->where('department_id_json', 'like', '%"' . $user->employeeDetail->department_id . '"%')
                        ->orWhereNull('department_id_json');
                });
                $subquery->where(function ($q) use ($user) {
                    $q->where('designation_id_json', 'like', '%"' . $user->employeeDetail->designation_id . '"%')
                        ->orWhereNull('designation_id_json');
                });
                $subquery->where(function ($q) use ($user) {
                    $q->where('employment_type_json', 'like', '%"' . $user->employeeDetail->employment_type . '"%')
                        ->orWhereNull('employment_type_json');
                });
            });
        });

        return $holiday->groupBy('date')->get();
    }

    public function componentCalculation($components, $basicSalary, $componentValueAmount, $payableSalary, $totalBasicSalary, $earningsTotal, $deductionsTotal, $earnings, $deductions, $salaryId = null)
    {

        if ($components->component->component_type == 'earning') {
            if ($components->component->value_type == 'fixed') {
                $basicSalary = $basicSalary - $componentValueAmount;

                $earnings[$components->component->component_name] = floatval($componentValueAmount);
            } elseif ($components->component->value_type == 'percent') {
                $componentValue = ($componentValueAmount / 100) * $payableSalary;
                $basicSalary = $basicSalary - $componentValue;

                $earnings[$components->component->component_name] = round(floatval($componentValue), 2);
            } elseif ($components->component->value_type == 'basic_percent') {

                $componentValue = ($componentValueAmount / 100) * $totalBasicSalary;
                $basicSalary = $basicSalary - $componentValue;
                $earnings[$components->component->component_name] = round(floatval($componentValue), 2);
            } else {
                $variableValue = EmployeeVariableComponent::where('monthly_salary_id', $salaryId)->where('variable_component_id', $components->salary_component_id)->first();
                $componentValue = (!is_null($variableValue)) ? $variableValue->variable_value : $componentValueAmount;

                $basicSalary = $basicSalary - floatval($componentValue);

                $earnings[$components->component->component_name] = round(floatval($componentValue), 2);
            }

            $earningsTotal = $earningsTotal + $earnings[$components->component->component_name];
        } else { // calculate deductions
            if ($components->component->value_type == 'fixed') {
                $deductions[$components->component->component_name] = floatval($componentValueAmount);
            } elseif ($components->component->value_type == 'percent') {
                $componentValue = ($componentValueAmount / 100) * $payableSalary;
                $deductions[$components->component->component_name] = round(floatval($componentValue), 2);
            } elseif ($components->component->value_type == 'basic_percent') {
                $componentValue = ($componentValueAmount / 100) * $totalBasicSalary;
                $deductions[$components->component->component_name] = round(floatval($componentValue), 2);
            } else {
                $variableValue = EmployeeVariableComponent::where('monthly_salary_id', $salaryId)->where('variable_component_id', $components->salary_component_id)->first();
                $componentValueAm = (!is_null($variableValue)) ? $variableValue->variable_value : $componentValueAmount;
                $deductions[$components->component->component_name] = floatval($componentValueAm);
            }

            $deductionsTotal = $deductionsTotal + $deductions[$components->component->component_name];
        }

        return [

            'earningsTotal' => $earningsTotal,
            'earnings' => $earnings,
            'deductionsTotal' => $deductionsTotal,
            'deductions' => $deductions
        ];
    }

    public function calculateTds($taxableSalary, $salaryTds, $previousLimit, $annualSalary, $salaryTdsTotal)
    {

        foreach ($salaryTds as $tds) {
            if ($annualSalary >= $tds->salary_from && $annualSalary <= $tds->salary_to) {
                $taxableSalary = $annualSalary - $tds->salary_from;

                $tdsValue = ($tds->salary_percent / 100) * $taxableSalary;
                $salaryTdsTotal = $salaryTdsTotal + $tdsValue;
            } elseif ($annualSalary >= $tds->salary_from && $annualSalary >= $tds->salary_to) {

                $previousLimit = $tds->salary_to - $previousLimit;
                $taxableSalary = $taxableSalary - $previousLimit;

                $tdsValue = ($tds->salary_percent / 100) * $previousLimit;
                $salaryTdsTotal = $salaryTdsTotal + $tdsValue;
            }
        }

        return $salaryTdsTotal;
    }

    public function countFullDaysPresentByUser($startDate, $endDate, $userId, $holidayData)
    {
        // $totalPresent = DB::select('SELECT count(DISTINCT DATE(attendances.clock_in_time) ) as presentCount from attendances where DATE(attendances.clock_in_time) >= "' . $startDate . '" and DATE(attendances.clock_in_time) <= "' . $endDate . '" and user_id="' . $userId . '" and half_day = "no"');

        $totalPresent = Attendance::select(
            DB::raw('count(DISTINCT DATE(attendances.clock_in_time) ) as presentCount')
        )
            ->where(DB::raw('DATE(attendances.clock_in_time)'), '>=', $startDate->toDateString())
            ->where(DB::raw('DATE(attendances.clock_in_time)'), '<=', $endDate->toDateString())
            ->where('half_day', 'no')
            ->where('user_id', $userId)
            ->whereNotIn(DB::raw('DATE(attendances.clock_in_time)'), $holidayData)
            ->get();

        return $totalPresent[0]->presentCount;
    }

    public function countHalfDaysPresentByUser($startDate, $endDate, $userId, $holidayData)
    {
        // $totalPresent = DB::select('SELECT count(DISTINCT DATE(attendances.clock_in_time) ) as presentCount from attendances where DATE(attendances.clock_in_time) >= "' . $startDate . '" and DATE(attendances.clock_in_time) <= "' . $endDate . '" and user_id="' . $userId . '" and half_day = "yes"');
        $totalPresent = Attendance::select(DB::raw('count(DISTINCT DATE(attendances.clock_in_time) ) as presentCount'))
            ->where(DB::raw('DATE(attendances.clock_in_time)'), '>=', $startDate->toDateString())
            ->where(DB::raw('DATE(attendances.clock_in_time)'), '<=', $endDate->toDateString())
            ->where('half_day', 'yes')
            ->where('user_id', $userId)
            ->whereNotIn(DB::raw('DATE(attendances.clock_in_time)'), $holidayData)->get();

        return (isset($totalPresent[0]->presentCount)) ? ($totalPresent[0]->presentCount / 2) : 0;
    }

    public function countGazattedPresentByUser($startDate, $endDate, $userId, $holidayData)
    {
        $totalPresent = Attendance::select(
            DB::raw('COUNT(DISTINCT DATE(attendances.clock_in_time)) as presentCount'),
            DB::raw('COUNT(DISTINCT CASE WHEN DAYOFWEEK(attendances.clock_in_time) IN (1,7) THEN DATE(attendances.clock_in_time) END) as weekendCount')
        )
            ->whereBetween(DB::raw('DATE(attendances.clock_in_time)'), [$startDate->toDateString(), $endDate->toDateString()])
            ->where('half_day', 'no')
            ->where('user_id', $userId)
            ->whereNotIn(DB::raw('DATE(attendances.clock_in_time)'), $holidayData)
            ->get();

        return $totalPresent[0]->weekendCount;
    }

    public function countHolidayPresentByUser($startDate, $endDate, $userId, $holidayData)
    {
        $totalPresent = Attendance::select(
            DB::raw('COUNT(DISTINCT DATE(attendances.clock_in_time)) as presentCount')
        )
            ->whereBetween(DB::raw('DATE(attendances.clock_in_time)'), [$startDate->toDateString(), $endDate->toDateString()])
            ->where('attendances.half_day', 'no')
            ->where('attendances.user_id', $userId)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('holidays')
                    ->whereRaw('DATE(holidays.date) = DATE(attendances.clock_in_time)');
            })
            ->whereNotIn(DB::raw('DATE(attendances.clock_in_time)'), $holidayData)
            ->first();

        return $totalPresent->presentCount;
    }

    public function countEveningShiftPresentByUser($startDate, $endDate, $userId, $holidayData)
    {
        $totalPresent = Attendance::select(
            DB::raw('COUNT(DISTINCT DATE(attendances.clock_in_time)) as presentCount')
        )
            ->where(DB::raw('DATE(attendances.clock_in_time)'), '>=', $startDate->toDateString())
            ->where(DB::raw('DATE(attendances.clock_in_time)'), '<=', $endDate->toDateString())
            ->where('half_day', 'no')
            ->where('user_id', $userId)
            ->whereNotNull('employee_shift_id')
            ->whereNotIn(DB::raw('DATE(attendances.clock_in_time)'), $holidayData)
            ->groupBy(DB::raw('DATE(attendances.clock_in_time)'))
            ->get();

        if ($totalPresent->isNotEmpty()) {
            $result = $totalPresent[0]->presentCount;
        } else {
            $result = 0;
        }

        return $result;
    }

    public function byDepartment($payrollCycle = null, $departmentId = null)
    {
        $users = User::join('employee_details', 'employee_details.user_id', '=', 'users.id');

        if ($departmentId) {
            $users = $users->where('employee_details.department_id', $departmentId);
        }

        if ($payrollCycle) {
            $users->join('employee_payroll_cycles', 'employee_payroll_cycles.user_id', '=', 'users.id')
                ->where('employee_payroll_cycles.payroll_cycle_id', $payrollCycle);
        }

        $users = $users->select('users.*')->get();

        $options = '';

        foreach ($users as $item) {
            $options .= '<option  data-content="<div class=\'d-inline-block mr-1\'><img class=\'taskEmployeeImg rounded-circle\' src=' . $item->image_url . ' ></div>  ' . $item->name . '" value="' . $item->id . '"> ' . $item->name . ' </option>';
        }

        return Reply::dataOnly(['status' => 'success', 'data' => $options]);
    }
}
