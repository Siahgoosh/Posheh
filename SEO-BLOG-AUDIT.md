# SEO & Blog System Audit — پوشه (Posheh)

**تاریخ Audit:** ۱۴۰۵/۰۵/۲۱ (2026-08-12)  
**شاخه:** `cursor/seo-blog-audit-a876`  
**دامنه:** `posheapp.ir`  
**محدوده این مرحله:** فقط Audit + معماری پیشنهادی + نقشه اجرا — **هیچ مقاله‌ای بازنویسی نشد و هیچ فاز اجرایی بعدی شروع نشد.**

---

## ۱. وضعیت فعلی (Current State)

### ۱.۱ تکنولوژی و معماری

| لایه | وضعیت |
|------|--------|
| Backend | Laravel — مدل `BlogPost`، API عمومی `/api/blog/*`، Admin `/api/admin/blog/*` |
| SSR برای کراولر | `BlogWebController` + Blade (`resources/views/blog/*`) روی مسیرهای `/blog*` |
| SPA | React/Vite — `BlogListPage` / `BlogCategoryPage` / `BlogPostPage` + `SeoHead` client-side |
| Nginx | `/blog` و `/sitemap.xml` و `/robots.txt` عمداً به PHP؛ بقیه SPA (`index.html`) |
| CMS Admin | `AdminBlogListPage` + `AdminBlogEditorPage` + `BlogSeoAnalyzer` + آپلود تصویر |
| دانش سئو | پوشه `SEO-POSHE/` (۳۰ سند + ۱۴٬۸۷۰ keyword در CSV + ۳ نمونه MD) — **به DB وصل نیست** |
| تولید انبوه | `BlogArticleGenerator` + `php artisan blog:seed --count=300` |

### ۱.۲ Schema دیتابیس (`blog_posts`)

**جدول واحد** — بدون جداول `categories` / `tags` / `authors` / `redirects` / `related_links` مستقل.

| فیلد | نقش |
|------|-----|
| `slug` (unique) | URL: `/blog/{slug}` |
| `title`, `excerpt`, `content` | محتوا |
| `cover_image` | تصویر شاخص (URL خارجی یا storage) |
| `meta_title`, `meta_description`, `keywords` | متا |
| `author_name` | رشته ساده (پیش‌فرض: تیم پوشه) |
| `reading_time`, `views` | UX / analytics خام |
| `is_published`, `published_at` | انتشار |
| `category_slug`, `category_label` | دسته‌بندی denormalized |
| `pillar_slug` | اشاره به پیلار (اغلب slug مقاله دیگر) |
| `faq` (JSON) | FAQ + Schema FAQPage |
| `related_slugs` (JSON) | مقالات مرتبط دستی/seed |
| `cta_text`, `cta_url` | CTA |

**وجود ندارد:** tags، canonical سفارشی، robots directive per-post، secondary keywords ساختاریافته، search intent، quality scores، جدول redirect، RSS، image metadata (width/height/alt جدا)، SoftDeletes، versioning.

### ۱.۳ حجم و منبع محتوا

| منبع | تعداد تقریبی | کیفیت واقعی |
|------|---------------|-------------|
| `BlogSeeder` | ۱۲ مقاله | محتوای نسبتاً اختصاصی‌تر (ولی روی deploy با `blog:seed` **بازنویسی می‌شود**) |
| `blog:seed --count=300` | ۱۲ پیلار + ~۲۸۸ مقاله دسته‌ای = ~۳۰۰ | **یک قالب HTML مشترک** با جایگزینی `{city}` / `{type}` / `{focus}` |
| `SEO-POSHE/articles/*.md` | ۳ مقاله با کیفیت بالا (~۹۷۰–۱۱۶۰ کلمه) | فقط فایل MD؛ اسلاگ‌های متفاوت از DB؛ در محصول publish نشده |
| Keyword DB | ۱۴٬۸۷۰ ردیف | برنامه‌ریزی محتوا؛ هنوز Content Audit Engine روی مقالات زنده اجرا نشده |

