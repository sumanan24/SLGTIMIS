<?php

class MarkController extends Controller {

    public function enterMarks() {
        if (!$this->checkExamsAccess()) {
            return;
        }
        $examId = (int) $this->get('exam_id', 0);
        $moduleId = trim((string) $this->get('module_id', ''));
        $examModel = $this->model('ExamModel');
        $exams = $examModel->listExamsWithCourse();

        $exam = null;
        $rows = [];
        $modules = [];
        if ($examId > 0) {
            $exam = $examModel->findWithCourse($examId);
            if ($exam) {
                $modules = $examModel->decodeExamModulesList($exam);
                if ($moduleId !== '' && !$examModel->examHasModule($exam, $moduleId)) {
                    $moduleId = '';
                }
                if ($moduleId === '' && !empty($modules)) {
                    $first = trim((string) ($modules[0]['module_id'] ?? ''));
                    if ($first !== '') {
                        $this->redirect('marks/enter?exam_id=' . $examId . '&module_id=' . rawurlencode($first));
                        return;
                    }
                }
                if ($moduleId !== '' && $examModel->examHasModule($exam, $moduleId)) {
                    $rows = $examModel->getStudentsWithMarksForModule($examId, $moduleId);
                }
            }
        }

        return $this->view('marks/enter', [
            'title' => 'Enter marks',
            'page' => 'exams-marks',
            'exams_list' => $exams,
            'exam' => $exam,
            'modules' => $modules,
            'rows' => $rows,
            'selected_exam_id' => $examId,
            'selected_module_id' => $moduleId,
        ]);
    }

    public function saveMarks() {
        if (!$this->checkExamsAccess()) {
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('marks/enter');
            return;
        }

        $examId = (int) $this->post('exam_id', 0);
        $moduleId = trim((string) $this->post('module_id', ''));
        if ($examId < 1 || $moduleId === '') {
            $_SESSION['error'] = 'Exam and module are required.';
            $this->redirect('marks/enter');
            return;
        }

        $rawFirst = $this->post('marks_first', []);
        $rawSecond = $this->post('marks_second', []);
        if (!is_array($rawFirst)) {
            $rawFirst = [];
        }
        if (!is_array($rawSecond)) {
            $rawSecond = [];
        }

        $examModel = $this->model('ExamModel');
        $exam = $examModel->findWithCourse($examId);
        if (!$exam) {
            $_SESSION['error'] = 'Exam not found.';
            $this->redirect('marks/enter');
            return;
        }
        if (!$examModel->examHasModule($exam, $moduleId)) {
            $_SESSION['error'] = 'Invalid module for this exam.';
            $this->redirect('marks/enter?exam_id=' . $examId);
            return;
        }

        $allowedIds = $examModel->getStudentIdsForExam($examId);
        $allowedSet = array_flip($allowedIds);

        $keys = ['q1', 'q2', 'q3', 'q4', 'q5', 'q6', 'q7', 'final'];
        $mf = [];
        $ms = [];
        foreach ($allowedIds as $sid) {
            $blockF = isset($rawFirst[$sid]) && is_array($rawFirst[$sid]) ? $rawFirst[$sid] : [];
            $blockS = isset($rawSecond[$sid]) && is_array($rawSecond[$sid]) ? $rawSecond[$sid] : [];
            $rowF = [];
            $rowS = [];
            foreach ($keys as $k) {
                $rowF[$k] = $blockF[$k] ?? '';
                $rowS[$k] = $blockS[$k] ?? '';
            }
            $mf[$sid] = $rowF;
            $ms[$sid] = $rowS;
        }

        $ok = $examModel->saveModuleMarks($examId, $moduleId, $mf, $ms);
        if ($ok) {
            $_SESSION['message'] = 'Marks saved for module ' . $moduleId . '.';
            $this->logActivity('UPDATE', 'exam_marks', (string) $examId, 'Saved module marks.', null, ['module_id' => $moduleId]);
        } else {
            $_SESSION['error'] = 'Could not save marks. Ensure database migration for marks is applied.';
        }

        $this->redirect('marks/enter?exam_id=' . $examId . '&module_id=' . rawurlencode($moduleId));
    }
}
