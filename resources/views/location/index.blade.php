@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
    <style>
        .filter-box {
            z-index: 2;
        }
    </style>
@endpush


@section('content')
    <!-- CONTENT WRAPPER START -->
    <div class="content-wrapper">

        <div class="d-grid d-lg-flex d-md-flex action-bar">
            <div id="table-actions" class="flex-grow-1 align-items-center">
                <x-forms.link-primary :link="route('location.create')" class="mr-3 float-left"
                                      icon="plus">
                    @lang('app.menu.location')
                </x-forms.link-primary>
            </div>
        </div>

        <!-- leave table Box Start -->
        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">

            {!! $dataTable->table(['class' => 'table table-hover border-0 w-100']) !!}

        </div>
        <!-- leave table End -->

    </div>
    <!-- CONTENT WRAPPER END -->

@endsection

@push('scripts')
    @include('sections.datatable_js')

    <script>
        $('#departments-table').on('preXhr.dt', function (e, settings, data) {
            const dateRangePicker = $('#datatableRange').data('daterangepicker');

            const parentId = $('#parent_id').val();
            const childId = $('#child').val();
            const searchText = $('#search-text-field').val();

            data['searchText'] = searchText;
            data['parentId'] = parentId;
            data['childId'] = childId;
        });

        const showTable = () => {
            window.LaravelDataTables["departments-table"].draw(true);
        }

        $('#parent_id, #child').on('change keyup',
            function () {
                if ($('#parent_id').val() != "all") {
                    $('#reset-filters').removeClass('d-none');

                } else if ($('#child').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                } else {
                    $('#reset-filters').addClass('d-none');
                }

                showTable();
            });

        $('#search-text-field').on('keyup', function () {
            if ($('#search-text-field').val() != "") {
                $('#reset-filters').removeClass('d-none');
                showTable();
            }
        });

        $('#reset-filters').click(function () {
            $('#filter-form')[0].reset();
            $('.filter-box #status').val('not finished');
            $('.filter-box .select-picker').selectpicker("refresh");
            $('#reset-filters').addClass('d-none');
            showTable();
        });

        $('#reset-filters-2').click(function () {
            $('#filter-form')[0].reset();

            $('.filter-box #parent_id').val('all');
            $('.filter-box #child').val('all');
            $('.filter-box .select-picker').selectpicker("refresh");
            $('#reset-filters').addClass('d-none');
            showTable();
        });

        $('body').on('click', '.delete-table-row', function () {
            var id = $(this).data('department-id');

            console.log(id);
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
                    var url = "{{ route('location.delete', ':id') }}";
                    url = url.replace(':id', id);

                    console.log(url);

                    var token = "{{ csrf_token() }}";

                    $.easyAjax({
                        type: 'POST',
                        url: url,
                        blockUI: true,
                        data: {
                            '_token': token,
                            '_method': 'DELETE'
                        },
                        success: function (response) {
                            console.log(response.redirectUrl);
                            window.location.href = response.redirectUrl;
                            // if (response.message == "success") {
                            //     // showTable();
                            // }
                        }
                    });
                }
            });
        });

        $('#quick-action-type').change(function () {
            const actionValue = $(this).val();

            if (actionValue != '') {
                $('#quick-action-apply').removeAttr('disabled');
            } else {
                $('#quick-action-apply').attr('disabled', true);
                $('.quick-action-field').addClass('d-none');
            }
        });

        $('#quick-action-apply').click(function () {
            const actionValue = $('#quick-action-type').val();
            if (actionValue === 'delete') {
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
                        applyQuickAction();
                    }
                });

            } else {
                applyQuickAction();
            }
        });

        const applyQuickAction = () => {
            const rowdIds = $("#departments-table input:checkbox:checked").map(function () {
                return $(this).val();
            }).get();


            const url = "{{ route('departments.apply_quick_action') }}?row_ids=" + rowdIds;

            $.easyAjax({
                url: url,
                container: '#quick-action-form',
                type: "POST",
                disableButton: true,
                buttonSelector: "#quick-action-apply",
                data: $('#quick-action-form').serialize(),
                success: function (response) {
                    if (response.status === 'success') {
                        showTable();
                        resetActionButtons();
                        deSelectAll();
                        $('#quick-action-form').hide();
                    }
                }
            })
        };

    </script>
@endpush
