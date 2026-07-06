# Architecture

## Clean Architecture Layers

```
┌─────────────────────────────────────────────┐
│              Presentation Layer              │
│  Controllers → Resources → Middleware        │
├─────────────────────────────────────────────┤
│              Application Layer               │
│  Services → DTOs → Jobs → Events             │
├─────────────────────────────────────────────┤
│               Domain Layer                   │
│  Models → Enums → Policies → Traits          │
├─────────────────────────────────────────────┤
│            Infrastructure Layer              │
│  Repositories → Database → Cache → Queue     │
└─────────────────────────────────────────────┘
```

## Multi-Tenancy

Each office is isolated via `office_id` on all tenant-scoped tables:

- **Global Scope**: `BelongsToOffice` trait auto-filters queries by authenticated user's office
- **Middleware**: `EnsureOfficeIsActive` validates office status
- **Super Admin**: Bypasses tenant scope for platform management

## Database Schema (ERD)

```
┌──────────────┐     ┌──────────────┐     ┌──────────────────┐
│ subscription │────<│ subscriptions│>────│     offices      │
│    _plans    │     └──────────────┘     └────────┬─────────┘
└──────────────┘                                    │
                                                    │ 1:N
┌──────────────┐     ┌──────────────┐     ┌────────┴─────────┐
│   wallets    │────<│   payments   │     │      users       │
└──────┬───────┘     └──────────────┘     └────────┬─────────┘
       │                                            │
┌──────┴───────┐                          ┌────────┴─────────┐
│wallet_trans  │                          │    properties    │
└──────────────┘                          └────────┬─────────┘
                                                   │
                              ┌────────────────────┼────────────────┐
                              │                    │                │
                    ┌─────────┴──────┐  ┌─────────┴──────┐  ┌──────┴───────┐
                    │property_media  │  │property_favs   │  │saved_searches│
                    └────────────────┘  └────────────────┘  └──────────────┘

┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│  activities  │  │    tasks     │  │  audit_logs  │  │   devices    │
└──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘

┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│   tickets    │  │announcements │  │feature_flags │  │  otp_codes   │
└──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘
```

## Property Permission Model

| Permission | Consultant View | Manager View |
|-----------|----------------|--------------|
| private | Own properties only | All |
| team | All team properties | All |
| office | All office properties | All |
| manager_only | Hidden | All |

## Authentication Flow

```
Mobile Input → Send OTP → SMS Gateway → Verify OTP → Sanctum Token → API Access
                                              ↓
                                        Device Registration
```

## Subscription Flow

```
Select Plan → Choose Gateway → Payment → Verify → Activate Subscription
                  ├── ZarinPal (redirect)
                  ├── Cafe Bazaar (in-app)
                  └── Wallet (instant)
```

## Caching Strategy

- **Redis**: Session, OTP rate limiting, query cache
- **Property counts**: Cached per office, invalidated on CRUD
- **Dashboard stats**: 5-minute TTL cache

## Queue Jobs

- `SendOtpSmsJob` — OTP delivery via SMS gateway
- Property expiration checks (scheduled)
- Saved search notifications (scheduled)
- Audit log processing

## Scalability

- Horizontal scaling via Docker replicas
- Database read replicas for search queries
- Redis cluster for session/cache
- S3-compatible storage for media files
- CDN for static assets and images

## Security

- Sanctum token-based API authentication
- OTP rate limiting (2-minute cooldown)
- Tenant isolation at query level
- Audit logging for all mutations
- HTTPS enforced in production

## Plugin System (Ready)

Architecture supports future plugins via:
- Feature flags per office/plan
- Event-driven hooks (property.created, user.invited)
- API versioning (/api/v1, /api/v2)
- White-label settings in office.settings JSON
