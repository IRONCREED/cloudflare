# Founding Profile — Cloudflare Operations

Status: `0.2.0-draft`, standalone candidate pending release ratification.

Identifier: `ic-cf-profile-001`.

Date: 2026-09-06.

## P01. Identity and scope

Project name: **Cloudflare Operations**. Standalone source: `https://github.com/IRONCREED/cloudflare`. Case 001 was copied from the `at-work/cloudflare/` subtree of `pan-canon/TheWorldOfCanon` under the explicit user instruction of 6 September 2026. The original subtree remains intact. The publication line is the IRONCREED series “The Great and Terrible Cloudflare”.

The project covers reusable tooling, examples, tests, evidence formats, and editorial technical material for controlled mass operations over Cloudflare policy. Case 001 is limited to zone-level WAF Custom Rules and fleet-safe desired-state operations.

## P02. External order and licensing

Mandatory law, GitHub terms, Cloudflare terms, and third-party licences retain force in their own scope. The project licensing map is declared by root `LICENSE.md` and follows the pinned `repository-licensing-policy` submodule at commit `6e4c2627717c079827ed4aa9044a5346b3ea3ddb`.

## P03. Sources of intent

Primary intent is set by the project authors/maintainers. Production incidents, operator requirements, Cloudflare platform behaviour, security evidence, and publication requirements may propose changes but do not autonomously create policy.

## P04. Canonical sources

Within this standalone repository, canonical project sources are the root governance files, `cases/*/src`, `cases/*/bin`, tests, and explicitly labelled example configuration. Runtime snapshots, secrets, client inventories, and production overlays are not canonical repository content and must remain outside Git.

The pinned Code Constitution source is the `code-constitution/` submodule at commit `220dc9c286ae06f8b6ed60cdda75112eed0408ed`.

## P05. Roles

Human maintainers hold editorial, normative, security-write, and release authority. CI, scripts, LLMs, and other automation are execution instruments only and acquire no authority to create or apply security policy independently.

## P06. Norm-making

A new reusable policy, change in write behaviour, relaxation of a safety guardrail, or expansion to another Cloudflare product requires an explicit reviewed change. Example policy may be changed as teaching material, but must remain clearly labelled as non-universal.

## P07. Execution

Read-only compilation, planning, export, and audit may be automated. Security writes require an explicit operator action and a credential with write scope. Fleet writes remain sequential unless a later reviewed act changes this rule.

## P08. Disputes and defects

Conflicting interpretations are resolved in favour of the last reviewed source and preserved evidence. A suspected destructive or over-broad rule blocks rollout until reviewed.

## P09. Jurisdictions

The initial domains are: governance, reusable fleet engine, Case 001 examples, tests, and runtime evidence formats. Future cases may add new domains without retroactively changing Case 001 semantics.

## P10. Ratification

This Profile records the authorized relocation as a candidate. Release ratification requires review of the prepared repository, pinned normative sources, check results, and an explicit human release decision. Copying files and automatic checks do not assert that decision.

## P11. Amendment

Changes to authority, secret handling, destructive write semantics, licensing, canonical-source boundaries, or the official repository require an explicit Profile amendment.

## P12. Protected provisions

Protected conditions are: no secrets in Git; no automatic security writes by analytical/LLM layers; dry-run as default; explicit `--apply`; preservation of unmanaged rules; backup before write; post-write readback; sequential fleet mutation; and distinction between example policy and recommended production policy.

## P13. Resources and obligations

Protected resources include API tokens, zone identifiers, private client overlays, production snapshots, Git history, and future CI secrets. Real client configuration is not publication material unless separately sanitised and approved.

## P14. Emergency order

On suspected credential compromise, unintended rule deployment, loss of unmanaged rules, or mismatch between desired and live state: stop rollout, revoke/rotate affected credentials, preserve evidence, and restore from the last verified pre-write snapshot where appropriate.

## P15. Evidence and registries

Evidence includes Git commits, compiled desired state, plans, pre/post snapshots, audit reports, test results, and explicit operator actions. Secrets are excluded from evidence artifacts.

## P16. Technical enforcement

At minimum, Case 001 must pass PHP syntax checks and its local fixture tests before publication. Writes are never required to run tests. A failure in checks blocks promotion of the candidate revision.

## P17. Transition and restoration

The source subtree is retained for restoration. The migration attestation records its exact tree and blob revisions, the standalone destination, and the preserved submodule revisions. Case identifiers and licensing declarations remain stable; production Cloudflare state is outside this migration.

## P18. Unwritten order

No recurring operational habit silently becomes policy. Material security behaviour, exceptions, and write procedures must be written before they are treated as binding project rules.
