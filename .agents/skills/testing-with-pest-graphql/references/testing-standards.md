# Testing Standards

Use these conventions in this repository.

## Test layout

- Feature tests: `tests/Feature/`
- GraphQL feature tests: `tests/Feature/GraphQL/`
- Unit tests: `tests/Unit/`

## Current GraphQL test examples

- `tests/Feature/GraphQL/AuthCodeMutationsTest.php`
- `tests/Feature/GraphQL/MeQueryTest.php`

## Commands

- Run focused GraphQL tests: `php artisan test --compact tests/Feature/GraphQL/AuthCodeMutationsTest.php`
- Run full suite: `php artisan test --compact`

## Practical assertions

- Assert `__typename` for union branch behavior.
- Assert auth behavior for session and token flows.
- Assert database writes for auth code issuance and token creation.
