@include('payroll::import.process-form', [
    'headingTitle' => __('app.importExcel') . ' ' . __('app.menu.salary'),
    'processRoute' => route('employee-salary.import.process'),
    'backRoute' => route('employee-salary.index'),
    'backButtonText' => __('app.backToEmployeeSalary'),
])