**نکته بحرانی deploy:** `scripts/deploy.sh` هر بار `BlogSeeder` و سپس `blog:seed --count=300 --force` را اجرا می‌کند. پیلارهای hand-written با همان slug توسط قالب ژنراتور **overwrite** می‌شوند.

### ۱.۴ URLهای فعلی (الگو)

- Index: `/blog`
- Category: `/blog/category/{category_slug}` — ۱۵ دسته ثابت در `BlogController::CATEGORIES`
- Article: `/blog/{slug}`
- نمونه‌های پیلار در seed:  
  `best-real-estate-crm-software-iran`, `property-filing-tips-for-agents`, `cloud-vs-excel-real-estate-management`, `real-estate-accounting-commission-guide`, `mubayaeh-contract-form-125-guide`, `property-customer-matching-system`, `real-estate-website-subdomain-guide`, `telegram-whatsapp-bot-real-estate`, `property-qr-code-marketing`, `digital-transformation-real-estate-agency`, `solo-agent-software-iran`, `real-estate-kpi-reports-dashboard`
- مقالات دسته‌ای: `{category}-{variant}-{n}` مثلاً `crm-crm-guide-1`, `filing-filing-tips-3`
- اسناد SEO-POSHE به URLهایی مثل `/blog/real-estate-crm-guide` و `/blog/pillar/...` ارجاع می‌دهند که **با مسیر/اسلاگ واقعی محصول هم‌خوان نیستند**

### ۱.۵ Categories (۱۵)

`software`, `crm`, `filing`, `agency`, `accounting`, `contracts`, `marketing`, `education`, `digital`, `ai`, `mobile`, `website`, `bots`, `reports`, `security`

صفحه دسته: لیست مقالات + H1 برچسب + متا ساده — **Landing Page واقعی نیست** (بدون intro غنی، featured، related categories اختصاصی، pagination قابل اعتماد در UI).

### ۱.۶ Tags

**سیستم Tag برای وبلاگ وجود ندارد.** (فیلد `tags` فقط روی Property است.)

### ۱.۷ Meta / Canonical / Robots / Sitemap

| مورد | وضعیت |
|------|--------|
| Canonical | SSR و SPA روی URL خود صفحه ست می‌شود؛ فیلد canonical سفارشی در DB نیست |
| Meta title | ژنراتور: `title \| پوشه`؛ سپس SSR دوباره `\| پوشه`؛ SPA هم دوباره `\| پوشه` → **برند تکراری** |
| Meta description | از excerpt کوتاه + پسوند ثابت |
| `robots.txt` داینامیک | `BlogWebController::robots()` قوانین خوب Allow/Disallow + Sitemap دارد |
| `backend/public/robots.txt` | فایل استاتیک با `Disallow:` خالی (اجازه همه) |
| Nginx `try_files $uri` | اگر فایل استاتیک باشد، **هرگز به کنترلر Laravel نمی‌رسد** → قوانین داینامیک عملاً مرده |
| `sitemap.xml` | تک‌فایل urlset: static + categories با پست + همه published posts؛ بدون image/sitemap index |
| noindex روی 404 SSR | در `$seo['noindex']` ست می‌شود ولی **در `layout.blade.php` رندر نمی‌شود** |
| RSS/Feed | وجود ندارد |

### ۱.۸ Schema Markup

پیاده‌سازی نسبتاً خوب (JSON-LD):

- Article (+ FAQPage در صورت FAQ)
- BreadcrumbList
- Organization / WebSite / CollectionPage / Blog (SPA)

شکاف‌ها:

- `WebSite.SearchAction` به `/blog?q=` اشاره می‌کند در حالی که جستجوی وبلاگ پیاده نشده
- نوع `BlogPosting` جدا استفاده نشده (Article کافی است ولی با docs ناسازگار)
- Schema ادعاهایی ندارد که در صفحه نباشند — خوب؛ ولی FAQ ژنراتور تکراری و کم‌ارزش است

### ۱.۹ Header / Footer / UI

