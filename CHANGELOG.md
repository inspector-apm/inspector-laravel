# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/) and this project adheres to
[Semantic Versioning](https://semver.org/).

## [5.0.0] - 2026-07-23

### Added

- Laravel AI SDK (`laravel/ai`) integration: agent prompts (`ai.prompt`) and tool invocations
  (`ai.tool`) are now reported as segments, with model, provider, and token usage context.
  New config options `inspector.ai` and `inspector.ai_body`.

### Changed

- Minimum PHP version raised from `8.1` to `8.3`.
- Minimum Laravel version raised from `9.0` to `12.0` (supports `12.x` and `13.x`).
- `orchestra/testbench` dev dependency raised to `^10.0|^11.0`.
- `larastan` dev dependency raised to `^3.0`.
- `livewire/livewire` dev dependency raised to `^3.7|^4.0`.

### Removed

- Lumen support (Lumen is end-of-life and incompatible with the Laravel 12 stack).
- The deprecated `Inspector\Laravel\InspectorLivewire` trait (Livewire v2-era; monitoring is
  automatic via the `LivewireServiceProvider`).
- Dead backward-compatibility guards: the Redis `Laravel >= 6` version check, the HTTP client
  `Laravel < 8.4` class-existence checks, the `JobReleasedAfterException` `Laravel >= 9` check, and
  the `MessageLogged` `Laravel < 5.4` fallback.

See [UPGRADE.md](UPGRADE.md) for details.

## [4.x] and earlier

See the Git history for releases prior to 5.0.0.
