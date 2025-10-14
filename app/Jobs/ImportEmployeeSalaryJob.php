<?php

namespace App\Jobs;

use App\Models\Allowance;
use Exception;
use App\Models\User;
use App\Models\Leave;
use App\Models\Attendance;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use App\Traits\ExcelImportable;
use App\Models\AttendanceSetting;
use App\Models\Detection;
use Illuminate\Support\Facades\DB;
use App\Models\EmployeeShiftSchedule;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Carbon\Exceptions\InvalidFormatException;

class ImportEmployeeSalaryJob implements ShouldQueue
{

    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use ExcelImportable;

    private $row;
    private $columns;
    private $company;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($row, $columns, $company = null)
    {
        $this->row = $row;
        $this->columns = $columns;
        $this->company = $company;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (
            $this->isColumnExists('email') && $this->isColumnExists('basic_salary') &&
            $this->isColumnExists('technical_allowance') && $this->isColumnExists('living_cost_allowance') &&
            $this->isColumnExists('special_allowance') && $this->isColumnExists('other_detection')
        ) {

            // user that have employee role
            $user = User::where('email', $this->getColumnValue('email'))->whereHas('roles', function ($q) {
                $q->where('name', 'employee');
            })->first();

            if (!$user) {
                $this->failJobWithMessage(__('messages.employeeNotFound'));
            } else {
                DB::beginTransaction();
                try {

                    $basic_salary = $this->isColumnExists('basic_salary') ? $this->getColumnValue('basic_salary') : 0;
                    $technical_allowance = $this->isColumnExists('technical_allowance') ? $this->getColumnValue('technical_allowance') : 0;
                    $living_cost_allowance = $this->isColumnExists('living_cost_allowance') ? $this->getColumnValue('living_cost_allowance') : 0;
                    $special_allowance = $this->isColumnExists('special_allowance') ? $this->getColumnValue('special_allowance') : 0;
                    $other_detection = $this->isColumnExists('other_detection') ? $this->getColumnValue('other_detection') : 0;
                    $credit_sales = $this->isColumnExists('credit_sales') ? $this->getColumnValue('credit_sales') : 0;
                    $deposit = $this->isColumnExists('deposit') ? $this->getColumnValue('deposit') : 0;
                    $loan = $this->isColumnExists('loan') ? $this->getColumnValue('loan') : 0;
                    $ssb = $this->isColumnExists('ssb') ? $this->getColumnValue('ssb') : 0;

                    $allowance = Allowance::firstOrNew(['user_id' => $user->id]);
                    $detection = Detection::firstOrNew(['user_id' => $user->id]);

                    if (!$allowance->exists) {
                        $allowance->basic_salary = $basic_salary;
                    }

                    $allowance->fill([
                        'technical_allowance' => $technical_allowance,
                        'living_cost_allowance' => $living_cost_allowance,
                        'special_allowance' => $special_allowance,
                    ]);

                    $detection->fill([
                        'other_detection' => $other_detection,
                        'credit_sales' => $credit_sales,
                        'deposit' => $deposit,
                        'loan' => $loan,
                        'ssb' => $ssb
                    ]);

                    $allowance->save();
                    $detection->save();


                    DB::commit();
                } catch (InvalidFormatException $e) {
                    DB::rollBack();
                    $this->failJob(__('messages.invalidDate'));
                } catch (Exception $e) {
                    DB::rollBack();
                    $this->failJobWithMessage($e->getMessage());
                }
            }
        } else {
            $this->failJob(__('messages.invalidData'));
        }
    }
}
