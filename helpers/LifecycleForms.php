<?php
class LifecycleForms {
    public static function statuses() {
        return ['Active', 'Completed', 'Discontinued', 'Suspended', 'Withdrawn'];
    }

    public static function certificateStatuses() {
        return ['Pending', 'Processing', 'Received', 'Ready for Collection', 'Issued', 'Replaced', 'Cancelled'];
    }

    public static function ojtStatuses() {
        return ['Not Allocated', 'Pending Placement', 'Allocated', 'Ongoing', 'Completed', 'Incomplete'];
    }

    public static function employmentTypes() {
        return ['Permanent', 'Temporary', 'Contract', 'Self-employed', 'Government', 'Private Sector', 'Foreign Employment', 'Internship', 'Unemployed', 'Further Studies'];
    }

    public static function clearanceSections() {
        return ['Department', 'Hostel', 'Library', 'Workshop', 'Finance', 'Administration', 'Final'];
    }

    public static function documentCategories() {
        return [
            'Admission application', 'NIC / Passport copy', 'Educational certificate', 'Previous qualification',
            'Photograph', 'OJT recommendation', 'OJT placement letter', 'OJT confirmation', 'Follow-up letter',
            'Course completion letter', 'Character certificate', 'Recommendation letter', 'Academic transcript',
            'Qualification certificate', 'Clearance form', 'Student ID card', 'Correspondence', 'Other',
        ];
    }

    public static function reports() {
        return [
            'enrolment' => 'Enrolment from the records on file',
            'by_year' => 'Year-wise enrolment',
            'by_course' => 'Course-wise enrolment',
            'by_batch' => 'Batch-wise students',
            'active' => 'Active students',
            'completed' => 'Completed students',
            'discontinued' => 'Discontinued students',
            'suspended' => 'Suspended students',
            'withdrawn' => 'Withdrawn students',
            'completion_rate' => 'Course completion rate',
            'certificates' => 'Certificate issuance register',
            'certificates_pending' => 'Pending certificates',
            'nvq_rate' => 'NVQ certification rate',
            'ojt' => 'OJT placement',
            'ojt_completed' => 'OJT completion',
            'employment' => 'Graduate employment',
            'local_foreign' => 'Local and foreign employment',
            'self_employed' => 'Self-employment',
            'unemployed' => 'Unemployed graduates',
            'first_job' => 'Time to first employment',
            'higher' => 'Higher education',
            'feedback' => 'Employer feedback',
            'alumni' => 'Alumni registration',
            'tracer' => 'Graduate tracer surveys',
            'attendance' => 'Attendance summary',
        ];
    }

    public static function registers() {
        return [
            'progress' => 'Competency and assessments',
            'certificate' => 'Certificates',
            'collection' => 'Certificate collection',
            'replacement' => 'Certificate replacement',
            'industry' => 'Employers and industries',
            'ojt' => 'OJT placements',
            'monitoring' => 'OJT monitoring',
            'evaluation' => 'OJT evaluations',
            'employment' => 'Employment',
            'education' => 'Higher education',
            'feedback' => 'Employer feedback',
            'event' => 'Alumni events',
            'engagement' => 'Alumni engagement',
            'tracer' => 'Tracer studies',
            'discipline' => 'Discipline',
            'welfare' => 'Welfare',
            'correspondence' => 'Correspondence',
            'approval' => 'Approvals',
        ];
    }

