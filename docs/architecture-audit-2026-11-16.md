# Architecture audit and refactoring plan
Date: 2026-11-16
Branch: main
Audited commit: f067dc67f12feb5e14220175d4b6917a12b9a5d3

## Scope and inventory

The repository tree contains:
- 33 controller-related PHP files (includes a controller concern)
- 54 Action classes
- 4 Service classes
- 3 DTO/Data classes
- 18 Form Request classes
- 1 API Resource
- 1 Policy

The codebase already has partial Action-based organization. The main issue is inconsistent adoption: some controllers delegate cleanly, while several controllers still contain query composition, validation, transactions, external I/O, state transitions, and response mapping.

## Findings from source review

### P0 — security and correctness to resolve before broad refactoring
1. `routes/sms.php` exposes `GET /sms/visit/{visit_number}` and `GET /sms/Confirmation/{visit_number}` without an authentication middleware. The handlers send SMS based on a guessable appointment ID. This is an IDOR/abuse surface and must be redesigned with authenticated authorization or signed, short-lived links and rate limiting. Do not simply add portal auth until intended public use is confirmed.
2. `PatientController::updateDescription()` updates the appointment status to `visited` while editing description/surgery details. This couples unrelated operations and bypasses the dedicated status workflow. Separate visit completion from descriptive edits and preserve the existing business rules deliberately.
3. `PatientController::updateStatus()` contains a transaction, row locks, collision checks, and TimeSlot state transitions in the controller. This belongs in an Action and should be covered by tests.
4. `Home1Controller` uses `Doctor::findOrFail(1)` in multiple public-page methods even though the routes contain a `{slug}` segment. A public page for a different doctor can therefore show the wrong doctor. Resolve the doctor by the route slug consistently before refactoring the page payloads.
5. The two portal review flows (`Portal/ReviewController` and `Portal/DoctorReviewController`) overlap. Route usage and frontend callers must be mapped before choosing one canonical flow.
6. The payment callback contains local test settlement, gateway validation, transaction orchestration, logging, and SMS dispatch. Extracting this requires regression tests around SEP callback idempotency, amount mismatch, retry states, and slot release. Avoid changing payment semantics without those tests.

### P1 — controller responsibility leakage
- `PatientController`: inline query filters, aggregate statistics, slot/date queries, payment label mapping, validation, transactions, and state transitions.
- `UsersController`: role/permission catalogs, authorization, validation, password hashing, doctor/clinic assignment validation, pivot serialization, transactions, and CRUD.
- `SmsController`: direct HTTP calls to Ghasedak, cache throttling, appointment lookup, SMS template payloads, date formatting, and response construction.
- `DoctorRegistrationController`: OTP orchestration, registration token lifecycle, validation, user/doctor creation transaction, pivot insertion, password generation, and SMS delivery.
- `Home1Controller`: many public pages, repeated eager loads, hard-coded doctor lookup, review aggregation, gallery mapping, and booking slot assembly.
- `AppointmentPaymentController`: substantial callback orchestration remains despite payment Actions and SEP Service.
- `Portal/ReviewController`: inline review query/mapping and approval/deletion state changes.
- `WorkingHourController` and `ShiftController`: still contain validation and authorization helpers; some operations already delegate to Actions.
- `Home/BookAppointmentAction` currently accepts the HTTP Request and validates inside the Action. Actions should accept validated input/DTOs, not depend on HTTP request objects.

### P2 — architecture consistency and duplication
- The project has only one Resource and one Policy despite several Inertia/API response structures and domain authorization paths. Add Resources only where a stable serialized contract is useful; do not force them onto every Inertia page.
- DTO usage is sparse and limited to profile operations plus DoctorData. Introduce DTOs for multi-field application commands/results where they clarify contracts, not for trivial scalar calls.
- `ProfileController` and `Portal/ProfileController` have separate password/profile workflows. Confirm which routes and model are authoritative, then consolidate or document the intentional distinction.
- There are duplicate review Action/request classes under `Actions/Portal/Review` and `Actions/Portal/DoctorReviews`; remove only after route and frontend usage is verified.
- Keep Form Requests responsible for input validation/authorization. Keep Policies or dedicated authorization services responsible for resource ownership. Avoid duplicating the same access checks across controllers and Actions.

## Target conventions

1. Controller: HTTP orchestration only — FormRequest, resolve actor/context, invoke Action, return response.
2. FormRequest: validation and request-level authorization.
3. Action: one application use case; accepts scalar arguments/DTOs/validated arrays, not Request objects.
4. Service: reusable domain/integration capability (Ghasedak, SEP, slot generation, etc.), not a dumping ground.
5. DTO: typed command/result for complex multi-field contracts.
6. Policy: resource ownership and ability checks.
7. Resource: stable JSON serialization boundaries; Inertia page props may use dedicated presenters/DTOs where justified.
8. Events/Jobs: notifications and other side effects that should not make a successful transaction appear failed.

## Refactoring sequence

### Phase 0 — audit and safety baseline
- Preserve this audit in the repository.
- Map every route to controller method and frontend caller.
- Record existing route names, request/response contracts, and model ownership constraints.
- Add focused regression tests before moving critical behavior.

### Phase 1 — high-risk correctness/security
- Secure SMS dispatch endpoints and remove appointment-ID-only public triggering.
- Separate appointment description editing from marking a visit complete.
- Make public doctor pages resolve the actual route slug.
- Verify secretary permissions and clinic scope on every mutation and direct-ID endpoint.
- Add tests for appointment status/slot synchronization and IDOR cases.

### Phase 2 — portal application use cases
- Extract PatientController list/filter/stats, available dates/slots, status transition, and description update into dedicated Actions and Requests.
- Extract UsersController assignment and user CRUD use cases; add DTOs for assignment commands.
- Complete Shift/WorkingHour/Clinic access scoping and move remaining validation into Form Requests.

### Phase 3 — integrations and payment
- Extract Ghasedak HTTP integration into a dedicated client/service and SMS use cases into Actions.
- Decompose SEP callback into narrowly scoped Actions/services while preserving idempotency and settlement rules.
- Add integration tests with fake HTTP/gateway responses.

### Phase 4 — public pages and registration
- Resolve doctor by slug in all public page actions.
- Move public page data composition to Actions/queries/presenters.
- Extract doctor registration/OTP flow into Form Requests, registration Action, and OTP/notification services.

### Phase 5 — cleanup and verification
- Consolidate duplicate review flows after route/frontend audit.
- Remove dead code/imports and standardize naming/return types.
- Add Resources only for actual JSON API boundaries.
- Run formatter, static analysis, PHPUnit, and build checks; report any unavailable checks honestly.

## Important execution notes
- Direct commits to `main` are authorized for this test project.
- Do not change payment, booking, SMS, or auth behavior based only on code movement.
- Before each edit, fetch the latest file from `main` and use its current blob SHA.
- This audit is source review only. No database migration, PHPUnit, static analysis, or frontend build has been run as part of the audit.
