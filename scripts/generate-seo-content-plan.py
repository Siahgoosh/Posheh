#!/usr/bin/env python3
"""Generate SEO-POSHE content plan markdown files."""
from __future__ import annotations
from pathlib import Path
from datetime import date, timedelta

ROOT = Path(__file__).resolve().parents[1] / "SEO-POSHE"

CATEGORIES = {
    "software": ("نرم‌افزار و سامانه املاک", "pillar-software"),
    "crm": ("CRM و فروش املاک", "pillar-crm"),
    "filing": ("فایلینگ و ثبت ملک", "pillar-filing"),
    "agency": ("مدیریت دفتر و آژانس", "pillar-agency"),
    "accounting": ("حسابداری و کمیسیون", "pillar-accounting"),
    "contracts": ("قرارداد و حقوقی", "pillar-contracts"),
    "marketing": ("بازاریابی و تبلیغات ملک", "pillar-marketing"),
    "education": ("آموزش مشاور املاک", "pillar-education"),
    "digital": ("تحول دیجیتال", "pillar-digital"),
    "ai": ("هوش مصنوعی در املاک", "pillar-ai"),
    "mobile": ("اپلیکیشن موبایل و دسکتاپ", "pillar-mobile"),
    "website": ("وبسایت اختصاصی املاک", "pillar-website"),
    "bots": ("ربات تلگرام و واتساپ", "pillar-bots"),
    "reports": ("گزارش و KPI", "pillar-reports"),
    "security": ("امنیت و OTP", "pillar-security"),
}

PILLARS = [
    ("pillar-software", "نرم افزار املاک", "نرم-افزار-املاک", "software"),
    ("pillar-crm", "CRM املاک", "crm-املاک", "crm"),
    ("pillar-filing", "ثبت ملک و فایلینگ", "ثبت-ملک-فایلینگ", "filing"),
    ("pillar-agency", "مدیریت دفتر املاک", "مدیریت-دفتر-املاک", "agency"),
    ("pillar-accounting", "حسابداری دفتر املاک", "حسابداری-دفتر-املاک", "accounting"),
    ("pillar-contracts", "قرارداد املاک", "قرارداد-املاک", "contracts"),
    ("pillar-marketing", "بازاریابی املاک", "بازاریابی-املاک", "marketing"),
    ("pillar-education", "آموزش مشاور املاک", "amoozesh-moshaver-amlak", "education"),
    ("pillar-digital", "تحول دیجیتال املاک", "digital-transformation-real-estate", "digital"),
    ("pillar-ai", "هوش مصنوعی در املاک", "ai-real-estate-iran", "ai"),
    ("pillar-mobile", "اپلیکیشن املاک", "real-estate-app", "mobile"),
    ("pillar-website", "وبسایت دفتر املاک", "real-estate-website", "website"),
    ("pillar-bots", "ربات املاک", "real-estate-bot", "bots"),
    ("pillar-reports", "گزارش KPI املاک", "real-estate-kpi", "reports"),
    ("pillar-matching", "تطبیق ملک مشتری", "property-customer-matching", "crm"),
    ("pillar-visits", "بازدید ملک", "property-visit-scheduling", "crm"),
    ("pillar-commissions", "کمیسیون مشاور", "real-estate-commission", "accounting"),
    ("pillar-team", "مدیریت تیم", "real-estate-team-management", "agency"),
    ("pillar-owner", "پورتال مالک", "owner-portal-real-estate", "filing"),
    ("pillar-cloud", "سامانه ابری املاک", "cloud-real-estate-software", "software"),
    ("pillar-jalali", "تقویم شمسی املاک", "jalali-calendar-real-estate", "software"),
    ("pillar-security", "امنیت سامانه املاک", "real-estate-software-security", "security"),
    ("pillar-excel", "جایگزین اکسل", "excel-alternative-real-estate", "software"),
    ("pillar-qr", "QR کد ملک", "property-qr-code", "filing"),
    ("pillar-telegram", "ربات تلگرام", "telegram-bot-real-estate", "bots"),
    ("pillar-whatsapp", "واتساپ املاک", "whatsapp-real-estate", "bots"),
    ("pillar-subscription", "قیمت نرم افزار املاک", "real-estate-software-pricing", "software"),
    ("pillar-solo", "نرم افزار مشاور مستقل", "solo-agent-software", "software"),
    ("pillar-premium", "سامانه حرفه‌ای املاک", "premium-real-estate-platform", "software"),
    ("pillar-compare", "مقایسه نرم افزار املاک", "compare-real-estate-software", "software"),
]

