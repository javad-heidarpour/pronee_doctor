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


-- Feature 084 menu entry. Equivalent to 2026_11_21_020000_add_no_show_risk_menu.php.
INSERT INTO menus
    (title, route_name, icon, `order`, guard, is_active, show_in_sidebar, permissions, roles, created_at, updated_at)
SELECT
    'سابقه عدم حضور بیماران', 'portal.no-show-risk.index', 'IconCalendarX', 58,
    'doctor', 1, 1, JSON_ARRAY('appointments.view'),
    JSON_ARRAY('doctor', 'admin', 'secretary'), NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM menus WHERE route_name = 'portal.no-show-risk.index'
);


-- Feature 085: clinic capacity analysis has no schema changes.
-- Equivalent menu entry for database/migrations/2026_11_21_030000_add_clinic_capacity_menu.php.
-- Apply this menu insert OR the Laravel migration, never both.
INSERT INTO menus
    (title, route_name, icon, `order`, guard, is_active, show_in_sidebar, permissions, roles, created_at, updated_at)
SELECT
    'تحلیل ظرفیت مطب', 'portal.shifts.capacity', 'IconChartBar', 59,
    'doctor', 1, 1, JSON_ARRAY('shifts.view'),
    JSON_ARRAY('doctor', 'admin', 'secretary'), NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM menus WHERE route_name = 'portal.shifts.capacity'
);


-- Feature 087: population health trends menu only; no schema changes.
-- Equivalent to database/migrations/2026_11_21_040000_add_population_health_menu.php.
-- Apply this INSERT OR the Laravel migration, never both.
INSERT INTO menus
    (title, route_name, icon, `order`, guard, is_active, show_in_sidebar, permissions, roles, created_at, updated_at)
SELECT
    'روند سلامت جمعیت بیماران', 'portal.population-health.index', 'IconChartNoAxesCombined', 61,
    'doctor', 1, 1, JSON_ARRAY('patients.clinical.view'),
    JSON_ARRAY('doctor', 'admin', 'secretary'), NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM menus WHERE route_name = 'portal.population-health.index'
);

