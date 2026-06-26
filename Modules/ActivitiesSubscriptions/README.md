# Activities Subscriptions Module

This module implements the Activities Subscriptions feature for Infantry ERP following Domain-Driven Design (DDD) architecture.

## Overview

The Activities Subscriptions module manages academy contracts, offers, subscriptions, and QR-based attendance tracking for Infantry House. It supports academies contracted to provide activities (tennis, padel, archery, etc.), their offers, pricing tiers, and subscriber management.

## Architecture

The module follows DDD principles with the following structure:

```
Modules/ActivitiesSubscriptions/
├── Domain/
│   ├── Entities/          # Core business models
│   ├── ValueObjects/      # Strongly typed objects
│   ├── Repositories/      # Interfaces for persistence
│   └── Services/          # Domain services
├── Application/
│   ├── DTOs/              # Data transfer objects
│   ├── Commands/          # Actions
│   ├── Handlers/          # Command handlers
│   └── Queries/           # Read operations
├── Infrastructure/
│   ├── Persistence/       # Repository implementations
│   ├── Migrations/        # Database migrations
│   ├── Factories/         # Model factories
│   └── Services/          # External integrations
└── UI/
    ├── Http/
    │   ├── Controllers/   # REST controllers
    │   ├── Requests/      # Form validation
    │   └── Resources/     # API resources
    └── Views/             # Blade views
```

## Features

### Core Entities

1. **Academy** - Manages academy contracts and revenue sharing
2. **Offer** - Defines activity packages with pricing
3. **Subscriber** - Customer information and type classification
4. **Subscription** - Links subscribers to offers with QR codes
5. **Attendance** - Tracks check-ins and usage

### Key Features

- Academy management with revenue sharing contracts
- Flexible offer creation (class-based or hourly)
- Subscriber type-based pricing (infantry, civilian, other)
- QR code generation and validation
- Real-time attendance tracking
- Comprehensive reporting capabilities

## API Endpoints

The module provides a separate API routes file at `/routes/api/activities_subscriptions.php` with the following endpoints:

### Academies
- `GET /api/activities-subscriptions/academies` - List academies
- `POST /api/activities-subscriptions/academies` - Create academy
- `GET /api/activities-subscriptions/academies/{id}` - Get academy
- `PUT /api/activities-subscriptions/academies/{id}` - Update academy
- `DELETE /api/activities-subscriptions/academies/{id}` - Delete academy

### Offers
- `GET /api/activities-subscriptions/offers` - List offers
- `POST /api/activities-subscriptions/offers` - Create offer
- `GET /api/activities-subscriptions/offers/{id}` - Get offer
- `PUT /api/activities-subscriptions/offers/{id}` - Update offer
- `DELETE /api/activities-subscriptions/offers/{id}` - Delete offer
- `GET /api/activities-subscriptions/offers/academy/{academyId}` - Get offers by academy

### Subscribers
- `GET /api/activities-subscriptions/subscribers` - List subscribers
- `POST /api/activities-subscriptions/subscribers` - Create subscriber
- `GET /api/activities-subscriptions/subscribers/{id}` - Get subscriber
- `PUT /api/activities-subscriptions/subscribers/{id}` - Update subscriber
- `DELETE /api/activities-subscriptions/subscribers/{id}` - Delete subscriber
- `POST /api/activities-subscriptions/subscribers/search-by-identifier` - Search by ID

### Subscriptions
- `GET /api/activities-subscriptions/subscriptions` - List subscriptions
- `POST /api/activities-subscriptions/subscriptions` - Create subscription
- `GET /api/activities-subscriptions/subscriptions/{id}` - Get subscription
- `PUT /api/activities-subscriptions/subscriptions/{id}` - Update subscription
- `DELETE /api/activities-subscriptions/subscriptions/{id}` - Delete subscription
- `GET /api/activities-subscriptions/subscriptions/subscriber/{subscriberId}` - Get by subscriber
- `GET /api/activities-subscriptions/subscriptions/academy/{academyId}` - Get by academy
- `POST /api/activities-subscriptions/subscriptions/{id}/qr` - Generate QR code

### Check-in
- `POST /api/activities-subscriptions/check-in/` - Check in with QR code
- `GET /api/activities-subscriptions/check-in/attendance/{subscriptionId}` - Get attendance history
- `GET /api/activities-subscriptions/check-in/attendance-by-date-range` - Get attendance by date range
- `GET /api/activities-subscriptions/check-in/stats` - Get attendance statistics

## Database Schema

The module includes migrations for the following tables:

1. **academies** - Academy information and contracts
2. **offers** - Activity packages and pricing
3. **subscribers** - Customer information
4. **subscriptions** - Subscription details and QR codes
5. **attendance** - Check-in records

## Installation

1. The module is automatically created using `php artisan module:make ActivitiesSubscriptions`
2. Run migrations: `php artisan migrate`
3. The API routes are automatically loaded from `/routes/api/activities_subscriptions.php`

## Usage Examples

### Creating an Academy

```php
POST /api/activities-subscriptions/academies
{
    "name": "Tennis Academy",
    "contracted": true,
    "revenue_share_infantry": 60,
    "revenue_share_academy": 40,
    "working_days": ["Monday", "Wednesday", "Friday"],
    "status": "active"
}
```

### Creating an Offer

```php
POST /api/activities-subscriptions/offers
{
    "academy_id": 1,
    "name": "Monthly Tennis Package",
    "num_classes": 12,
    "duration_start": "2024-01-01",
    "duration_end": "2024-01-31",
    "available_days": ["Monday", "Wednesday", "Friday"],
    "price_infantry": 200.00,
    "price_civilian": 300.00,
    "price_other": 400.00
}
```

### Creating a Subscription

```php
POST /api/activities-subscriptions/subscriptions
{
    "subscriber_id": 1,
    "offer_id": 1,
    "academy_id": 1,
    "start_date": "2024-01-01",
    "end_date": "2024-01-31",
    "chosen_days": ["Monday", "Wednesday"]
}
```

### Checking In

```php
POST /api/activities-subscriptions/check-in/
{
    "qr_code": "encrypted_qr_code_value",
    "day_of_week": "Monday"
}
```

## Security

- QR codes are encrypted using Laravel's app key
- All API endpoints include proper validation
- Revenue sharing percentages are validated to total 100%
- Subscriber types are strictly validated

## Future Enhancements

- Financial reporting integration
- Advanced analytics and dashboards
- Mobile app integration
- Automated billing and payment processing
- Multi-language support
