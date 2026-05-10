<?php
/**
 * G.C.E. O/L (Sri Lanka) — nine slots: six fixed main subjects (grade only in UI)
 * and three basket subjects (dropdown + grade).
 *
 * @return array<string, array{label?: string, fixed_subject?: string, subjects?: list<string>}>
 */
declare(strict_types=1);

$other = 'Other (see details on O/L certificate)';

return [
    '01' => [
        'label' => 'Religion',
        'fixed_subject' => 'Religion',
    ],
    '02' => [
        'label' => 'Sinhala / Tamil Language & Literature',
        'fixed_subject' => 'Sinhala / Tamil Language & Literature',
    ],
    '03' => [
        'label' => 'English Language',
        'fixed_subject' => 'English Language',
    ],
    '04' => [
        'label' => 'Mathematics',
        'fixed_subject' => 'Mathematics',
    ],
    '05' => [
        'label' => 'History',
        'fixed_subject' => 'History',
    ],
    '06' => [
        'label' => 'Science',
        'fixed_subject' => 'Science',
    ],
    '07' => [
        'label' => 'Basket 1 — choose one subject',
        'subjects' => [
            'Business & Accounting Studies',
            'Geography',
            'Civic Education',
            'Entrepreneurship Studies',
            'Second Language (Sinhala/Tamil)',
            'Pali',
            'Sanskrit',
            'French',
            'German',
            'Hindi',
            'Japanese',
            'Arabic',
            'Korean',
            'Chinese',
            'Russian',
            $other,
        ],
    ],
    '08' => [
        'label' => 'Basket 2 — choose one subject',
        'subjects' => [
            'Music',
            'Art',
            'Dancing',
            'Literature',
            'Drama & Theatre',
            $other,
        ],
    ],
    '09' => [
        'label' => 'Basket 3 — choose one subject',
        'subjects' => [
            'ICT',
            'Agriculture & Food Technology',
            'Aquatic Bio-resource Technology',
            'Arts & Crafts',
            'Home Economics',
            'Health & Physical Education',
            'Communication & Media Studies',
            'Design & Construction Technology',
            'Design & Mechanical Technology',
            'Design, Electrical & Electronic Technology',
            'Electronic Writing & Shorthand',
            $other,
        ],
    ],
];
