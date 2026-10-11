<?php
/**
 * Education qualifications shown on the student record.
 * Read-only from the linked admission application (O/L, A/L, NVQ).
 */
class StudentQualificationModel extends Model {
    public function listForStudent(string $studentId): array {
        $studentId = trim($studentId);
        if ($studentId === '') {
            return [];
        }
        require_once BASE_PATH . '/helpers/AdmissionRegistrationService.php';
        $application = (new AdmissionRegistrationService())->findApplicationForStudent($studentId);
        return $application ? $this->groupsFromApplication($application) : [];
    }

    private function groupsFromApplication(array $app): array {
        $groups = [];

        $olSubjects = $this->collectSubjects($app, 'ol_subject_name_', 'ol_subject_', 9);
        $olIndex = $this->clean($app['ol_index_number'] ?? '');
        $olYear = $this->clean($app['ol_exam_year'] ?? '');
        if ($olIndex !== '' || $olYear !== '' || $olSubjects !== []) {
            $groups[] = [
                'qual_type' => 'ol',
                'type_label' => 'G.C.E. O/L',
                'title' => 'G.C.E. Ordinary Level',
                'year_completed' => $olYear,
                'index_number' => $olIndex,
                'stream' => '',
                'institute' => '',
                'result' => '',
                'subjects_list' => $olSubjects,
            ];
        }

        $alSubjects = $this->collectSubjects($app, 'al_subject_name_', 'al_subject_', 3);
        $alIndex = $this->clean($app['al_index_number'] ?? '');
        $alYear = $this->clean($app['al_exam_year'] ?? '');
        $alStream = $this->clean($app['al_stream'] ?? '');
        if ($alIndex !== '' || $alYear !== '' || $alStream !== '' || $alSubjects !== []) {
            $groups[] = [
                'qual_type' => 'al',
                'type_label' => 'G.C.E. A/L',
                'title' => 'G.C.E. Advanced Level',
                'year_completed' => $alYear,
                'index_number' => $alIndex,
                'stream' => $alStream,
                'institute' => '',
                'result' => '',
                'subjects_list' => $alSubjects,
            ];
        }

        $nvqCourse = $this->clean($app['nvq_course_name'] ?? '');
        $nvqInstitute = $this->clean($app['nvq_institute_name'] ?? '');
        $nvqYear = $this->clean($app['nvq_year_completed'] ?? '');
        $nvqLevel = $this->clean($app['nvq_level'] ?? '');
        if ($this->isDeclaredValue($nvqCourse) || $this->isDeclaredValue($nvqInstitute) || in_array($nvqLevel, ['3', '4', '5'], true)) {
            $title = $this->isDeclaredValue($nvqCourse)
                ? $nvqCourse
                : ('NVQ Level ' . ($nvqLevel !== '' ? $nvqLevel : '4'));
            $groups[] = [
                'qual_type' => 'nvq',
                'type_label' => 'NVQ',
                'title' => $title,
                'year_completed' => ($nvqYear !== '' && $nvqYear !== '2000') ? $nvqYear : '',
                'index_number' => '',
                'stream' => $nvqLevel !== '' ? ('Level ' . $nvqLevel) : '',
                'institute' => $this->isDeclaredValue($nvqInstitute) ? $nvqInstitute : '',
                'result' => $nvqLevel !== '' ? ('Level ' . $nvqLevel) : '',
                'subjects_list' => [],
            ];
        }

        return $groups;
    }

    private function collectSubjects(array $app, string $namePrefix, string $resultPrefix, int $count): array {
        $subjects = [];
        for ($i = 1; $i <= $count; $i++) {
            $slot = sprintf('%02d', $i);
            $name = $this->clean($app[$namePrefix . $slot] ?? '');
            $result = $this->clean($app[$resultPrefix . $slot . '_marks'] ?? '');
            if ($name === '' && $result === '') {
                continue;
            }
            $subjects[] = [
                'name' => $name !== '' ? $name : ('Subject ' . $i),
                'result' => $result,
            ];
        }
        return $subjects;
    }

    private function isDeclaredValue(string $value): bool {
        $normalized = strtoupper(str_replace(['.', ' '], '', $value));
        return $value !== '' && !in_array($normalized, ['NA', 'N/A', 'NONE', '-'], true);
    }

    private function clean($value): string {
        return trim((string) $value);
    }
}
