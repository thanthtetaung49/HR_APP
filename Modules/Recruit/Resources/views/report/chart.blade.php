@php
    $chartTotal = array_sum($chart['chart_data']);
@endphp

<style>
    .recruit-report-chart {
        position: relative;
        width: 420px;
        max-width: 100%;
        height: 300px;
        margin: 0 auto;
        padding: 12px;
        overflow: hidden;
    }

    .recruit-report-chart canvas {
        display: block;
        width: 100% !important;
        height: 100% !important;
    }

    @media (max-width: 575.98px) {
        .recruit-report-chart {
            width: 100%;
            height: 260px;
        }
    }
</style>

@if ($chartTotal > 0)
    <div class="recruit-report-chart">
        <canvas id="recruitment-report-chart"></canvas>
    </div>

    <script>
        (function() {
            const canvas = document.getElementById('recruitment-report-chart');

            if (!canvas) {
                return;
            }

            if (window.recruitmentReportChart instanceof Chart) {
                window.recruitmentReportChart.destroy();
            }

            window.recruitmentReportChart = new Chart(canvas.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: @json($chart['labels']),
                    datasets: [{
                        data: @json($chart['chart_data']),
                        backgroundColor: @json($chart['colors']),
                        borderColor: '#ffffff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: 4
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom'
                        },
                        title: {
                            display: false
                        }
                    }
                }
            });
        })();
    </script>
@else
    <div class="text-center text-lightest p-20" style="height: 250px">
        <i class="side-icon f-21 bi bi-pie-chart"></i>
        <div class="f-15 mt-4">- @lang('messages.notEnoughData') -</div>
    </div>
@endif
