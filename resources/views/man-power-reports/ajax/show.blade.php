<div id="manpower-section">
    <div class="row">
        <div class="col-sm-12">
            <div class="card bg-white border-0 b-shadow-4">
                <div class="card-header bg-white  border-bottom-grey  justify-content-between p-20">
                    <div class="row">
                        <div class="col-md-10 col-10">
                            <h3 class="heading-h1">@lang('app.manPowerDetails')</h3>
                        </div>
                        <div class="col-md-2 col-2 text-right">
                            <div class="dropdown">
                                <button class="btn btn-lg f-14 px-2 py-1 text-dark-grey  rounded  dropdown-toggle"
                                    type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fa fa-ellipsis-h"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right border-grey rounded b-shadow-4 p-0"
                                    aria-labelledby="dropdownMenuLink" tabindex="0">
                                    <a class="dropdown-item" data-redirect-url="{{ url()->previous() }}"
                                        href="{{ route('man-power-reports.edit', $reports->id) }}">@lang('app.edit')</a>
                                    <a class="dropdown-item delete-manpower">@lang('app.delete')</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <x-cards.data-row :label="__('app.menu.budgetYear')" :value="$reports->budget_year" html="true" />

                    @if ($reports->quarter == 1)
                        <x-cards.data-row :label="__('app.menu.quarter')" :value="'Q1 (Jan - Dec)'" html="true" />
                    @elseif ($reports->quarter == 2)
                        <x-cards.data-row :label="__('app.menu.quarter')" :value="'Q2 (Apr - Dec)'" html="true" />
                    @elseif ($reports->quarter == 3)
                        <x-cards.data-row :label="__('app.menu.quarter')" :value="'Q3 (Jul - Dec)'" html="true" />
                    @else
                        <x-cards.data-row :label="__('app.menu.quarter')" :value="'Q4 (Oct - Dec)'" html="true" />
                    @endif

                    <x-cards.data-row :label="__('app.menu.manPowerSetup')" :value="$reports->man_power_setup" html="true" />
                    <x-cards.data-row :label="__('app.menu.maxBasicSalary')" :value="$reports->man_power_basic_salary" html="true" />
                    <x-cards.data-row :label="__('app.menu.teams')" :value="$reports->teams->team_name" html="true" />
                    <x-cards.data-row :label="__('app.menu.designation')" :value="$reports->designation->name" html="true" />
                    <x-cards.data-row :label="__('app.menu.status')" :value="$reports->status" html="true" />
                    <x-cards.data-row :label="__('app.menu.remark')" :value="$reports->remarks ? $reports->remarks : '-----'" html="true" />



                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $('body').on('click', '.delete-manpower', function() {
        Swal.fire({
            title: "@lang('messages.sweetAlertTitle')",
            text: "@lang('messages.recoverRecord')",
            icon: 'warning',
            showCancelButton: true,
            focusConfirm: false,
            confirmButtonText: "@lang('messages.confirmDelete')",
            cancelButtonText: "@lang('app.cancel')",
            customClass: {
                confirmButton: 'btn btn-primary mr-3',
                cancelButton: 'btn btn-secondary'
            },
            showClass: {
                popup: 'swal2-noanimation',
                backdrop: 'swal2-noanimation'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                var url = "{{ route('man-power-reports.destroy', $reports->id) }}";

                var token = "{{ csrf_token() }}";

                $.easyAjax({
                    type: 'POST',
                    url: url,
                    data: {
                        '_token': token,
                        '_method': 'DELETE'
                    },
                    success: function(response) {
                        window.location.href = response.redirectUrl;
                        // if (response.status == "success") {
                        // }
                    }
                });
            }
        });
    });
</script>
