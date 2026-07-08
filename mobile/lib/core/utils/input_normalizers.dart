String toEnglishDigits(String input) {
  const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
  const arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

  var result = input;
  for (var i = 0; i < 10; i++) {
    result = result.replaceAll(persian[i], '$i').replaceAll(arabic[i], '$i');
  }
  return result;
}

String normalizeMobile(String mobile) {
  var digits = toEnglishDigits(mobile).replaceAll(RegExp(r'\D'), '');
  if (digits.startsWith('98')) {
    digits = '0${digits.substring(2)}';
  }
  if (!digits.startsWith('0')) {
    digits = '0$digits';
  }
  return digits;
}

String normalizeOtpCode(String code) {
  var digits = toEnglishDigits(code).replaceAll(RegExp(r'\D'), '');
  if (digits.isNotEmpty && digits.length < 6) {
    digits = digits.padLeft(6, '0');
  }
  return digits;
}
