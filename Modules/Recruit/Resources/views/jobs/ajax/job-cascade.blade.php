<script>
    // Location → Department
    $(document)
        .off('change.hrJob', '#hr_location_id')
        .on('change.hrJob', '#hr_location_id', function() {

            const locationId = $(this).val();
            const departmentDropdown = $('#employee_department');

            departmentDropdown
                .html('<option value="">--</option>')
                .selectpicker('refresh');

            $('#designation_id')
                .html('<option value="">--</option>')
                .selectpicker('refresh');

            $('#rank_level')
                .html('<option value="">--</option>')
                .selectpicker('refresh');

            $('#management_rank_id')
                .html('<option value="">--</option>')
                .selectpicker('refresh');

            if (!locationId) {
                return;
            }

            const url =
                "{{ route('jobs.departments_by_location', ':id') }}"
                .replace(':id', locationId);

            $.get(url, function(response) {
                response.data.forEach(function(row) {
                    departmentDropdown.append(
                        new Option(row.team_name, row.id)
                    );
                });

                departmentDropdown.selectpicker('refresh');
            });
        });


    // Department → Designation
    $(document)
        .off('change.hrJob', '#employee_department')
        .on('change.hrJob', '#employee_department', function() {

            const departmentId = $(this).val();
            const designationDropdown = $('#designation_id');

            designationDropdown
                .html('<option value="">--</option>')
                .selectpicker('refresh');

            $('#rank_level')
                .html('<option value="">--</option>')
                .selectpicker('refresh');

            if (!departmentId) {
                return;
            }

            const url =
                "{{ route('jobs.designations_by_department', ':id') }}"
                .replace(':id', departmentId);

            $.get(url, function(response) {
                response.data.forEach(function(row) {
                    designationDropdown.append(
                        new Option(row.name, row.id)
                    );
                });

                designationDropdown.selectpicker('refresh');
            });
        });


    // Designation → Rank
    $(document)
        .off('change.hrJob', '#designation_id')
        .on('change.hrJob', '#designation_id', function() {

            const designationId = $(this).val();
            const rankDropdown = $('#rank_level');
            const managementRankDropdown = $('#management_rank_id');

            rankDropdown
                .html('<option value="">--</option>')
                .selectpicker('refresh');

            managementRankDropdown
                .html('<option value="">--</option>')
                .selectpicker('refresh');

            if (!designationId) {
                return;
            }

            const url =
                "{{ route('jobs.rank_by_designation', ':id') }}"
                .replace(':id', designationId);

            $.get(url, function(response) {
                if (
                    response.rank !== null &&
                    response.rank !== undefined &&
                    response.rank !== ''
                ) {
                    rankDropdown.append(
                        new Option(
                            'Rank ' + response.rank,
                            response.rank,
                            true,
                            true
                        )
                    );
                }

                rankDropdown.selectpicker('refresh');

                if (response.management_rank) {
                    managementRankDropdown.append(
                        new Option(
                            response.management_rank.name,
                            response.management_rank.id,
                            true,
                            true
                        )
                    );
                }

                managementRankDropdown.selectpicker('refresh');
            });
        });

    $(document)
        .off('change.jobVacancy', '#designation_id')
        .on(
            'change.jobVacancy',
            '#designation_id',
            loadAvailableVacancy
        );



    function loadAvailableVacancy() {
        const locationId = $('#hr_location_id').val();
        const departmentId = $('#employee_department').val();
        const designationId = $('#designation_id').val();

        console.log('hi');

        $('#vacancy_count').val('');

        if (
            !locationId ||
            !departmentId ||
            !designationId
        ) {
            return;
        }

        $.get(
                "{{ route('jobs.available_vacancy') }}", {
                    location_id: locationId,
                    department_id: departmentId,
                    designation_id: designationId
                }
            )
            .done(function(response) {
                $('#vacancy_count').val(response.vacancy);
            })
            .fail(function(xhr) {
                $('#vacancy_count').val(0);

                Swal.fire({
                    icon: 'warning',
                    title: 'Manpower Plan Not Found',
                    text: xhr.responseJSON?.message ??
                        'No approved manpower plan was found.'
                });
            });
    }
</script>