- SSR Blade: هدر مینیمال لوگو + فوتر لینک‌های پایه؛ دارک تم ساده؛ فونت Tahoma/Arial
- SPA: هدر sticky + `SiteFooter`؛ کارت‌ها؛ TOC روی مقاله؛ بدون hero حرفه‌ای، بدون Popular، بدون Search واقعی، بدون prev/next، بدون share buttons، بدون نمایش cover در **بدنه** مقاله SPA (فقط OG)
- RTL و responsive پایه وجود دارد؛ UI هنوز سطح «حرفه‌ای landing» مورد نظر PHASE 11 نیست

### ۱.۱۰ Admin CMS (وضعیت فعلی)

موجود: Create / Edit / Delete / Publish flag / SEO score panel / FAQ / related_slugs متنی / cover upload / category select  

ناقص نسبت به PHASE 12: Schedule واقعی، Preview، Duplicate، Manage Tag/Author به‌صورت entity، پیشنهاد مقالات مرتبط تعاملی، Canonical/Robots per article، Content Audit dashboard

### ۱.۱۱ SEO Score فعلی

`BlogSeoAnalyzer` چک‌های طول title/meta، slug، keyword در title/content، طول متن، excerpt، cover، H2، لینک، alt، **keyword density** را جمع می‌زند.  
این با هدف «Content Quality / Helpful Content» فاصله دارد و می‌تواند محتوای doorway را هم امتیاز بالا بدهد.

---

## ۲. مشکلات بحرانی (Critical)

1. **Near-duplicate / Doorway content در مقیاس ~۳۰۰ مقاله**  
   یک قالب ~۶۳۰ کلمه‌ای با تعویض شهر/نوع ملک/فوکوس؛ ساختار H2/H3/FAQ/CTA تقریباً یکسان. ریسک Helpful Content Update و duplicate clustering بسیار بالاست.

2. **Overwrite محتوای باارزش در هر Deploy**  
   `deploy.sh` → `blog:seed --force` پیلارها و مقالات موجود با همان slug را با قالب ژنراتور جایگزین می‌کند. هر ویرایش دستی ادمین روی این slugها در deploy بعدی از بین می‌رود.

3. **`robots.txt` استاتیک مسیر داینامیک را می‌کشد**  
   فایل خالی `public/robots.txt` + `try_files $uri` → Disallowهای `/dashboard` `/admin` `/api/` اعمال نمی‌شوند.

4. **کشف ناقص مقالات در UI (orphan discovery)**  
   SSR index/category: `limit(50)` بدون pagination. SPA list: فقط صفحه اول `per_page=50` بدون UI صفحه‌بندی. با ~۳۰۰ پست، اکثر URLها فقط از sitemap/لینک مستقیم قابل کشف‌اند؛ crawl budget و UX آسیب می‌بینند.

5. **دوگانگی SSR vs SPA و meta ناقص SSR**  
   Google عمدتاً HTML Blade را می‌بیند؛ noindex روی 404 SSR اعمال نمی‌شود؛ SPA cover در body نیست؛ برندینگ title دوبل/سه‌تایی.

6. **گسست SEO-POSHE از محصول**  
   ۱۴٬۸۷۰ کلمه + ۳ مقاله باکیفیت + لینک به `/blog/pillar/...` و اسلاگ‌های متفاوت → استراتژی روی کاغذ، اجرا در DB با محتوای ضعیف.

---

## ۳. مشکلات متوسط (Medium)

1. فقط ۱۰ Unsplash cover تکراری برای صدها مقاله (تصویر غیرمرتبط/خارجی، بدون WebP محلی، بدون width/height → CLS ریسک).
2. Category به‌صورت ثابت در کد PHP؛ بدون CMS entity، بدون description/SEO landing غنی.
3. `related_slugs` از هم‌دسته یا پیلار؛ anchorهای تولیدی مثل `مقاله: crm crm guide 1` بی‌معنی.
4. FAQ تکراری ۴ سوالی تقریباً یکسان برای همه مقالات ژنراتور.
5. Meta description الگوی ثابت + excerpt کوتاه → CTR ضعیف و شباهت زیاد.
6. Sitemap تک‌فایل؛ بدون تفکیک post/category/image؛ `changefreq=weekly` برای همه (کم‌ارزش).
7. SearchAction Schema به جستجوی پیاده‌نشده.
8. نبود سیستم Redirect 301 برای تغییر slug / merge.
9. نبود Tags؛ در عین حال docs سئو Tag را بحث می‌کند.
10. Admin: related به‌صورت comma text؛ بدون پیشنهاد هوشمند؛ بدون preview.
11. `pillar_slug` گاهی به slug مقاله اشاره می‌کند، گاهی مفهومی دیگر؛ ناسازگار.
12. Double counting views احتمالی اگر هم SSR و هم SPA درخواست بخورد (بسته به ترافیک واقعی).

