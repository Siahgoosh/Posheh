# BLOG-PERMISSION-MATRIX

## Current runtime

Access to admin blog APIs/UI requires platform staff (`super_admin`, `platform_admin`, `platform_support`, `platform_finance` via `EnsurePlatformStaff` / `canAccessAdminPanel`).

Fine-grained CMS roles below are the **target matrix** for configuration; until role claims are added to auth, treat all platform staff as SUPER_ADMIN-equivalent for blog operations and rely on audit logs.

## Target matrix

| Capability | SUPER_ADMIN | ADMIN | EDITOR | AUTHOR | REVIEWER | SEO_MANAGER |
|------------|-------------|-------|--------|--------|----------|-------------|
| Create draft | ✓ | ✓ | ✓ | ✓ | | |
| Edit own draft | ✓ | ✓ | ✓ | ✓ | | |
| Edit any | ✓ | ✓ | ✓ | | | SEO fields* |
| Submit review | ✓ | ✓ | ✓ | ✓ | | |
| Approve | ✓ | ✓ | | | ✓ | |
| Publish / Schedule | ✓ | ✓ | | | | |
| Unpublish / Archive | ✓ | ✓ | | | | |
| SEO settings | ✓ | ✓ | ✓ | view | view | ✓ |
| Bulk SEO (noindex…) | ✓ | ✓ | | | | ✓ (+confirm) |
| Redirects | ✓ | ✓ | | | | ✓ |
| Media delete | ✓ | ✓ | ✓ | | | |
| Force delete | ✓ | ✓ | | | | |
| AI assist | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Manage permissions | ✓ | | | | | |

\*SEO_MANAGER may edit SEO fields without body publish rights.

## Safety

Dangerous bulk actions require `confirm_affected` + `confirm_destructive` and must show affected article list first.
