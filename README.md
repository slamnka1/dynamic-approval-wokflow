# CyberAgora — Dynamic Approval Workflow

Laravel 13 + Vue 3 SPA implementation of a dynamic approval-workflow system. Admins build forms with arbitrary fields and configure per-form approval workflows; requesters submit filled forms; approvers act on items routed to them.

## Stack

- **Backend:** Laravel 13, MySQL, Sanctum (token auth), Redis (predis driver, cache decorator pattern).
- **Frontend:** Vue 3 + Vue Router 4 + Pinia + Axios + Tailwind 4, served as an SPA from a single Blade shell.
- **Architecture:** SOLID, layered — `Domain/Contracts` interfaces, `Repositories` Eloquent implementations, `Decorators` for caching, `Services` for orchestration, `Strategy` pattern for approval processors, FormRequests for input validation, API Resources for output.

## Prerequisites

- PHP 8.3+, Composer
- MySQL 8 (XAMPP/MAMP fine)
- Redis (running locally on 6379)
- Node 20+

## Quickstart

```bash
# 1. Install backend deps
composer install

# 2. Install frontend deps
npm install

# 3. Bootstrap env
cp .env.example .env
php artisan key:generate
# edit DB_DATABASE / DB_USERNAME / DB_PASSWORD in .env if needed
# create the database first:  mysql -u root -e "CREATE DATABASE cyberagora"

# 4. Migrate + seed (creates demo users + 2 demo forms)
php artisan migrate:fresh --seed

# 5. Build the SPA (or `npm run dev` for HMR)
npm run build

# 6. Serve
php artisan serve
# → http://127.0.0.1:8000
```

## Demo accounts

All passwords: `password`

| Role      | Email                       |
| --------- | --------------------------- |
| Admin     | `admin@cyberagora.test`     |
| Requester | `ali@cyberagora.test`       |
| Requester | `rahim@cyberagora.test`     |
| Approver  | `omar@cyberagora.test`      |
| Approver  | `sarra@cyberagora.test`     |
| Approver  | `salma@cyberagora.test`     |

## Demo flows

The seeder creates two forms exercising both workflow types:

1. **Expense Reimbursement** (sequential, 2-step) — Omar → Sarra.
2. **PTO Request** (threshold, 2-of-3) — pool of Omar/Sarra/Salma, any 2 approvals → approved.

Try in the browser:
1. Sign in as **ali**, submit an expense, see it pending in **My Requests**.
2. Sign in as **omar**, see it in **Pending Approvals**, approve.
3. Sign in as **sarra**, approve again — status flips to `approved`.
4. Sign in as **ali**, submit a PTO; sign in as any 2 of omar/sarra/salma to approve.
5. Any single rejection at any point → request becomes `rejected`.
6. Sign in as **admin** → **Manage Forms** → build a new form + workflow inline.

## API documentation

Full OpenAPI 3.1 spec + Postman collection live in [`/docs`](docs/):

