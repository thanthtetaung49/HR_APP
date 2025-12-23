<div id="managementRanks-section">
    <div class="row">
        <div class="col-sm-12">
            <div class="card bg-white border-0 b-shadow-4">
                <div class="card-header bg-white  border-bottom-grey  justify-content-between p-20">
                    <div class="row">
                        <div class="col-md-10 col-10">
                            <h3 class="heading-h1">@lang('app.managementRankDetials')</h3>
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
                                        href="{{ route('management-ranks.edit', $managementRank->id) }}">@lang('app.edit')</a>
                                    <a class="dropdown-item delete-managementRank">@lang('app.delete')</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <x-cards.data-row :label="__('app.menu.managementRanks')" :value="$managementRank->name" html="true" />
                        @php
                            $rank = json_decode($managementRank->rank);
                            $rank = array_map(fn ($r) => "Rank " . $r, $rank);
                            $rank = join(", ", $rank);
                        @endphp
                        <x-cards.data-row :label="__('app.menu.rank')" :value="$rank" html="true" />
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $('body').on('click', '.delete-managementRank', function() {
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
                var url = "{{ route('management-ranks.destroy', $managementRank->id) }}";

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