-- Feature 092: user notification center (run this OR the matching Laravel migration, not both).
CREATE TABLE IF NOT EXISTS notifications (
  id CHAR(36) NOT NULL PRIMARY KEY,
  type VARCHAR(255) NOT NULL,
  notifiable_type VARCHAR(255) NOT NULL,
  notifiable_id BIGINT UNSIGNED NOT NULL,
  data TEXT NOT NULL,
  read_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX notifications_notifiable_type_notifiable_id_index (notifiable_type, notifiable_id),
  INDEX notifications_owner_read_idx (notifiable_type, notifiable_id, read_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Feature 095: doctor educational articles (run this OR the matching Laravel migration, not both).
CREATE TABLE IF NOT EXISTS doctor_articles (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  doctor_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  slug VARCHAR(200) NOT NULL,
  excerpt VARCHAR(500) NULL,
  body LONGTEXT NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'draft',
  published_at TIMESTAMP NULL,
  meta_title VARCHAR(180) NULL,
  meta_description VARCHAR(320) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  deleted_at TIMESTAMP NULL,
  CONSTRAINT doctor_articles_doctor_id_foreign FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
  UNIQUE KEY doctor_articles_doctor_slug_unique (doctor_id, slug),
  INDEX doctor_articles_publish_idx (doctor_id, status, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Feature 092: notification center menu.
INSERT INTO menus (title, route_name, icon, `order`, guard, is_active, show_in_sidebar, permissions, roles, created_at, updated_at)
SELECT 'مرکز اعلان‌ها', 'portal.notifications.index', 'IconBell', 4, 'doctor', 1, 1, JSON_ARRAY(), JSON_ARRAY('doctor','admin','secretary'), NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM menus WHERE route_name = 'portal.notifications.index');

-- Feature 095: educational articles menu.
INSERT INTO menus (title, route_name, icon, `order`, guard, is_active, show_in_sidebar, permissions, roles, created_at, updated_at)
SELECT 'مقالات آموزشی', 'portal.articles.index', 'IconBookOpen', 72, 'doctor', 1, 1, JSON_ARRAY(), JSON_ARRAY('doctor','admin'), NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM menus WHERE route_name = 'portal.articles.index');


-- Feature 096: doctor FAQs (run this OR the matching Laravel migration, never both).
CREATE TABLE doctor_faqs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    doctor_id BIGINT UNSIGNED NOT NULL,
    question VARCHAR(240) NOT NULL,
    answer TEXT NOT NULL,
    category VARCHAR(80) NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY doctor_faqs_public_order_idx (doctor_id, is_published, sort_order),
    CONSTRAINT doctor_faqs_doctor_fk
        FOREIGN KEY (doctor_id) REFERENCES doctors (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO menus
    (title, route_name, icon, `order`, guard, is_active, show_in_sidebar, permissions, roles, created_at, updated_at)
SELECT
    'پرسش‌های متداول', 'portal.faqs.index', 'IconHelpCircle', 73,
    'doctor', 1, 1, JSON_ARRAY(), JSON_ARRAY('doctor', 'admin'), NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM menus WHERE route_name = 'portal.faqs.index'
);

-- Feature 097: enforce one social-platform row per doctor.
-- Equivalent to database/migrations/2026_11_21_100000_harden_doctor_social_links.php.
-- Review duplicate rows before running; this keeps the lowest id for each doctor/platform pair.
DELETE duplicate_row
FROM doctor_socials AS duplicate_row
INNER JOIN doctor_socials AS kept_row
    ON duplicate_row.doctor_id = kept_row.doctor_id
   AND duplicate_row.social_platform_id = kept_row.social_platform_id
   AND duplicate_row.id > kept_row.id;

ALTER TABLE doctor_socials
    ADD UNIQUE KEY doctor_socials_doctor_platform_unique (doctor_id, social_platform_id);

-- Feature 098: support ticket intake and admin response (run this OR migration, never both).
CREATE TABLE support_tickets (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    reference VARCHAR(20) NOT NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    category VARCHAR(30) NOT NULL DEFAULT 'other',
    subject VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'open',
    priority VARCHAR(20) NOT NULL DEFAULT 'normal',
    staff_response TEXT NULL,
    responded_by BIGINT UNSIGNED NULL,
    responded_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY support_tickets_reference_unique (reference),
    KEY support_tickets_status_created_idx (status, created_at),
    KEY support_tickets_email_created_idx (email, created_at),
    CONSTRAINT support_tickets_user_fk
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT support_tickets_responder_fk
        FOREIGN KEY (responded_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO menus
    (title, route_name, icon, `order`, guard, is_active, show_in_sidebar, permissions, roles, created_at, updated_at)
SELECT
    'درخواست‌های پشتیبانی', 'portal.support-tickets.index', 'IconLifeBuoy', 74,
    'doctor', 1, 1, JSON_ARRAY(), JSON_ARRAY('admin'), NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM menus WHERE route_name = 'portal.support-tickets.index'
);

-- Feature 099: locale selection is session-based and requires no database schema change.


-- Feature 100: patient satisfaction survey (run this OR the Laravel migration, never both).
-- Survey rows are unique per appointment; signed-link validation is enforced by the application.
CREATE TABLE appointment_satisfaction_surveys (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    appointment_id BIGINT UNSIGNED NOT NULL,
    doctor_id BIGINT UNSIGNED NOT NULL,
    overall_rating TINYINT UNSIGNED NOT NULL,
    communication_rating TINYINT UNSIGNED NOT NULL,
    waiting_rating TINYINT UNSIGNED NOT NULL,
    facility_rating TINYINT UNSIGNED NOT NULL,
    comment TEXT NULL,
    is_anonymous TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY appointment_satisfaction_surveys_appointment_id_unique (appointment_id),
    KEY satisfaction_doctor_created_idx (doctor_id, created_at),
    CONSTRAINT satisfaction_survey_appointment_fk
        FOREIGN KEY (appointment_id) REFERENCES appointments (id) ON DELETE CASCADE,
    CONSTRAINT satisfaction_survey_doctor_fk
        FOREIGN KEY (doctor_id) REFERENCES doctors (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO menus
    (title, route_name, icon, `order`, guard, is_active, show_in_sidebar, permissions, roles, created_at, updated_at)
SELECT
    'رضایت بیماران', 'portal.satisfaction.index', 'IconStar', 75,
    'doctor', 1, 1, JSON_ARRAY('appointments.view'),
    JSON_ARRAY('doctor', 'admin', 'secretary'), NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM menus WHERE route_name = 'portal.satisfaction.index'
);