- **Swagger UI** — start the server, visit [`http://127.0.0.1:8000/docs`](http://127.0.0.1:8000/docs).
- **OpenAPI JSON** — [`docs/openapi.json`](docs/openapi.json) (also served at `/docs/openapi.json`).
- **Postman / Insomnia** — import [`docs/postman_collection.json`](docs/postman_collection.json) (also served at `/docs/postman_collection.json`). The `Login` request auto-saves the token to `{{token}}`.

## API surface

```
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout                    (auth)
GET    /api/auth/me                        (auth)

GET    /api/forms                          (auth)         — active forms (cached, redis)
GET    /api/forms/{id}                     (auth)         — form + schema
POST   /api/forms/{form}/requests          (auth)         — submit a filled form
GET    /api/my/requests                    (auth)         — submission history
GET    /api/my/requests/{request}          (auth)         — single request + action trail

# admin
GET    /api/admin/forms                    (admin)
POST   /api/admin/forms                    (admin)
PUT    /api/admin/forms/{form}             (admin)
DELETE /api/admin/forms/{form}             (admin)
GET    /api/admin/forms/{form}/workflow    (admin)
POST   /api/admin/forms/{form}/workflow    (admin)
GET    /api/admin/users?role=approver      (admin)

# approver
GET    /api/approvals/pending              (approver)
GET    /api/approvals/past                 (approver)
GET    /api/approvals/{request}            (approver)
POST   /api/approvals/{request}/approve    (approver)
POST   /api/approvals/{request}/reject     (approver)
```

## Domain model

Three normalised tables drive the workflow:

- `approval_requests` — the **state machine** (one row per submission). Tracks `status` (pending/approved/rejected/cancelled) and `current_step_order` (sequential workflows).
- `approval_request_values` — the **submitted form payload, normalised**. One row per filled field, with **typed columns** (`value_string` / `value_number` / `value_date` / `value_boolean` / `file_path`) — only the column matching the field's type is populated. Indexable, queryable, type-safe — no JSON blobs.
- `approval_actions` — the **append-only audit log** of every approve/reject decision. Drives the state-machine transitions.

Workflow types:
- **Sequential**: ordered list of approvers; `current_step_order` advances on approve, the request becomes `approved` after the last step. Any reject → `rejected` immediately.
- **Threshold**: pool of approvers; once N (`required_approvals`) approves are recorded → `approved`. Any reject → `rejected`.

[`SequentialApprovalProcessor`](app/Services/Approval/SequentialApprovalProcessor.php) and [`ThresholdApprovalProcessor`](app/Services/Approval/ThresholdApprovalProcessor.php) are interchangeable strategies behind [`ApprovalProcessorInterface`](app/Domain/Contracts/ApprovalProcessorInterface.php); [`ApprovalProcessorFactory`](app/Services/Approval/ApprovalProcessorFactory.php) picks the right one per request.

## SOLID notes

- **S** — every service / processor / repository owns a single responsibility.
- **O** — [`CachedFormRepository`](app/Repositories/Decorators/CachedFormRepository.php) wraps the Eloquent repo via decorator. New workflow types plug in as new processors implementing [`ApprovalProcessorInterface`](app/Domain/Contracts/ApprovalProcessorInterface.php) without touching existing code.
- **L** — every `*Repository` and every `ApprovalProcessor` is freely substitutable behind its interface.
- **I** — narrow interfaces (e.g. [`DynamicFieldValidatorInterface`](app/Domain/Contracts/DynamicFieldValidatorInterface.php) exposes a single `validate()` method).
- **D** — controllers and services depend on interfaces; bindings live in [`AppServiceProvider`](app/Providers/AppServiceProvider.php).

## Caching

`forms:active:list` and `forms:show:{id}` are cached in Redis (DB 1) with 10-minute TTL via [`CachedFormRepository`](app/Repositories/Decorators/CachedFormRepository.php). Any write (`create`, `update`, `delete`) busts both keys for the affected form. Verify locally:

```bash
redis-cli -n 1 --scan --pattern "*forms*"
```

## Project layout

```
app/
├── Domain/
│   ├── Contracts/    # interfaces (DIP)
│   ├── DTOs/
│   └── Enums/
├── Repositories/
│   ├── Eloquent*Repository.php
│   └── Decorators/CachedFormRepository.php
├── Services/
│   ├── *Service.php
│   └── Approval/
│       ├── SequentialApprovalProcessor.php
│       ├── ThresholdApprovalProcessor.php
│       └── ApprovalProcessorFactory.php
├── Validation/DynamicFieldValidator.php
├── Persistence/RequestValueWriter.php
└── Http/
    ├── Controllers/Api/
    ├── Requests/
    ├── Resources/
    └── Middleware/EnsureRole.php

resources/js/
├── app.js, App.vue, router.js
├── api/client.js
├── stores/{auth,ui}.js
├── components/{DynamicForm,FieldRenderer,StatusBadge}.vue
└── views/{Login,Register,Dashboard,RequestDetail}.vue
    ├── admin/{FormsIndex,FormEditor}.vue
    ├── user/{AvailableForms,SubmitRequest,MyRequests}.vue
    └── approver/{PendingApprovals,PastApprovals}.vue
```

## License

MIT.
