@include('import.process-form', [
    'headingTitle' => __('app.importExcel') . ' ' . __('app.menu.shifts'),
    'processRoute' => route('shifts.import.process'),
    'backRoute' => route('shifts.index'),
    'backButtonText' => __('app.backToEmployeeShift'),
])
