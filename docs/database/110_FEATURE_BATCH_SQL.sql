-- Proneex | 110-feature batch | Workbench SQL
-- This is the direct MySQL equivalent of:
-- database/migrations/2026_11_20_200000_create_clinic_staff_performance_reviews_table.php
-- Apply only after confirming that the three referenced parent tables exist.
-- Do not run this together with the Laravel migration; choose one deployment path.

CREATE TABLE clinic_staff_performance_reviews (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    organization_id BIGINT UNSIGNED NOT NULL,
    clinic_id BIGINT UNSIGNED NOT NULL,
    staff_user_id BIGINT UNSIGNED NOT NULL,
    reviewer_user_id BIGINT UNSIGNED NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    reliability_score TINYINT UNSIGNED NOT NULL,
    task_completion_score TINYINT UNSIGNED NOT NULL,
    teamwork_score TINYINT UNSIGNED NOT NULL,
    communication_score TINYINT UNSIGNED NOT NULL,
    learning_score TINYINT UNSIGNED NOT NULL,
    strengths TEXT NULL,
    improvement_areas TEXT NULL,
    goals TEXT NULL,
    reviewer_notes TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    published_at TIMESTAMP NULL,
    acknowledged_at TIMESTAMP NULL,
    employee_response TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    CONSTRAINT cspr_org_fk FOREIGN KEY (organization_id)
        REFERENCES clinic_organizations (id) ON DELETE CASCADE,
    CONSTRAINT cspr_clinic_fk FOREIGN KEY (clinic_id)
        REFERENCES addresses (id) ON DELETE CASCADE,
    CONSTRAINT cspr_staff_fk FOREIGN KEY (staff_user_id)
        REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT cspr_reviewer_fk FOREIGN KEY (reviewer_user_id)
        REFERENCES users (id) ON DELETE RESTRICT,
    UNIQUE KEY cspr_unique_period
        (organization_id, clinic_id, staff_user_id, period_start, period_end),
    KEY cspr_org_status_period_idx (organization_id, status, period_end),
    KEY cspr_staff_ack_idx (staff_user_id, status, acknowledged_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Post-deployment verification:
SELECT COUNT(*) AS performance_review_rows FROM clinic_staff_performance_reviews;
SHOW CREATE TABLE clinic_staff_performance_reviews;


-- Feature 084: explicit attendance outcomes for historical no-show analysis.
-- Equivalent to database/migrations/2026_11_21_010000_add_appointment_attendance_outcomes.php
-- Run this OR the Laravel migration, never both.
ALTER TABLE appointments
    ADD COLUMN attendance_outcome VARCHAR(20) NULL AFTER status,
    ADD COLUMN attendance_outcome_recorded_at TIMESTAMP NULL AFTER attendance_outcome,
    ADD COLUMN attendance_outcome_recorded_by BIGINT UNSIGNED NULL AFTER attendance_outcome_recorded_at,
    ADD KEY appt_attendance_learning_idx
        (doctor_id, patient_id, attendance_outcome, appointment_date),
    ADD CONSTRAINT appt_attendance_recorded_by_fk
        FOREIGN KEY (attendance_outcome_recorded_by) REFERENCES users(id)
        ON DELETE SET NULL;

-- Historical rows intentionally remain NULL: attendance must not be inferred
-- from appointment status or cancellation. Backfill only from verified records.
SELECT attendance_outcome, COUNT(*) AS total
FROM appointments
GROUP BY attendance_outcome;
