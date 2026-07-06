# API Documentation

Base URL: `https://api.posheh.ir/api/v1`

All authenticated endpoints require `Authorization: Bearer {token}` header.

## Authentication

### Send OTP
```
POST /auth/otp/send
```
```json
{ "mobile": "09121111111" }
```

### Verify OTP
```
POST /auth/otp/verify
```
```json
{
  "mobile": "09121111111",
  "code": "123456",
  "device_id": "optional-device-id",
  "device_name": "iPhone 15",
  "platform": "ios"
}
```

### Get Current User
```
GET /auth/me
```

### Logout
```
POST /auth/logout
```

## Dashboard

### Get Dashboard Data
```
GET /dashboard
```

Response includes stats, recent properties, expiring properties, activities, and tasks.

## Properties

### List Properties
```
GET /properties?q=search&type=sale&min_price=1000000000&per_page=20
```

Query parameters:
| Parameter | Type | Description |
|-----------|------|-------------|
| q | string | Quick search |
| type | string | sale, rent, mortgage, etc. |
| status | string | active, expired, sold |
| min_price | integer | Minimum price |
| max_price | integer | Maximum price |
| min_area | float | Minimum area |
| max_area | float | Maximum area |
| rooms | integer | Number of rooms |
| city | string | City filter |
| has_parking | boolean | Parking filter |
| favorites_only | boolean | Show favorites only |
| sort_by | string | created_at, price, area |
| sort_dir | string | asc, desc |
| per_page | integer | Items per page (max 100) |

### Create Property
```
POST /properties
```
```json
{
  "code": "A-1001",
  "type": "sale",
  "permission": "office",
  "owner_name": "علی رضایی",
  "owner_mobile": "09123456789",
  "price": 5000000000,
  "area": 120,
  "rooms": 3,
  "city": "تهران",
  "district": "ولنجک",
  "address": "خیابان ...",
  "description": "توضیحات ملک",
  "has_parking": true,
  "has_elevator": true
}
```

### Get Property
```
GET /properties/{id}
```

### Update Property
```
PUT /properties/{id}
```

### Delete Property
```
DELETE /properties/{id}
```

### Similar Properties
```
GET /properties/{id}/similar
```

### Toggle Favorite
```
POST /properties/{id}/favorite
```

## Office Management

### Create Office
```
POST /office
```

### Get Team Members
```
GET /office/team
```

### Invite Consultant
```
POST /office/invite
```
```json
{
  "mobile": "09123456789",
  "role": "consultant"
}
```

## Subscriptions

### Get Plans
```
GET /plans
```

### Subscribe
```
POST /subscribe
```
```json
{
  "plan_id": 1,
  "gateway": "zarinpal"
}
```

## Admin (Super Admin Only)

### List Offices
```
GET /admin/offices
```

### Analytics
```
GET /admin/analytics
```

### Tickets
```
GET /admin/tickets
```

### Announcements
```
GET /admin/announcements
POST /admin/announcements
```

## Error Responses

```json
{
  "message": "Error description",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

HTTP Status Codes:
- 200: Success
- 201: Created
- 401: Unauthenticated
- 403: Forbidden
- 422: Validation Error
- 500: Server Error
