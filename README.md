# Cloudflare Operations

Source for the IRONCREED article series about operating Cloudflare as a policy plane across multiple zones.

This directory is intentionally a monorepo: each case/article receives its own self-contained implementation under `cases/`. At this stage only the first case exists.

## Cases

- [`001-crawler-surface-policy`](cases/001-crawler-surface-policy/) — reusable zone-level WAF Custom Rules fleet workflow derived from a production crawler incident.

## Governance

The project adopts the pinned Code Constitution source and the shared Repository Licensing Policy through Git submodules. The standalone source is [IRONCREED/cloudflare](https://github.com/IRONCREED/cloudflare). Root `.gitmodules` declares both submodules with their original pinned revisions. The relocation record is in `governance/attestations/standalone-migration-2026-09-06.json`. Governance ratification remains a separate human release decision.

- [`CONSTITUTION.md`](CONSTITUTION.md)
- [`governance/PROFILE.md`](governance/PROFILE.md)
- [`LICENSE.md`](LICENSE.md)
- `code-constitution/` — pinned submodule
- `repository-licensing-policy/` — pinned submodule

## Scope

This monorepo is about **mass operations over Cloudflare rules**, not complete Cloudflare account management. The first case intentionally limits itself to zone-level WAF Custom Rules in the `http_request_firewall_custom` phase: compile desired policy, inspect live state, plan, explicitly apply, export evidence, and audit drift across an explicit zone inventory.

DNS, billing, account membership, Workers, Zero Trust, cache rules, origin configuration, and unrelated Cloudflare products are out of scope for Case 001.

## Safety model

- dry-run/read-only commands are the default;
- writes require an explicit `--apply` path;
- fleet writes are sequential, not concurrent;
- unmanaged rules are preserved;
- pre-write and post-write snapshots are stored outside Git;
- read and write tokens are separate environment variables;
- no real tokens, client domains, zone IDs, production snapshots, or private hostname overlays belong in this repository.

The files under `examples/` are demonstrations, **not universal security recommendations**. Country policy, bot policy, crawler policy, and scanner/User-Agent policy always require site-specific review.

Cloudflare and related marks belong to Cloudflare, Inc. This independent repository is not affiliated with or endorsed by Cloudflare.

## Install

```bash
git clone https://github.com/IRONCREED/cloudflare.git
cd cloudflare
git submodule update --init --recursive
cd cases/001-crawler-surface-policy
php tests/run.php
```

The engine can run its offline tests without downloading normative submodules. Submodule access follows the upstream repositories’ access policies.
