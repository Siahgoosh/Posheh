/// Cafe Bazaar in-app billing configuration.
/// RSA public key from پنل کافه‌بازار → پرداخت درون‌برنامه‌ای
const String bazaarRsaPublicKey = String.fromEnvironment(
  'BAZAAR_RSA_KEY',
  defaultValue:
      'MIHNMA0GCSqGSIb3DQEBAQUAA4G7ADCBtwKBrwCuvLevG3vLVmyHo7IAHjd980CDwGCHFnuna7jHAxsgHmiCjfg390MR3c1UiL7Zd3hiiQqEgM3KbDv46NGcVeUwovagW7dMVQTIUzjjd7ymrCvt+/NM1zuwat0gf397xld7q+yw5A5GxPFjDuHISE07f49AsT3gL61RodeNyrk1/D/jXrzrfO7voOLwrC0+RdspNK+eX5XqBiPiUcuCNistOCuh3WYLUppxpvIvqsMCAwEAAQ==',
);

/// JWT تخفیف پویا — از پنل کافه‌بازار
const String bazaarDynamicDiscountJwt = String.fromEnvironment(
  'BAZAAR_DISCOUNT_JWT',
  defaultValue: '2dJvIhxXsk9boRwhaBEYhY9I84Tbfzt96NBMjymLLrU',
);

const String bazaarPackageName = 'ir.posheapp.posheh';

/// Bazaar subscription SKU ids — must match پنل کافه‌بازار
const Map<String, String> bazaarPlanSkus = {
  'solo': 'solo01',
  'office': 'office01',
  'premium': 'office02',
};

String bazaarSkuForPlan(String slug) => bazaarPlanSkus[slug] ?? slug;

String? planSlugForBazaarSku(String sku) {
  for (final entry in bazaarPlanSkus.entries) {
    if (entry.value == sku) return entry.key;
  }
  return null;
}
