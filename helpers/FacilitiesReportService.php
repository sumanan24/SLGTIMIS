<?php
/**
 * Facilities reports and CSV export.
 */
class FacilitiesReportService {
    private $model;

    public function __construct() {
        require_once BASE_PATH . '/models/FacilitiesTicketModel.php';
        $this->model = new FacilitiesTicketModel();
    }

    public function rows(array $filters): array {
        return $this->model->listForExport($filters);
    }

    public function summary(array $filters): array {
        return [
            'counts' => $this->model->dashboardCounts($filters),
            'status' => $this->model->statusBreakdown($filters),
            'priority' => $this->model->priorityBreakdown($filters),
            'departments' => $this->model->departmentPerformance(),
            'workload' => $this->model->staffWorkload($filters['responsible_department_id'] ?? null),
            'monthly' => $this->model->monthlyTrend(8),
            'avg_hours' => $this->model->averageResolutionHours(),
        ];
    }

    public function toCsv(array $rows): string {
        $fh = fopen('php://temp', 'w+');
        fputcsv($fh, ['Ticket', 'Date', 'Requester', 'Department', 'Category', 'Title', 'Location', 'Priority', 'Status', 'Assigned', 'Deadline', 'Progress', 'Overdue']);
        foreach ($rows as $row) {
            fputcsv($fh, [
                $row['ticket_number'] ?? '',
                $row['created_at'] ?? '',
                $row['requester_name'] ?? $row['requester_staff_id'] ?? '',
                $row['requester_department_name'] ?? '',
                $row['category_name'] ?? '',
                $row['title'] ?? '',
                $row['location'] ?? '',
                $row['current_priority'] ?? '',
                $row['status'] ?? '',
                $row['responsible_name'] ?? $row['responsible_staff_id'] ?? '',
                $row['deadline'] ?? '',
                $row['progress_percent'] ?? 0,
                !empty($row['is_overdue']) ? 'Yes' : 'No',
            ]);
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        return $csv;
    }
}
