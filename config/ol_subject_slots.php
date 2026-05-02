<?php
/**
 * G.C.E. O/L (Sri Lanka) — nine examination slots: six mandatory “main” subjects
 * and three optional “basket” subjects (one from each category).
 *
 * @return array<string, array{label: string, subjects: list<string>}>
 */
declare(strict_types=1);

$other = 'Other (see details on O/L certificate)';

return [
    '01' => [
        'label' => 'Religion',
        'subjects' => [
            'Buddhism',
            'Christianity',
            'Roman Catholicism',
            'Islam',
            'Hinduism',
            $other,
        ],
    ],
    '02' => [
        'label' => 'First language & literature',
        'subjects' => [
            'Sinhala Language & Literature',
            'Tamil Language & Literature',
            $other,
        ],
    ],
    '03' => [
        'label' => 'English language',
        'subjects' => [
            'English Language',
            $other,
        ],
    ],
    '04' => [
        'label' => 'Mathematics',
        'subjects' => [
            'Mathematics',
            $other,
        ],
    ],
    '05' => [
        'label' => 'Science',
        'subjects' => [
            'Science',
            $other,
        ],
    ],
    '06' => [
        'label' => 'History',
        'subjects' => [
            'History',
            $other,
        ],
    ],
    '07' => [
        'label' => 'Basket I — choose one subject from this category',
        'subjects' => [
            'Business & Accounting Studies',
            'Geography',
            'Civic Education',
            'Entrepreneurship Studies',
            'Second Language (Sinhala)',
            'Second Language (Tamil)',
            'Pali',
            'Sanskrit',
            'French',
            'German',
            'Hindi',
            'Japanese',
            'Arabic',
            'Chinese',
            'Korean',
            'Russian',
            $other,
        ],
    ],
    '08' => [
        'label' => 'Basket II — choose one subject from this category',
        'subjects' => [
            'Eastern Music',
            'Western Music',
            'Carnatic Music',
            'Oriental Dancing',
            'Bharatha Natya',
            'Art',
            'English Literature',
            'Appreciation of Sinhala Literary Texts',
            'Appreciation of Tamil Literary Texts',
            'Appreciation of Arabic Literary Texts',
            'Drama and Theatre (Sinhala)',
            'Drama and Theatre (Tamil)',
            'Drama and Theatre (English)',
            $other,
        ],
    ],
    '09' => [
        'label' => 'Basket III — choose one subject from this category',
        'subjects' => [
            'Information & Communication Technology',
            'Agriculture & Food Technology',
            'Aquatic Bio-resources Technology',
            'Arts & Crafts',
            'Home Economics',
            'Health Science',
            'Health & Physical Education',
            'Communication & Media Studies',
            'Media Studies',
            'Design & Construction Technology',
            'Design & Mechanical Technology',
            'Design, Electrical & Electronic Technology',
            'Electronic Writing & Shorthand (Sinhala)',
            'Electronic Writing & Shorthand (Tamil)',
            'Electronic Writing & Shorthand (English)',
            'Engineering Technology',
            'Bio-systems Technology',
            'Science for Technology',
            'Agriculture',
            $other,
        ],
    ],
];
