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
- Current confirmed bug count: **2** (BUG-001 and BUG-002; backend review is still partial).

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

One validated finding has been entered; backend review is not complete.

| ID | Severity | Category | Location | Confidence | Status | Fix commit / verification |
|---|---|---|---|---|---|---|
| BUG-001 | Medium | Configuration / Authorization | `app/Providers/AuthServiceProvider.php:7,15` | Confirmed | Open | Pending |
| BUG-002 | Medium | Authorization / Type Contract | `config/auth.php:23-40`; `app/Policies/DoctorImagePolicy.php:10,15`; `app/Policies/Portal/DoctorPolicy.php:14,22,30` | Confirmed (type mismatch; runtime conditional) | Open | Pending |

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
- [x] Recursive Git tree fetched; 904 files; not truncated.
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
- Fully reviewed backend files recorded: **40 of 535** (32 in the preceding audit pass plus 8 additional unique full-file reads in this pass).
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

### BUG-001 — Doctor policy namespace mismatch

- **Severity:** Medium
- **Category:** Configuration / Authorization
- **Location:** `app/Providers/AuthServiceProvider.php:7,15`
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
