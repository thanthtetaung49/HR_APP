var url = "{{ route('attendances.check_half_day') }}";

$.easyAjax({
                    url: url,
                    type: "POST",
                    container: '#attendance-container',
                    blockUI: true,
                    disableButton: true,
                    buttonSelector: "#save-attendance",
                    data: $('#attendance-container').serialize(),
                    success: function (response) {
                        url = "{{route('attendances.store')}}";
                        if (response.halfDayExist == true && response.requestedHalfDay == 'no' && response.halfDayDurEnd == 'no') {
                            Swal.fire({
                                title: "@lang('messages.sweetAlertTitle')",
                                text: "@lang('messages.halfDayAlreadyApplied')",
                                icon: 'warning',
                                showCancelButton: true,
                                focusConfirm: false,
                                confirmButtonText: "@lang('messages.rejectIt')",
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
                                    saveAttendanceForm(url);
                                }
                            });

                        } else if (response.fullDayExist == true && response.requestedFullDay == 'no') {
                            Swal.fire({
                                title: "@lang('messages.sweetAlertTitle')",
                                text: "@lang('messages.fullDayAlreadyApplied')",
                                icon: 'warning',
                                showCancelButton: true,
                                focusConfirm: false,
                                confirmButtonText: "@lang('messages.rejectIt')",
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
                                    saveAttendanceForm();
                                }
                            });
                        }else {
                            saveAttendanceForm(url);
                        }
                    }
                });