    public static function normalize(array $spec, array $input) {
        $data = [];
        foreach ($spec['fields'] as $field) {
            $name = $field['name'];
            $value = trim((string) (isset($input[$name]) ? $input[$name] : ''));
            $type = isset($field['type']) ? $field['type'] : 'text';
            if (!empty($field['required']) && $value === '') {
                return ['data' => null, 'error' => $field['label'] . ' is required.'];
            }
            if ($type === 'select' && $value !== '' && isset($field['options']) && !isset($field['options'][$value])) {
                return ['data' => null, 'error' => 'Choose a valid ' . $field['label'] . '.'];
            }
            if ($type === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return ['data' => null, 'error' => $field['label'] . ' must be a valid email address.'];
            }
            if ($type === 'phone' && $value !== '') {
                $digits = preg_replace('/\s+/', '', $value);
                if (!preg_match('/^0?\d{9,12}$/', $digits)) {
                    return ['data' => null, 'error' => $field['label'] . ' must be a valid phone number.'];
                }
                $value = $digits;
            }
            if ($type === 'date') {
                $value = preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
                if (!empty($field['required']) && $value === null) {
                    return ['data' => null, 'error' => $field['label'] . ' must be a valid date.'];
                }
            } elseif ($type === 'number') {
                $value = $value === '' ? null : $value;
            } elseif ($value === '') {
                $value = null;
            }
            $data[$name] = $value;
        }
        return ['data' => $data, 'error' => ''];
    }

    public static function kind($name) {
        $all = self::all();
        return isset($all[$name]) ? $all[$name] : null;
    }

