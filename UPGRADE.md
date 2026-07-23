# Upgrade Guide

## Upgrading to 5.0.0

### Requirements

Version 5.0.0 raises the supported runtime floor:

- **PHP** `>= 8.3` (was `>= 8.1`)
- **Laravel** `>= 12.0` (was `>= 9.0`; supports `12.x` and `13.x`)

If your application runs an older PHP or Laravel version, keep using the latest `4.x` release.

### Breaking changes

- **Lumen support removed.** Lumen is end-of-life and is not maintained against the Laravel 12
  ecosystem. If you were on Lumen, migrate to Laravel or stay on `4.x`.

- **Deprecated `InspectorLivewire` trait removed.** The `Inspector\Laravel\InspectorLivewire` trait
  (the old Livewire v2 manual integration) has been deleted. Livewire monitoring is automatic —
  simply install Livewire `^3.7` or `^4.0`; no trait or manual boot is needed. Remove any
  `use InspectorLivewire;` statements and references to its hook methods.

- **`laravel/ai` is an optional integration.** Agent and tool monitoring activates automatically
  when the `laravel/ai` package is installed in your application. No configuration is required; set
  `INSPECTOR_AI=false` to disable it, or `INSPECTOR_AI_BODY=false` to omit prompt/response text from
  the reported segments.

### No other behavioral changes

Aside from dropping the unsupported old runtimes and the items above, monitoring behavior is
unchanged.
