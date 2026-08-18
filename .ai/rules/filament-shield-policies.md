# Filament Shield must not overwrite custom policies

`php artisan shield:generate --all` regenerates policy classes and drops custom authorization: effective-document `update()` lock, `assignTraining` / `makeEffective`, issuance/execution access checks, competency `assign`/`verify`, and GxP “deactivate instead of delete”.

Policy file generation is disabled in `config/filament-shield.php` (`policies.generate` => false). If you re-enable it, pass `--ignore-existing-policies` and restore custom method bodies afterward. Do not treat Shield CRUD stubs as the source of truth for those policies.
