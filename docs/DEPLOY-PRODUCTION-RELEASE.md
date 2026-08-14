# Production Release Deploy Guide

## مشکل «بعد از دیپلوی فیچرها غیب می‌شوند»

`scripts/deploy.sh` کل checkout را با `git reset --hard origin/<branch>` عوض می‌کند.

اگر فقط یک شاخهٔ فیچر (مثلاً SEO یا CRM) دیپلوی شود، کدِ شاخه‌های دیگر از سرور حذف می‌شود.
دادهٔ دیتابیس معمولاً می‌ماند، اما UI/API مربوط به آن فیچر دیگر در کد نیست.

## راه‌حل

همیشه از شاخهٔ یکپارچه دیپلوی کنید:

```bash
./scripts/deploy.sh cursor/production-release-a876
```

این شاخه شامل:

- CRM حرفه‌ای
- حسابداری دفتری
- تور مجازی ۳۶۰ / Smart Walk
- چت آنلاین / Communication
- Blog CMS + SEO (فاز ۲ و ۳)

است.

## محافظ‌های جدید در `deploy.sh`

| متغیر | پیش‌فرض | اثر |
|--------|---------|-----|
| (بدون آرگومان) | `cursor/production-release-a876` | دیپلوی release |
| `ALLOW_FEATURE_DEPLOY=1` | خاموش | اجازهٔ دیپلوی شاخهٔ فیچر جدا |
| `BLOG_SEED_ON_DEPLOY=1` | خاموش | seed انبوه ۳۰۰ مقاله |
| `BLOG_REBUILD_ON_DEPLOY=1` | خاموش | اعمال batch بازسازی محتوا به‌صورت draft |
| `SKIP_DEMO_SEED` | `1` | جلوگیری از seed دمو |

دیپلوی شاخهٔ فیچر بدون `ALLOW_FEATURE_DEPLOY=1` **رد می‌شود**.

## دستورات ایمن بعد از دیپلوی

```bash
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan blog:cms-bootstrap
docker compose exec -T app php artisan communication:install --force
# فقط وقتی می‌خواهید draftهای بازسازی را وارد کنید:
docker compose exec -T app php artisan blog:rebuild-batch 1 --force
```

## ممنوع

- `./scripts/deploy.sh cursor/seo-blog-cms-a876` به‌تنهایی
- `./scripts/deploy.sh cursor/crm-professional-a876` به‌تنهایی
- `BLOG_SEED_ON_DEPLOY=1` روی پروداکشن دارای محتوای curated

## سیاست توسعهٔ بعدی

هر فیچر جدید باید قبل از دیپلوی پروداکشن وارد `cursor/production-release-a876` (یا `main` پس از merge) شود.
