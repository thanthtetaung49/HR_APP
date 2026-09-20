<!-- LEAVE GENRAL SETTING START -->
<div class="col-lg-12 col-md-12 ntfcn-tab-content-left w-100 p-4">
    <div class="d-block d-lg-flex d-md-flex">
        <x-alert type="info">@lang('modules.leaves.leaveSettingNote')</x-alert>
    </div>

    <div class="d-block d-lg-flex d-md-flex">
        <p> @lang('modules.leaves.reportingManager') </p>
        <div class="col-lg-4">

            <select name="permission" class="form-control select-picker manager-permission"
                    onchange="changeStatus(this.value)">
                <option
                    @if ($overtimeSetting->manager_permission == 'pre-approve') @endif value="pre-approve">@lang('modules.leaves.preApprove')</option>
                <option @if ($overtimeSetting->manager_permission == 'approved') selected
                        @endif value="approved">@lang('modules.leaves.approve')</option>
                <option @if ($overtimeSetting->manager_permission == 'cannot-approve') selected
                        @endif value="cannot-approve">@lang('modules.leaves.canNotApprove')</option>
            </select>
        </div>
        <p> Overtime </p>
    </div>
</div>

</div>
<!-- LEAVE GENRAL SETTING ENDS -->

<script>

    function changeStatus(value) {

        var url = "{{ route('payroll.overtime_settings.change_permission') }}";
        var token = "{{ csrf_token() }}";
        var id = {{$overtimeSetting->id}};

        $.easyAjax({
            type: 'POST',
            url: url,
            data: {
                '_token': token,
                'value': value,
                'id': id,
            },
        });
    }
</script>
