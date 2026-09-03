<?php
/**
 * Academic year CRUD (Management).
 */

class AcademicYearController extends Controller {

    private function canManage() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        return ($userRole === 'ADM') || $userModel->isAdmin($_SESSION['user_id']);
    }

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        if (!$this->checkNotSAO()) {
            return;
        }

        $model = $this->model('AcademicYearModel');
        $years = $model->getAll();

        $data = [
            'title' => 'Academic Years',
            'page' => 'academic-years',
            'years' => $years,
            'isADM' => $this->canManage(),
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null,
        ];
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('academic-years/index', $data);
    }

    public function create() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        if (!$this->checkAdminOrADM()) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $payload = $this->readForm(true);
            if (!empty($payload['errors'])) {
                $_SESSION['error'] = implode(' ', $payload['errors']);
                $this->redirect('academic-years/create');
                return;
            }

            $model = $this->model('AcademicYearModel');
            if ($model->exists($payload['data']['academic_year'])) {
                $_SESSION['error'] = 'That academic year already exists.';
                $this->redirect('academic-years/create');
                return;
            }

            $sqlError = null;
            $result = $model->createYear($payload['data'], $sqlError);
            if ($result !== false) {
                $this->logActivity(
                    'CREATE',
                    'academic',
                    $payload['data']['academic_year'],
                    'Academic year created: ' . $payload['data']['academic_year'],
                    null,
                    $payload['data']
                );
                $_SESSION['message'] = 'Academic year created successfully.';
                $this->redirect('academic-years');
                return;
            }

            $_SESSION['error'] = $sqlError
                ? ('Failed to create academic year. ' . $sqlError)
                : 'Failed to create academic year.';
            $this->redirect('academic-years/create');
            return;
        }

        $data = [
            'title' => 'Create Academic Year',
            'page' => 'academic-years',
            'error' => $_SESSION['error'] ?? null,
        ];
        unset($_SESSION['error']);
        return $this->view('academic-years/create', $data);
    }

    public function edit() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        if (!$this->checkAdminOrADM()) {
            return;
        }

        $id = trim((string) $this->get('id', ''));
        if ($id === '') {
            $_SESSION['error'] = 'Academic year is required.';
            $this->redirect('academic-years');
            return;
        }

        $model = $this->model('AcademicYearModel');
        $year = $model->getById($id);
        if (!$year) {
            $_SESSION['error'] = 'Academic year not found.';
            $this->redirect('academic-years');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $payload = $this->readForm(false);
            if (!empty($payload['errors'])) {
                $_SESSION['error'] = implode(' ', $payload['errors']);
                $this->redirect('academic-years/edit?id=' . urlencode($id));
                return;
            }

            $sqlError = null;
            if ($model->updateYear($id, $payload['data'], $sqlError)) {
                $this->logActivity(
                    'UPDATE',
                    'academic',
                    $id,
                    'Academic year updated: ' . $id,
                    $year,
                    $payload['data']
                );
                $_SESSION['message'] = 'Academic year updated successfully.';
                $this->redirect('academic-years');
                return;
            }

            $_SESSION['error'] = $sqlError
                ? ('Failed to update academic year. ' . $sqlError)
                : 'Failed to update academic year.';
            $this->redirect('academic-years/edit?id=' . urlencode($id));
            return;
        }

        $data = [
            'title' => 'Edit Academic Year',
            'page' => 'academic-years',
            'year' => $year,
            'error' => $_SESSION['error'] ?? null,
        ];
        unset($_SESSION['error']);
        return $this->view('academic-years/edit', $data);
    }

    public function delete() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        if (!$this->checkAdminOrADM()) {
            return;
        }

        $id = trim((string) $this->get('id', ''));
        if ($id === '') {
            $_SESSION['error'] = 'Academic year is required.';
            $this->redirect('academic-years');
            return;
        }

        $model = $this->model('AcademicYearModel');
        $year = $model->getById($id);
        if (!$year) {
            $_SESSION['error'] = 'Academic year not found.';
            $this->redirect('academic-years');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usage = $model->usageSummary($id);
            if ($usage['total'] > 0) {
                $_SESSION['error'] = 'Cannot delete this academic year. It is used by ' . implode(', ', $usage['details']) . '.';
                $this->redirect('academic-years');
                return;
            }

            if ($model->deleteYear($id)) {
                $this->logActivity(
                    'DELETE',
                    'academic',
                    $id,
                    'Academic year deleted: ' . $id,
                    $year,
                    null
                );
                $_SESSION['message'] = 'Academic year deleted successfully.';
            } else {
                $_SESSION['error'] = 'Failed to delete academic year.';
            }
            $this->redirect('academic-years');
            return;
        }

        $data = [
            'title' => 'Delete Academic Year',
            'page' => 'academic-years',
            'year' => $year,
            'usage' => $model->usageSummary($id),
        ];
        return $this->view('academic-years/delete', $data);
    }

    /**
     * @return array{errors: list<string>, data: array<string, string>}
     */
    private function readForm($includeYear) {
        $year = trim((string) $this->post('academic_year', ''));
        $status = trim((string) $this->post('academic_year_status', 'Active'));
        $firstStart = trim((string) $this->post('first_semi_start_date', ''));
        $firstEnd = trim((string) $this->post('first_semi_end_date', ''));
        $secondStart = trim((string) $this->post('second_semi_start_date', ''));
        $secondEnd = trim((string) $this->post('second_semi_end_date', ''));

        $errors = [];
        if ($includeYear) {
            if ($year === '') {
                $errors[] = 'Academic year is required.';
            } elseif (strlen($year) > 11) {
                $errors[] = 'Academic year must be 11 characters or fewer.';
            } elseif (!preg_match('/^\d{4}(\/\d{4})?$/', $year)) {
                $errors[] = 'Use format YYYY or YYYY/YYYY (e.g. 2026/2027).';
            }
        }
        if (!in_array($status, ['Active', 'Completed'], true)) {
            $errors[] = 'Select a valid status.';
        }

        $dates = [
            'First semester start' => $firstStart,
            'First semester end' => $firstEnd,
            'Second semester start' => $secondStart,
            'Second semester end' => $secondEnd,
        ];
        foreach ($dates as $label => $value) {
            if ($value === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                $errors[] = $label . ' is required.';
            }
        }
        if ($firstStart !== '' && $firstEnd !== '' && $firstStart > $firstEnd) {
            $errors[] = 'First semester start must be on or before the end date.';
        }
        if ($secondStart !== '' && $secondEnd !== '' && $secondStart > $secondEnd) {
            $errors[] = 'Second semester start must be on or before the end date.';
        }

        $data = [
            'first_semi_start_date' => $firstStart,
            'first_semi_end_date' => $firstEnd,
            'second_semi_start_date' => $secondStart,
            'second_semi_end_date' => $secondEnd,
            'academic_year_status' => $status,
        ];
        if ($includeYear) {
            $data = ['academic_year' => $year] + $data;
        }

        return ['errors' => $errors, 'data' => $data];
    }
}