---

## ۴. مشکلات جزئی (Minor)

1. فونت SSR پیش‌فرض سیستم (Tahoma) — با هویت برند SPA ناسازگار.
2. عدم نمایش `updated_at` جلالی در UI مقاله.
3. نبود Share buttons / prev-next.
4. نبود Breadcrumb Schema روی بعضی صفحات category به‌صورت کامل در SSR.
5. `keywords` meta (کم‌اثر برای Google مدرن).
6. Reading time فرمول‌های متفاوت (ژنراتور vs admin validator).
7. Admin list pagination در API هست؛ UX لیست باید بررسی شود.
8. تصاویر محتوا داخل HTML ژنراتور وجود ندارند (فقط cover).
9. Canonical همیشه self؛ سناریوی merge/duplicate را پوشش نمی‌دهد.

---

## ۵. امتیازدهی کیفیت محتوا (مدل پیشنهادی Audit — بدون اجرای rewrite)

چون DB زنده در این محیط agent در دسترس نبود، امتیازها بر اساس **کد seed + ژنراتور + نمونه‌های SEO-POSHE** برآورد شده‌اند. پس از اتصال به production DB باید Content Audit Engine همین مدل را روی ردیف‌های واقعی اجرا کند.

### مدل امتیاز (۰–۱۰۰)

وزن‌های پیشنهادی: Originality 15 · Usefulness 15 · Completeness 10 · Intent match 10 · Readability 8 · Structure 8 · Internal linking 8 · Visual 6 · Freshness 5 · Trust 8 · Conversion 7

### گروه‌بندی برآوردی

| گروه | تخمین تعداد | توضیح |
|------|-------------|--------|
| **A (80–100)** | ۰–۳ | فقط نمونه‌های MD در `SEO-POSHE/articles` نزدیک A هستند؛ در DB زنده احتمالاً ۰ |
| **B (60–79)** | ۰–۱۲ | پیلارهای BlogSeeder *قبل از overwrite*؛ بعد از `blog:seed` عملاً به C/D سقوط می‌کنند |
| **C (40–59)** | ~۱۲ پیلار پس از seed | ساختار H2/FAQ/CTA دارند ولی محتوای template |
| **D (0–39)** | ~۲۸۸ | مقالات city×type×template — originality و usefulness پایین |

**Action پیش‌فرض پیشنهادی (هنوز اجرا نشود):**

| Action | کاندیداها |
|--------|-----------|
| KEEP | فعلاً تقریباً هیچ‌کدام از ۳۰۰ پست seed بدون بازبینی |
| UPDATE | پیلارهای ۱۲تایی پس از نجات از overwrite — غنی‌سازی با محتوای SEO-POSHE |
| REWRITE | اکثر مقالات دسته‌ای که intent واقعی و keyword P1 دارند |
| MERGE | خوشه‌های نزدیک مثل «CRM در شهر X» تکراری بین categories |
| REDIRECT | پس از merge؛ فقط با نقشه overlap + internal link analysis |
| NOINDEX | صفحات نازک موقت تا rewrite |
| DELETE | فقط پس از تأیید عدم ترافیک/بک‌لینک (هنوز انجام نشود) |

---

## ۶. مقالات ضعیف

