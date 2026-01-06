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
use App\Models\Detection;
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
use App\Models\EmployeeShiftSchedule;
use Modules\Payroll\Entities\SalaryTds;
use Modules\Payroll\Entities\SalarySlip;
use Modules\Payroll\Entities\PayrollCycle;
use Modules\Payroll\Entities\PayrollSetting;
use Modules\Payroll\Entities\OvertimeRequest;
use App\Http\Controllers\AccountBaseController;
use App\Models\EmployeeShift;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Log;
use MacsiDigital\OAuth2\Support\Token\DB as TokenDB;
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

        $employee = new EmployeeDetails();
        $getCustomFieldGroupsWithFields = $employee->getCustomFieldGroupsWithFields();

        if ($getCustomFieldGroupsWithFields) {
            foreach ($getCustomFieldGroupsWithFields->fields as $field) {
                if ($field->type == 'select' && $field->label == "Rank") {
                    $this->field = $field;
                }
            }
        }

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

        $carbonMonth = Carbon::createFromFormat('m', $this->salarySlip->month)->addMonths(1);
        $this->month = $carbonMonth->format('M');

        abort_403(!($viewPermission == 'all'
            || ($viewPermission == 'owned' && $this->salarySlip->user_id == user()->id)
            || ($viewPermission == 'added' && $this->salarySlip->added_by == user()->id)
            || ($viewPermission == 'both' && ($this->salarySlip->user_id == user()->id || $this->salarySlip->added_by == user()->id))
        ));

        $this->basicSalary = $this->salarySlip->basic_salary;
        $this->basicSalaryInMonth = $this->salarySlip->monthly_salary;
        $this->technicalAllowance = $this->salarySlip->technical_allowance;
        $this->livingCostAllowance = $this->salarySlip->living_cost_allowance;
        $this->specialAllowance = $this->salarySlip->special_allowance;
        $this->overtimeAmount =  $this->salarySlip->overtime_amount;
        $this->offDayHolidaySalary =  $this->salarySlip->off_day_holiday_salary;
        $this->gazattedAllowance =  $this->salarySlip->gazatted_allowance;
        $this->eveningShiftAllowance =  $this->salarySlip->evening_shift_allowance;

        $this->absent = $this->salarySlip->absent;
        $this->leaveWithoutPayDetection = $this->salarySlip->leave_without_pay_detection;
        $this->afterLateDetection = $this->salarySlip->after_late_detection;
        $this->betweenLateDetection = $this->salarySlip->between_late_detection;

        $this->totalAllowance =  $this->salarySlip->gross_salary;
        $this->totalDetection = $this->salarySlip->total_deductions;

        $this->creditSales = $this->salarySlip->credit_sales;
        $this->deposit = $this->salarySlip->deposit;
        $this->loan = $this->salarySlip->loan;
        $this->ssb = $this->salarySlip->ssb;
        $this->otherDetection = $this->salarySlip->other_detection;

        $this->netSalary = $this->totalAllowance - $this->totalDetection;

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
        $salarySlip = SalarySlip::findOrFail($id);
        $userAllowance = Allowance::findOrFail($request->allowanceId);
        $userDetection = Detection::where('user_id', $userAllowance->user_id)->first();

        $technicalAllowance = $request->technical_allowance;
        $livingCostAllowance = $request->living_cost_allowance;
        $specialAllowance = $request->special_allowance;

        $basicSalaryInMonth = $salarySlip->monthly_salary + $technicalAllowance + $livingCostAllowance + $specialAllowance;
        $totalDetection = $salarySlip->absent + $salarySlip->leave_without_pay_detection + $request->other_detection + $request->credit_sales + $request->deposit + $request->loan + $request->ssb;

        $netSalary = $basicSalaryInMonth - $totalDetection;

        // salarySlip
        $salarySlip->status = $request->status;
        $salarySlip->total_deductions = round(($totalDetection), 2);
        $salarySlip->net_salary = round(($netSalary), 2);
        $salarySlip->gross_salary = round(($basicSalaryInMonth), 2);
        $salarySlip->last_updated_by = user()->id;

        // allowance
        $userAllowance->basic_salary = $request->basic_salary;
        $userAllowance->living_cost_allowance = $request->living_cost_allowance;
        $userAllowance->technical_allowance = $request->technical_allowance;
        $userAllowance->special_allowance = $request->special_allowance;

        // detection
        $userDetection->other_detection = $request->other_detection;
        $userDetection->credit_sales = $request->credit_sales;
        $userDetection->deposit = $request->deposit;
        $userDetection->loan = $request->loan;
        $userDetection->ssb = $request->ssb;

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
        $payrollCycleData = PayrollCycle::find($payrollCycle);

        $startDate = CarbonImmutable::parse($month[0])->subMonth()->setDay(26);
        $endDate = CarbonImmutable::parse($month[1])->setDay(25);

        $lastDayOfMonth = $startDate->lastOfMonth();
        $daysInMonth = (int) abs($lastDayOfMonth->diffInDays($startDate) + 26);

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
        } else if ($request->rank_id) {
            $users = User::with('employeeDetail')
                ->join('employee_payroll_cycles', 'employee_payroll_cycles.user_id', '=', 'users.id')
                ->join('employee_monthly_salaries', 'employee_monthly_salaries.user_id', '=', 'users.id')
                ->where('employee_payroll_cycles.payroll_cycle_id', $payrollCycle)
                ->where('employee_monthly_salaries.allow_generate_payroll', 'yes')
                ->join('employee_details', 'employee_details.user_id', '=', 'users.id')
                ->select('users.id', 'users.name', 'users.email', 'users.status', 'users.email_notifications', 'users.created_at', 'users.image', 'users.mobile', 'users.country_id', 'employee_monthly_salaries.id as salary_id')
                ->where('employee_details.rank', $request->rank_id)->get();
        } else {
            $users = User::with('employeeDetail')
                ->leftJoin('employee_details', 'employee_details.user_id', '=', 'users.id')
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

        foreach ($users as $user) {
            $userId = $user->id;
            $employeeDetails = EmployeeDetails::where('user_id', $userId)->first();
            $joiningDate = Carbon::parse($employeeDetails->joining_date);
            $exitDate = (!is_null($employeeDetails->last_date)) ? Carbon::parse($employeeDetails->last_date) : null;
            $payDays = $daysInMonth;
            $HolidayStartDate = ($joiningDate->greaterThan($startDate)) ? $joiningDate : $startDate;
            $HolidayEndDate = (!is_null($exitDate) && $endDate->greaterThan($exitDate)) ? $exitDate : $endDate;

            $subQuery = Attendance::select(
                'clock_in_time',
                DB::raw('ROW_NUMBER() OVER (PARTITION BY DATE(clock_in_time) ORDER BY clock_in_time ASC) as row_num')
            )
                ->where('user_id', $userId)
                ->whereDate('clock_in_time', '>=', $startDate)
                ->whereDate('clock_in_time', '<=', $endDate);

            // Leave Without Pay
            $leave = DB::table('leaves')->select(
                DB::raw("CAST(SUM(CASE WHEN leaves.leave_type_id = 7 THEN 1 ELSE 0 END) AS UNSIGNED) AS absentLeave"),
                DB::raw("CAST(SUM(CASE WHEN leaves.leave_type_id = 6 AND leaves.duration <> 'half day' THEN 1 ELSE 0 END) AS UNSIGNED) AS normalLeaveWithoutPay"),
                DB::raw("CAST(SUM(CASE WHEN leaves.leave_type_id = 6 AND leaves.duration = 'half day' THEN 1 ELSE 0 END) AS UNSIGNED) AS halfDayLeaveWithoutPay"),
                DB::raw(
                    "
                    CAST(
                        (
                            SUM(CASE WHEN leaves.leave_type_id = 6 AND leaves.duration <> 'half day' THEN 1 ELSE 0 END) +
                            SUM(CASE WHEN leaves.leave_type_id = 6 AND leaves.duration = 'half day' THEN 1 ELSE 0 END) * 0.5
                        ) AS DECIMAL(5, 1)
                    )
                    AS totalLeaveWithoutPay
                "
                )
            )
                ->leftJoin('leave_types', 'leaves.leave_type_id', '=', 'leave_types.id')
                ->where('leaves.user_id', $userId)
                ->where('leaves.paid', 0) // unpaid leave
                ->where('leaves.status', 'approved')
                ->whereDate('leaves.leave_date', '>=', $startDate)
                ->whereDate('leaves.leave_date', '<=', $endDate)
                ->first();

            // halfDay Late
            $halfDay = Attendance::select(
                DB::raw("COUNT('attendances.*') AS totalHalfDayCount")
            )
                ->leftJoin('leaves', function ($join) use ($userId) {
                    $join->on(DB::raw('DATE(attendances.clock_in_time)'), '=', 'leaves.leave_date')
                        ->where('leaves.user_id', '=', $userId)
                        ->where('leaves.status', '=', 'approved');
                })
                ->where('attendances.user_id', $userId)
                ->whereDate('attendances.clock_in_time', '>=', $startDate)
                ->whereDate('attendances.clock_in_time', '<=', $endDate)
                ->where('attendances.half_day', 'yes')
                ->where('attendances.half_day_late', 'yes')
                ->first();

            // attendance Late
            $attendanceLateInMonth = Attendance::select(
                DB::raw("CAST(SUM(CASE WHEN attendances.late = 'yes' AND ranked.row_num = 1 THEN 1 ELSE 0 END ) AS UNSIGNED) AS lateCount"),
                DB::raw("CAST(SUM(CASE WHEN attendances.late_between = 'yes' AND ranked.row_num = 1 THEN 1 ELSE 0 END ) AS UNSIGNED) AS lateBetweenCount"),
                DB::raw("CAST(SUM(CASE WHEN attendances.break_time_late = 'yes' AND ranked.row_num = 2 THEN 1 ELSE 0 END ) AS UNSIGNED) AS breakTimeLateCount"),
                DB::raw("CAST(SUM(CASE WHEN attendances.breaktime_late_between = 'yes' AND ranked.row_num = 2 THEN 1 ELSE 0 END ) AS UNSIGNED) AS breakTimeLateBetweenCount"),
            )
                ->joinSub($subQuery, 'ranked', function ($join) {
                    $join->on('attendances.clock_in_time', '=', 'ranked.clock_in_time');
                })
                ->where('attendances.user_id', $userId)
                ->whereDate('attendances.clock_in_time', '>=', $startDate)
                ->whereDate('attendances.clock_in_time', '<=', $endDate)
                ->where('attendances.half_day', 'no')
                ->first();

            $attLateAfter = $attendanceLateInMonth->lateCount;
            $attLateBetween = $attendanceLateInMonth->lateBetweenCount;
            $attBreakTimeAfter = $attendanceLateInMonth->breakTimeLateCount;
            $attBreakTimeLateBetween = $attendanceLateInMonth->breakTimeLateBetweenCount;
            $toalLwpCount = $leave->totalLeaveWithoutPay;
            $absentInMonth = $leave->absentLeave;
            $halfDayLateCount = $halfDay->totalHalfDayCount;

            Log::info("Late Count", [
                "halfDayLateCount" => $halfDayLateCount,
                "attLateAfter" => $attLateAfter,
                "attBreakTimeAfter" => $attBreakTimeAfter,
                "attLateBetween" => $attLateBetween,
                "attBreakTimeLateBetween" => $attBreakTimeLateBetween
            ]);

            $holidayData = $this->getHolidayByDates($HolidayStartDate->toDateString(), $HolidayEndDate->toDateString(), $userId)
                ->pluck('holiday_date')
                ->values(); // Getting Holiday Data

            $eveningShiftPresentCount = $this->countEveningShiftPresentByUser($startDate, $endDate, $userId, $holidayData); // Getting Attendance Data
            $gazattedPresentCount = $this->countHolidayPresentByUser($startDate, $endDate, $userId, $holidayData);

            if ($endDate->greaterThan($joiningDate)) {
                $payDays = (int) $this->countAttendace($startDate, $endDate, $userId, $daysInMonth, $useAttendance, $joiningDate, $exitDate);
                $allowance = Allowance::where('user_id', $userId)->first();

                $additionalSalaries = Allowance::select([
                    'additional_basic_salaries.type',
                    DB::raw('SUM(additional_basic_salaries.amount) as amount'),
                ])
                    ->join('additional_basic_salaries', 'allowances.id', 'additional_basic_salaries.salary_allowance_id')
                    ->whereDate('date', '<=', $endDate)
                    ->groupBy('additional_basic_salaries.type')
                    ->get();

                $overtimeAmount = OvertimeRequest::where('user_id', $userId)
                    ->where('status', 'accept')
                    ->whereDate('date', '>=', $startDate)
                    ->whereDate('date', '<=', $endDate)
                    ->sum('amount');

                $detuction = Detection::where('user_id', $userId)->first();

                if ($payrollCycleData->cycle == 'monthly') {
                    $daysInMonth = $daysInMonth;
                } elseif ($payrollCycleData->cycle == 'weekly') {
                    $daysInMonth = 4;
                } elseif ($payrollCycleData->cycle == 'semimonthly') {
                    $daysInMonth = 2;
                } elseif ($payrollCycleData->cycle == 'biweekly') {
                    $daysInMonth = 30;
                }

                $fixedBasicSalary = $allowance ? $allowance->basic_salary : 0;
                $technicalAllowance = $allowance ? $allowance->technical_allowance : 0;
                $livingCostAllowance = $allowance ? $allowance->living_cost_allowance : 0;
                $specialAllowance = $allowance ? $allowance->special_allowance : 0;

                $perDaySalary = $fixedBasicSalary / $daysInMonth;

                $perDayTechAllowance = $technicalAllowance / $daysInMonth;
                $perDayLivingCostAllowance = $livingCostAllowance / $daysInMonth;
                $perDaySpecialAllowance = $specialAllowance / $daysInMonth;

                if ($payrollCycleData->cycle == 'biweekly') {
                    $perDaySalary = $perDaySalary * 14;
                    $perDayTechAllowance = $perDayTechAllowance * 14;
                    $perDayLivingCostAllowance = $perDayLivingCostAllowance * 14;
                    $perDaySpecialAllowance = $perDaySpecialAllowance * 14;
                }

                $basicSalaryInMonth = $perDaySalary * $daysInMonth;
                $technicalAllowance = $perDayTechAllowance * $daysInMonth;
                $livingCostAllowance = $perDayLivingCostAllowance * $daysInMonth;
                $specialAllowance = $perDaySpecialAllowance * $daysInMonth;

                // joining date
                if ($joiningDate->between($startDate, $endDate) && $joiningDate->greaterThan($startDate)) {
                    $daysDifference = $joiningDate->diffInDays($endDate) + 1;
                    $basicSalaryInMonth = $daysDifference * $perDaySalary;
                    $technicalAllowance = $daysDifference * $perDayTechAllowance;
                    $livingCostAllowance = $daysDifference * $perDayLivingCostAllowance;
                    $specialAllowance = $daysDifference * $perDaySpecialAllowance;
                }

                // exist date
                if (!is_null($exitDate) && $exitDate->between($startDate, $endDate) && $endDate->greaterThan($exitDate)) {
                    $daysDifference = $startDate->diffInDays($exitDate) + 1;
                    $basicSalaryInMonth = $daysDifference * $perDaySalary;
                    $technicalAllowance = $daysDifference * $perDayTechAllowance;
                    $livingCostAllowance = $daysDifference * $perDayLivingCostAllowance;
                    $specialAllowance = $daysDifference * $perDaySpecialAllowance;
                }

                foreach ($additionalSalaries as $additionalSalary) {
                    if ($additionalSalary->type == 'increment') {
                        $basicSalaryInMonth += (int) $additionalSalary->amount;
                    } else {
                        $basicSalaryInMonth -= (int) $additionalSalary->amount;
                    }
                }

                // $payableSalary = $perDaySalary * $payDays;
                $offDayHolidaySalary = $this->offDayHolidayOvertime($startDate, $endDate, $userId, $holidayData);

                $gazattedAllowance = $gazattedPresentCount * 3000;
                $eveningShiftAllowance = $eveningShiftPresentCount * 500;

                $totalBetweenLateCount = floor(($attLateBetween / 3) + ($attBreakTimeLateBetween / 3));
                $totalAfterLateCount = floor($attLateAfter + $attBreakTimeAfter);

                $allLeaveWithoutPayCount = $totalBetweenLateCount + $totalAfterLateCount + $halfDayLateCount + $toalLwpCount;

                $leaveWithoutPayDetection = ($toalLwpCount * $perDaySalary);
                $betweenLateDetection = ($totalBetweenLateCount * $perDaySalary);
                $afterLateDetection = ($totalAfterLateCount * $perDaySalary) + ($halfDayLateCount * $perDaySalary);

                Log::info('attLateAfter', [
                    'userId' => $userId,
                    'userName' => $user->name,
                    'attLateAfter' => $attLateAfter,
                    'attBreakTimeAfter' => $attBreakTimeAfter,
                    'attLateBetween' => $attLateBetween,
                    'attBreakTimeLateBetween' => $attBreakTimeLateBetween,
                    'halfDayLateCount' => $halfDayLateCount,
                    'perDaySalary' => $perDaySalary,
                    'daysInMonth' => $daysInMonth,
                    'afterLateDetection' => $afterLateDetection
                ]);

                $absentDetection = $absentInMonth * $perDaySalary * 2;
                $totalLeaveWithoutPaySalary = $allLeaveWithoutPayCount * $perDaySalary;

                $otherDetection = $detuction ? $detuction->other_detection : 0;
                $creditSales = $detuction ? $detuction->credit_sales : 0;
                $deposit = $detuction ? $detuction->deposit : 0;
                $loan = $detuction ? $detuction->loan : 0;
                $ssb = $detuction ? $detuction->ssb : 0;

                // detection calculation
                $totalDetection = ($totalLeaveWithoutPaySalary) + $otherDetection + $creditSales + $deposit + $loan + $ssb + $absentDetection;

                $allowanceCalculation = $technicalAllowance + $livingCostAllowance + $specialAllowance + $gazattedAllowance + $eveningShiftAllowance;

                $earnings = $allowanceCalculation + $overtimeAmount + $offDayHolidaySalary;

                // earning calculation
                $totalBasicSalary = $basicSalaryInMonth + $earnings;
                $netSalary = $totalBasicSalary - $totalDetection;
                $payrollSetting = PayrollSetting::first();

                $data = [
                    'user_id' => $userId,
                    'currency_id' => $payrollSetting->currency_id,
                    'salary_group_id' => null, // null
                    'basic_salary' => round(($fixedBasicSalary), 2),
                    'monthly_salary' => round($basicSalaryInMonth, 2),
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
                    'overtime_amount' => $overtimeAmount,
                    'off_day_holiday_salary' => $offDayHolidaySalary,
                    'gazatted_allowance' => $gazattedAllowance,
                    'evening_shift_allowance' => $eveningShiftAllowance,
                    'absent' => $absentDetection,
                    'leave_without_pay_detection' => $leaveWithoutPayDetection,
                    'after_late_detection' => $afterLateDetection,
                    'between_late_detection' => $betweenLateDetection,
                    'total_leave_without_pay_salary' => $totalLeaveWithoutPaySalary,
                    'technical_allowance' => $technicalAllowance,
                    'living_cost_allowance' => $livingCostAllowance,
                    'special_allowance' => $specialAllowance,
                    'other_detection' => $otherDetection,
                    'credit_sales' => $creditSales,
                    'deposit' => $deposit,
                    'loan' => $loan,
                    'ssb' => $ssb
                ];

                $userSlip = SalarySlip::where('user_id', $userId)
                    ->where(function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('salary_from', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                            ->orWhereBetween('salary_to', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
                    })
                    ->where('year', $year)->first();

                if (!is_null($userSlip) && $userSlip->status != 'paid') {
                    $userSlip->delete();
                }

                if (is_null($userSlip) || (!is_null($userSlip) && $userSlip->status != 'paid')) {
                    SalarySlip::create($data);
                }
            }
            // dd('stop');
        }


        return Reply::dataOnly(['status' => 'success']);
    }

    protected function calculateTdsSalary($userId, $joiningDate, $financialyearStart, $financialyearEnd, $payrollMonthEndDate, $payrollMonthStartDate)
    {

        $totalEarning = 0;

        if ($joiningDate->greaterThan($financialyearStart)) {
            $allowance = EmployeeMonthlySalary::employeeNetSalary($userId);
            $currentSalary = $initialSalary = $allowance['initialSalary'];
        } else {
            $allowance = EmployeeMonthlySalary::employeeNetSalary($userId, $financialyearStart);
            $currentSalary = $initialSalary = $allowance['netSalary'];
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
        $this->salarySlip = SalarySlip::with('user', 'user.employeeDetail', 'salary_group', 'salary_payment_method', 'payroll_cycle', 'user.userAllowances')
            ->findOrFail($id);
        $this->company = $this->salarySlip->company;

        $carbonMonth = Carbon::createFromFormat('m', $this->salarySlip->month)->addMonths(1);
        $this->month = $carbonMonth->format('M');

        $this->salaryPaymentMethods = SalaryPaymentMethod::all();

        $this->basicSalary = $this->salarySlip->basic_salary;
        $this->basicSalaryInMonth = $this->salarySlip->monthly_salary;
        $this->technicalAllowance = $this->salarySlip->technical_allowance;
        $this->livingCostAllowance = $this->salarySlip->living_cost_allowance;
        $this->specialAllowance = $this->salarySlip->special_allowance;
        $this->overtimeAmount =  $this->salarySlip->overtime_amount;
        $this->offDayHolidaySalary =  $this->salarySlip->off_day_holiday_salary;
        $this->gazattedAllowance =  $this->salarySlip->gazatted_allowance;
        $this->eveningShiftAllowance =  $this->salarySlip->evening_shift_allowance;

        $this->absent = $this->salarySlip->absent;
        $this->leaveWithoutPayDetection = $this->salarySlip->leave_without_pay_detection;
        $this->afterLateDetection = $this->salarySlip->after_late_detection;
        $this->betweenLateDetection = $this->salarySlip->between_late_detection;

        $this->totalAllowance =  $this->salarySlip->gross_salary;
        $this->totalDetection = $this->salarySlip->total_deductions;

        // $detuction = Detection::where('user_id', $this->salarySlip->user_id)->first();

        $this->creditSales = $this->salarySlip->credit_sales;
        $this->deposit = $this->salarySlip->deposit;
        $this->loan = $this->salarySlip->loan;
        $this->ssb = $this->salarySlip->ssb;
        $this->otherDetection = $this->salarySlip->other_detection;

        $this->netSalary = $this->totalAllowance - $this->totalDetection;

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

        $pdf = app('dompdf.wrapper');
        $pdf->setOption('enable_php', true);
        $pdf->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

        $month = Carbon::createFromFormat('m', $this->salarySlip->month)->addMonths(1)->translatedFormat('F');

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

            // dd($presentCount);

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

            // dd($presentCount, $holidays, $payDays);

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
                    $q->where('department_id_json', 'like', '%' . $user->employeeDetail->department_id . '%')
                        ->orWhereNull('department_id_json');
                });
                $subquery->where(function ($q) use ($user) {
                    $q->where('designation_id_json', 'like', '%' . $user->employeeDetail->designation_id . '%')
                        ->orWhereNull('designation_id_json');
                });
                $subquery->where(function ($q) use ($user) {
                    $q->where('employment_type_json', 'like', '%' . $user->employeeDetail->employment_type . '%')
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

    public function offDayHolidayOvertime($startDate, $endDate, $userId, $holidayData)
    {
        $dailyOvertime = Attendance::select(
            DB::raw('DATE(attendances.clock_in_time) as date'),
            'attendances.employee_shift_id',
            'employee_details.overtime_hourly_rate',
            DB::raw("SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND, attendances.clock_in_time, attendances.clock_out_time))) as total_hours"),
            DB::raw("(
                CASE
                    WHEN SUM(TIMESTAMPDIFF(SECOND, attendances.clock_in_time, attendances.clock_out_time)) <= 8 * 60 * 60
                    THEN
                        employee_details.overtime_hourly_rate
                        * (SUM(TIMESTAMPDIFF(SECOND, attendances.clock_in_time, attendances.clock_out_time)) / 3600)
                        * 2
                    WHEN SUM(TIMESTAMPDIFF(SECOND, attendances.clock_in_time, attendances.clock_out_time)) > 8 * 60 * 60
                    THEN
                        employee_details.overtime_hourly_rate * 8 * 2
                    ELSE
                        0
                END
            ) AS offDayHolidayOvertimeAmount")
        )
            ->leftJoin('users', 'users.id', '=', 'attendances.user_id')
            ->leftJoin('employee_details', 'employee_details.user_id', '=', 'users.id')
            ->where('attendances.half_day', 'no')
            ->where('attendances.user_id', $userId)
            ->where(function ($q) use ($holidayData) {
                $q->where(function ($q2) use ($holidayData) {
                    $q2->whereIn(DB::raw('DATE(attendances.clock_in_time)'), $holidayData)
                        ->where('attendances.employee_shift_id', '<>', 1);
                })
                    ->orWhere(function ($q2) use ($holidayData) {
                        $q2->whereNotIn(DB::raw('DATE(attendances.clock_in_time)'), $holidayData)
                            ->where('attendances.employee_shift_id', '=', 1);
                    });
            })
            ->whereBetween(DB::raw('DATE(attendances.clock_in_time)'), [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy('date', 'attendances.employee_shift_id', 'employee_details.overtime_hourly_rate')
            ->orderBy('date');

        $offDayHolidayOvertimeAmount = DB::query()
            ->fromSub($dailyOvertime, 'daily')
            ->select(DB::raw("SUM(offDayHolidayOvertimeAmount) AS offDayHolidayOvertimeAmount"))
            ->value('offDayHolidayOvertimeAmount');


        return $offDayHolidayOvertimeAmount;
    }

    public function countHolidayPresentByUser($startDate, $endDate, $userId, $holidayData)
    {
        $presentCount = Attendance::select(
            DB::raw("SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND, attendances.clock_in_time, attendances.clock_out_time))) as total_hours")
        )
            ->whereIn(DB::raw("DATE(attendances.clock_in_time)"), $holidayData)
            ->whereBetween(DB::raw('DATE(attendances.clock_in_time)'), [$startDate->toDateString(), $endDate->toDateString()])
            ->where('attendances.half_day', 'no')
            ->where('attendances.user_id', $userId)
            ->where('attendances.employee_shift_id', '<>', 1) // employee_shift_id 1 must be offDay
            ->groupBy(DB::raw('DATE(attendances.clock_in_time), attendances.user_id'))
            ->havingRaw('SUM(TIMESTAMPDIFF(SECOND, attendances.clock_in_time, attendances.clock_out_time)) >= ?', [
                8 * 60 * 60
            ])
            ->count();

        return $presentCount;
    }

    public function countEveningShiftPresentByUser($startDate, $endDate, $userId, $holidayData)
    {
        $totalPresent = Attendance::select(
            DB::raw('COUNT(DISTINCT DATE(attendances.clock_in_time)) as presentCount')
        )
            ->whereIn('employee_shift_id', [4, 5, 6, 7]) // 4, 5, 6 and 7 are evening  shift
            ->where(DB::raw('DATE(attendances.clock_in_time)'), '>=', $startDate->toDateString())
            ->where(DB::raw('DATE(attendances.clock_in_time)'), '<=', $endDate->toDateString())
            ->where('half_day', 'no')
            ->where('user_id', $userId)
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

    public function byRank(Request $request)
    {
        $users = User::join('employee_details', 'employee_details.user_id', '=', 'users.id');

        $payrollCycle = $request->cycleId;
        $rankId = $request->rankId;

        if ($rankId != "all" && $rankId != '') {
            $designations = Designation::where('rank_id', $request->rankId)->get();

            $designationsId = $designations->pluck('id')->toArray();

            $users = $users->whereIn('users.designation_id', $designationsId);
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

    public function employeeData($startDate = null, $endDate = null, $userId = null)
    {
        // todo ::~
        $ant = []; // Array For attendance Data indexed by similar date
        $dateWiseData = []; // Array For Combine Data

        $lastDayOfMonth = $startDate->copy()->lastOfMonth()->startOfDay();
        $daysInMonth = (int) abs($lastDayOfMonth->diffInDays($startDate) + 26);

        $totalWorkingDays = $daysInMonth;

        $attendances = Attendance::userAttendanceByDate($startDate, $endDate, $userId); // Getting Attendance Data
        $holidays = Holiday::getHolidayByDates($startDate, $endDate, $userId); // Getting Holiday Data

        $totalWorkingDays = $totalWorkingDays - count($holidays);
        $daysPresent = Attendance::countDaysPresentByUser($startDate, $endDate, $userId);
        $daysLate = Attendance::countDaysLateByUser($startDate, $endDate, $userId);
        $halfDays = Attendance::countHalfDaysByUser($startDate, $endDate, $userId);

        $daysAbsent = (($totalWorkingDays - $daysPresent) < 0) ? '0' : ($totalWorkingDays - $daysPresent);
        $holidayCount = Count($holidays);

        // Getting Leaves Data
        $leavesDates = Leave::where('user_id', $userId)
            ->where('leave_date', '>=', $startDate)
            ->where('leave_date', '<=', $endDate)
            ->where('status', 'approved')
            ->select('leave_date', 'reason', 'duration')
            ->get()->keyBy('date')->toArray();

        $holidayData = $holidays->keyBy('holiday_date');
        $holidayArray = $holidayData->toArray();

        // Set Date as index for same date clock-ins
        foreach ($attendances as $attand) {
            $clockInTime = Carbon::createFromFormat('Y-m-d H:i:s', $attand->clock_in_time->timezone(company()->timezone)->toDateTimeString(), 'UTC');

            if (!is_null($attand->employee_shift_id)) {
                $shiftStartTime = Carbon::parse($clockInTime->copy()->toDateString() . ' ' . $attand->shift->office_start_time);
                $shiftEndTime = Carbon::parse($clockInTime->copy()->toDateString() . ' ' . $attand->shift->office_end_time);

                if ($shiftStartTime->gt($shiftEndTime)) {
                    $shiftEndTime = $shiftEndTime->addDay();
                }

                $shiftSchedule = EmployeeShiftSchedule::with('shift')->where('user_id', $attand->user_id)->where('date', $attand->clock_in_time->format('Y-m-d'))->first();

                if (($shiftSchedule && $attand->employee_shift_id == $shiftSchedule->shift->id) || is_null($shiftSchedule)) {
                    $ant[$clockInTime->copy()->toDateString()][] = $attand; // Set attendance Data indexed by similar date

                } elseif ($clockInTime->betweenIncluded($shiftStartTime, $shiftEndTime)) {
                    $ant[$clockInTime->copy()->toDateString()][] = $attand; // Set attendance Data indexed by similar date

                } elseif ($clockInTime->betweenIncluded($shiftStartTime->copy()->subDay(), $shiftEndTime->copy()->subDay())) {
                    $ant[$clockInTime->copy()->subDay()->toDateString()][] = $attand; // Set attendance Data indexed by previous date
                }
            } else {
                $ant[$attand->clock_in_date][] = $attand; // Set attendance Data indexed by similar date
            }
        }

        // Set All Data in a single Array
        // @codingStandardsIgnoreStart

        for ($date = $endDate; $date->diffInDays($startDate) > 0; $date->subDay()) {
            // @codingStandardsIgnoreEnd

            if ($date->isPast() || $date->isToday()) {

                // Set default array for record
                $dateWiseData[$date->toDateString()] = [
                    'holiday' => false,
                    'attendance' => false,
                    'leave' => false
                ];

                // Set Holiday Data
                if (array_key_exists($date->toDateString(), $holidayArray)) {
                    $dateWiseData[$date->toDateString()]['holiday'] = $holidayData[$date->toDateString()];
                }

                // Set Attendance Data
                if (array_key_exists($date->toDateString(), $ant)) {
                    $dateWiseData[$date->toDateString()]['attendance'] = $ant[$date->toDateString()];
                }

                // Set Leave Data
                if (array_key_exists($date->toDateString(), $leavesDates)) {
                    $dateWiseData[$date->toDateString()]['leave'] = $leavesDates[$date->toDateString()];
                }
            }
        }

        if ($startDate->isPast() || $startDate->isToday()) {
            // Set default array for record
            $dateWiseData[$startDate->toDateString()] = [
                'holiday' => false,
                'attendance' => false,
                'leave' => false
            ];

            // Set Holiday Data
            if (array_key_exists($startDate->toDateString(), $holidayArray)) {
                $dateWiseData[$startDate->toDateString()]['holiday'] = $holidayData[$startDate->toDateString()];
            }

            // Set Attendance Data
            if (array_key_exists($startDate->toDateString(), $ant)) {
                $dateWiseData[$startDate->toDateString()]['attendance'] = $ant[$startDate->toDateString()];
            }

            // Set Leave Data
            if (array_key_exists($startDate->toDateString(), $leavesDates)) {
                $dateWiseData[$startDate->toDateString()]['leave'] = $leavesDates[$startDate->toDateString()];
            }
        }

        // dd([
        //     'daysPresent' => $daysPresent,
        //     'daysLate' => $daysLate,
        //     'halfDays' => $halfDays,
        //     'totalWorkingDays' => $totalWorkingDays,
        //     'absentDays' => $daysAbsent
        // ]);


        return [
            'daysPresent' => $daysPresent,
            'daysLate' => $daysLate,
            'halfDays' => $halfDays,
            'totalWorkingDays' => $totalWorkingDays,
            'absentDays' => $daysAbsent
        ];
    }

    public function attendanceShift($defaultAttendanceSettings)
    {
        $checkPreviousDayShift = EmployeeShiftSchedule::with('shift')->where('user_id', user()->id)
            ->where('date', now($this->company->timezone)->subDay()->toDateString())
            ->first();

        $checkTodayShift = EmployeeShiftSchedule::with('shift')->where('user_id', user()->id)
            ->where('date', now(company()->timezone)->toDateString())
            ->first();

        $backDayFromDefault = Carbon::parse(now($this->company->timezone)->subDay()->format('Y-m-d') . ' ' . $defaultAttendanceSettings->office_start_time, $this->company->timezone);

        $backDayToDefault = Carbon::parse(now($this->company->timezone)->subDay()->format('Y-m-d') . ' ' . $defaultAttendanceSettings->office_end_time, $this->company->timezone);

        if ($backDayFromDefault->gt($backDayToDefault)) {
            $backDayToDefault->addDay();
        }

        $nowTime = Carbon::createFromFormat('Y-m-d H:i:s', now($this->company->timezone)->toDateTimeString(), 'UTC');

        if ($checkPreviousDayShift && $nowTime->betweenIncluded($checkPreviousDayShift->shift_start_time, $checkPreviousDayShift->shift_end_time)) {
            $attendanceSettings = $checkPreviousDayShift;
        } else if ($nowTime->betweenIncluded($backDayFromDefault, $backDayToDefault)) {
            $attendanceSettings = $defaultAttendanceSettings;
        } else if (
            $checkTodayShift &&
            ($nowTime->betweenIncluded($checkTodayShift->shift_start_time, $checkTodayShift->shift_end_time)
                || $nowTime->gt($checkTodayShift->shift_end_time)
                || (!$nowTime->betweenIncluded($checkTodayShift->shift_start_time, $checkTodayShift->shift_end_time) && $defaultAttendanceSettings->show_clock_in_button == 'no'))
        ) {
            $attendanceSettings = $checkTodayShift;
        } else if ($checkTodayShift && !is_null($checkTodayShift->shift->early_clock_in)) {
            $attendanceSettings = $checkTodayShift;
        } else {
            $attendanceSettings = $defaultAttendanceSettings;
        }


        if (isset($attendanceSettings->shift)) {
            return $attendanceSettings->shift;
        }

        return $attendanceSettings;
    }

    public function attendanceShiftLate($defaultAttendanceSettings = null, $userId = null, $date = null, $clockInTime = null)
    {
        $checkPreviousDayShift = EmployeeShiftSchedule::without('shift')->where('user_id', $userId)
            ->where('date', $date->copy()->subDay()->toDateString())
            ->first();

        $checkTodayShift = EmployeeShiftSchedule::without('shift')->where('user_id', $userId)
            ->where('date', $date->copy()->toDateString())
            ->first();

        $backDayFromDefault = Carbon::parse($date->copy()->subDay()->format('Y-m-d') . ' ' . $defaultAttendanceSettings->office_start_time);

        $backDayToDefault = Carbon::parse($date->copy()->subDay()->format('Y-m-d') . ' ' . $defaultAttendanceSettings->office_end_time);

        if ($backDayFromDefault->gt($backDayToDefault)) {
            $backDayToDefault->addDay();
        }

        $nowTime = Carbon::createFromFormat('Y-m-d H:i:s', $clockInTime, 'UTC');


        // $nowTime = Carbon::createFromFormat('Y-m-d H:i:s', $date->copy()->toDateString() . ' ' . $clockInTime, 'UTC');

        if ($checkPreviousDayShift && $nowTime->betweenIncluded($checkPreviousDayShift->shift_start_time, $checkPreviousDayShift->shift_end_time)) {
            $attendanceSettings = $checkPreviousDayShift;
        } else if ($nowTime->betweenIncluded($backDayFromDefault, $backDayToDefault)) {
            $attendanceSettings = $defaultAttendanceSettings;
        } else if (
            $checkTodayShift &&
            ($nowTime->betweenIncluded($checkTodayShift->shift_start_time, $checkTodayShift->shift_end_time) || $nowTime->gt($checkTodayShift->shift_end_time))
        ) {
            $attendanceSettings = $checkTodayShift;
        } else if ($checkTodayShift && $checkTodayShift->shift->shift_type == 'flexible') {
            $attendanceSettings = $checkTodayShift;
        } else {
            $attendanceSettings = $defaultAttendanceSettings;
        }

        return $attendanceSettings->shift;
    }
}
