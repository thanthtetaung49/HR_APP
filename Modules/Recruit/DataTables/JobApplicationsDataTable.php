<?php

namespace Modules\Recruit\DataTables;

use Illuminate\Support\Carbon;
use App\DataTables\BaseDataTable;
use Modules\Recruit\Entities\RecruitApplicantBlacklist;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Modules\Recruit\Entities\RecruitJobApplication;
use Modules\Recruit\Entities\RecruitApplicationStatus;

use function Aws\boolean_value;

class JobApplicationsDataTable extends BaseDataTable
{

    private $editJobApplicationPermission;
    private $deleteJobApplicationPermission;
    private $viewJobApplicationPermission;

    public function __construct()
    {
        parent::__construct();
        $this->editJobApplicationPermission = user()->permission('edit_job_application');
        $this->deleteJobApplicationPermission = user()->permission('delete_job_application');
        $this->viewJobApplicationPermission = user()->permission('view_job_application');
    }

    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        $jobBoardColumns = RecruitApplicationStatus::orderBy('position', 'asc')->get();

        return datatables()
            ->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="select-table-row" id="datatable-row-' . $row->id . '"  name="datatable_ids[]" value="' . $row->id . '" onclick="dataTableRowCheck(' . $row->id . ')">';
            })
            ->editColumn('full_name', function ($row) {
                return '<div class="media align-items-center">
                <div class="media-body">
                <h5 class="mb-0 f-13 text-darkest-grey"><a href="' . route('job-applications.show', [$row->id]) . '" class="openRightModal">' . $row->full_name . '</a></h5>
                </div>
                </div>';
            })
            ->addColumn('recruit_job_id', function ($row) {
                return '<a href="' . route('jobs.show', [$row->recruit_job_id]) . '" class="text-darkest-grey" >' . $row->title . '</a>';
            })
            ->editColumn('location', function ($row) {
                return $row->location;
            })
            ->editColumn('created_at', function ($row) {
                return $row->created_at->format($this->company->date_format);
            })
            ->editColumn('status', function ($row) use ($jobBoardColumns) {
                if (
                    $this->editJobApplicationPermission == 'all' ||
                    ($this->editJobApplicationPermission == 'added' && $row->added_by == user()->id) ||
                    ($this->editJobApplicationPermission == 'owned' && user()->id == $row->recruiter_id) ||
                    ($this->editJobApplicationPermission == 'both' && user()->id == $row->recruiter_id) ||
                    $row->added_by == user()->id
                ) {
                    $status = '<select data-size="4" class="form-control select-picker change-status" data-status-id="' . $row->id . '">';

                    foreach ($jobBoardColumns as $item) {
                        $status .= '<option ';

                        if ($item->id == $row->recruit_application_status_id) {
                            $status .= 'selected';
                        }

                        $status .= '  data-content="<i class=\'fa fa-circle mr-2\' style=\'color: ' . $item->color . '\'></i> ' . $item->status . '" value="' . $item->id . '">' . $item->status . '</option>';
                    }

                    $status .= '</select>';
                } else {
                    return ' <i class="fa fa-circle mr-1 text-light-green f-10" style=\'color: ' . $row->color . '\'></i>' . $row->status;
                }

                return $status;
            })
            ->addColumn('name', function ($row) {
                return $row->full_name;
            })
            ->addColumn('phone', function ($row) {
                return $row->phone;
            })
            ->addColumn('email', function ($row) {
                return $row->email;
            })
            ->addColumn('current_ctc', function ($row) {
                return $row->current_ctc ?? '--';
            })
            ->addColumn('expected_ctc', function ($row) {
                return $row->expected_ctc ?? '--';
            })
            ->addColumn('total_experience', function ($row) {
                return $row->total_experience ?? '--';
            })
            ->addColumn('source', function ($row) {
                return $row->application_source ?? '--';
            })
            ->addColumn('gender', function ($row) {
                return $row->gender ?? '--';
            })
            ->addColumn('job_name', function ($row) {
                return $row->title;
            })
            ->addColumn('legacy_status_name', fn($row) => $row->status)
            ->addColumn('age', fn($row) => $row->date_of_birth ? Carbon::parse($row->date_of_birth)->age : null)
            ->editColumn('work_experience_details', function ($row) {
                return collect($row->work_experience_details ?? [])->map(function ($item) {
                    return implode(' | ', array_filter([$item['company'] ?? null, $item['position'] ?? null, isset($item['years']) ? $item['years'] . ' years' : null]));
                })->implode('; ');
            })
            ->editColumn('job_offer_decision', function ($row) {
                $decisions = [
                    'accepted' => [
                        'label' => 'Accepted',
                        'color' => '#047857',
                        'background' => '#d1fae5',
                        'icon' => 'fa-check-circle',
                    ],
                    'declined' => [
                        'label' => 'Declined',
                        'color' => '#b91c1c',
                        'background' => '#fee2e2',
                        'icon' => 'fa-times-circle',
                    ],
                ];

                if (
                    empty($row->job_offer_decision) ||
                    !isset($decisions[$row->job_offer_decision])
                ) {
                    return '<span class="text-lightest f-12">--</span>';
                }

                $decision = $decisions[$row->job_offer_decision];

                return sprintf(
                    '<span class="d-inline-flex align-items-center px-3 py-2 rounded-pill f-12 font-weight-semibold"
            style="color:%s; background-color:%s; white-space:nowrap;">
            <i class="fa %s mr-2"></i>
            %s
        </span>',
                    $decision['color'],
                    $decision['background'],
                    $decision['icon'],
                    e($decision['label'])
                );
            })
            ->editColumn('selection_phase', function ($row) {
                $phases = [
                    'cv_screening' => [
                        'label' => 'CV Screening',
                        'color' => '#1d4ed8',
                        'background' => '#dbeafe',
                        'icon' => 'fa-file-alt',
                    ],
                    'first_interview' => [
                        'label' => 'First Interview',
                        'color' => '#7c3aed',
                        'background' => '#ede9fe',
                        'icon' => 'fa-comments',
                    ],
                    'second_interview' => [
                        'label' => 'Second Interview',
                        'color' => '#c2410c',
                        'background' => '#ffedd5',
                        'icon' => 'fa-user-check',
                    ],
                    'job_offer' => [
                        'label' => 'Job Offer',
                        'color' => '#047857',
                        'background' => '#d1fae5',
                        'icon' => 'fa-file-signature',
                    ],
                    'hiring' => [
                        'label' => 'Hiring',
                        'color' => '#0f766e',
                        'background' => '#ccfbf1',
                        'icon' => 'fa-user-plus',
                    ],
                ];

                $phase = $phases[$row->selection_phase] ?? [
                    'label' => ucwords(
                        str_replace('_', ' ', $row->selection_phase ?? 'Unknown')
                    ),
                    'color' => '#64748b',
                    'background' => '#f1f5f9',
                    'icon' => 'fa-circle',
                ];

                return sprintf(
                    '<span class="d-inline-flex align-items-center px-3 py-2 rounded-pill f-12 font-weight-semibold"
            style="color:%s; background-color:%s; white-space:nowrap;">
            <i class="fa %s mr-2"></i>
            %s
        </span>',
                    $phase['color'],
                    $phase['background'],
                    $phase['icon'],
                    e($phase['label'])
                );
            })
            ->editColumn('overall_status', function ($row) {
                $statuses = [
                    'not_started' => [
                        'label' => 'Not Started',
                        'color' => '#64748b',
                        'background' => '#f1f5f9',
                        'icon' => 'fa-clock',
                    ],
                    'in_progress' => [
                        'label' => 'In Progress',
                        'color' => '#1d4ed8',
                        'background' => '#dbeafe',
                        'icon' => 'fa-spinner',
                    ],
                    'keep_cv' => [
                        'label' => 'Keep CV',
                        'color' => '#a16207',
                        'background' => '#fef3c7',
                        'icon' => 'fa-bookmark',
                    ],
                    'rejected' => [
                        'label' => 'Rejected',
                        'color' => '#b91c1c',
                        'background' => '#fee2e2',
                        'icon' => 'fa-times-circle',
                    ],
                    'passed' => [
                        'label' => 'Passed',
                        'color' => '#047857',
                        'background' => '#d1fae5',
                        'icon' => 'fa-check-circle',
                    ],
                    'hired' => [
                        'label' => 'Hired',
                        'color' => '#6d28d9',
                        'background' => '#ede9fe',
                        'icon' => 'fa-user-check',
                    ],
                ];

                $status = $statuses[$row->overall_status] ?? [
                    'label' => ucwords(
                        str_replace('_', ' ', $row->overall_status ?? 'Unknown')
                    ),
                    'color' => '#64748b',
                    'background' => '#f1f5f9',
                    'icon' => 'fa-circle',
                ];

                return sprintf(
                    '<span class="d-inline-flex align-items-center px-3 py-2 rounded-pill f-12 font-weight-semibold"
            style="color:%s; background-color:%s; white-space:nowrap;">
            <i class="fa %s mr-2"></i>
            %s
        </span>',
                    $status['color'],
                    $status['background'],
                    $status['icon'],
                    e($status['label'])
                );
            })
            ->addColumn('is_black_list', function ($row) {
                $isBlacklisted = boolean_value($row->is_blacklisted);

                // dd($isBlacklisted);

                if ($isBlacklisted) {
                    return '
            <span class="d-inline-flex align-items-center px-3 py-2 rounded-pill f-12 font-weight-semibold"
                style="color:#b91c1c; background-color:#fee2e2; white-space:nowrap;">
                <i class="fa fa-ban mr-2"></i>
                Blacklisted
            </span>
        ';
                }

                return '
        <span class="d-inline-flex align-items-center px-3 py-2 rounded-pill f-12 font-weight-semibold"
            style="color:#047857; background-color:#d1fae5; white-space:nowrap;">
            <i class="fa fa-check-circle mr-2"></i>
            Clear
        </span>
    ';
            })
            ->addColumn('action', function ($row) {
                $action = '<div class="task_view">

                    <div class="dropdown">
                        <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link"
                            id="dropdownMenuLink-' . $row->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-options-vertical icons"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink-' . $row->id . '" tabindex="0">';

                if (
                    $this->viewJobApplicationPermission == 'all' ||
                    ($this->viewJobApplicationPermission == 'added' && $row->added_by == user()->id) ||
                    ($this->viewJobApplicationPermission == 'owned' && user()->id == $row->recruiter_id) ||
                    ($this->viewJobApplicationPermission == 'both' && user()->id == $row->recruiter_id) ||
                    $row->added_by == user()->id
                ) {
                    $action .= '<a href="' . route('job-applications.show', [$row->id]) . '" class="dropdown-item openRightModal"><i class="fa fa-eye mr-2"></i>' . __('app.view') . '</a>';
                }

                if (
                    $this->editJobApplicationPermission == 'all' ||
                    ($this->editJobApplicationPermission == 'added' && $row->added_by == user()->id) ||
                    ($this->editJobApplicationPermission == 'owned' && user()->id == $row->recruiter_id) ||
                    ($this->editJobApplicationPermission == 'both' && user()->id == $row->recruiter_id) ||
                    $row->added_by == user()->id
                ) {
                    $action .= '<a class="dropdown-item" href="' . route('job-applications.edit', [$row->id]) . '">
                                    <i class="fa fa-edit mr-2"></i>
                                    ' . trans('app.edit') . '
                                </a>';
                }

                if (
                    $this->editJobApplicationPermission == 'all' ||
                    ($this->editJobApplicationPermission == 'added' && $row->added_by == user()->id) ||
                    ($this->editJobApplicationPermission == 'owned' && user()->id == $row->recruiter_id) ||
                    ($this->editJobApplicationPermission == 'both' && user()->id == $row->recruiter_id) ||
                    $row->added_by == user()->id
                ) {
                    $action .= '<a class="dropdown-item archive-job" href="javascript:;" data-application-id="' . $row->id . '">
                                    <i class="fa fa-archive mr-2"></i>
                                    ' . trans('recruit::modules.jobApplication.archiveApplication') . '
                                </a>';
                }

                if (
                    $this->editJobApplicationPermission == 'all' ||
                    ($this->editJobApplicationPermission == 'added' && $row->added_by == user()->id) ||
                    ($this->editJobApplicationPermission == 'owned' && user()->id == $row->recruiter_id) ||
                    ($this->editJobApplicationPermission == 'both' && user()->id == $row->recruiter_id) ||
                    $row->added_by == user()->id
                ) {
                    $action .= '<a class="dropdown-item follow-up" href="javascript:;" data-datatable="true" data-application-id="' . $row->id . '">
                    <i class="fa fa-thumbs-up mr-2"></i>
                    ' . trans('modules.lead.addFollowUp') . '
                    </a>';
                }

                if (
                    $this->deleteJobApplicationPermission == 'all' ||
                    ($this->deleteJobApplicationPermission == 'added' && $row->added_by == user()->id) ||
                    ($this->deleteJobApplicationPermission == 'owned' && user()->id == $row->recruiter_id) ||
                    ($this->deleteJobApplicationPermission == 'both' && user()->id == $row->recruiter_id) ||
                    $row->added_by == user()->id
                ) {
                    $action .= '<a class="dropdown-item delete-table-row" href="javascript:;" data-application-id="' . $row->id . '">
                                    <i class="fa fa-trash mr-2"></i>
                                    ' . trans('app.delete') . '
                                </a>';
                }

                $action .= '</div>
                    </div>
                </div>';

                return $action;
            })
            ->addColumn('selection_phase_export', function ($row) {
                $labels = [
                    'cv_screening' => 'CV Screening',
                    'first_interview' => 'First Interview',
                    'second_interview' => 'Second Interview',
                    'job_offer' => 'Job Offer',
                    'hiring' => 'Hiring',
                ];

                return $labels[$row->selection_phase] ?? '';
            })
            ->addColumn('overall_status_export', function ($row) {
                $labels = [
                    'not_started' => 'Not Started',
                    'in_progress' => 'In Progress',
                    'keep_cv' => 'Keep CV',
                    'rejected' => 'Rejected',
                    'hired' => 'Hired',
                ];

                return $labels[$row->overall_status] ?? '';
            })
            ->addIndexColumn()
            ->setRowId(fn($row) => 'row-' . $row->id)
            ->rawColumns(['action', 'status', 'selection_phase', 'overall_status', 'full_name', 'recruit_job_id', 'location', 'date', 'check', 'phone', 'email', 'current_ctc', 'expected_ctc', 'total_experience', 'source', 'gender', 'job_offer_decision', 'is_black_list']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param  $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(RecruitJobApplication $model)
    {
        $request = $this->request();

        // dd($request->selection_phase, $request->overall_status);
        $startDate = null;
        $endDate = null;

        if ($request->startDate !== null && $request->startDate != 'null' && $request->startDate != '') {
            $startDate = Carbon::createFromFormat($this->company->date_format, $request->startDate)->toDateString();
        }

        if ($request->endDate !== null && $request->endDate != 'null' && $request->endDate != '') {
            $endDate = Carbon::createFromFormat($this->company->date_format, $request->endDate)->toDateString();
        }

        $model = $model->select('recruit_job_applications.*', 'recruit_jobs.title', 'recruit_jobs.recruiter_id', 'company_addresses.location', 'recruit_application_status.color', 'recruit_application_status.status', 'application_sources.application_source');
        $model = $model->leftJoin('recruit_application_status', 'recruit_application_status.id', '=', 'recruit_job_applications.recruit_application_status_id');
        $model = $model->leftJoin('recruit_jobs', 'recruit_jobs.id', '=', 'recruit_job_applications.recruit_job_id')
            ->leftJoin('company_addresses', 'company_addresses.id', '=', 'recruit_job_applications.location_id')
            ->leftJoin('application_sources', 'application_sources.id', '=', 'recruit_job_applications.application_source_id')
            // ->where('recruit_job_applications.is_blacklisted', false)
            ->groupBy('recruit_job_applications.id');

        if ($this->viewJobApplicationPermission == 'added') {
            $model->where(function ($query) {
                return $query->where('recruit_job_applications.added_by', user()->id);
            });
        }

        if ($this->viewJobApplicationPermission == 'owned') {
            $model->where(function ($query) {
                return $query->where('recruit_jobs.recruiter_id', user()->id);
            });
        }

        if ($this->viewJobApplicationPermission == 'both') {
            $model->where(function ($query) {
                return $query->where('recruit_job_applications.added_by', user()->id)
                    ->orWhere('recruit_jobs.recruiter_id', user()->id);
            });
        }

        if ($this->request()->searchText != '') {
            $model = $model->where(function ($query) {
                $query->where('recruit_job_applications.full_name', 'like', '%' . request('searchText') . '%')
                    ->orWhere('recruit_job_applications.phone', 'like', '%' . request('searchText') . '%')
                    ->orWhere('recruit_job_applications.email', 'like', '%' . request('searchText') . '%')
                    ->orWhere('recruit_jobs.title', 'like', '%' . request('searchText') . '%')
                    ->orWhere('company_addresses.location', 'like', '%' . request('searchText') . '%')
                    ->orWhere('recruit_application_status.status', 'like', '%' . request('searchText') . '%')
                    ->orWhere('application_sources.application_source', 'like', '%' . request('searchText') . '%');
            });
        }

        if ($request->blacklist != null && $request->blacklist != 'all') {
            $model = $model->where('recruit_job_applications.is_blacklisted', $request->blacklist);
        } else {
            $model = $model->where('recruit_job_applications.is_blacklisted', 0);
        }

        if ($request->job != 0 && $request->job != null && $request->job != 'all') {
            $model->where('recruit_jobs.id', '=', $request->job);
        }

        if ($request->location != 0 && $request->location != null && $request->location != 'all') {
            $model = $model->where('company_addresses.id', '=', $request->location);
        }

        if ($request->status != 0 && $request->status != null && $request->status != 'all') {
            $model = $model->where('recruit_job_applications.recruit_application_status_id', '=', $request->status);
        }

        if ($request->gender != null && $request->gender != 'all') {
            $model = $model->where('recruit_job_applications.gender', '=', $request->gender);
        }

        if ($request->total_experience != null && $request->total_experience != 'all') {
            $model = $model->where('recruit_job_applications.total_experience', '=', $request->total_experience);
        }

        if ($request->current_location != null && $request->current_location != 'all') {
            $model = $model->where('recruit_job_applications.current_location', '=', $request->current_location);
        }

        if ($request->current_ctc_min != null && $request->current_ctc_min != '') {
            $model = $model->where('recruit_job_applications.current_ctc', '>=', $request->current_ctc_min);
        }

        if ($request->current_ctc_max != null && $request->current_ctc_max != '') {
            $model = $model->where('recruit_job_applications.current_ctc', '<=', $request->current_ctc_max);
        }

        if ($request->expected_ctc_min != null && $request->expected_ctc_min != '') {
            $model = $model->where('recruit_job_applications.expected_ctc', '>=', $request->expected_ctc_min);
        }

        if ($request->expected_ctc_max != null && $request->expected_ctc_max != '') {
            $model = $model->where('recruit_job_applications.expected_ctc', '<=', $request->expected_ctc_max);
        }

        if ($request->startDate != null && $request->startDate != '') {
            $model = $model->whereDate('recruit_job_applications.created_at', '>=', $startDate);
        }

        if ($request->endDate != null && $request->endDate != '') {
            $model = $model->whereDate('recruit_job_applications.created_at', '<=', $endDate);
        }

        if ($request->selection_phase != null && $request->selection_phase != 'all') {
            $model = $model->where('recruit_job_applications.selection_phase', '=', $request->selection_phase);
        }

        if ($request->overall_status != null && $request->overall_status != 'all') {
            $model = $model->where('recruit_job_applications.overall_status', '=', $request->overall_status);
        }

        // dd($model->get(), $request->selection_phase, $request->overall_status);

        return $model->orderBy('recruit_job_applications.id', 'desc');
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return parent::setBuilder('job-applications-table')
            ->parameters([
                'autoWidth' => false,
                'scrollX' => true,
                'order' => [7, 'desc'],
                'initComplete' => 'function () {
                    window.LaravelDataTables["job-applications-table"].buttons().container()
                     .appendTo( "#table-actions")
                 }',
                'fnDrawCallback' => 'function( oSettings ) {
                   //
                   $(".select-picker").selectpicker();
                 }'
            ])
            ->buttons(
                Button::make([
                    'extend' => 'excel',
                    'text' => '<i class="fa fa-file-export"></i> ' . trans('app.exportExcel'),
                    'filename' => 'Job_Applicants_' . now()->format('Ymd'),
                ])
            );
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            'check' => [
                'title' => '<input type="checkbox" name="select_all_table" id="select-all-table" onclick="selectAllTable(this)">',
                'exportable' => false,
                'orderable' => false,
                'searchable' => false
            ],
            '#' => ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'visible' => false, 'title' => '#'],
            __('recruit::modules.jobApplication.name') => ['data' => 'full_name', 'exportable' => false, 'name' => 'full_name', 'title' => __('recruit::modules.jobApplication.name'), 'width' => '150px',  'className' => 'text-nowrap'],
            __('recruit::modules.jobApplication.phone') => ['data' => 'phone', 'name' => 'phone', 'visible' => false, 'title' => __('recruit::modules.jobApplication.phone')],
            __('recruit::modules.jobApplication.email') => ['data' => 'email', 'name' => 'email', 'visible' => false, 'title' => __('recruit::modules.jobApplication.email')],
            __('recruit::modules.front.fullName') => ['data' => 'name', 'visible' => false, 'name' => 'name', 'title' => __('recruit::modules.front.fullName')],
            __('recruit::modules.jobApplication.jobs') => ['data' => 'recruit_job_id', 'exportable' => false, 'name' => 'recruit_jobs.title', 'title' => __('recruit::modules.jobApplication.jobs'), 'width' => '150px', 'className' => 'text-nowrap'],
            __('recruit::app.jobOffer.job') => ['data' => 'job_name', 'visible' => false, 'name' => 'job_name', 'title' => __('recruit::app.jobOffer.job')],
            // __('recruit::modules.job.location') => ['data' => 'location', 'name' => 'company_addresses.location', 'title' => __('recruit::modules.job.location')],
            __('recruit::modules.jobApplication.gender') => ['data' => 'gender', 'name' => 'gender', 'visible' => false, 'title' => __('recruit::modules.jobApplication.gender')],
            __('recruit::app.jobApplication.date') => ['data' => 'created_at', 'name' => 'created_at', 'title' => __('recruit::app.jobApplication.date'), 'width' => '150px', 'className' => 'text-nowrap'],
            // __('app.status') => ['data' => 'status', 'name' => 'status', 'exportable' => false, 'orderable' => false, 'title' => __('app.status')],
            __('recruit::modules.jobApplication.currentCtc') => ['data' => 'current_ctc', 'name' => 'current_ctc', 'visible' => false, 'title' => __('recruit::modules.jobApplication.currentCtc')],
            __('recruit::modules.jobApplication.expectedCtc') => ['data' => 'expected_ctc', 'name' => 'expected_ctc', 'visible' => false, 'title' => __('recruit::modules.jobApplication.expectedCtc')],
            __('recruit::modules.jobApplication.experience') => ['data' => 'total_experience', 'name' => 'total_experience', 'visible' => false, 'title' => __('recruit::modules.jobApplication.experience')],
            __('recruit::modules.sourceSetting.source') => ['data' => 'source', 'name' => 'source', 'visible' => false, 'title' => __('recruit::modules.sourceSetting.source')],
            'Date of Birth' => ['data' => 'date_of_birth', 'name' => 'date_of_birth', 'visible' => false, 'title' => 'Date of Birth'],
            'Age' => ['data' => 'age', 'name' => 'age', 'visible' => false, 'orderable' => false, 'title' => 'Age'],
            'Rank Level' => ['data' => 'management_rank_level', 'name' => 'management_rank_level', 'visible' => false, 'title' => 'Rank Level'],
            'Marital Status' => ['data' => 'marital_status', 'name' => 'marital_status', 'visible' => false, 'title' => 'Marital Status'],
            'Education' => ['data' => 'education', 'name' => 'education', 'visible' => false, 'title' => 'Education'],
            'Certifications & Qualifications' => ['data' => 'certifications_qualifications', 'name' => 'certifications_qualifications', 'visible' => false, 'title' => 'Certifications & Qualifications'],
            'Work Experience Details' => ['data' => 'work_experience_details', 'name' => 'work_experience_details', 'visible' => false, 'title' => 'Work Experience Details'],
            'Last Salary (Minimum)' => ['data' => 'last_salary_minimum', 'name' => 'last_salary_minimum', 'visible' => false, 'title' => 'Last Salary (Minimum)'],
            'Expected Salary (Minimum)' => ['data' => 'expected_salary_minimum', 'name' => 'expected_salary_minimum', 'visible' => false, 'title' => 'Expected Salary (Minimum)'],
            'NRC' => ['data' => 'nrc', 'name' => 'nrc', 'visible' => false, 'title' => 'NRC'],
            'Selection Phase' => [
                'data' => 'selection_phase',
                'name' => 'selection_phase',
                'title' => 'Selection Phase',
                'exportable' => false,
            ],
            'Selection Phase Export' => [
                'data' => 'selection_phase_export',
                'name' => 'selection_phase_export',
                'title' => 'Selection Phase',
                'visible' => false,
                'orderable' => false,
                'searchable' => false,
                'exportable' => true,
            ],
            'Overall Status' => [
                'data' => 'overall_status',
                'name' => 'overall_status',
                'title' => 'Overall Status',
                'exportable' => false,
            ],
            'Overall Status Export' => [
                'data' => 'overall_status_export',
                'name' => 'overall_status_export',
                'title' => 'Overall Status',
                'visible' => false,
                'orderable' => false,
                'searchable' => false,
                'exportable' => true,
            ],
            'Rejection Details' => ['data' => 'rejection_reason_details', 'name' => 'rejection_reason_details', 'visible' => false, 'title' => 'Rejection Details'],
            'Keep CV Reason' => ['data' => 'keep_cv_reason', 'name' => 'keep_cv_reason', 'visible' => false, 'title' => 'Keep CV Reason'],
            'Job Offer Decision' => ['data' => 'job_offer_decision', 'name' => 'job_offer_decision', 'visible' => true, 'title' => 'Job Offer Decision'],
            'Blacklist' => ['data' => 'is_black_list', 'name' => 'is_black_list', 'visible' => true, 'title' => 'Blacklist'],
            'Rejection Reason' => ['data' => 'rejection_reason', 'name' => 'rejection_reason', 'visible' => false, 'title' => 'Rejection Reason'],
            'Blacklist Reason' => ['data' => 'blacklist_reason', 'name' => 'blacklist_reason', 'visible' => false, 'title' => 'Blacklist Reason'],
            'Current Location' => ['data' => 'current_location', 'name' => 'current_location', 'visible' => false, 'title' => 'Current Location'],
            'Notice Period' => ['data' => 'notice_period', 'name' => 'notice_period', 'visible' => false, 'title' => 'Notice Period'],
            'Cover Letter' => ['data' => 'cover_letter', 'name' => 'cover_letter', 'visible' => false, 'title' => 'Cover Letter'],
            'Existing Recruit Status' => ['data' => 'legacy_status_name', 'name' => 'legacy_status_name', 'visible' => false, 'orderable' => false, 'title' => 'Existing Recruit Status'],
            'Employee User ID' => ['data' => 'employee_user_id', 'name' => 'employee_user_id', 'visible' => false, 'title' => 'Employee User ID'],


            Column::computed('action', __('app.action'))
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->width(200)
                ->addClass('text-right pr-20')
        ];
    }
}
