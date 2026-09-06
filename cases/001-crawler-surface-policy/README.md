# Case 001 — Crawler Surface Policy and fleet-safe WAF operations

This case extracts the reusable mass-operation mechanics from an internal production workflow and removes client-specific state.

The implementation manages only the zone-level Cloudflare WAF Custom Rules phase `http_request_firewall_custom`. It does not attempt to become a general Cloudflare account manager.

## What is reusable here

The engine implements this control loop:

```text
explicit inventory
  -> compile desired managed rules per zone
  -> read live phase entry point
  -> preserve unmanaged rules
  -> plan/diff
  -> explicit apply
  -> pre/post snapshot
  -> audit drift
  -> sequential fleet rollout
```

The example policies demonstrate six classes of rules used in the source workflow:

1. bot/crawler policy;
2. crawler access to transactional surfaces;
3. country/continent policy;
4. suspicious scanner/User-Agent policy;
5. optional named-IP-list policy;
6. hostname-specific inline rules.

They are **examples only**. Copying country blocks, bot blocks, or User-Agent rules without reviewing a real site can damage availability, indexing, integrations, or legitimate automation.

## Requirements

- PHP 8.1+ with cURL;
- a Cloudflare API token for read-only operations;
- a separate write-capable token for `--apply` operations;
- explicit zone inventory.

Recommended local environment variables:

```bash
export CLOUDFLARE_READ_TOKEN='...'
export CLOUDFLARE_WRITE_TOKEN='...'
```

Do not store tokens in this repository.

## Configuration

Copy `examples/` to an ignored local directory before using real zones:

```bash
cp -R examples local
```

Then replace the reserved demonstration domains such as `domain-01.example` with your actual zones and review every policy.

The example inventory also sets `max_physical_rules: 5` to demonstrate a plan-budget guardrail. This value is plan- and account-dependent; change or remove it after checking the actual zone limits.

The default config root is `examples/`. For real use:

```bash
export CFOPS_CONFIG_DIR="$PWD/local"
export CFOPS_RUNTIME_DIR="$PWD/runtime"
```

## Read-only commands

Compile one zone:

```bash
php bin/compile.php --zone=domain-02.example
```

Plan against Cloudflare:

```bash
php bin/plan.php --zone=example.com
```

Audit all configured zones:

```bash
php bin/audit.php
```

Export current phase state:

```bash
php bin/export.php
```

Fleet dry-run:

```bash
php bin/rollout.php
```

## Explicit write

One zone:

```bash
php bin/apply.php --zone=example.com --apply
```

Fleet, sequential and stop-on-error:

```bash
php bin/rollout.php --apply
```

`rollout.php` never mutates zones concurrently. Cloudflare recommends avoiding concurrent updates to the same ruleset; this implementation intentionally keeps the write path serial and updates the phase entry point as one ruleset operation.

## Managed versus unmanaged rules

Only rules whose description starts with the configured `managed_prefix` are owned by this tool. During apply:

- existing managed rules are replaced by the freshly compiled desired managed rules;
- existing unmanaged rules are preserved in their live order;
- desired managed rules replace the existing managed block at its first previous position, or append when no managed block exists;
- apply refuses a result above the configured physical-rule budget;
- the final entry point is written once;
- a snapshot is taken immediately before and after the write.

This is a deliberately conservative ownership boundary. If another system also writes rules using the same prefix, the boundary is no longer safe.

## Zone entry point creation

If the `http_request_firewall_custom` entry point does not yet exist, `apply.php` creates a zone ruleset of kind `zone` for that phase. Otherwise it updates the existing phase entry point.

## Tests

```bash
php tests/run.php
```

Tests are fixture-only and require no Cloudflare token.

## Verification boundaries

Offline tests cover configuration compilation and conservative drift comparison. They do not prove live Cloudflare expression validation or edge request behavior. Production smoke results reported in the article belong to the earlier private pilot. Run a site-specific edge smoke after any deployment.

Use one writer per zone. Sequential rollout within this process provides no lock against dashboard changes or another process. Unmanaged rules retain their relative order; collapsing interleaved managed rules may move their position relative to that managed block and requires plan review. The compiler supports the demonstrated simple block/challenge rules; do not assume arbitrary action parameters are retained for managed policies.

User-Agent fallbacks are spoofable classification signals. The crawler example uses literal English paths and substring query matching; review localized shop slugs, encoded requests, and query values on each hostname. The named legacy bot categories remain supported according to Cloudflare documentation checked on 6 September 2026.

Expression comparison preserves whitespace inside quoted values. Formatting differences can produce a conservative change report. Deployment directories carry a random suffix to avoid overwriting evidence from repeated writes within one second.