- تقریباً تمام خروجی `BlogArticleGenerator::generate()` (الگوی `*-guide-1`, `*-tips-2`, … تا ۲۰ variant × ۱۵ دسته)
- FAQ و CTA کپی‌پیست
- عناوین «راهنمای جامع {type} در {city}» بدون داده بازار واقعی شهر
- لینک‌های داخلی با anchor ماشینی

## ۷. مقالات ارزشمند (یا پتانسیل بالا)

1. سه فایل `SEO-POSHE/articles/*.md` (کیفیت تحریریری واقعی)
2. دوازده موضوع پیلار در BlogSeeder (موضوعات درست؛ محتوا پس از seed ضعیف شده)
3. خوشه‌های P1 در `keywords.csv` حول CRM، فایلینگ، حسابداری، قرارداد، تحول دیجیتال

## ۸. مقالات تکراری / Topic Overlap

- ۲۰ قالب یکسان × ۱۵ دسته = همان «نکات / اشتباهات / قیمت‌گذاری / CRM / …» فقط با برچسب دسته و شهر متفاوت
- هم‌پوشانی بین `software` و `crm` و `digital` روی پیام «به جای اکسل از سامانه ابری»
- پیلار `best-real-estate-crm-software-iran` با ده‌ها مقاله `crm-*-*` هم‌موضوع
- اسلاگ‌های docs (`real-estate-crm-guide`) vs محصول (`best-real-estate-crm-software-iran`) = دو هویت برای یک موضوع

## ۹. کاندیدای Merge (پیشنهادی — نیاز به تأیید انسانی)

قبل از هر merge: تحلیل Topic Overlap + لینک داخلی + intent. **ادغام خودکار ممنوع.**

| خوشه | پیشنهاد مقاله اصلی | کاندیدای redirect بعدی |
|------|---------------------|-------------------------|
| انتخاب CRM املاک ایران | `best-real-estate-crm-software-iran` (پس از rewrite واقعی) | مقالات `crm-crm-*` نازک + اسلاگ docs |
| اکسل vs ابر | `cloud-vs-excel-real-estate-management` | مقالات `digital-digital-*` تکراری |
| فایلینگ | `property-filing-tips-for-agents` + MD چک‌لیست | `filing-filing-*` نازک |
| مبایعه‌نامه ۱۲۵ | `mubayaeh-contract-form-125-guide` | `contracts-contract-*` عمومی |

## ۱۰. مقالات نیازمند Rewrite

- هر ۱۲ پیلار با محتوای واقعی SEO-POSHE / تحریر انسانی (اولویت P0)
- برای هر دسته: ۱–۳ مقاله cornerstone به‌جای ۲۰ doorway
- اولویت keyword: P1 + intent informational/commercial از CSV، نه همه ۱۴٬۸۷۰ ردیف

---

## ۱۱. Category Problems

- ۱۵ دسته در کد ثابت؛ بعضی هم‌مرز (`software`/`crm`/`digital`/`ai`)
- صفحه دسته Landing نیست (بدون توضیح، featured، related categories غنی)
- SSR اگر دسته خالی باشد 404؛ API categories همه را با count برمی‌گرداند
- Pagination UI ناقص → با ۲۰ پست/دسته فعلاً کمتر دیده می‌شود، با رشد مشکل‌ساز می‌شود
- عدم همگام‌سازی کامل با `SEO-POSHE/05-Blog-Categories.md` در سطح محتوا

## ۱۲. URL Problems

- اسلاگ‌های ژنراتور طولانی/تکراری و انگلیسی-ماشینی (`software-software-guide-1`)
- ناسازگاری اسلاگ docs با DB
- مسیر `/blog/pillar/...` در اسناد وجود دارد؛ در routes نیست
- تغییر slug بدون جدول redirects = شکستن URL
- Deploy reseeding باعث ثبات URL می‌شود ولی **محتوای پشت URL بی‌ثبات** است

## ۱۳. Sitemap Problems

- تک‌فایل؛ با رشد ۴۰۰+ هنوز قابل قبول است ولی image sitemap ندارد
- همه posts published وارد می‌شوند (خوب) — اما اگر noindex اضافه شود فیلتر ندارد
- `lastmod` از `updated_at`؛ reseeding مکرر lastmod را مصنوعی تازه می‌کند (سیگنال گمراه‌کننده)
- صفحات static مثل `/contact` بدون تضمین وجود در SPA
- Ping endpoint قدیمی استفاده نشده (خوب)

