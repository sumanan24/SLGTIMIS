<?php
/**
 * Staff module catalog for per-person access.
 * Role defaults reuse existing UserModel RBAC. Staff rows override per person.
 */
return [
    'students' => [
        'label' => 'Student Details',
        'prefixes' => ['students'],
        'sort' => 10,
    ],
    'staff' => [
        'label' => 'Staff Details',
        'prefixes' => ['staff'],
        'sort' => 20,
    ],
    'staff_attendance' => [
        'label' => 'Staff Attendance',
        'prefixes' => ['attendance/staff-device'],
        'sort' => 30,
    ],
    'personal_files' => [
        'label' => 'Staff Personal Files',
        'prefixes' => ['staff/edit', 'staff/file'],
        'sort' => 40,
    ],
    'student_attendance' => [
        'label' => 'Student Attendance',
        'prefixes' => ['attendance'],
        'sort' => 50,
    ],
    'staff_roles' => [
        'label' => 'Staff Roles',
        'prefixes' => ['staff-roles'],
        'sort' => 60,
    ],
    'departments' => [
        'label' => 'Departments',
        'prefixes' => ['departments'],
        'sort' => 70,
    ],
    'courses' => [
        'label' => 'Courses',
        'prefixes' => ['courses'],
        'sort' => 80,
    ],
    'groups' => [
        'label' => 'Groups',
        'prefixes' => ['groups'],
        'sort' => 90,
    ],
    'exams' => [
        'label' => 'Exams',
        'prefixes' => ['exams'],
        'sort' => 100,
    ],
    'payments' => [
        'label' => 'Payments',
        'prefixes' => ['payments', 'payment-types'],
        'sort' => 110,
    ],
    'devices' => [
        'label' => 'Asset Management',
        'prefixes' => ['devices'],
        'sort' => 120,
    ],
    'complaint_letters' => [
        'label' => 'Student Letters',
        'prefixes' => ['complaint-letters'],
        'sort' => 130,
    ],
    'student_applications' => [
        'label' => 'Student Applications',
        'prefixes' => ['student-applications', 'application-admission'],
        'sort' => 140,
    ],
    'instructor_diary' => [
        'label' => 'Instructor Diary',
        'prefixes' => ['instructor-diary'],
        'sort' => 150,
    ],
    'circuit_program' => [
        'label' => 'Circuit Program',
        'prefixes' => ['circuit-program'],
        'sort' => 160,
    ],
];
