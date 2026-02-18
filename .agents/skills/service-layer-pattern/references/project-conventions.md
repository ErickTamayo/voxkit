# Project Conventions

Use these conventions in this repository.

## File placement

- Services: `app/Services/`
- GraphQL mutation/query classes: `app/GraphQL/Mutations/` and `app/GraphQL/Queries/`
- REST controllers: `app/Http/Controllers/`
- Feature tests: `tests/Feature/`

## Current service examples

- `app/Services/AuthenticationService.php`
- `app/Services/AuthCodeService.php`

## Edge-layer examples

- `app/GraphQL/Mutations/AuthCodeMutations.php`

## Practical rules

- Keep rate-limit/auth/business orchestration in services.
- Keep GraphQL mutation classes focused on argument mapping and union response shaping.
- When logic grows, move it out of mutation/controller classes into a dedicated service.
