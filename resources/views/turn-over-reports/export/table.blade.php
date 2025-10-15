 <table class="table table-bordered">
     <thead>
         <tr>
             <th colspan="6">
                 <h3>Turn Over Report
                     <span id="locationTitle">({{ $locationName }})</span>
                 </h3>
             </th>
         </tr>
         <tr>
             <th rowspan="2" class="align-middle">Description</th>
             @foreach ($months as $key => $month)
                 <th colspan="3">{{ $month }} - @php echo now()->year % 100; @endphp</th>
             @endforeach
         </tr>
         <tr>
             @foreach ($months as $key => $month)
                 <th>Operation</th>
                 <th>Supporting</th>
                 <th>Total</th>
             @endforeach
         </tr>
     </thead>
     <tbody>

         <tr>
             <td>Total MP</td>
             @foreach ($months as $key => $month)
                 @php
                     $operation = 0;
                     $supporting = 0;
                 @endphp

                 @foreach ($employeeTotal as $item)
                     @if ($item->month == $key)
                         @php
                             $operation = $item->operation_employee_count;
                             $supporting = $item->supporting_employee_count;
                         @endphp
                     @endif
                 @endforeach

                 <td>{{ $operation }}</td> <!-- Operation -->
                 <td>{{ $supporting }}</td> <!-- Supporting -->
                 <td>{{ $operation + $supporting }}</td> <!-- Total -->
             @endforeach
         </tr>
         <tr>
             <td>Resign</td>
             @foreach ($months as $key => $month)
                 @php
                     $operation = 0;
                     $supporting = 0;
                     $total = 0;
                 @endphp

                 @foreach ($turnOverReports as $item)
                     @if ($item->month == $key)
                         @if ($item->department_type == 'operation')
                             @php $operation = $item->resigned_total; @endphp
                         @endif

                         @if ($item->department_type == 'supporting')
                             @php $supporting = $item->resigned_total; @endphp
                         @endif
                     @endif
                 @endforeach

                 @php $total = $operation + $supporting; @endphp

                 <td>{{ $operation }}</td> <!-- Operation -->
                 <td>{{ $supporting }}</td> <!-- Supporting -->
                 <td>{{ $total }}</td> <!-- Total -->
             @endforeach
         </tr>

         {{-- Turnover % --}}
         <tr>
             <td>Turnover %</td>
             @foreach ($months as $key => $month)
                 @php
                     $operation_total = 0;
                     $supporting_total = 0;

                     foreach ($employeeTotal as $item) {
                         if ($item->month == $key) {
                             $operation_total = $item->operation_employee_count;
                             $supporting_total = $item->supporting_employee_count;
                         }
                     }

                     $total_total = $operation_total + $supporting_total;

                     // Get resign counts
                     $operation_resign = 0;
                     $supporting_resign = 0;

                     foreach ($turnOverReports as $item) {
                         if ($item->month == $key) {
                             if ($item->department_type == 'operation') {
                                 $operation_resign = $item->resigned_total;
                             }
                             if ($item->department_type == 'supporting') {
                                 $supporting_resign = $item->resigned_total;
                             }
                         }
                     }

                     $total_resign = $operation_resign + $supporting_resign;

                     // Percentages
                     $operation_pct = $operation_total > 0 ? round(($operation_resign / $operation_total) * 100, 0) : 0;
                     $supporting_pct =
                         $supporting_total > 0 ? round(($supporting_resign / $supporting_total) * 100, 0) : 0;
                     $total_pct = $total_total > 0 ? round(($total_resign / $total_total) * 100, 0) : 0;

                 @endphp

                 <td>{{ $operation_pct }}%</td>
                 <td>{{ $supporting_pct }}%</td>
                 <td class="{{ $total_pct > 10 ? 'text-danger fw-bold' : '' }}">
                     {{ $total_pct }}%
                 </td>
             @endforeach
         </tr>

         <tr>
             <td>Probation</td>
             @foreach ($months as $key => $month)
                 @php
                     $operation = 0;
                     $supporting = 0;
                     $total = 0;
                 @endphp

                 @foreach ($turnOverReports as $item)
                     @if ($item->month == $key)
                         @if ($item->department_type == 'operation')
                             @php $operation = $item->probation_total; @endphp
                         @endif

                         @if ($item->department_type == 'supporting')
                             @php $supporting = $item->probation_total; @endphp
                         @endif
                     @endif
                 @endforeach

                 @php $total = $operation + $supporting; @endphp

                 <td>{{ $operation }}</td> <!-- Operation -->
                 <td>{{ $supporting }}</td> <!-- Supporting -->
                 <td>{{ $total }}</td> <!-- Total -->
             @endforeach
         </tr>

         {{-- Turnover % --}}
         <tr>
             <td>Turnover %</td>
             @foreach ($months as $key => $month)
                 @php
                     $operation_total = 0;
                     $supporting_total = 0;

                     foreach ($employeeTotal as $item) {
                         if ($item->month == $key) {
                             $operation_total = $item->operation_employee_count;
                             $supporting_total = $item->supporting_employee_count;
                         }
                     }

                     $total_total = $operation_total + $supporting_total;

                     // Get resign counts
                     $operation_probation = 0;
                     $supporting_probation = 0;

                     foreach ($turnOverReports as $item) {
                         if ($item->month == $key) {
                             if ($item->department_type == 'operation') {
                                 $operation_probation = $item->probation_total;
                             }
                             if ($item->department_type == 'supporting') {
                                 $supporting_probation = $item->probation_total;
                             }
                         }
                     }

                     $total_probation = $operation_probation + $supporting_probation;

                     // Percentages
                     $operation_pct =
                         $operation_total > 0 ? round(($operation_probation / $operation_total) * 100, 0) : 0;
                     $supporting_pct =
                         $supporting_total > 0 ? round(($supporting_probation / $supporting_total) * 100, 0) : 0;
                     $total_pct = $total_total > 0 ? round(($total_probation / $total_total) * 100, 0) : 0;

                 @endphp

                 <td>{{ $operation_pct }}%</td>
                 <td>{{ $supporting_pct }}%</td>
                 <td class="{{ $total_pct > 10 ? 'text-danger fw-bold' : '' }}">
                     {{ $total_pct }}%
                 </td>
             @endforeach
         </tr>

         <tr>
             <td>Permanent</td>
             @foreach ($months as $key => $month)
                 @php
                     $operation = 0;
                     $supporting = 0;
                     $total = 0;
                 @endphp

                 @foreach ($turnOverReports as $item)
                     @if ($item->month == $key)
                         @if ($item->department_type == 'operation')
                             @php $operation = $item->permanent_total; @endphp
                         @endif

                         @if ($item->department_type == 'supporting')
                             @php $supporting = $item->permanent_total; @endphp
                         @endif
                     @endif
                 @endforeach

                 @php $total = $operation + $supporting; @endphp

                 <td>{{ $operation }}</td> <!-- Operation -->
                 <td>{{ $supporting }}</td> <!-- Supporting -->
                 <td>{{ $total }}</td> <!-- Total -->
             @endforeach
         </tr>

         {{-- Turnover % --}}
         <tr>
             <td>Turnover %</td>
             @foreach ($months as $key => $month)
                 @php
                     $operation_total = 0;
                     $supporting_total = 0;

                     foreach ($employeeTotal as $item) {
                         if ($item->month == $key) {
                             $operation_total = $item->operation_employee_count;
                             $supporting_total = $item->supporting_employee_count;
                         }
                     }

                     $total_total = $operation_total + $supporting_total;

                     // Get resign counts
                     $operation_permanent = 0;
                     $supporting_permanent = 0;

                     foreach ($turnOverReports as $item) {
                         if ($item->month == $key) {
                             if ($item->department_type == 'operation') {
                                 $operation_permanent = $item->permanent_total;
                             }
                             if ($item->department_type == 'supporting') {
                                 $supporting_permanent = $item->permanent_total;
                             }
                         }
                     }

                     $total_permanent = $operation_permanent + $supporting_permanent;

                     // Percentages
                     $operation_pct =
                         $operation_total > 0 ? round(($operation_permanent / $operation_total) * 100, 0) : 0;
                     $supporting_pct =
                         $supporting_total > 0 ? round(($supporting_permanent / $supporting_total) * 100, 0) : 0;
                     $total_pct = $total_total > 0 ? round(($total_permanent / $total_total) * 100, 0) : 0;

                 @endphp

                 <td>{{ $operation_pct }}%</td>
                 <td>{{ $supporting_pct }}%</td>
                 <td class="{{ $total_pct > 10 ? 'text-danger fw-bold' : '' }}">
                     {{ $total_pct }}%
                 </td>
             @endforeach
         </tr>
     </tbody>
 </table>
