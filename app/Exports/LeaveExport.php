<?php

namespace App\Exports;

use App\Models\Leave;
use App\Models\EmployeeDetails;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use App\Models\LeaveSetting;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class LeaveExport implements FromCollection, WithHeadings, WithEvents
{

    /**
     * @return Collection
     */
    public static $sum;
    public $startdate;
    public $exportAll;
    public $enddate;
    private $viewLeavePermission;
    private $reportingPermission;

    public function __construct($startdate, $enddate, $exportAll)
    {
        $this->startdate = $startdate;
        $this->enddate = $enddate;
        $this->exportAll = $exportAll;
        $this->viewLeavePermission = user()->permission('view_leave');
        $this->reportingPermission = LeaveSetting::value('manager_permission');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => [self::class, 'afterSheet'],
        ];
    }

    public static function afterSheet(AfterSheet $event)
    {
        $emp_status = self::$sum;
        $total = count($emp_status);
        $rowIndex = 2;

        foreach ($emp_status as $index => $leave) {

            if (isset($leave['leave_date']['comments'])) {
                $data =  $leave['leave_date']['data'];
                $comments = implode(', ', $leave['leave_date']['comments']);
                $cell = 'D' . $rowIndex;
                $event->sheet->getDelegate()->setCellValue($cell, $data);
                $event->sheet->getDelegate()->getComment($cell)->getText()->createTextRun($comments);
            }

            if (isset($leave['status']['comments']['status'])) {
                $data =  $leave['status']['data'];
                $statusComments = $leave['status']['comments']['status'];
                $statusCell = 'F' . $rowIndex;
                $event->sheet->getDelegate()->setCellValue($statusCell, $data);
                $event->sheet->getDelegate()->getComment($statusCell)->getText()->createTextRun($statusComments);
            }

            $rowIndex++;
        }

        $event->sheet->getDelegate()->getStyle('B:AG')
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    public function headings(): array
    {
        $arr = array();

        $arr[] =[
            '#',
            __('app.id'),
            __('app.employee'),
            __('app.leaveDate'),
            __('app.duration'),
            __('app.leaveStatus'),
            __('app.leaveType'),
            __('app.paid')
        ];

        return $arr;
    }

    public function collection()
    {
        $leavesList = Leave::with('user', 'user.employeeDetail', 'user.employeeDetail.designation', 'user.session', 'type')
            ->join('leave_types', 'leave_types.id','leaves.leave_type_id')
            ->join('users', 'leaves.user_id', 'users.id')
            ->join('employee_details', 'employee_details.user_id', 'users.id')
            ->selectRaw('leaves.*, leave_types.color, leave_types.type_name')
            ->orderBy('leaves.leave_date')
            ->orderBy('leaves.user_id');

        if($this->exportAll == false){
            if (!is_null($this->startdate)) {
                $leavesList->whereRaw('Date(leaves.leave_date) >= ?', [$this->startdate]);
            }

            if (!is_null($this->enddate)) {
                $leavesList->whereRaw('Date(leaves.leave_date) <= ?', [$this->enddate]);
            }
        }

        if (request()->searchText != '') {
            $leavesList->where('users.name', 'like', '%' . request()->searchText . '%');
        }

        if ($this->viewLeavePermission == 'owned') {
            $leavesList->where(function ($q) {
                $q->orWhere('leaves.user_id', '=', user()->id);

                ($this->reportingPermission != 'cannot-approve') ? $q->orWhere('employee_details.reporting_to', user()->id) : '';
            });
        }

        if ($this->viewLeavePermission == 'added') {
            $leavesList->where(function ($q) {
                $q->orWhere('leaves.added_by', '=', user()->id);

                ($this->reportingPermission != 'cannot-approve') ? $q->orWhere('employee_details.reporting_to', user()->id) : '';
            });
        }

        if ($this->viewLeavePermission == 'both') {

            $leavesList->where(function ($q) {
                $q->orwhere('leaves.user_id', '=', user()->id);

                $q->orWhere('leaves.added_by', '=', user()->id);

                ($this->reportingPermission != 'cannot-approve') ? $q->orWhere('employee_details.reporting_to', user()->id) : '';
            });
        }

        $leaveLists = $leavesList->get();
        $leavedata = array();
        $emp_leave = 1;
        $employee_index = 0;

        foreach ($leaveLists as $leavesList) {

            $leavedata[$employee_index]['#'] = $emp_leave;
            $leavedata[$employee_index]['employee_name'] = $leavesList->user->id;
            $leavedata[$employee_index]['id'] = $leavesList->user->name;

            $leavedata[$employee_index]['leave_date'] = $leavesList->leave_date->format('d-m-Y') .' ('.Carbon::parse($leavesList->leave_date)->translatedFormat('l').')';
            $leavedata[$employee_index]['duration'] = ucfirst($leavesList->duration);
            $leavedata[$employee_index]['leave_status'] = ucfirst($leavesList->status);
            $leavedata[$employee_index]['leave_type'] = $leavesList->type->type_name;

            $leavedata[$employee_index]['paid'] = $leavesList->type->paid == 1 ? __('app.paid') : __('app.unpaid');

            $employee_index++;
            $emp_leave++;
        }

        $leavedata = collect($leavedata);
        self::$sum = $leavedata;

        return $leavedata;

    }

    public function map($leavedata): array
    {
        $data = array();
        $data[] = $leavedata['employee_name'];
        return $data;
    }
}