    public static function all() {
        $employment = self::employmentTypes();
        $yes = ['Yes' => 'Yes', 'No' => 'No'];
        return [
            'progress' => [
                'table' => 'student_module_progress',
                'title' => 'Module progress',
                'needs_student' => true,
                'fields' => [
                    ['name' => 'module_name', 'label' => 'Module', 'required' => true],
                    ['name' => 'theory_marks', 'label' => 'Theory marks', 'type' => 'number'],
                    ['name' => 'practical_marks', 'label' => 'Practical marks', 'type' => 'number'],
                    ['name' => 'continuous_marks', 'label' => 'Continuous assessment', 'type' => 'number'],
                    ['name' => 'final_marks', 'label' => 'Final marks', 'type' => 'number'],
                    ['name' => 'competency_result', 'label' => 'Competency result'],
                    ['name' => 'attendance_percent', 'label' => 'Attendance %', 'type' => 'number'],
                    ['name' => 'completion_status', 'label' => 'Completion', 'type' => 'select', 'options' => ['In progress' => 'In progress', 'Completed' => 'Completed', 'Repeat' => 'Repeat', 'Not completed' => 'Not completed']],
                    ['name' => 'repeat_note', 'label' => 'Repeat examination'],
                    ['name' => 'reassessment_note', 'label' => 'Reassessment'],
                ],
            ],
            'certificate' => [
                'table' => 'student_certificates',
                'title' => 'Certificate',
                'needs_student' => true,
                'fields' => [
                    ['name' => 'printed_name', 'label' => 'Name on certificate', 'required' => true],
                    ['name' => 'course_name', 'label' => 'Course'],
                    ['name' => 'trade', 'label' => 'Trade'],
                    ['name' => 'nvq_level', 'label' => 'NVQ level'],
                    ['name' => 'qualification', 'label' => 'Qualification'],
                    ['name' => 'certificate_type', 'label' => 'Certificate type'],
                    ['name' => 'completion_date', 'label' => 'Completion date', 'type' => 'date'],
                    ['name' => 'certification_date', 'label' => 'Certification date', 'type' => 'date'],
                    ['name' => 'issuance_date', 'label' => 'Issuance date', 'type' => 'date'],
                    ['name' => 'awarding_authority', 'label' => 'Awarding authority'],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => array_combine(self::certificateStatuses(), self::certificateStatuses()), 'required' => true],
                    ['name' => 'collection_status', 'label' => 'Collection status'],
                    ['name' => 'collection_date', 'label' => 'Collection date', 'type' => 'date'],
                    ['name' => 'pending_reason', 'label' => 'Pending reason'],
                    ['name' => 'transcript_note', 'label' => 'Transcript'],
                    ['name' => 'tvec_note', 'label' => 'TVEC / NVQ note'],
                ],
            ],
            'collection' => [
                'table' => 'certificate_events',
                'title' => 'Certificate collection',
                'needs_student' => true,
                'fixed' => ['event_type' => 'collection'],
                'fields' => [
                    ['name' => 'certificate_id', 'label' => 'Certificate', 'type' => 'select', 'options_key' => 'certificates', 'required' => true],
                    ['name' => 'event_date', 'label' => 'Collection date', 'type' => 'date', 'required' => true],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['Collected' => 'Collected', 'Returned' => 'Returned']],
                    ['name' => 'note', 'label' => 'Note'],
                ],
            ],
            'replacement' => [
                'table' => 'certificate_events',
                'title' => 'Replacement request',
                'needs_student' => true,
                'fixed' => ['event_type' => 'replacement'],
                'fields' => [
                    ['name' => 'certificate_id', 'label' => 'Certificate', 'type' => 'select', 'options_key' => 'certificates', 'required' => true],
                    ['name' => 'event_date', 'label' => 'Request date', 'type' => 'date', 'required' => true],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['Requested' => 'Requested', 'Issued' => 'Issued', 'Rejected' => 'Rejected']],
                    ['name' => 'note', 'label' => 'Reason'],
                ],
            ],
            'industry' => [
                'table' => 'ojt_industries',
                'title' => 'Industry',
                'needs_student' => false,
                'fields' => [
                    ['name' => 'name', 'label' => 'Establishment', 'required' => true],
                    ['name' => 'address', 'label' => 'Address'],
                    ['name' => 'sector', 'label' => 'Sector'],
                    ['name' => 'phone', 'label' => 'Phone', 'type' => 'phone'],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
                    ['name' => 'supervisor', 'label' => 'Supervisor'],
                ],
            ],
            'ojt' => [
                'table' => 'student_ojt',
                'title' => 'OJT placement',
                'needs_student' => true,
                'fields' => [
                    ['name' => 'industry_id', 'label' => 'Industry', 'type' => 'select', 'options_key' => 'industries'],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => array_combine(self::ojtStatuses(), self::ojtStatuses()), 'required' => true],
                    ['name' => 'start_date', 'label' => 'Start', 'type' => 'date'],
                    ['name' => 'end_date', 'label' => 'End', 'type' => 'date'],
                    ['name' => 'attendance_percent', 'label' => 'Attendance %', 'type' => 'number'],
                    ['name' => 'completion_confirmed', 'label' => 'Completion confirmed', 'type' => 'select', 'options' => ['0' => 'No', '1' => 'Yes']],
                    ['name' => 'recommendation_note', 'label' => 'Recommendation letter'],
                    ['name' => 'confirmation_note', 'label' => 'Placement confirmation'],
                    ['name' => 'pending_note', 'label' => 'Pending note'],
                    ['name' => 'follow_up', 'label' => 'Follow-up'],
                ],
            ],
            'monitoring' => [
                'table' => 'ojt_monitoring',
                'title' => 'Monitoring visit',
                'needs_student' => true,
                'fields' => [
                    ['name' => 'ojt_id', 'label' => 'Placement', 'type' => 'select', 'options_key' => 'placements', 'required' => true],
                    ['name' => 'visit_date', 'label' => 'Visit date', 'type' => 'date', 'required' => true],
                    ['name' => 'officer_name', 'label' => 'Officer'],
                    ['name' => 'report', 'label' => 'Report', 'type' => 'textarea'],
                ],
            ],
            'evaluation' => [
                'table' => 'ojt_evaluations',
                'title' => 'Employer evaluation',
                'needs_student' => true,
                'fields' => [
                    ['name' => 'ojt_id', 'label' => 'Placement', 'type' => 'select', 'options_key' => 'placements', 'required' => true],
                    ['name' => 'evaluator', 'label' => 'Evaluator', 'required' => true],
                    ['name' => 'score', 'label' => 'Score', 'type' => 'number'],
                    ['name' => 'evaluated_on', 'label' => 'Date', 'type' => 'date'],
                    ['name' => 'comments', 'label' => 'Comments', 'type' => 'textarea'],
                ],
            ],
            'employment' => [
                'table' => 'graduate_employment',
                'title' => 'Employment',
                'needs_student' => true,
                'fields' => [
                    ['name' => 'status_before', 'label' => 'Before admission', 'type' => 'select', 'options' => array_combine($employment, $employment)],
                    ['name' => 'status_during', 'label' => 'During training', 'type' => 'select', 'options' => array_combine($employment, $employment)],
                    ['name' => 'status_after', 'label' => 'After graduation', 'type' => 'select', 'options' => array_combine($employment, $employment)],
                    ['name' => 'current_status', 'label' => 'Current status', 'type' => 'select', 'options' => array_combine($employment, $employment)],
                    ['name' => 'employment_type', 'label' => 'Employment type', 'type' => 'select', 'options' => array_combine($employment, $employment)],
                    ['name' => 'organisation', 'label' => 'Organisation'],
                    ['name' => 'address', 'label' => 'Address'],
                    ['name' => 'sector', 'label' => 'Sector'],
                    ['name' => 'designation', 'label' => 'Designation'],
                    ['name' => 'job_role', 'label' => 'Job role'],
                    ['name' => 'start_date', 'label' => 'Start date', 'type' => 'date'],
                    ['name' => 'location_scope', 'label' => 'Local or foreign', 'type' => 'select', 'options' => ['Local' => 'Local', 'Foreign' => 'Foreign']],
                    ['name' => 'salary_range', 'label' => 'Salary range (optional)'],
                    ['name' => 'course_related', 'label' => 'Course related', 'type' => 'select', 'options' => $yes],
                    ['name' => 'days_to_first_job', 'label' => 'Days to first job', 'type' => 'number'],
                    ['name' => 'self_employed_detail', 'label' => 'Self-employment'],
                    ['name' => 'business_detail', 'label' => 'Business ownership'],
                    ['name' => 'placement_assistance', 'label' => 'Placement assistance'],
                    ['name' => 'verified', 'label' => 'Verified', 'type' => 'select', 'options' => ['0' => 'No', '1' => 'Yes']],
                ],
            ],
            'education' => [
                'table' => 'graduate_higher_education',
                'title' => 'Higher education',
                'needs_student' => true,
                'fields' => [
                    ['name' => 'institution', 'label' => 'Institution', 'required' => true],
                    ['name' => 'programme', 'label' => 'Programme'],
                    ['name' => 'qualification', 'label' => 'Qualification'],
                    ['name' => 'start_date', 'label' => 'Start', 'type' => 'date'],
                    ['name' => 'end_date', 'label' => 'End', 'type' => 'date'],
                    ['name' => 'note', 'label' => 'Note'],
                ],
            ],
            'feedback' => [
                'table' => 'employer_feedback',
                'title' => 'Employer feedback',
                'needs_student' => true,
                'fields' => [
                    ['name' => 'employment_id', 'label' => 'Employment record', 'type' => 'select', 'options_key' => 'employment'],
                    ['name' => 'employer_name', 'label' => 'Employer', 'required' => true],
                    ['name' => 'rating', 'label' => 'Rating 1 to 5', 'type' => 'number'],
                    ['name' => 'feedback', 'label' => 'Feedback', 'type' => 'textarea'],
                ],
            ],
            'event' => [
                'table' => 'alumni_events',
                'title' => 'Alumni event',
                'needs_student' => false,
                'fields' => [
                    ['name' => 'title', 'label' => 'Event', 'required' => true],
                    ['name' => 'event_date', 'label' => 'Date', 'type' => 'date'],
                    ['name' => 'location', 'label' => 'Location'],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ],
            ],
            'engagement' => [
                'table' => 'alumni_engagement',
                'title' => 'Alumni engagement',
                'needs_student' => true,
                'fields' => [
                    ['name' => 'kind', 'label' => 'Type', 'type' => 'select', 'required' => true, 'options' => [
                        'event' => 'Event participation',
                        'guest' => 'Guest lecture',
                        'mentoring' => 'Mentoring',
                        'guidance' => 'Career guidance',
                        'feedback' => 'Training feedback',
                        'ojt_support' => 'OJT placement support',
                        'job_share' => 'Employment opportunity',
                        'contribution' => 'Contribution',
                    ]],
                    ['name' => 'title', 'label' => 'Title', 'required' => true],
                    ['name' => 'engaged_on', 'label' => 'Date', 'type' => 'date'],
                    ['name' => 'event_id', 'label' => 'Linked event', 'type' => 'select', 'options_key' => 'events'],
                    ['name' => 'detail', 'label' => 'Detail', 'type' => 'textarea'],
                ],
            ],
            'discipline' => [
                'table' => 'student_discipline',
                'title' => 'Discipline record',
                'needs_student' => true,
                'fields' => [
                    ['name' => 'incident_date', 'label' => 'Incident date', 'type' => 'date', 'required' => true],
                    ['name' => 'category', 'label' => 'Category', 'type' => 'select', 'required' => true, 'options' => ['Conduct' => 'Conduct', 'Attendance' => 'Attendance', 'Academic' => 'Academic', 'Hostel' => 'Hostel', 'Other' => 'Other']],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => true],
                    ['name' => 'action_taken', 'label' => 'Action taken'],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'options' => ['Open' => 'Open', 'Warning' => 'Warning', 'Action taken' => 'Action taken', 'Closed' => 'Closed']],
                ],
            ],
            'welfare' => [
                'table' => 'student_welfare',
                'title' => 'Welfare assistance',
                'needs_student' => true,
                'fields' => [
                    ['name' => 'aid_date', 'label' => 'Date', 'type' => 'date', 'required' => true],
                    ['name' => 'aid_type', 'label' => 'Type', 'type' => 'select', 'required' => true, 'options' => ['Financial' => 'Financial', 'Medical' => 'Medical', 'Counselling' => 'Counselling', 'Hostel' => 'Hostel', 'Other' => 'Other']],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                    ['name' => 'amount', 'label' => 'Amount', 'type' => 'number'],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'options' => ['Requested' => 'Requested', 'Approved' => 'Approved', 'Issued' => 'Issued', 'Closed' => 'Closed']],
                ],
            ],
            'correspondence' => [
                'table' => 'student_correspondence',
                'title' => 'Correspondence',
                'needs_student' => true,
                'fields' => [
                    ['name' => 'letter_date', 'label' => 'Letter date', 'type' => 'date', 'required' => true],
                    ['name' => 'direction', 'label' => 'Direction', 'type' => 'select', 'required' => true, 'options' => ['Outgoing' => 'Outgoing', 'Incoming' => 'Incoming']],
                    ['name' => 'subject', 'label' => 'Subject', 'required' => true],
                    ['name' => 'body', 'label' => 'Letter', 'type' => 'textarea'],
                ],
            ],
            'approval' => [
                'table' => 'lifecycle_approvals',
                'title' => 'Approval request',
                'needs_student' => true,
                'fields' => [
                    ['name' => 'module_name', 'label' => 'Workflow', 'type' => 'select', 'required' => true, 'options' => [
                        'certificate' => 'Certificate issuance',
                        'replacement' => 'Certificate replacement',
                        'completion' => 'Course completion',
                        'clearance' => 'Student clearance',
                        'ojt' => 'OJT completion',
                        'employment' => 'Employment verification',
                        'alumni' => 'Alumni verification',
                        'historical' => 'Historical record correction',
                    ]],
                    ['name' => 'subject', 'label' => 'Subject', 'required' => true],
                    ['name' => 'remarks', 'label' => 'Remarks', 'type' => 'textarea'],
                ],
            ],
            'tracer' => [
                'table' => 'graduate_tracer_surveys',
                'title' => 'Tracer survey',
                'needs_student' => true,
                'fields' => [
                    ['name' => 'survey_year', 'label' => 'Survey year', 'type' => 'number', 'required' => true],
                    ['name' => 'employed', 'label' => 'Employed', 'type' => 'select', 'options' => $yes],
                    ['name' => 'course_related', 'label' => 'Course related', 'type' => 'select', 'options' => $yes],
                    ['name' => 'location_scope', 'label' => 'Location', 'type' => 'select', 'options' => ['Local' => 'Local', 'Foreign' => 'Foreign']],
                    ['name' => 'higher_study', 'label' => 'Higher studies', 'type' => 'select', 'options' => $yes],
                    ['name' => 'note', 'label' => 'Note', 'type' => 'textarea'],
                ],
            ],
        ];
    }
}