## ۱۴. Technical SEO Problems

| موضوع | جزئیات |
|-------|--------|
| robots | استاتیک بر داینامیک غالب است |
| noindex SSR | رندر نمی‌شود |
| Title duplication | `\| پوشه` چندبار |
| Core Web Vitals | Unsplash خارجی = LCP ریسک؛ بدون width/height؛ فونت/JS SPA برای کاربران |
| Mobile | پایه OK؛ CTA و تایپوگرافی SSR ضعیف |
| Crawl | ۵۰تایی cap در listing |
| Structured data | SearchAction شکسته |
| Security upload | image validation نسبی هست؛ MIME عمیق/antivirus نیست (متوسط) |
| XSS محتوا | `{!! $post->content !!}` و `dangerouslySetInnerHTML` — اعتماد به ادمین؛ sanitize لازم |

## ۱۵. Internal Linking Problems

- ژنراتور همیشه به چند پیلار ثابت + related هم‌دسته لینک می‌دهد
- Anchor ضعیف / انگلیسی‌شده از slug
- نبود موتور پیشنهاد بر اساس semantic similarity
- Admin فقط CSV دستی related_slugs
- لینک‌های شکسته بالقوه به اسلاگ‌های docs که در DB نیستند
- عدم orphan detection سیستماتیک

## ۱۶. Image Problems

- بدون تصویر داخل محتوا در مقالات seed
- Cover خارجی Unsplash (۱۰ تصویر چرخشی)
- ALT روی cover = title (قابل قبول حداقلی)
- نبود caption / description / responsive srcset / AVIF
- تصاویر MD به `/images/blog/*.webp` اشاره می‌کنند که احتمالاً در repo نیستند

---

## ۱۷. Recommended Architecture

### ۱۷.۱ اصول

1. **Help the user** مقدم بر SEO score عددی است.
2. حجم کمترِ محتوای ممتاز ≫ صدها doorway.
3. URLهای ایندکس‌شده را بدون 301 نشکن.
4. دانش `SEO-POSHE` منبع استراتژی است؛ DB منبع حقیقت انتشار.
5. Deploy هرگز نباید محتوا را silently overwrite کند.

### ۱۷.۲ مدل داده پیشنهادی (افزایشی)

```
blog_categories          # slug, label, description, seo_*, parent_id, is_indexed
blog_tags                # اختیاری و محافظه‌کارانه
blog_authors             # name, bio, avatar, sameAs
blog_posts               # گسترش فیلدها: canonical_url, robots, focus_keyword,
                         # secondary_keywords JSON, intent, quality_score, seo_score,
                         # action_status, noindex, scheduled_at, deleted_at
blog_post_tag
blog_related_posts       # post_id, related_id, anchor, source=auto|manual, approved
blog_redirects           # from_path, to_path, status_code, hits
blog_content_audits      # snapshot متریک‌های PHASE 1 per post
blog_images              # post_id, url, alt, width, height, role=cover|inline
```

### ۱۷.۳ لایه‌های سیستم

| لایه | مسئولیت |
|------|----------|
| Content Audit Engine | استخراج متریک‌ها + نمره A–D + پیشنهاد Action (بدون auto-delete) |
| Keyword/Intent layer | اتصال به CSV/keywords + intent + user problem |
| Internal Linking Engine | پیشنهاد + تأیید ادمین |
| Publishing pipeline | draft → schedule → publish → sitemap lastmod واقعی → feed |
| SSR blade parity | meta کامل شامل robots؛ cover؛ TOC اختیاری |
| Sitemap index | `sitemap.xml` → post/category/image در صورت نیاز |
| Redirect service | تغییر slug / merge امن |

### ۱۷.۴ سیاست محتوا

