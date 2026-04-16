-- Run once if `student_applications` already exists with INT result columns.
-- Allows letter grades (A, B, C, …) and numeric marks 0–100.

ALTER TABLE `student_applications`
    MODIFY `ol_subject_01_marks` VARCHAR(10) DEFAULT NULL,
    MODIFY `ol_subject_02_marks` VARCHAR(10) DEFAULT NULL,
    MODIFY `ol_subject_03_marks` VARCHAR(10) DEFAULT NULL,
    MODIFY `ol_subject_04_marks` VARCHAR(10) DEFAULT NULL,
    MODIFY `ol_subject_05_marks` VARCHAR(10) DEFAULT NULL,
    MODIFY `ol_subject_06_marks` VARCHAR(10) DEFAULT NULL,
    MODIFY `ol_subject_07_marks` VARCHAR(10) DEFAULT NULL,
    MODIFY `ol_subject_08_marks` VARCHAR(10) DEFAULT NULL,
    MODIFY `ol_subject_09_marks` VARCHAR(10) DEFAULT NULL,
    MODIFY `al_subject_01_marks` VARCHAR(10) DEFAULT NULL,
    MODIFY `al_subject_02_marks` VARCHAR(10) DEFAULT NULL,
    MODIFY `al_subject_03_marks` VARCHAR(10) DEFAULT NULL;
