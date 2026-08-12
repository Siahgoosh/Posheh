# Lead Generation System

## Funnel

```
Article/Contact → CTA view/click → Form start → Form submit → cro_leads
→ optional Customer sync (CRO_CRM_SYNC_OFFICE_ID) → status workflow → feedback
```

## Form variants

- **short:** name?, mobile*, request_type, consent
- **specialized:** + city, budget, message (commercial/contact)

## Lead fields

Source: ORGANIC|BLOG|ARTICLE|CONTACT|LANDING_PAGE|DIRECT|REFERRAL  
Request: BUY|SELL|RENT|…|DEMO|SUPPORT|OTHER  
Status: NEW→CONTACTED→QUALIFIED→NEGOTIATION→WON|LOST

## Dedup

Same mobile → new row flagged `is_duplicate` + linked `duplicate_of`; original meta.follow_ups updated.

## Scoring

Intent + request type + budget/city + article attribution (0–100). High score → severity alert.

## Notifications

`seo_alerts` kind=`cro_lead` (dashboard). Telegram optional/log.

## Spam

Rate limit / IP, honeypot `website`, server validation.
