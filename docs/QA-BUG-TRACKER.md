# Proneex QA Bug Tracker

> Persistent audit ledger for cross-chat continuation. This document tracks findings only; it does not authorize code changes, bug fixes, commits, or pushes.

## Audit baseline

- Repository: `javad-heidarpour/pronee_doctor`
- Default branch: `main`
- Baseline commit at file creation: `141c4564ccdde1a48701741eb72c5c728984e70c`
- Baseline commit message: `feat(finance): add idempotency key for installment receipts`
- Baseline checked: 2026-11-19
- Baseline CI run: [Quality Checks #879](https://github.com/javad-heidarpour/pronee_doctor/actions/runs/44511955557) — success
- Repository tree at audit commit `23d6ed3ed7fac58d232d0255f3236f00a555f941`: 905 files; tree response was not truncated.
- Audit status: **IN PROGRESS — no claim of full-repository completion**
- Current confirmed bug count: **7 open findings** (BUG-001, BUG-003 through BUG-008). BUG-002 was reviewed against live call paths and rejected; backend review remains partial.

## Rules for every continuation

1. Review and report only. Do not modify application code, tests, configuration, migrations, or dependencies.
2. Do not fix, commit, push, merge, or create branches unless the user explicitly authorizes a separate implementation phase.
3. Every confirmed or probable finding must cite the exact file path, line number(s), and a short current code excerpt.
4. If evidence is insufficient, record it under hypotheses/questions, not as a confirmed bug.
5. Never infer file contents from names. Files not actually read are **Not reviewed**.
6. Assign one globally unique, sequential ID per root cause: `BUG-001`, `BUG-002`, etc. Never reuse an ID or duplicate the same root cause.
7. Distinguish confidence: Confirmed / Probable / Hypothesis.
8. Distinguish severity: Critical / High / Medium / Low.
9. After each review section, update this ledger with findings, scope, and progress. Preserve existing IDs and history.
10. At the end of the audit, list all unreviewed files or clearly bounded unreviewed groups, with reasons, plus open questions.
11. A passing CI run is supporting evidence only; it is not proof that runtime behavior, business logic, authorization, or integration contracts are correct.
12. For each future fix explicitly authorized by the user, update the matching BUG entry and record the fixing commit/PR and verification evidence. Do not mark fixed without evidence.

## Status vocabulary

- `Open`: finding reported; not yet fixed.
- `Under review`: requires more evidence or cross-file validation.
- `Fix authorized`: user explicitly authorized implementation.
- `Fixed — unverified`: code change exists, but verification is incomplete.
- `Verified fixed`: regression evidence supports closure.
- `Rejected`: finding disproved; retain the rationale and evidence.
- `Deferred`: intentionally postponed by user.
- `Not reproducible`: attempted reproduction did not reproduce; retain environment and steps.

## Bug register

One configuration finding remains open; BUG-002 is retained as rejected history. Backend review is not complete.

| ID | Severity | Category | Location | Confidence | Status | Fix commit / verification |
|---|---|---|---|---|---|---|
| BUG-001 | Medium | Configuration / Authorization | `app/Providers/AuthServiceProvider.php:9,17` | Confirmed | Open | Pending |
| BUG-002 | Medium | Authorization / Type Contract | `config/auth.php:23-40`; `app/Policies/DoctorImagePolicy.php:10,15`; `app/Policies/Portal/DoctorPolicy.php:14,22,30` | Rejected: current call path passes `Doctor` directly; no live `DoctorPolicy` Gate invocation found | Rejected | Not a runtime defect on reviewed paths |
| BUG-003 | Medium | Logic / Auditability | `app/Http/Controllers/Portal/PatientCarePathwayController.php:92-100` | Confirmed | Open | Pending |
| BUG-004 | Medium | Frontend-backend contract | `app/Actions/Home/BuildPublicWorkingHoursAction.php:20-21,56-59` | Confirmed when a doctor has multiple clinics open on the same weekday | Open | Pending |
| BUG-005 | Medium | Logic / Booking / Payment | `app/Actions/Portal/Appointment/UpdateAppointmentStatusAction.php:59-81`; `app/Actions/Payments/InitiateAppointmentPaymentAction.php:38-45` | Confirmed conditional on reactivating an expired cancelled reservation | Open | Pending |
| BUG-006 | Low | Concurrency / Idempotency | `app/Http/Controllers/Portal/ClinicFinanceController.php:308-322`; `database/migrations/2026_11_19_200000_add_idempotency_key_to_patient_installment_payments.php:15` | Confirmed race; occurrence requires concurrent same-key requests on different installments | Open | Pending |
| BUG-007 | Medium | Reliability / Health Interop | `app/Console/Commands/DispatchHealthExchangeMessages.php:21-42` | Confirmed crash-recovery gap; stuck state requires interruption after claim | Open | Pending |
| BUG-008 | Medium | Authorization / Middleware | `app/Http/Middleware/DoctorAuth.php:128-135,163-240`; `routes/portal/patients.php:23-26` | Confirmed for secretary accounts with otherwise sufficient controller-level permission | Open | Pending |

## Finding template

Use this exact structure for each new finding:

### BUG-NNN — Short title

- **Severity:** Critical / High / Medium / Low
- **Category:** Syntax / Logic / Security / Frontend-backend contract / Database / Performance / Dependency / Tests / Other
- **Location:** `path/to/file.ext:line`
- **Code excerpt:**
  ```
  exact short excerpt from the reviewed revision
  ```
- **Explanation:** Why this is a defect, including relevant cross-file evidence.
- **Confidence:** Confirmed / Probable / Hypothesis
- **Suggested remedy:** One or two sentences; no implementation unless separately authorized.
- **Status:** Open
- **First observed commit:** commit SHA
- **Fix commit / verification:** Pending

## Review progress

### Repository inventory

- [x] Main branch and baseline commit identified.
- [x] Recursive Git tree fetched; 905 files; not truncated.
- [x] Latest baseline CI run inspected; both backend and frontend jobs succeeded.
- [ ] Backend files reviewed in full.
- [ ] Frontend files reviewed in full.
- [ ] Routes, middleware, policies, and authorization matrix reviewed in full.
- [ ] Migrations, models, factories, and seeders reviewed in full.
- [ ] Tests reviewed in full.
- [ ] Configuration, dependencies, and CI workflow reviewed in full.
- [ ] Cross-layer API contracts reviewed.
- [ ] Final report and unreviewed-file inventory completed.

### Files previously inspected in the preliminary pass

The following paths were fetched/read at least in part in an earlier preliminary pass. Re-fetch the relevant files at the active audit commit before citing exact line numbers or declaring them fully reviewed:

- `app/Http/Middleware/DoctorAuth.php`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `app/Providers/AuthServiceProvider.php`
- `app/Models/User.php`
- `app/Models/Doctor.php`
- `config/auth.php`
- `config/session.php`
- `routes/web.php`
- `routes/portal/portal.php`
- `routes/portal/Users.php`
- `routes/portal/patients.php`
- `routes/portal/clinics.php`
- `routes/portal/shifts.php`
- `routes/portal/working-hours.php`
- `routes/portal/Fees.php`
- `routes/portal/Profile.php`
- `routes/portal/gallery.php`
- `routes/portal/doctor-reviews.php`
- `routes/auth.php`
- `routes/DoctorRegister.php`
- `app/Models/Menu.php`
- `app/Http/Controllers/Portal/StaffManagementController.php`
- `app/Http/Controllers/Portal/UsersController.php`
- `app/Http/Controllers/Portal/SecurityAuditController.php`
- `app/Http/Controllers/Portal/PatientAccessRequestController.php`
- `app/Http/Controllers/Portal/HealthInteropController.php`
- `app/Http/Controllers/Portal/ClinicOrganizationController.php`
- `app/Http/Controllers/Portal/ClinicController.php`
- `app/Http/Controllers/Portal/AppointmentController.php`
- `app/Http/Controllers/Portal/PatientController.php`
- `app/Http/Controllers/Portal/Concerns/AuthorizesManagedUsers.php`
- `app/Http/Controllers/Portal/Concerns/HasActiveDoctor.php`
- `app/Policies/Portal/DoctorPolicy.php`
- `app/Policies/DoctorImagePolicy.php`
- `app/Actions/Portal/Auth/AuthenticatePortalUserAction.php`
- `app/Actions/Portal/Users/CreateManagedUserAction.php`
- `app/Actions/Portal/Users/UpdateManagedUserAction.php`
- `app/Actions/Portal/Users/UpdateSecretaryAssignmentsAction.php`
- `app/Actions/Portal/Users/ChangeManagedUserStatusAction.php`
- `app/Actions/Portal/Dashboard/SelectActiveDoctorAction.php`
- `app/Http/Requests/Portal/PortalLoginRequest.php`
- `app/Http/Requests/Portal/SaveManagedUserRequest.php`
- `app/Http/Requests/Portal/UpdateSecretaryAssignmentsRequest.php`
- `tests/Unit/Portal/Users/UpdateSecretaryAssignmentsActionTest.php`
- `tests/Unit/Portal/Users/BuildSecretaryAssignmentsDataActionTest.php`

**Important:** This list records prior fetch/read activity, not a declaration that every listed file was fully reviewed. Some were retrieved in large batches and need targeted re-reading before line-specific claims.


### Current audit pass — 2026-11-19

- Active source revision: `23d6ed3ed7fac58d232d0255f3236f00a555f941`.
- Repository tree: 905 files, recursive response not truncated.
- Backend scope inventory: 535 files across `app/` (367), `routes/` (21), `database/` (68), `config/` (11), `bootstrap/` (4), and `tests/` (64).
- Frontend scope: `resources/` (325 files; 172 Vue files).
- Backend status: **PARTIAL — do not claim file-by-file completion**.
- Fully reviewed backend files recorded: **78 of 535** (40 previously recorded plus 38 additional unique full-file reads in this priority pass).
- Files fully read in this pass:
  - `app/Models/User.php`
  - `app/Models/Doctor.php`
  - `app/Models/Appointment.php`
  - `app/Models/AppointmentPayment.php`
  - `app/Models/Patient.php`
  - `app/Models/PatientClinicalRecord.php`
  - `app/Models/ClinicOrganization.php`
  - `app/Http/Middleware/DoctorAuth.php`
  - `app/Http/Middleware/HandleInertiaRequests.php`
  - `app/Providers/AuthServiceProvider.php`
  - `app/Policies/Portal/DoctorPolicy.php`
  - `app/Policies/DoctorImagePolicy.php`
  - `app/Actions/Portal/Auth/AuthenticatePortalUserAction.php`
  - `app/Actions/Portal/Appointment/CreatePortalAppointmentAction.php`
  - `app/Actions/Portal/Appointment/UpdateAppointmentStatusAction.php`
  - `app/Actions/Payments/InitiateAppointmentPaymentAction.php`
  - `app/Actions/Payments/VerifyAndSettleSepPaymentAction.php`
  - `app/Actions/Payments/ProcessLocalSepPaymentCallbackAction.php`
  - `app/Services/Payments/SepPaymentService.php`
  - `app/Services/HealthInterop/HealthExchangeGateway.php`
  - `app/Services/HealthInterop/HealthExchangeOutbox.php`
  - `app/Http/Controllers/Portal/HealthInteropController.php`
  - `app/Console/Commands/DispatchHealthExchangeMessages.php`
  - `app/Models/HealthExchangeMessage.php`
  - `app/Services/Finance/ClinicAccountingService.php`
  - `app/Support/StaffPayrollCalculator.php`
  - `app/Http/Controllers/Portal/Concerns/HasActiveDoctor.php`
  - `app/Actions/Portal/Dashboard/SelectActiveDoctorAction.php`
  - `app/Models/Menu.php`
  - `config/health_interop.php`
  - `database/migrations/2026_11_19_120000_create_health_interop_and_insurance_tables.php`
  - `routes/portal/patients.php`
  - `app/Http/Controllers/Portal/Concerns/AuthorizesManagedUsers.php`
  - `app/Http/Controllers/Portal/AppointmentController.php`
  - `app/Http/Controllers/Portal/StaffObligationController.php`
  - `app/Http/Requests/Portal/SaveManagedUserRequest.php`
  - `config/auth.php`
  - `app/Http/Controllers/Portal/UsersController.php`
  - `app/Actions/Portal/Users/BuildSecretaryAssignmentsDataAction.php`
  - `app/Actions/Portal/Users/UpdateSecretaryAssignmentsAction.php`
- Partially read in this pass:
  - `app/Http/Controllers/Portal/StaffManagementController.php` — lines 1–180 only.
  - `routes/portal/portal.php` — retrieved in a multi-file response that was truncated; not considered fully read.
- The following are **not reviewed in this pass**: all other backend files among the 535-file backend scope, including the remaining Actions, Controllers, Requests, Models, Services, routes, migrations, factories, configuration, bootstrap files, and tests. The earlier preliminary-inspection list above does not count as a full review.
- No application code, test, configuration, migration, or dependency file was changed.

### Priority audit pass — payment, authorization, booking, patient/staff access

- Source revision reviewed: `main` at `23d6ed3ed7fac58d232d0255f3236f00a555f941`; audit ledger updates remain isolated to branch `qa/backend-audit-2026-11-19`.
- Full-file count after this pass: **78 / 535** (38 backend files; the separate Blade view below is outside the 535-file backend scope) backend-scope files. The 38 additional unique backend full-file reads are:
  - `app/Http/Controllers/Portal/StaffManagementController.php` (all 490 lines, assembled from complete line ranges)
  - `app/Http/Controllers/Portal/PatientController.php`
  - `routes/portal/portal.php`
  - `app/Http/Controllers/Portal/GalleryController.php`
  - `app/Models/DoctorImage.php`
  - `routes/portal/gallery.php`
  - `routes/portal/Profile.php`
  - `app/Http/Controllers/ProfileController.php`
  - `app/Http/Controllers/Portal/AuthController.php`
  - `app/Http/Controllers/Portal/Concerns/AuthorizesPortalAppointments.php`
  - `app/Http/Controllers/AppointmentPaymentController.php`
  - `routes/payment.php`
  - `routes/appointment.php`
  - `app/Actions/Payments/FindSepCallbackPaymentAction.php`
  - `app/Actions/Payments/ValidateSepCallbackTerminalAction.php`
  - `app/Actions/Payments/NormalizeSepVerificationResponseAction.php`
  - `app/Actions/Payments/RecordSepCallbackFailureAction.php`
  - `app/Actions/Payments/CancelFailedAppointmentAction.php`
  - `app/Actions/Payments/ProcessLocalSepPaymentTestAction.php`
  - `app/Actions/Payments/ReconcileKnownSepPaymentAction.php`
  - `app/Http/Controllers/Portal/PaymentReviewController.php`
  - `app/Http/Controllers/Portal/ProcurementController.php`
  - `app/Http/Controllers/Portal/StaffAttendanceController.php` (all 503 lines, assembled from complete line ranges)
  - `app/Http/Controllers/Portal/ClinicOrganizationController.php`
  - `app/Http/Controllers/Portal/ClinicFinanceController.php`
  - `database/migrations/2026_11_17_090000_add_unique_gateway_reference_to_appointment_payments.php`
  - `tests/Unit/Payments/VerifyAndSettleSepPaymentActionTest.php`
  - `tests/Unit/Payments/InitiateAppointmentPaymentActionTest.php`
  - `bootstrap/app.php`
  - `bootstrap/providers.php`
  - `routes/web.php`
  - `app/Http/Controllers/VisitController.php`
  - `app/Actions/Home/BookAppointmentAction.php`
  - `app/Actions/Portal/Appointment/ListAvailableSlotsAction.php`
  - `app/Actions/Portal/Appointment/EnsureSecretaryCanBookClinicAction.php`
  - `database/migrations/2026_11_19_100000_create_clinic_staff_attendance_tables.php`
  - `database/migrations/2026_11_19_120000_create_staff_compensation_and_replacement_tables.php`
  - `app/Models/TimeSlot.php`
- Additional full-file reads overlapping the prior 40-file inventory were not counted twice.
- Payment flow cross-check: public payment route is signed and throttled; callback supports GET/POST and the CSRF exception is explicitly configured for `payment/callback`; callback resolves a local payment by UUID, verifies SEP RefNum/terminal/amount, locks appointment then payment, and prevents RefNum reuse using both application checks and a unique DB index. Tests cover amount mismatch, terminal mismatch, repeat paid callback, token reuse, expired reservation, and review-required attempts. No new confirmed SEP bug was found in this pass.
- Booking flow cross-check: public and portal booking both serialize by doctor and lock the selected slot; active appointments are checked before reservation. No new confirmed duplicate-slot race was established in reviewed paths.
- Staff scope cross-check: attendance records are scoped by organization/clinic and self-service records by authenticated staff user; manager corrections/decisions require owner scope. Attendance daily uniqueness and payroll period uniqueness exist in migrations. No confirmed horizontal-access bug was established in these reviewed files.
- BUG-001 execution status: AuthServiceProvider is loaded through `bootstrap/providers.php`, and the registered Doctor policy namespace is demonstrably wrong. However, no live route/controller invocation of the Doctor policy was identified in the reviewed scope; classify it as a loaded but currently unproven/latent authorization configuration defect, not a demonstrated exploitable path.
- BUG-002 execution status: **Rejected.** `GalleryController::update/destroy` invoke `DoctorImagePolicy` directly and pass the `Doctor` returned by `currentDoctor()`; this matches the policy method signature. The separate `DoctorPolicy` has no identified live callsite in the reviewed routes/controllers and its registration is affected by BUG-001. The previously claimed User-to-Doctor TypeError is therefore not reachable on the inspected execution paths.
- Not reviewed in this pass: remaining payment, patient, staff, user, and authorization files outside the 39-file list; all other backend files remain pending. No application code, tests, migrations, routes, configuration, or dependencies were changed.

### BUG-001 — Doctor policy namespace mismatch

- **Severity:** Medium
- **Category:** Configuration / Authorization
- **Location:** `app/Providers/AuthServiceProvider.php:9,17`
- **Code excerpt:**
  ```php
  use App\\Policies\\DoctorPolicy;

  protected $policies = [
      Doctor::class => DoctorPolicy::class,
  ];
  ```
- **Cross-file evidence:** The only DoctorPolicy file in the active tree is `app/Policies/Portal/DoctorPolicy.php`; its namespace is `App\\Policies\\Portal`, not `App\\Policies`.
- **Explanation:** The provider registers a fully-qualified class name that does not match the class defined in the repository. When this policy is resolved, the registered class cannot be autoloaded.
- **Confidence:** Confirmed (namespace/registration mismatch); runtime impact depends on a code path invoking this policy.
- **Suggested remedy:** Make the provider import and registration match the actual policy namespace, then test the relevant authorization paths.
- **Status:** Open
- **First observed commit:** `23d6ed3ed7fac58d232d0255f3236f00a555f941`
- **Fix commit / verification:** Pending


### BUG-002 — Policy principal type does not match the authenticated User model

- **Severity:** Medium
- **Category:** Authorization / Type Contract
- **Location:** `config/auth.php:23-40`; `app/Models/User.php:19`; `app/Policies/DoctorImagePolicy.php:10,15`; `app/Policies/Portal/DoctorPolicy.php:14,22,30`
- **Code excerpt:**
  ```php
  // config/auth.php
  'web' => ['driver' => 'session', 'provider' => 'users'],
  'users' => ['driver' => 'eloquent', 'model' => User::class],

  // DoctorImagePolicy.php
  public function update(Doctor $doctor, DoctorImage $image): bool
  ```
- **Explanation:** The web guard authenticates an `App\\Models\\User` principal, while the policy methods type-hint the first argument as `App\\Models\\Doctor`. Laravel passes the authenticated principal as the first policy argument, so invoking these policies with the configured guard can raise a TypeError. The Doctor policy also has the separate namespace registration defect recorded as BUG-001.
- **Confidence:** Confirmed type-contract mismatch; runtime impact depends on a policy invocation. A direct call site for DoctorImagePolicy was not found in the reviewed search results.
- **Suggested remedy:** Change policy signatures and authorization logic to accept the actual authenticated User principal, then resolve and verify the associated doctor profile/ownership.
- **Status:** Open
- **First observed commit:** `23d6ed3ed7fac58d232d0255f3236f00a555f941`
- **Fix commit / verification:** Pending


## Open questions / evidence needed

- Confirm expected permission matrix for doctor, secretary, clinic manager, and system administrator.
- Confirm whether administrators may access patient records and health-interoperability functions across all doctors/organizations.
- Confirm tenant/organization/clinic ownership boundaries and intended cross-clinic workflows.
- Confirm expected behavior when a secretary's doctor/clinic assignments change during an active session.
- Confirm deployment/runtime versions and external integration environments when runtime-only behavior is assessed.

## Continuation protocol for future chats

At the start of a later chat:
1. Fetch this file from the latest QA audit branch (do not write to `main`); compare its base with the current `main` HEAD without modifying `main`.
2. Read the current branch HEAD and compare it with the audit baseline.
3. Preserve all existing BUG IDs and statuses.
4. Continue from the first unchecked review area; do not ask the user to repeat findings already recorded here.
5. Revalidate any old line references against the current commit before citing them.
6. Update this file only as a tracking-document operation explicitly authorized by the user; never infer permission to change application code from permission to update this ledger.

---
Last ledger update: 2026-11-19

### Clarification and continuation — payment evidence + patient/clinical/finance audit

- **Branch safety:** `main` remained at `23d6ed3ed7fac58d232d0255f3236f00a555f941`. This tracker update is confined to `qa/backend-audit-2026-11-19`.
- **Correction to the previous section:** its list contained 39 bullets because it mixed 38 backend files with `resources/views/payments/sep-redirect.blade.php`. The Blade file is out of backend scope. The correct backend total after the previous pass was **78 / 535**, not 79. The 38 backend paths are retained below; the Blade file was also fully read but is not counted.
- **Current backend full-file total:** **136 / 535** (78 prior + 58 new unique backend files in this group). No frontend completion claim is made.
- **Three controller read ranges (complete):**
  - `app/Http/Controllers/Portal/ClinicOrganizationController.php`: **282 lines**, complete range **1–282**.
  - `app/Http/Controllers/Portal/ClinicFinanceController.php`: **424 lines**, complete range **1–424**.
  - `app/Http/Controllers/Portal/ProcurementController.php`: **265 lines**, complete range **1–265**.
- **Prior pass — exact 38 backend files fully read:**
- `app/Http/Controllers/Portal/StaffManagementController.php`
- `app/Http/Controllers/Portal/PatientController.php`
- `routes/portal/portal.php`
- `app/Http/Controllers/Portal/GalleryController.php`
- `app/Models/DoctorImage.php`
- `routes/portal/gallery.php`
- `routes/portal/Profile.php`
- `app/Http/Controllers/ProfileController.php`
- `app/Http/Controllers/Portal/AuthController.php`
- `app/Http/Controllers/Portal/Concerns/AuthorizesPortalAppointments.php`
- `app/Http/Controllers/AppointmentPaymentController.php`
- `routes/payment.php`
- `routes/appointment.php`
- `app/Actions/Payments/FindSepCallbackPaymentAction.php`
- `app/Actions/Payments/ValidateSepCallbackTerminalAction.php`
- `app/Actions/Payments/NormalizeSepVerificationResponseAction.php`
- `app/Actions/Payments/RecordSepCallbackFailureAction.php`
- `app/Actions/Payments/CancelFailedAppointmentAction.php`
- `app/Actions/Payments/ProcessLocalSepPaymentTestAction.php`
- `app/Actions/Payments/ReconcileKnownSepPaymentAction.php`
- `app/Http/Controllers/Portal/PaymentReviewController.php`
- `app/Http/Controllers/Portal/ProcurementController.php`
- `app/Http/Controllers/Portal/StaffAttendanceController.php`
- `app/Http/Controllers/Portal/ClinicOrganizationController.php`
- `app/Http/Controllers/Portal/ClinicFinanceController.php`
- `database/migrations/2026_11_17_090000_add_unique_gateway_reference_to_appointment_payments.php`
- `tests/Unit/Payments/VerifyAndSettleSepPaymentActionTest.php`
- `tests/Unit/Payments/InitiateAppointmentPaymentActionTest.php`
- `bootstrap/app.php`
- `bootstrap/providers.php`
- `routes/web.php`
- `app/Http/Controllers/VisitController.php`
- `app/Actions/Home/BookAppointmentAction.php`
- `app/Actions/Portal/Appointment/ListAvailableSlotsAction.php`
- `app/Actions/Portal/Appointment/EnsureSecretaryCanBookClinicAction.php`
- `database/migrations/2026_11_19_100000_create_clinic_staff_attendance_tables.php`
- `database/migrations/2026_11_19_120000_create_staff_compensation_and_replacement_tables.php`
- `app/Models/TimeSlot.php`
- Additional out-of-scope file fully read but not counted: `resources/views/payments/sep-redirect.blade.php`.
- **Payment and booking controls — source evidence (active source revision `23d6ed3`):**
  - SEP callback execution: `routes/payment.php:23-25` maps GET/POST `/payment/callback` to the callback controller; `app/Http/Controllers/AppointmentPaymentController.php:245-250` invokes `$settlementAction->handle(...)`.
  - Payment initiation serializes the appointment and its payment attempts: `app/Actions/Payments/InitiateAppointmentPaymentAction.php:26-31` locks the appointment; `48-52` locks payment rows before checking paid/review/pending attempts.
  - Amount reconciliation: `app/Actions/Payments/VerifyAndSettleSepPaymentAction.php:123-137` compares the numeric verified amount with `$payment->amount_rial` and returns `amount_mismatch` on mismatch.
  - Settlement locks: `app/Actions/Payments/VerifyAndSettleSepPaymentAction.php:140-159` starts the transaction and locks appointment first, payment second.
  - RefNum replay/ownership: `app/Actions/Payments/VerifyAndSettleSepPaymentAction.php:173-194` rejects a changed RefNum for the same payment and checks whether the RefNum belongs to another payment.
  - Database uniqueness: `database/migrations/2026_11_17_090000_add_unique_gateway_reference_to_appointment_payments.php:36-40` adds unique index `appointment_payments_gateway_ref_unique` over `gateway_name, ref_num`.
  - Public slot locking: `app/Actions/Home/BookAppointmentAction.php:43-73` locks doctor, clinic, then selected time slot; checks active appointments and availability before creating the appointment. `110-132` continues the national-code duplicate check and creation.
  - Portal slot locking: `app/Actions/Portal/Appointment/CreatePortalAppointmentAction.php:34-41` locks doctor and selected time slot; `53-56` checks clinic relation under lock; `78-84` checks active appointment and slot status.
- **Patient / clinical / finance group — 58 new unique backend files fully read:**
- `app/Http/Controllers/PatientHealthController.php`
- `app/Http/Controllers/PatientMedicationController.php`
- `app/Http/Controllers/Portal/PatientEncounterController.php`
- `app/Http/Controllers/Portal/PatientFollowUpController.php`
- `app/Http/Controllers/Portal/PatientAccessRequestController.php`
- `app/Http/Controllers/Portal/Patient360Controller.php`
- `app/Http/Controllers/Portal/ClinicCashFlowController.php`
- `app/Http/Controllers/Portal/PatientCarePathwayController.php`
- `app/Http/Controllers/Portal/ClinicalRiskRadarController.php`
- `app/Http/Controllers/Portal/PredictivePatientController.php`
- `app/Actions/Portal/Patient/ArchivePatientClinicalRecordAction.php`
- `app/Actions/Portal/Patient/BackfillPatientsFromAppointmentsAction.php`
- `app/Actions/Portal/Patient/CreatePatientEncounterAction.php`
- `app/Actions/Portal/Patient/CreatePatientFollowUpAction.php`
- `app/Actions/Portal/Patient/ListPortalPatientsAction.php`
- `app/Actions/Portal/Patient/LoadPortalPatientRecordAction.php`
- `app/Actions/Portal/Patient/RecordPatientVitalSignAction.php`
- `app/Actions/Portal/Patient/ResolveOrCreatePatientAction.php`
- `app/Actions/Portal/Patient/RestorePatientClinicalRecordAction.php`
- `app/Actions/Portal/Patient/SavePatientClinicalRecordAction.php`
- `app/Actions/Portal/Patient/SignPatientEncounterAction.php`
- `app/Actions/Portal/Patient/UpdatePatientEncounterAction.php`
- `app/Actions/Visit/ShowVisitAction.php`
- `app/Http/Requests/Portal/SavePatientClinicalRecordRequest.php`
- `app/Http/Requests/Portal/StorePatientEncounterRequest.php`
- `app/Http/Requests/Portal/StorePatientFollowUpRequest.php`
- `app/Http/Requests/Portal/StorePatientVitalSignRequest.php`
- `app/Http/Requests/Portal/AvailableSlotsRequest.php`
- `app/Http/Requests/Portal/AvailableClinicRequest.php`
- `app/Models/PatientEncounter.php`
- `app/Models/PatientEncounterPrescription.php`
- `app/Models/PatientMedicationPlan.php`
- `app/Models/PatientMedicationDose.php`
- `app/Models/PatientVitalSign.php`
- `app/Models/PatientHealthObservation.php`
- `app/Models/PatientAccountAccess.php`
- `app/Models/PatientAccessRequest.php`
- `app/Models/PatientCarePathway.php`
- `app/Models/PatientTreatmentPlan.php`
- `app/Models/PatientInstallmentPayment.php`
- `app/Models/PatientTreatmentInstallment.php`
- `app/Models/PatientTreatmentItem.php`
- `database/migrations/2026_11_17_140000_create_patients_table_and_link_appointments.php`
- `database/migrations/2026_11_17_180000_create_patient_clinical_records_table.php`
- `database/migrations/2026_11_18_090000_create_patient_vital_signs_table.php`
- `database/migrations/2026_11_18_160000_add_related_record_to_patient_clinical_records.php`
- `database/migrations/2026_11_18_170000_add_related_allergy_record_to_patient_clinical_records.php`
- `database/migrations/2026_11_18_180000_add_structured_details_to_patient_clinical_records.php`
- `database/migrations/2026_11_18_190000_create_patient_follow_up_tables.php`
- `database/migrations/2026_11_18_200000_create_patient_encounters_tables.php`
- `database/migrations/2026_11_18_210000_create_clinic_finance_and_treatment_plans_tables.php`
- `database/migrations/2026_11_18_340000_create_patient_portal_access_tables.php`
- `database/migrations/2026_11_18_250000_link_treatment_followups_and_consent_revocation.php`
- `database/migrations/2026_11_18_260000_create_service_inventory_requirements.php`
- `database/migrations/2026_11_19_160000_add_supplier_payables_and_obligation_dates.php`
- `database/migrations/2026_11_19_180000_add_unique_supplier_payment_reference.php`
- `database/migrations/2026_11_19_190000_add_inventory_movement_idempotency_key.php`
- `database/migrations/2026_11_19_200000_add_idempotency_key_to_patient_installment_payments.php`
- **Findings:** No new confirmed bug was established in this group. Do not interpret this as proof that all patient, prescription, clinical-history, or finance code is bug-free. Existing BUG-001 remains Open; BUG-002 remains Rejected. No IDs were allocated because no new root cause met the evidence threshold.
- **Coverage notes:** Patient access, owner/caregiver paths, encounter create/update/sign, prescriptions, medication reminders, vital signs, follow-ups, care pathways, risk radar, patient 360, treatment plans, installment receipts, supplier payments, inventory movement idempotency, and accounting journal posting were inspected along the listed paths.
- **Not yet reviewed / remaining in this area:** `database/migrations/2026_11_18_350000_create_patient_health_observations_table.php`; patient-treatment item completion and its complete route-to-controller call path; remaining treatment/finance migrations and tests not listed above; any unlisted patient/clinical/finance files among the remaining backend inventory. These remain Not reviewed until fetched and read.
- **No application code, routes, tests, migrations, configuration, dependencies, or frontend files were changed.** Only this audit tracker is updated on the audit branch.


### Deep control-failure review + test audit + next app group

- **Read scope/count correction:** previous confirmed full backend count was 136/535. This pass fully read all 64 files under `tests/`; two tests were already in the 136 count, so this adds 62 unique test files. It also fully read 64 new unique `app/` files and 10 additional files (6 migrations, one service, one model, one request, one controller). New total: **272 / 535** backend files fully read. The 64 test files include support classes; this is a file count, not 64 executable test classes.
- **Test scan result:** no `markTestSkipped`, `markTestIncomplete`, or `->skip()` calls found in the 64 files. `tests/Unit/ExampleTest.php:14` contains only `$this->assertTrue(true);` and is a vacuous example, not meaningful behavioral coverage. Abstract support files `tests/Feature/Auth/AuthTestCase.php` and `tests/TestCase.php` are not counted as assertion-empty test cases.
- **Test coverage gaps (not standalone bugs):**
  - `tests/Unit/Payments/InitiateAppointmentPaymentActionTest.php` does not exercise zero/negative fee input; the action itself rejects `amount <= 0`.
  - No controller/feature test covers `ClinicFinanceController::recordPayment` idempotency conflict, cross-installment key reuse, or concurrent retries. `ClinicAccountingServiceTest` tests journal idempotency, which is a different layer.
  - Booking tests cover sequential duplicate booking, not two real concurrent transactions competing for one slot.
  - Patient action tests check cross-patient/cross-doctor scoping, but no HTTP feature test changes a patient/encounter ID in the URL as user A to try to access patient B.
  - No controller-level test exercises `PatientCarePathwayController::verifyStep`.
- **Failure-path conclusions:**
  - SEP initiation explicitly rejects non-positive amount at `app/Actions/Payments/InitiateAppointmentPaymentAction.php:68-72`; amount mismatch is rejected at `app/Actions/Payments/VerifyAndSettleSepPaymentAction.php:123-137`; callback settlement locks appointment/payment at lines 140-159 and checks RefNum reuse at 173-203. No confirmed amount-zero/negative or callback replay bypass found in inspected path.
  - Same idempotency key with different amount on the **same installment** is serialized by the installment row lock and returns 409 after the first commit (`app/Http/Controllers/Portal/ClinicFinanceController.php:308-322)). A concurrent collision across **different installment rows** can still race: each request locks a different installment, both can observe no existing key, and the unique constraint on `patient_installment_payments.idempotency_key` (`database/migrations/2026_11_19_200000_add_idempotency_key_to_patient_installment_payments.php:15`) rejects one insert. The controller does not catch/translate that unique-key exception, so the losing request can return 500; no duplicate payment is committed. This is recorded as BUG-006.
  - `UpdateAppointmentStatusAction` prevents moving an already `visited` appointment backwards and disallows setting `visited` through the generic status endpoint. No direct transition bypass from `visited` was established.
  - Patient A/B URL tampering was not demonstrated: portal encounter routes scope the encounter to the current doctor and accessible clinics; patient clinical routes scope the patient and record to the current doctor/patient. Patient-facing endpoints derive the patient from active owner access and verify access again for resource IDs.
- **Nullable/schema-contract review of the 13 models and 16 requested migrations:** no confirmed null dereference was found. Nullable fields such as `PatientMedicationPlan.end_date`, `PatientAccountAccess.permissions/expires_at/revoked_at`, `PatientEncounter.clinical_snapshot`, and optional care-pathway dates are guarded or have null-safe fallbacks in the inspected paths. A contract mismatch exists: `patient_encounters.assessment` and `plan` are nullable in migration `2026_11_18_200000_create_patient_encounters_tables.php:26-27`, while the request requires them (`StorePatientEncounterRequest.php:21-22`) and create/update actions index them directly. HTTP writes validate them, so no reachable null crash was proven; imported/legacy rows can still be null.
- **Foreign keys / onDelete:** all concrete FK relationships in the 16 requested migrations have explicit constraints and delete behavior (cascade, restrict, or null-on-delete). `source_type/source_id` in ledger/journal source references are polymorphic pairs and intentionally have no conventional FK; no other missing FK/onDelete defect was confirmed.
- **Index gap:** `patients.national_code` is queried alone at `app/Http/Controllers/PatientHealthController.php:245`, but migration `2026_11_17_140000_create_patients_table_and_link_appointments.php:23` only defines composite index `(doctor_id, national_code)`. Because the query omits the leading `doctor_id`, that composite index does not efficiently support this global lookup. This is a performance/index observation, not a confirmed data-access bypass.
- **New confirmed findings:**
  - **BUG-003 — Medium — runtime failure after care-pathway verification.** `routes/portal/patients.php:26` exposes POST `/care-pathway-steps/{step}/verify`. In `app/Http/Controllers/Portal/PatientCarePathwayController.php:94-98`, `$pathway` is assigned only inside the transaction closure; line 99 reads `$pathway->id` outside that closure. On a valid patient-reported step, the transaction commits the step/pathway update, then the undefined variable causes an error/500 during audit logging. Reachability: **confirmed**. Confidence: **high**. Fix: return the pathway (or its ID) from the transaction and use that returned value after commit.
  - **BUG-004 — Medium — public working hours hide all but one clinic per weekday.** `app/Actions/Home/BuildPublicWorkingHoursAction.php:20-21` groups by weekday **and** clinic, but lines 56-59 select only the first row for each weekday. `ShowDoctorProfileAction.php:34-36` uses this action in the public profile flow, reached through `HomeController::showDoctor` and `routes/web.php:44`. When a doctor has multiple active clinics with hours on the same weekday, the public `working_hours` payload omits all but one clinic. Reachability: **confirmed when multiple clinics share a weekday**. Confidence: **high**. Fix: preserve and return all clinic entries for each weekday rather than `firstWhere(day_of_week)`.
  - **BUG-005 — Medium — reactivated public reservation retains expired deadline.** Public booking sets `reservation_expires_at = now()->addMinutes(10)` at `app/Actions/Home/BookAppointmentAction.php:171`. The reachable portal status endpoint is `routes/portal/patients.php:30-31`; `UpdateAppointmentStatusAction.php:59-81` allows cancelled appointments to return to pending when the slot is free but only updates `status`, leaving the original expiry unchanged. Payment initiation rejects pending appointments whose deadline is past (`InitiateAppointmentPaymentAction.php:38-45`). A cancelled expired public reservation can therefore be reactivated as pending but cannot initiate payment. Reachability: **conditional, through the current status endpoint after expiry**. Confidence: **high**. Fix: either prohibit reactivation after expiry or set a new reservation deadline atomically when reactivating.
  - **BUG-006 — Low — cross-installment concurrent idempotency collision returns 500.** Route `routes/portal/portal.php:201` exposes the installment-payment endpoint. `ClinicFinanceController.php:308-322` locks only the selected installment before checking the globally unique key; two concurrent requests using the same key against different installments can both pass the lookup, then one hits the unique index at migration `2026_11_19_200000...:15`. The unique index prevents duplicate persistence, but the exception is not translated to the documented 409 conflict. Reachability: **conditional on concurrent requests targeting different installments**. Confidence: **high for the race, conditional for occurrence**. Fix: claim/lock the idempotency key in a dedicated row/table or catch the unique violation and re-read the existing payment, returning 409/success according to payload equality.
- **Prior 38-file exact list (for audit continuity):**
- `app/Http/Controllers/Portal/StaffManagementController.php`
- `app/Http/Controllers/Portal/PatientController.php`
- `routes/portal/portal.php`
- `app/Http/Controllers/Portal/GalleryController.php`
- `app/Models/DoctorImage.php`
- `routes/portal/gallery.php`
- `routes/portal/Profile.php`
- `app/Http/Controllers/ProfileController.php`
- `app/Http/Controllers/Portal/AuthController.php`
- `app/Http/Controllers/Portal/Concerns/AuthorizesPortalAppointments.php`
- `app/Http/Controllers/AppointmentPaymentController.php`
- `routes/payment.php`
- `routes/appointment.php`
- `app/Actions/Payments/FindSepCallbackPaymentAction.php`
- `app/Actions/Payments/ValidateSepCallbackTerminalAction.php`
- `app/Actions/Payments/NormalizeSepVerificationResponseAction.php`
- `app/Actions/Payments/RecordSepCallbackFailureAction.php`
- `app/Actions/Payments/CancelFailedAppointmentAction.php`
- `app/Actions/Payments/ProcessLocalSepPaymentTestAction.php`
- `app/Actions/Payments/ReconcileKnownSepPaymentAction.php`
- `app/Http/Controllers/Portal/PaymentReviewController.php`
- `app/Http/Controllers/Portal/ProcurementController.php`
- `app/Http/Controllers/Portal/StaffAttendanceController.php`
- `app/Http/Controllers/Portal/ClinicOrganizationController.php`
- `app/Http/Controllers/Portal/ClinicFinanceController.php`
- `database/migrations/2026_11_17_090000_add_unique_gateway_reference_to_appointment_payments.php`
- `tests/Unit/Payments/VerifyAndSettleSepPaymentActionTest.php`
- `tests/Unit/Payments/InitiateAppointmentPaymentActionTest.php`
- `bootstrap/app.php`
- `bootstrap/providers.php`
- `routes/web.php`
- `app/Http/Controllers/VisitController.php`
- `app/Actions/Home/BookAppointmentAction.php`
- `app/Actions/Portal/Appointment/ListAvailableSlotsAction.php`
- `app/Actions/Portal/Appointment/EnsureSecretaryCanBookClinicAction.php`
- `database/migrations/2026_11_19_100000_create_clinic_staff_attendance_tables.php`
- `database/migrations/2026_11_19_120000_create_staff_compensation_and_replacement_tables.php`
- `app/Models/TimeSlot.php`
- **The 64 test files fully read in this pass (all 64 are enumerated):**
- `tests/Feature/Auth/AuthTestCase.php`
- `tests/Feature/Auth/AuthenticationTest.php`
- `tests/Feature/Auth/EmailVerificationTest.php`
- `tests/Feature/Auth/PasswordConfirmationTest.php`
- `tests/Feature/Auth/PasswordResetTest.php`
- `tests/Feature/Auth/PasswordUpdateTest.php`
- `tests/Feature/Auth/RegistrationTest.php`
- `tests/Feature/ExampleTest.php`
- `tests/Feature/ProfileTest.php`
- `tests/Feature/Sms/SmsRoutesTest.php`
- `tests/Feature/Visit/VisitDetailsSecurityTest.php`
- `tests/TestCase.php`
- `tests/Unit/Auth/AuthenticationConfigurationTest.php`
- `tests/Unit/Auth/RegistrationDataTest.php`
- `tests/Unit/Auth/UserAuthContractTest.php`
- `tests/Unit/Database/CoreSchemaMigrationTest.php`
- `tests/Unit/ExampleTest.php`
- `tests/Unit/Finance/CashFlowForecastCalculatorTest.php`
- `tests/Unit/Finance/ClinicAccountingServiceTest.php`
- `tests/Unit/Finance/ClinicRevenueOptimizerTest.php`
- `tests/Unit/Home/BookAppointmentActionTest.php`
- `tests/Unit/Models/MedicalReferralTest.php`
- `tests/Unit/Payments/CancelFailedAppointmentActionTest.php`
- `tests/Unit/Payments/InitiateAppointmentPaymentActionTest.php`
- `tests/Unit/Payments/PaymentValidationActionsTest.php`
- `tests/Unit/Payments/RecordSepCallbackFailureActionTest.php`
- `tests/Unit/Payments/VerifyAndSettleSepPaymentActionTest.php`
- `tests/Unit/Portal/Appointment/AppointmentDataTest.php`
- `tests/Unit/Portal/Appointment/CreatePortalAppointmentActionTest.php`
- `tests/Unit/Portal/Appointment/UpdateAppointmentDescriptionActionTest.php`
- `tests/Unit/Portal/ClinicBrain/ClinicBrainAnalyzerTest.php`
- `tests/Unit/Portal/ClinicBrain/WaitlistOfferEligibilityTest.php`
- `tests/Unit/Portal/ClinicIntelligence/InventoryDemandForecasterTest.php`
- `tests/Unit/Portal/Fees/UpdateUnpaidAppointmentsFeeActionTest.php`
- `tests/Unit/Portal/Patient/ArchivePatientClinicalRecordActionTest.php`
- `tests/Unit/Portal/Patient/CreatePatientFollowUpActionTest.php`
- `tests/Unit/Portal/Patient/ListPortalPatientsActionTest.php`
- `tests/Unit/Portal/Patient/LoadPortalPatientRecordActionTest.php`
- `tests/Unit/Portal/Patient/PatientEncounterWorkflowTest.php`
- `tests/Unit/Portal/Patient/RecordPatientVitalSignActionTest.php`
- `tests/Unit/Portal/Patient/SavePatientClinicalRecordActionTest.php`
- `tests/Unit/Portal/Scheduling/SchedulingActionsTest.php`
- `tests/Unit/Portal/Scheduling/SchedulingDataTest.php`
- `tests/Unit/Portal/Scheduling/SchedulingRequestAuthorizationTest.php`
- `tests/Unit/Portal/Users/BuildSecretaryAssignmentsDataActionTest.php`
- `tests/Unit/Portal/Users/UpdateSecretaryAssignmentsActionTest.php`
- `tests/Unit/Review/StoreReviewDataTest.php`
- `tests/Unit/Services/ClinicProfessionalSettlementCalculatorTest.php`
- `tests/Unit/Services/ClinicalDecisionSupportTest.php`
- `tests/Unit/Services/ClinicalRiskRadarTest.php`
- `tests/Unit/Services/Growth/ClinicGrowthEngineTest.php`
- `tests/Unit/Services/HealthInterop/IranianNationalCodeTest.php`
- `tests/Unit/Services/MedicalReferralWorkflowTest.php`
- `tests/Unit/Services/PatientCarePathwayProgressTest.php`
- `tests/Unit/Services/PatientRetention/RetentionSegmentTest.php`
- `tests/Unit/Services/PredictivePatientEngineTest.php`
- `tests/Unit/Services/ReferralCollaborationOpportunityFinderTest.php`
- `tests/Unit/Services/ReferralNetworkIntelligenceTest.php`
- `tests/Unit/Sms/GhasedakSmsGatewayTest.php`
- `tests/Unit/Sms/LoginOtpTest.php`
- `tests/Unit/Sms/SendVisitOtpSmsActionTest.php`
- `tests/Unit/StaffAttendanceCalculatorTest.php`
- `tests/Unit/StaffObligationCalculatorTest.php`
- `tests/Unit/StaffPayrollCalculatorTest.php`
- **The 64 new app files fully read in this pass:**
- `app/Actions/Auth/BuildDoctorRegistrationFormAction.php`
- `app/Actions/Auth/LogoutAuthenticatedUserAction.php`
- `app/Actions/Auth/RegisterDoctorAction.php`
- `app/Actions/Auth/RegisterPublicUserAction.php`
- `app/Actions/Auth/ResetUserPasswordAction.php`
- `app/Actions/Auth/SendDoctorRegistrationOtpAction.php`
- `app/Actions/Auth/SendDoctorRegistrationPasswordAction.php`
- `app/Actions/Auth/SendPasswordResetLinkAction.php`
- `app/Actions/Auth/UpdateAuthenticatedUserPasswordAction.php`
- `app/Actions/Auth/VerifyDoctorRegistrationOtpAction.php`
- `app/Actions/Home/BuildPublicDoctorBookingPageAction.php`
- `app/Actions/Home/BuildPublicDoctorGalleryPageAction.php`
- `app/Actions/Home/BuildPublicDoctorReviewsPageAction.php`
- `app/Actions/Home/BuildPublicWorkingHoursAction.php`
- `app/Actions/Home/FindPublicDoctorBySlugAction.php`
- `app/Actions/Home/HomeIndexAction.php`
- `app/Actions/Home/ListActiveDoctorsAction.php`
- `app/Actions/Home/ListPublicMedicalServicesAction.php`
- `app/Actions/Home/ListPublicSurgicalProceduresAction.php`
- `app/Actions/Home/ShowDoctorProfileAction.php`
- `app/Actions/Payments/ListPaymentsRequiringReviewAction.php`
- `app/Actions/Payments/SendPaidAppointmentConfirmationAction.php`
- `app/Actions/Payments/ValidateAppointmentPaymentPhoneAction.php`
- `app/Actions/Portal/Appointment/BuildPatientIndexOptionsAction.php`
- `app/Actions/Portal/Appointment/ExportPortalAppointmentsAction.php`
- `app/Actions/Portal/Appointment/ListAvailableDatesAction.php`
- `app/Actions/Portal/Appointment/ListPortalAppointmentsAction.php`
- `app/Actions/Portal/Appointment/LoadPortalAppointmentDetailsAction.php`
- `app/Actions/Portal/Appointment/UpdateAppointmentDescriptionAction.php`
- `app/Actions/Portal/Auth/LogoutPortalUserAction.php`
- `app/Actions/Portal/Clinic/DeleteClinicAction.php`
- `app/Actions/Portal/Clinic/EnsureDoctorOwnsActiveClinicAction.php`
- `app/Actions/Portal/Clinic/ListClinicsAction.php`
- `app/Actions/Portal/Clinic/StoreClinicAction.php`
- `app/Actions/Portal/Clinic/UpdateClinicAction.php`
- `app/Actions/Portal/Dashboard/BuildDashboardAction.php`
- `app/Actions/Portal/Dashboard/BuildDoctorSelectionOptionsAction.php`
- `app/Actions/Portal/Dashboard/ResolveActiveDoctorAction.php`
- `app/Actions/Portal/DoctorProfile/ChangeDoctorPasswordAction.php`
- `app/Actions/Portal/DoctorReviews/BuildDoctorReviewStatisticsAction.php`
- `app/Actions/Portal/Users/BuildManagedUserAssignmentsPageAction.php`
- `app/Actions/Portal/Users/BuildManagedUserCreatePageAction.php`
- `app/Actions/Portal/Users/BuildManagedUserEditPageAction.php`
- `app/Actions/Portal/Users/BuildManagedUsersIndexPageAction.php`
- `app/Actions/Portal/Users/ChangeManagedUserStatusAction.php`
- `app/Actions/Portal/Users/CreateManagedUserAction.php`
- `app/Actions/Portal/Users/ListManagedUserPermissionsAction.php`
- `app/Actions/Portal/Users/ListManagedUserRolesAction.php`
- `app/Actions/Portal/Users/RestoreOrDeleteManagedUserAction.php`
- `app/Actions/Portal/Users/UpdateManagedUserAction.php`
- `app/Actions/Portal/WorkingHours/BuildWorkingHourConflictMessageAction.php`
- `app/Actions/Portal/WorkingHours/DeleteWorkingHourAction.php`
- `app/Actions/Portal/WorkingHours/ListWorkingHoursAction.php`
- `app/Actions/Portal/WorkingHours/SaveWorkingHourAction.php`
- `app/Actions/Portal/WorkingHours/ToggleWorkingHourAction.php`
- `app/Actions/Portal/Shifts/CreateTimeSlotAction.php`
- `app/Actions/Portal/Shifts/DeleteTimeSlotAction.php`
- `app/Actions/Portal/Shifts/GenerateSlotsForAllDoctorsAction.php`
- `app/Actions/Portal/Shifts/GenerateTimeSlotsAction.php`
- `app/Actions/Portal/Shifts/ListTimeSlotsAction.php`
- `app/Actions/Portal/Shifts/ToggleTimeSlotAction.php`
- `app/Actions/Portal/Shifts/UpdateTimeSlotAction.php`
- `app/DTOs/Portal/Scheduling/TimeSlotData.php`
- `app/DTOs/Portal/Scheduling/WorkingHourData.php`
- **10 additional files fully read (outside the 64 app files above):**
- `database/migrations/2026_11_18_220000_create_clinic_organization_operations_tables.php`
- `database/migrations/2026_11_18_230000_add_organization_scope_and_management_menus.php`
- `database/migrations/2026_11_18_350000_create_patient_health_observations_table.php`
- `database/migrations/2026_11_18_360000_create_patient_medication_companion_tables.php`
- `database/migrations/2026_11_18_390000_create_patient_care_pathways_tables.php`
- `database/migrations/2026_11_18_400000_add_patient_care_pathway_menu.php`
- `app/Services/PatientCarePathwayProgress.php`
- `app/Models/PatientCarePathwayStep.php`
- `app/Http/Requests/Portal/UpdateAppointmentStatusRequest.php`
- `app/Http/Controllers/HomeController.php`
- **No application code, test code, route, migration, config, dependency, or frontend file was changed.** Only this tracker is updated on the audit branch.


### Policy / middleware / commands / observers / health interop / SMS / request validation pass

- **Source revision:** `main` at `23d6ed3ed7fac58d232d0255f3236f00a555f941`.
- **Count correction:** the prior 272/535 consisted of 136 earlier unique files + 64 new `app/` files + 10 additional files + 62 newly counted tests (64 test files were read, 2 already counted). This pass adds **75 unique fully read files**; 23 other files in the targeted groups were re-read but were already in the 272. New total: **347 / 535**; remaining: **188**. The 535 inventory includes all 64 files under `tests/`; the test files are not an extra category beyond 535.
- **Targeted group coverage:** all 88 files found in the recursive-tree filter for Policies, Middleware, Console Commands, Observers, Services, Requests, and `PatientCarePathwayController` were fetched/read in full across this and earlier passes. Of those, 23 were already counted in the earlier 272. No files under `app/Jobs/` were present in the recursive tree; eight Console Command files were inspected.
- **BUG-003 severity re-evaluation:** remains **Medium**, not High on the evidence available. In `verifyStep()`, there is no outer/local `$pathway` assignment before the transaction. The only assignment is inside the closure at lines 94-96; line 99 reads it after the closure, outside PHP closure scope. The database transaction can commit the step update and possible pathway completion before the undefined-variable error. The staff member receives a 500 despite the persisted verification, and the audit event is not recorded. No controller-level test for `PatientCarePathwayController::verifyStep` was found in the 64 fully read test files; the existing `PatientCarePathwayProgressTest` exercises the service, not this controller endpoint. The route is declared once at `routes/portal/patients.php:26`; no second route/call path was identified. High would become defensible if the system's regulatory/clinical policy requires this audit event as a safety-critical control or if verification failure is shown to stop a critical patient-care process; neither was established by current repository evidence.
- **BUG-004 end-user impact:** a patient or public visitor opening the doctor's public profile sees only one clinic's hours for a weekday when multiple clinics have hours on that same weekday. The other clinic's schedule is absent from the public `working_hours` data, so patients may wrongly infer that the second branch is unavailable or may contact/book against the wrong location. No data is deleted; the defect is a misleading/incomplete public schedule. Medium retained.
- **BUG-005 end-user impact:** a doctor/secretary can change an expired, cancelled public reservation back to `pending`; the portal then shows a pending appointment, but the patient cannot complete online payment because the original `reservation_expires_at` remains in the past and payment initiation rejects it. Staff may believe the reservation was reopened while the patient encounters an expired-reservation/payment failure. Medium retained.
- **New finding — BUG-007 — stale PROCESSING health-exchange messages are never reclaimed.**
  - `app/Console/Commands/DispatchHealthExchangeMessages.php:21-28` selects only `pending`, `failed`, and `blocked` messages.
  - Lines 31-42 change a row to `processing` and commit that state before the outbound HTTP call at line 48.
  - If the PHP process/worker is terminated after the claim transaction commits and before the success/failure update at lines 49-63, the row remains `processing`. Future command runs never select it, and no lease/timeout/reaper for `processing` was found in the inspected command, model, scheduler, or service paths. The queued clinical message can remain undelivered indefinitely without an automatic retry.
  - **Severity:** Medium. **Confidence:** Confirmed for the unrecoverable state transition; occurrence depends on process interruption. Suggested remedy: add a claim lease/processing timestamp and reclaim expired claims safely, with remote idempotency preserved.
- **New finding — BUG-008 — care-pathway routes are not mapped to secretary permissions in DoctorAuth.**
  - `routes/portal/patients.php:23-26` declares route names `portal.care-pathways.index`, `portal.care-pathways.store`, and `portal.care-pathway-steps.verify`.
  - `PatientCarePathwayController::index/store/verifyStep` explicitly calls `authorizeSecretaryPermission(...)`, indicating that secretary access is intended when the corresponding permission exists.
  - `app/Http/Middleware/DoctorAuth.php:219-228` maps only prefixes such as `portal.patients.`, `portal.follow-ups.`, and `portal.encounters.`; it has no mapping for `portal.care-pathways.` or `portal.care-pathway-steps.`.
  - At lines 128-135, an unmapped route returns null and the middleware aborts 403 before the controller's permission check. Therefore, a secretary with `patients.clinical.view` / `patients.manage` still cannot view/create care pathways or verify patient-reported steps. This is an authorization/feature-access defect, not unauthorized access. **Severity:** Medium. **Confidence:** Confirmed. Suggested remedy: map these route names to the intended permission(s), retaining the controller's doctor/secretary checks.
- **Other targeted review results:** no additional confirmed bypass was found in the inspected identity/insurance/SEP paths. The health identity endpoint explicitly reports structural validation only and does not claim official identity verification. Insurance verification explicitly records `awaiting_integration`/`not_configured` and returns `insurance_approved=false`; it does not falsely approve a patient. The outbox encrypts payloads, and the gateway enforces HTTPS, an allow-listed hostname, disabled redirects, and explicit remote acceptance. SMS reminders require consent flags for installment reminders; follow-up reminders are claimed before sending and capped at three attempts. The OTP service hashes codes, limits attempts, consumes a code once, and serializes verification per phone. No Jobs directory was found; the health-exchange dispatcher command is not in `routes/console.php` scheduler, so automatic delivery additionally depends on an external/manual invocation. This scheduling gap is recorded as an operational question, not a confirmed bug without proof that the command is expected to run automatically.
- **Request-validation review:** reviewed request classes generally constrain enumerations, numeric ranges, dates, and file types. Some request `authorize()` methods only check authenticated/active user, but the route middleware/controller/action applies the actual role, doctor, clinic, and patient scope on the inspected high-risk flows. No standalone exploitable request-validation bypass was confirmed in this group. `StorePatientFollowUpRequest` accepts a `sms_consent_confirmed` boolean from the staff form; the create action only enables SMS when that flag is true. Whether this staff-entered flag constitutes adequate evidence of consent is a product/legal control question, not a proven code-level bypass from repository evidence.
- **Files newly fully read and counted in this pass (75 unique paths):**
  - `app/Console/Commands/BackfillPatientsFromAppointments.php`
  - `app/Console/Commands/CreateCollateralDueReminders.php`
  - `app/Console/Commands/ExpirePendingAppointments.php`
  - `app/Console/Commands/GenerateAppointmentSlots.php`
  - `app/Console/Commands/SendAppointmentReminders.php`
  - `app/Console/Commands/SendInstallmentReminders.php`
  - `app/Console/Commands/SendPatientFollowUpReminders.php`
  - `app/Actions/Security/RecordSecurityAuditEventAction.php`
  - `app/Services/HealthInterop/IranianNationalCode.php`
  - `app/Services/Sms/GhasedakSmsGateway.php`
  - `app/Services/LoginOtp.php`
  - `routes/console.php`
  - `app/Providers/AppServiceProvider.php`
  - `app/Http/Requests/BookAppointmentRequest.php`
  - `app/Http/Requests/Portal/StorePortalAppointmentRequest.php`
  - `app/Http/Requests/Portal/GenerateTimeSlotsRequest.php`
  - `app/Http/Requests/Portal/StoreTimeSlotRequest.php`
  - `app/Http/Requests/Portal/UpdateTimeSlotRequest.php`
  - `app/Http/Requests/Portal/SaveWorkingHourRequest.php`
  - `app/Http/Requests/Portal/UpdateAppointmentStatusRequest.php`
  - `app/Http/Requests/Portal/UpdateFeeRequest.php`
  - `app/Http/Requests/Portal/ClinicRequest.php`
  - `app/Http/Requests/Portal/ListPaymentReviewRequest.php`
  - `app/Http/Requests/Portal/ListSecurityAuditLogsRequest.php`
  - `app/Http/Requests/Portal/UpdateManagedUserStatusRequest.php`
  - `app/Http/Requests/Portal/ReplyDoctorReviewRequest.php`
  - `app/Http/Requests/Portal/StoreGalleryImagesRequest.php`
  - `app/Http/Requests/StoreReviewRequest.php`
  - `app/Http/Requests/ReplyReviewRequest.php`
  - `app/Observers/AppointmentAuditObserver.php`
  - `app/Observers/PatientAuditObserver.php`
  - `app/Observers/PatientClinicalRecordObserver.php`
  - `app/Services/AppointmentSlotGenerator.php`
  - `app/Services/ClinicBrain/ClinicBrainAnalyzer.php`
  - `app/Services/ClinicBrain/WaitlistOfferEligibility.php`
  - `app/Services/ClinicIntelligence/InventoryDemandForecaster.php`
  - `app/Services/ClinicalDecisionSupport.php`
  - `app/Services/ClinicalRiskRadar.php`
  - `app/Services/Finance/ClinicCashFlowForecastService.php`
  - `app/Services/Finance/ClinicRevenueOptimizer.php`
  - `app/Services/Growth/ClinicGrowthEngine.php`
  - `app/Services/MedicalReferralSettlementGenerator.php`
  - `app/Services/MedicalReferralWorkflow.php`
  - `app/Services/PatientCarePathwayProgress.php`
  - `app/Services/PatientHealthIntelligence.php`
  - `app/Services/PatientRetention/RetentionSegment.php`
  - `app/Services/ReferralCollaborationOpportunityFinder.php`
  - `app/Services/PredictivePatientEngine.php`
  - `app/Services/ReferralNetworkIntelligence.php`
  - `app/Services/ProfileService.php`
  - `app/Http/Requests/Portal/AvailableClinicRequest.php`
  - `app/Http/Requests/Portal/AvailableSlotsRequest.php`
  - `app/Http/Requests/Portal/ListDoctorReviewsRequest.php`
  - `app/Http/Requests/Portal/ListPortalAppointmentsRequest.php`
  - `app/Http/Requests/Portal/ListTimeSlotsRequest.php`
  - `app/Http/Requests/Portal/ListWorkingHoursRequest.php`
  - `app/Http/Requests/Portal/PortalLoginRequest.php`
  - `app/Http/Requests/Auth/ConfirmPasswordRequest.php`
  - `app/Http/Requests/Auth/LoginRequest.php`
  - `app/Http/Requests/Auth/RegisterPublicUserRequest.php`
  - `app/Http/Requests/Auth/SendDoctorRegistrationOtpRequest.php`
  - `app/Http/Requests/Auth/SendPasswordResetLinkRequest.php`
  - `app/Http/Requests/Auth/StoreDoctorRegistrationRequest.php`
  - `app/Http/Requests/Auth/StoreNewPasswordRequest.php`
  - `app/Http/Requests/Auth/UpdateAuthenticatedPasswordRequest.php`
  - `app/Http/Requests/Auth/VerifyDoctorRegistrationOtpRequest.php`
  - `app/Http/Requests/Portal/StorePostRequest.php`
  - `app/Http/Requests/Portal/UpdateAppointmentDescriptionRequest.php`
  - `app/Http/Requests/Portal/UpdateGalleryImageRequest.php`
  - `app/Http/Requests/Profile/DeleteProfileRequest.php`
  - `app/Http/Requests/Profile/UpdateProfilePasswordRequest.php`
  - `app/Http/Requests/Profile/UpdateProfileRequest.php`
  - `app/Http/Requests/ProfileUpdateRequest.php`
  - `app/Http/Requests/StoreCommentRequest.php`
  - `app/Services/ClinicProfessionalSettlementCalculator.php`
- **Targeted files re-read but not counted again** because they were already in the 272 baseline: `app/Http/Controllers/Portal/PatientCarePathwayController.php`, `app/Policies/DoctorImagePolicy.php`, `app/Policies/Portal/DoctorPolicy.php`, `app/Http/Middleware/DoctorAuth.php`, `app/Http/Middleware/HandleInertiaRequests.php`, `app/Console/Commands/DispatchHealthExchangeMessages.php`, `routes/portal/patients.php`, `app/Services/HealthInterop/HealthExchangeGateway.php`, `app/Services/HealthInterop/HealthExchangeOutbox.php`, `app/Services/Payments/SepPaymentService.php`, `app/Http/Controllers/Portal/HealthInteropController.php`, `app/Models/HealthExchangeMessage.php`, `bootstrap/app.php`, `bootstrap/providers.php`, `app/Http/Controllers/Portal/Concerns/HasActiveDoctor.php`, `routes/portal/portal.php`, `app/Http/Requests/Portal/StorePatientEncounterRequest.php`, `app/Http/Requests/Portal/SavePatientClinicalRecordRequest.php`, `app/Http/Requests/Portal/StorePatientFollowUpRequest.php`, `app/Http/Requests/Portal/StorePatientVitalSignRequest.php`, `app/Http/Requests/Portal/SaveManagedUserRequest.php`, `app/Http/Requests/Portal/UpdateSecretaryAssignmentsRequest.php`, `app/Services/Finance/ClinicAccountingService.php`.
- **Not yet reviewed in the 535-file backend inventory:** 188 files. This is a count only; the remaining inventory still needs a fresh tree-based exact path list and staged review.
- **No application code, test code, route, migration, configuration, dependency, or frontend file was changed.** Only this tracker is updated on the audit branch.
