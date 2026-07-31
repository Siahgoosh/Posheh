# SMS از سرور خارج از ایران (هلند)

سرور پوشه در هلند است. **نیازی به VPS ایران یا relay نیست** — OTP از همان API وب‌سرویس مکث (`ippanel.com/services.jspd`) ارسال می‌شود.

## تنظیم `.env` (سرور هلند)

```env
SMS_MODE=live
IPPANEL_API_MODE=jspd
IPPANEL_USERNAME=...
IPPANEL_PASSWORD=...
IPPANEL_FROM_NUMBER=+983000505
IPPANEL_OTP_FROM_NUMBER=+9810008721297974
```

با `IPPANEL_API_MODE=jspd`، کد ورود مستقیم از **JSPD webservice API** (`op=send`) فرستاده می‌شود — بدون Edge API و بدون پترن (که از IP خارج `deny` می‌دهد).

پیام OTP: `کد تأیید پوشه: 123456`

## فعال‌سازی روی سرور

```bash
./scripts/force-sms-jspd.sh

# تست
docker compose exec app php artisan system:sms-probe 09170577873 --send
```

## علت رایج: deploy هر بار SMS را خاموش می‌کند

**علائم:**
- API می‌گوید «کد ارسال شد» ولی SMS نمی‌آید
- کد `123456` کار می‌کند
- در پنل مکث چیزی ثبت نمی‌شود

**رفع فوری:**

```bash
./scripts/enable-live-sms.sh
docker compose exec app php artisan system:sms-probe 09170577873 --send
```

## Edge API و پترن

- `edge.ippanel.com` از هلند معمولاً **timeout / 502** می‌دهد — برای OTP استفاده نمی‌شود.
- API پترن JSPD از IP خارج اغلب **`deny`** برمی‌گرداند.
- **وب‌سرویس plain** (`op=send`) همان APIای است که از هلند کار کرده (مثلاً پیامک ۱۸:۲۱).

اگر می‌خواهید OTP با **پترن** (متن قالب‌دار) برود، باید IP سرور را در پنل مکث whitelist کنید یا از [SMS Relay](SMS-RELAY.md) روی VPS ایران استفاده کنید (اختیاری).

## حالت log (توسعه / بدون SMS واقعی)

```bash
docker compose exec app php artisan system:sms-enable --log
# کد OTP: 123456
```

## سرور داخل ایران

اگر سرور IP ایران دارد، می‌توانید `IPPANEL_API_MODE=auto` یا `jspd` با username/password + whitelist IP استفاده کنید. در صورت دسترسی به Edge، `IPPANEL_API_KEY` و `IPPANEL_API_MODE=edge` هم ممکن است.
