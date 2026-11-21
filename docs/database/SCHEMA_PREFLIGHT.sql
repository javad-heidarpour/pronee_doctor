-- Proneex | READ-ONLY database preflight
-- Run in MySQL Workbench against the target database and send back every result grid.
-- This script does not create, alter, update, or delete anything.

-- 1) Confirm the exact target database and server version.
SELECT DATABASE() AS target_database, VERSION() AS mysql_version;

-- 2) Migration ledger. If the table is absent, this query returns no rows from information_schema.
SELECT migration, batch
FROM migrations
ORDER BY batch, migration;

-- 3) Existence and row estimates for tables changed/created in the recent roadmap.
SELECT table_name, table_rows
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name IN (
    'clinic_professional_sessions',
    'clinic_professional_dues',
    'clinic_staff_contracts',
    'clinic_staff_leave_balances',
    'clinic_staff_shift_replacements',
    'clinic_staff_obligations',
    'clinic_staff_obligation_installments',
    'clinic_staff_obligation_repayments',
    'clinic_suppliers',
    'clinic_supplier_quotes',
    'clinic_purchase_orders',
    'clinic_purchase_order_items',
    'clinic_supplier_payments',
    'clinic_staff_work_schedules',
    'clinic_staff_attendances',
    'clinic_staff_attendance_events',
    'clinic_staff_leave_requests',
    'clinic_organization_holidays',
    'patient_insurance_verifications',
    'health_exchange_messages',
    'patient_documents',
    'patient_consents',
    'doctor_holidays',
    'patient_problems',
    'clinic_staff_responsibilities',
    'clinic_staff_responsibility_events',
    'appointment_payment_refunds',
    'clinic_cash_register_sessions',
    'clinic_cash_transactions',
    'clinic_discount_codes',
    'clinic_discount_redemptions',
    'clinic_shared_services',
    'clinic_shared_service_requests',
    'clinic_staff_performance_reviews',
    'clinic_business_leads',
    'notifications',
    'doctor_articles',
    'doctor_faqs',
    'support_tickets',
    'appointment_satisfaction_surveys'
  )
ORDER BY table_name;

-- 4) Exact columns for the previously partially-applied professional settlement migration
-- and other incremental schema changes.
SELECT table_name, column_name, column_type, is_nullable, column_default, ordinal_position
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND (
    (table_name = 'clinic_professional_sessions')
    OR (table_name = 'appointments' AND column_name IN (
      'attendance_outcome',
      'attendance_outcome_recorded_at',
      'attendance_outcome_recorded_by',
      'discount_code_id',
      'original_fee_rial',
      'discount_amount_rial'
    ))
    OR (table_name = 'time_slots' AND column_name = 'cancelled_reason')
    OR (table_name = 'clinic_staff_obligations' AND column_name = 'planned_disbursement_date')
    OR (table_name = 'clinic_inventory_movements' AND column_name = 'idempotency_key')
    OR (table_name = 'patient_installment_payments' AND column_name = 'idempotency_key')
    OR (table_name = 'medical_referrals' AND column_name = 'requested_services')
  )
ORDER BY table_name, ordinal_position;

-- 5) Foreign keys on affected tables. Names are included to catch MySQL's 64-character limit.
SELECT table_name, constraint_name, column_name, referenced_table_name,
       referenced_column_name, delete_rule, update_rule,
       CHAR_LENGTH(constraint_name) AS constraint_name_length
FROM information_schema.key_column_usage k
JOIN information_schema.referential_constraints r
  USING (constraint_schema, constraint_name, table_name)
WHERE k.table_schema = DATABASE()
  AND k.referenced_table_name IS NOT NULL
  AND k.table_name IN (
    'clinic_professional_sessions',
    'clinic_professional_dues',
    'clinic_staff_shift_replacements',
    'clinic_staff_contracts',
    'clinic_staff_obligations',
    'patient_documents',
    'patient_consents',
    'patient_problems',
    'appointment_payment_refunds',
    'clinic_cash_register_sessions',
    'clinic_cash_transactions',
    'clinic_discount_codes',
    'clinic_discount_redemptions',
    'clinic_shared_services',
    'clinic_shared_service_requests',
    'clinic_staff_performance_reviews',
    'clinic_business_leads',
    'notifications',
    'doctor_articles',
    'doctor_faqs',
    'support_tickets',
    'appointment_satisfaction_surveys'
  )
ORDER BY table_name, constraint_name, column_name;

-- 6) Index definitions for affected tables.
SELECT table_name, index_name, non_unique, seq_in_index, column_name
FROM information_schema.statistics
WHERE table_schema = DATABASE()
  AND table_name IN (
    'clinic_professional_sessions',
    'clinic_professional_dues',
    'clinic_staff_shift_replacements',
    'clinic_supplier_payments',
    'clinic_inventory_movements',
    'patient_installment_payments',
    'appointments',
    'doctor_socials',
    'notifications',
    'doctor_articles',
    'doctor_faqs',
    'support_tickets',
    'appointment_satisfaction_surveys'
  )
ORDER BY table_name, index_name, seq_in_index;

-- 7) Relevant menu entries; if menus table exists.
SELECT id, route_name, title, guard, is_active, show_in_sidebar, permissions, roles
FROM menus
WHERE route_name IN (
  'portal.no-show-risk.index',
  'portal.shifts.capacity',
  'portal.population-health.index',
  'portal.notifications.index',
  'portal.articles.index',
  'portal.faqs.index',
  'portal.support-tickets.index',
  'portal.satisfaction.index'
)
ORDER BY route_name;

-- 8) Potential duplicate social platform rows; read-only diagnostic.
SELECT doctor_id, social_platform_id, COUNT(*) AS duplicate_count,
       GROUP_CONCAT(id ORDER BY id) AS row_ids
FROM doctor_socials
GROUP BY doctor_id, social_platform_id
HAVING COUNT(*) > 1
ORDER BY duplicate_count DESC, doctor_id;