- متوقف کردن تولید انبوه template تا Audit Engine + تحریر پیلارها
- هدف کوتاه‌مدت: ۱۲–۳۰ مقاله A/B واقعی
- Category pages = landing با intro انسانی
- Tag فقط اگر navigation/SEO واقعی داشته باشد؛ در غیر این صورت noindex یا عدم ساخت صفحه

---

## ۱۸. Implementation Plan (پس از تأیید — اجرا نشود تا دستور بعدی)

### مرحله ۰ — ایمنی (فوری قبل از هر تغییر محتوا)

1. Backup جدول `blog_posts`
2. حذف/شرطی کردن `blog:seed` از `deploy.sh` (یا فقط در محیط خالی)
3. حذف یا rename `public/robots.txt` استاتیک تا کنترلر داینامیک فعال شود
4. رندر `noindex` در Blade layout

### مرحله ۱ — Content Audit Engine

- Command/API: اسکن همه posts → متریک‌های PHASE 1 → ذخیره `blog_content_audits`
- داشبورد ادمین: فیلتر Grade / Action / Category
- خروجی CSV برای تحریر

### مرحله ۲ — تثبیت Technical SEO

- Title pipeline یکپارچه (یک بار برند)
- Pagination واقعی list/category (SSR + SPA)
- Sitemap فیلتر noindex/canonical-elsewhere
- Cover در body SPA + dimensions
- Sanitize HTML ادمین

### مرحله ۳ — نجات و Rewrite پیلارها

- ایمپورت/بازنویسی ۱۲ پیلار با استاندارد SEO-POSHE (بدون حدس قیمت/قانون)
- Map اسلاگ docs → اسلاگ زنده + 301 در صورت نیاز
- FAQ/CTA/Internal link واقعی

### مرحله ۴ — پاکسازی خوشه‌ای

- NOINDEX موقت doorwayهای D
- MERGE انتخابی با redirect
- کاهش categories هم‌مرز در صورت نیاز (با redirect دسته)

### مرحله ۵ — CMS حرفه‌ای

- Related suggestions UI
- Schedule / Preview / Duplicate
- SEO assistant بدون اتکای افراطی به density
- Authors / optional tags

### مرحله ۶ — UI/UX Blog

- Hero + featured + latest + categories + search
- Article: TOC، share، prev/next، updated date، تصاویر بهینه

### مرحله ۷ — اندازه‌گیری

- GSC sitemap submit
- مانیتور ایندکس / cannibalization
- تبدیل blog → register با UTM (طبق SEO-POSHE/24)

---

## ۱۹. خلاصه اجرایی

سیستم وبلاگ از نظر **اسکلت فنی** (SSR برای کراول، مدل SEO fields، FAQ JSON-LD، Admin editor، sitemap پویا، ۱۵ دسته) جلوتر از صفر است؛ اما از نظر **ارزش محتوایی** عمدتاً روی یک ژنراتور قالب‌محور ~۳۰۰تایی سوار شده که با Helpful Content و هدف «حل مسئله کاربر» در تضاد است. دانش غنی `SEO-POSHE` هنوز به محصول وصل نیست و deploy reseeding خطر نابودی ویرایش‌های واقعی را دارد.

**اولویت فوری بعد از تأیید شما:** ایمنی deploy + robots + Audit Engine — سپس rewrite پیلارها — نه تولید مقاله بیشتر.

---

## ۲۰. وضعیت این مرحله

- [x] بررسی پروژه / ساختار / تکنولوژی  
- [x] Schema و سیستم Blog  
- [x] استخراج الگوی URL / Category / Meta / Canonical / robots / sitemap  
- [x] شناسایی مشکلات فنی و محتوایی  
- [x] معماری پیشنهادی + نقشه اجرا  
- [x] ایجاد `SEO-BLOG-AUDIT.md`  
- [ ] **STOP** — بازنویسی مقاله، پیاده‌سازی فاز ۱+، یا تغییر production تا دستور بعدی انجام نمی‌شود.

---

*Audit مبتنی بر کد و دارایی‌های مخزن است. برای امتیاز دقیق per-article روی داده production، پس از دسترسی DB باید Content Audit Engine اجرا شود.*
