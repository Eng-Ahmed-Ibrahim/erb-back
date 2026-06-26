# MembershipCards Module

منظومة عضويات الأنشطة لضباط المشاة وعائلاتهم

## Overview

This module manages officer memberships, beneficiaries, subscriptions, fee plans, and NFC membership card issuance for infantry officers and their families.

## Architecture

The module follows Domain-Driven Design (DDD) patterns:

```
MembershipCards/
├── App/Providers/          # Laravel service providers
├── Application/            # Application layer (CQRS)
│   ├── Commands/           # Write commands
│   ├── DTOs/               # Data Transfer Objects
│   ├── Handlers/           # Command handlers
│   └── Queries/            # Read queries
├── Domain/                 # Domain layer
│   ├── Entities/           # Domain entities
│   ├── Repositories/       # Repository interfaces
│   ├── Services/           # Domain services
│   └── ValueObjects/       # Value objects
├── Infrastructure/         # Infrastructure layer
│   ├── Migrations/         # Database migrations
│   └── Persistence/        # Eloquent repositories
├── UI/Http/Controllers/    # API controllers
├── config/                 # Module configuration
└── routes/                 # API & web routes
```

## Database Tables

- `mc_officers` - Officer profiles
- `mc_beneficiaries` - Family members
- `mc_fee_plans` - Fee structures
- `mc_subscriptions` - Active subscriptions
- `mc_membership_cards` - NFC card records

## API Endpoints

### Officers
- `GET /api/v1/membership-cards/officers` - List officers
- `POST /api/v1/membership-cards/officers` - Create officer
- `GET /api/v1/membership-cards/officers/{id}` - Get officer
- `PUT /api/v1/membership-cards/officers/{id}` - Update officer
- `DELETE /api/v1/membership-cards/officers/{id}` - Delete officer
- `GET /api/v1/membership-cards/officers/find` - Find by identifier

### Beneficiaries (nested under officers)
- `GET /api/v1/membership-cards/officers/{officerId}/beneficiaries` - List beneficiaries
- `POST /api/v1/membership-cards/officers/{officerId}/beneficiaries` - Add beneficiary
- `GET /api/v1/membership-cards/officers/{officerId}/beneficiaries/{id}` - Get beneficiary
- `PUT /api/v1/membership-cards/officers/{officerId}/beneficiaries/{id}` - Update beneficiary
- `DELETE /api/v1/membership-cards/officers/{officerId}/beneficiaries/{id}` - Delete beneficiary

### Fee Plans
- `GET /api/v1/membership-cards/fee-plans` - List fee plans
- `POST /api/v1/membership-cards/fee-plans` - Create fee plan
- `GET /api/v1/membership-cards/fee-plans/{id}` - Get fee plan
- `PUT /api/v1/membership-cards/fee-plans/{id}` - Update fee plan
- `DELETE /api/v1/membership-cards/fee-plans/{id}` - Delete fee plan
- `GET /api/v1/membership-cards/fee-plans/type/{type}` - Get by beneficiary type

### Subscriptions
- `GET /api/v1/membership-cards/subscriptions` - List subscriptions
- `POST /api/v1/membership-cards/subscriptions` - Create subscription
- `GET /api/v1/membership-cards/subscriptions/{id}` - Get subscription
- `DELETE /api/v1/membership-cards/subscriptions/{id}` - Delete subscription
- `POST /api/v1/membership-cards/subscriptions/{id}/suspend` - Suspend
- `POST /api/v1/membership-cards/subscriptions/{id}/activate` - Activate
- `GET /api/v1/membership-cards/subscriptions/expiring` - Expiring subscriptions
- `POST /api/v1/membership-cards/subscriptions/calculate-fees` - Calculate fees

### Membership Cards
- `GET /api/v1/membership-cards/cards` - List cards
- `POST /api/v1/membership-cards/cards` - Issue card
- `GET /api/v1/membership-cards/cards/{id}` - Get card
- `POST /api/v1/membership-cards/cards/validate` - Validate card by UID
- `POST /api/v1/membership-cards/cards/{id}/print` - Mark as printed
- `POST /api/v1/membership-cards/cards/{id}/encode` - Mark as encoded
- `POST /api/v1/membership-cards/cards/{id}/revoke` - Revoke card
- `GET /api/v1/membership-cards/cards/expiring` - Expiring cards
- `GET /api/v1/membership-cards/cards/not-printed` - Cards not printed
- `GET /api/v1/membership-cards/cards/not-encoded` - Cards not encoded

## Installation

1. Enable the module in `modules_statuses.json`:
```json
{
    "MembershipCards": true
}
```

2. Run migrations:
```bash
php artisan migrate
```

3. Clear cache:
```bash
php artisan cache:clear
php artisan config:clear
```

## Configuration

Configuration options are available in `config/membershipcards.php`:

- `default_subscription_duration` - Default subscription duration in months
- `card.type` - Card type (MIFARE)
- `card.encoder` - Card encoder model
- `relationship_types` - Valid relationship types
- `ranks` - Officer rank options
- `weapon_types` - Weapon type options