TOPICS_SUFFIX = [
    "راهنمای کامل", "نکات کاربردی", "اشتباهات رایج", "چک‌لیست", "برای مبتدیان",
    "برای مدیران", "برای مشاوران", "در ایران ۱۴۰۴", "بدون اکسل", "با مثال عملی",
    "سوالات متداول", "مزایا و معایب", "هزینه و قیمت", "نحوه انتخاب", "پیاده‌سازی",
    "از صفر تا صد", "تجربه کاربران", "مقایسه روش‌ها", "قوانین و مقررات", "ابزارهای رایگان",
    "اتوماسیون", "صرفه‌جویی زمان", "افزایش فروش", "مدیریت سرنخ", "گزارش‌گیری",
    "امنیت داده", "آموزش گام‌به‌گام", "برای دفاتر کوچک", "برای آژانس بزرگ", "دیجیتال‌سازی",
]


def blog_categories_md() -> str:
    lines = ["# معماری دسته‌بندی وبلاگ پوشه", "", "> ۱۵ دسته · ۳۵ مقاله در هر دسته = ۵۲۵ ایده پایه", ""]
    for slug, (label, pillar) in CATEGORIES.items():
        lines += [f"## {label}", "", f"- **slug:** `{slug}`", f"- **pillar:** `{pillar}`", f"- **URL:** `/blog/category/{slug}`", ""]
        lines.append("### ایده مقالات")
        for i, suf in enumerate(TOPICS_SUFFIX[:35], 1):
            lines.append(f"{i}. {label.replace(' و ', ' ')} — {suf}")
        lines.append("")
    return "\n".join(lines)


def pillar_pages_md() -> str:
    lines = ["# صفحات پیلار (Pillar Pages)", "", "> ۳۰ صفحه پیلار · هر کدام ۲۰–۴۰ مقاله پشتیبان", ""]
    for pid, title, url_slug, cat in PILLARS:
        lines += [
            f"## {title}",
            "",
            f"| فیلد | مقدار |",
            f"|------|-------|",
            f"| ID | `{pid}` |",
            f"| URL | `/blog/pillar/{url_slug}` |",
            f"| کلمه هدف | {title} |",
            f"| دسته | {cat} |",
            f"| CTA | شروع ۴۸ ساعت رایگان — پوشه |",
            f"| Schema | SoftwareApplication + FAQPage |",
            "",
            "### ساختار H2",
            "1. تعریف و اهمیت",
            "2. چالش‌های دفاتر املاک ایران",
            "3. راه‌حل پوشه",
            "4. مقایسه با روش سنتی",
            "5. سوالات متداول",
            "6. شروع رایگان",
            "",
            "### مقالات پشتیبان (نمونه)",
        ]
        for suf in TOPICS_SUFFIX[:25]:
            lines.append(f"- {title} — {suf}")
        lines.append("")
    return "\n".join(lines)


def topic_clusters_md() -> str:
    lines = ["# نقشه خوشه‌های موضوعی (Topic Clusters)", ""]
    for pid, title, url_slug, cat in PILLARS:
        lines += [f"## خوشه: {title}", f"**پیلار:** `/blog/pillar/{url_slug}`", ""]
        for i in range(1, 31):
            lines.append(f"- [{cat}] مقاله {i}: {title} — موضوع فرعی {i}")
        lines.append("")
    return "\n".join(lines)


def content_calendar_md() -> str:
    start = date(2026, 7, 21)
    lines = ["# تقویم محتوا ۱۲ ماهه", "", "> ۳ مقاله در هفته · ۱۵۶ مقاله در سال", ""]
    idx = 0
    d = start
    week = 1
    while d < start.replace(year=start.year + 1):
        lines.append(f"### هفته {week} — {d.isoformat()}")
        for day_offset in [0, 2, 4]:
            post_date = d + timedelta(days=day_offset)
            pillar = PILLARS[idx % len(PILLARS)]
            lines.append(f"- {post_date.isoformat()}: **{pillar[1]}** — {TOPICS_SUFFIX[idx % len(TOPICS_SUFFIX)]} (P1)")
            idx += 1
        lines.append("")
        d += timedelta(days=7)
        week += 1
    return "\n".join(lines)


def main():
    ROOT.mkdir(parents=True, exist_ok=True)
    (ROOT / "articles").mkdir(exist_ok=True)
    files = {
        "05-Blog-Categories.md": blog_categories_md(),
        "06-Pillar-Pages.md": pillar_pages_md(),
        "07-Topic-Clusters.md": topic_clusters_md(),
        "08-Content-Calendar.md": content_calendar_md(),
    }
    for name, content in files.items():
        (ROOT / name).write_text(content, encoding="utf-8")
        print(f"Wrote {name} ({len(content.splitlines())} lines)")


if __name__ == "__main__":
    main()
