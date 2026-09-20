<div class="modal-header">
    <h5 class="modal-title" id="modelHeading">@lang('app.view') @lang('payroll::app.menu.overtimeRequest')</h5>
    <button type="button" onclick="removeOpenModal()" class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">×</span></button>
</div>
@if ($overtimeRequest->manager_status_permission === 'pre-approve')
    <div class="alert alert-success mt-3 mb-0">
        <i class="fa fa-check-circle mr-1"></i>
        @lang('payroll::messages.overtimePreApproved')
    </div>
@endif
<div class="modal-body">

    <p>
        <x-employee :user="$employee" />
    </p>

    <div class="col-12 px-0 pb-3 d-lg-flex">
        <p class="mb-0 text-lightest f-14 w-30 d-inline-block text-capitalize">
            @lang('app.startDate') </p>
        <p class="mb-0 text-dark-grey f-14">
            {{ $overtimeRequest->start_date->format(company()->date_format) }}
        </p>

        <p class="mb-0 text-lightest f-14 w-30 ml-3 d-inline-block text-capitalize">
            @lang('app.endDate') </p>
        <p class="mb-0 text-dark-grey f-14">
            {{ $overtimeRequest->end_date->format(company()->date_format) }}
        </p>

    </div>


    <div class="table-responsive">
        <x-table class="table-bordered" headType="thead-light">
            <x-slot name="thead">
                <th>#</th>
                <th>@lang('app.date')</th>
                <th>@lang('payroll::modules.payroll.overtimeHours')</th>
                <th>@lang('payroll::modules.payroll.clockedInHours')</th>
                <th>@lang('app.amount')</th>
            </x-slot>
            @php
                $payCode = $overtimeRequest->policy->payCode;
                $clockedHour = 0;
            @endphp

        </x-table>
    </div>

</div>
<div class="modal-footer">
    <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.close')</x-forms.button-cancel>
    @if (
        $overtimeRequest->status == 'pending' &&
            (user()->hasRole('admin') || in_array($roleId, $allowRoles) || $reportingTo == user()->id))
        <x-forms.button-secondary id="reject" data-type="reject" class="react-button"
            icon="times">@lang('app.reject')</x-forms.button-secondary>
        <x-forms.button-primary id="acceptButton" data-type="accept" class="react-button"
            icon="check">@lang('app.accept')</x-forms.button-primary>
    @endif
</div>
@php
    $isAdminOrHr = user()->hasRole('admin') || user()->hasRole('hr-officer') || user()->hasRole('hr-manager');

    $isAllowedRole = in_array($roleId, $allowRoles ?? []);

    $isFinalApprover = $isAdminOrHr || $isAllowedRole;

    $isReportingManager =
        (int) $reportingTo === (int) user()->id && (int) $overtimeRequest->user_id !== (int) user()->id;

    $alreadyPreApproved = $overtimeRequest->manager_status_permission === 'pre-approve';
@endphp

<div class="modal-footer">
    <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">
        @lang('app.close')
    </x-forms.button-cancel>

    @if ($overtimeRequest->status === 'pending')
        {{-- Final HR/Admin or policy-role action --}}
        @if ($isFinalApprover)
            <x-forms.button-secondary id="reject" data-type="reject" class="react-button" icon="times">
                @lang('app.reject')
            </x-forms.button-secondary>

            <x-forms.button-primary id="acceptButton" data-type="accept" class="react-button" icon="check">
                @lang('app.accept')
            </x-forms.button-primary>

            {{-- Reporting manager action --}}
        @elseif ($isReportingManager && !$alreadyPreApproved)
            @if (in_array($managerPermission, ['approved', 'pre-approve'], true))
                <x-forms.button-secondary id="reject" data-type="reject" class="react-button" icon="times">
                    @lang('app.reject')
                </x-forms.button-secondary>
            @endif

            @if ($managerPermission === 'approved')
                <x-forms.button-primary id="acceptButton" data-type="accept" class="react-button" icon="check">
                    @lang('app.accept')
                </x-forms.button-primary>
            @elseif ($managerPermission === 'pre-approve')
                <x-forms.button-primary id="preApproveButton" type="button" icon="check">
                    @lang('app.preApprove')
                </x-forms.button-primary>
            @endif
        @endif
    @endif
</div>
<script>
    $(MODAL_LG).on('click', '.react-button', function() {
        var id = {{ $overtimeRequest->id }};
        var type = $(this).data('type');
        var butonText = "@lang('payroll::messages.confirmAccept')";
        if (type != 'accept') {
            butonText = "@lang('payroll::messages.confirmReject')";
        }
        Swal.fire({
            title: "@lang('messages.sweetAlertTitle')",
            text: "@lang('payroll::messages.recoverRecord')",
            icon: 'warning',
            showCancelButton: true,
            focusConfirm: false,
            confirmButtonText: butonText,
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

                var url = "{{ route('overtime-request-accept', ':id') }}" ;
                url = url.replace(':id', id);

                $.easyAjax({
                    type: 'POST',
                    url: url,
                    blockUI: true,
                    success: function(response) {
                        if (response.status == "success") {
                            showTable();
                            $(MODAL_LG).modal('hide');
                        }
                    }
                });
            }
        });
    });
    /* PAYROLL SALARY SCRIPTS */


    $(MODAL_LG).off('click.overtimePreApprove','#preApproveButton')
        .on('click.overtimePreApprove','#preApproveButton',
            function() {
                const requestId = {{ $overtimeRequest->id }};

                Swal.fire({
                    title: "@lang('messages.sweetAlertTitle')",
                    text: "{{ __('payroll::messages.confirmPreApproveOvertime') }}",
                    icon: 'warning',
                    showCancelButton: true,
                    focusConfirm: false,
                    confirmButtonText: "{{ __('payroll::messages.preApproveOvertime') }}"
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
                }).then(function(result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    let url = "{{ route('overtime-request-pre-approve', ':id') }}";

                    url = url.replace(':id',requestId);

                    $.easyAjax({
                        type: 'POST',
                        url: url,
                        blockUI: true,
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            if ( response.status !== 'success'
                            ) {
                                return;
                            }

                            $(MODAL_LG).modal('hide');

                            showTable();
                        }
                    });
                });
            }
        );
</script>
