<div id="criteria-section">
    <div class="row">
        <div class="col-sm-12">
            <div class="card bg-white border-0 b-shadow-4">
                <div class="card-header bg-white  border-bottom-grey  justify-content-between p-20">
                    <div class="row">
                        <div class="col-md-10 col-10">
                            <h3 class="heading-h1">@lang('app.criteriaDetails')</h3>
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
                                        href="{{ route('criteria.edit', $criteria->id) }}">@lang('app.edit')</a>
                                    <a class="dropdown-item delete-criteria">@lang('app.delete')</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @php
                        $employee = new App\Models\EmployeeDetails();
                        $getCustomFieldGroupsWithFields = $employee->getCustomFieldGroupsWithFields();

                        if ($getCustomFieldGroupsWithFields) {
                            $fields = $getCustomFieldGroupsWithFields->fields;
                        }

                        if (isset($fields) && count($fields) > 0) {
                            foreach ($fields as $field) {
                                if ($field->type == 'select' && $field->name == 'exit-reasons-1') {
                                    $options = $field->values;
                                    $exitReason = $options[$criteria->exit_reason_id] ?? $criteria->exit_reason_id;
                                }
                            }
                        }
                    @endphp

                    <x-cards.data-row :label="__('app.menu.exitsReason')" :value="$exitReason" html="true" />
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $('body').on('click', '.delete-criteria', function() {
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
                var url = "{{ route('criteria.destroy', $criteria->id) }}";

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
