const _persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

/// Convert ASCII digits in [input] to Persian digits.
String toPersianDigits(String input) {
  final buffer = StringBuffer();
  for (final rune in input.runes) {
    final ch = String.fromCharCode(rune);
    final code = rune;
    if (code >= 0x30 && code <= 0x39) {
      buffer.write(_persianDigits[code - 0x30]);
    } else {
      buffer.write(ch);
    }
  }
  return buffer.toString();
}

/// Group an integer with thousands separators then localize to Persian digits.
String formatNumber(num? value) {
  if (value == null) return '۰';
  final intPart = value.round().abs().toString();
  final buffer = StringBuffer();
  for (var i = 0; i < intPart.length; i++) {
    if (i > 0 && (intPart.length - i) % 3 == 0) buffer.write('٬');
    buffer.write(intPart[i]);
  }
  final sign = value < 0 ? '-' : '';
  return toPersianDigits('$sign$buffer');
}

/// Format a price in tomans, matching the web `formatPrice`.
String formatPrice(num? value) {
  if (value == null || value == 0) return 'رایگان';
  return '${formatNumber(value)} تومان';
}
