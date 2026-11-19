# Health interoperability and insurance integration

## Scope

This module introduces a privacy-aware outbox for appointment and signed-encounter events, a structural Iranian national-code check, and a record of insurance eligibility attempts.

It deliberately does not claim that a national code has been identity-verified, that insurance is eligible, or that SEPAS accepted a clinical document merely because the local application queued it.

## Current implementation

- `IranianNationalCode` normalizes Persian/Arabic digits and validates the national-code checksum. This is a syntax check only, not identity proof.
- `patient_insurance_verifications` stores auditable insurance attempts. Payer response data is encrypted at rest.
- `health_exchange_messages` is an encrypted transactional outbox with unique idempotency key, retry state, attempt count and remote tracking identifier.
- Appointment events and signed encounter events (including medication orders) can be queued from the portal.
- `health:dispatch-exchange` dispatches pending messages only when a destination is explicitly enabled and configured.
- The portal page shows destination readiness, exchange status and insurance attempts.

## Not enabled until official technical onboarding

The SEPAS gateway envelope in `HealthExchangeGateway` is a generic transport adapter, not a claim of conformance to the Ministry's production SEPAS contract. Exact endpoint, authentication, message schema, terminology/value sets, signing requirements, acknowledgements and test-certificate workflow must be supplied by the Ministry or its authorized operator and then implemented in the destination adapter.

Insurance eligibility for Tamin and Salamat is not live yet. Their API contracts, OTP/provider authentication, insurer-specific request/response schemas, benefit rules and sandbox credentials are required. A queued/accepted transport message must never be interpreted as eligibility approval or a paid claim.

National identity verification must be connected to an authorized identity service under the applicable legal basis. The local checksum validator is not a substitute.

## Environment

All destinations default to disabled:

- `SEPAS_ENABLED=false`, `SEPAS_CONTRACT_APPROVED=false`, `SEPAS_EXCHANGE_URL=`, `SEPAS_API_TOKEN=`
- `TAMIN_INSURANCE_ENABLED=false`, `TAMIN_CONTRACT_APPROVED=false`, `TAMIN_API_URL=`, `TAMIN_API_TOKEN=`
- `SALAMAT_INSURANCE_ENABLED=false`, `SALAMAT_CONTRACT_APPROVED=false`, `SALAMAT_API_URL=`, `SALAMAT_API_TOKEN=`

Do not enable these flags with guessed endpoints or credentials. The separate `*_CONTRACT_APPROVED` gate must remain false until the official contract has been implemented, reviewed and approved. The current generic gateway requires a JSON response containing `accepted: true`; adapt it to the official contract before production activation.

## Operations

After deploying the code and running migrations:

- Open `/portal/health-interop`.
- Queue an appointment or signed encounter from the corresponding portal action.
- Run `php artisan health:dispatch-exchange --limit=50` from a scheduled worker after the official destination adapter is configured.
- Review `status`, `attempts`, `last_error`, and `remote_tracking_id`.

The queue is idempotent per destination/event/entity. If the official contract requires amendment/cancellation messages, add explicit event types and their own idempotency semantics rather than mutating a previously transmitted event.

## Production prerequisites

1. Official SEPAS integration pack, MDS and terminology mappings, test/production endpoints, certificates and acknowledgement rules.
2. Authorized health operator/provider agreement and clinic/provider identifiers.
3. Tamin and Salamat technical specifications and approved credentials.
4. Authorized identity verification contract.
5. Security review, consent/legal-basis workflow, retention policy, encryption-key backup and access audit.
6. End-to-end tests against each official sandbox before enabling any destination.