/// Cafe Bazaar in-app billing configuration.
/// Set BAZAAR_RSA_KEY at build time with the RSA public key from
/// https://pishkhan.cafebazaar.ir → برنامه → پرداخت درون‌برنامه‌ای
const String bazaarRsaPublicKey = String.fromEnvironment(
  'BAZAAR_RSA_KEY',
  defaultValue: '',
);

const String bazaarPackageName = 'ir.posheapp.posheh';

/// Bazaar subscription SKU ids — must match products defined in Bazaar developer panel.
String bazaarSkuForPlan(String slug) => slug;
