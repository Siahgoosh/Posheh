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

num? _asNum(dynamic value) {
  if (value == null) return null;
  if (value is num) return value;
  return num.tryParse(value.toString().replaceAll(',', '').replaceAll('٬', ''));
}

/// Group an integer with thousands separators then localize to Persian digits.
/// Accepts num or numeric strings (Laravel decimal casts often arrive as strings).
String formatNumber(dynamic value) {
  final n = _asNum(value);
  if (n == null) return '۰';
  final intPart = n.round().abs().toString();
  final buffer = StringBuffer();
  for (var i = 0; i < intPart.length; i++) {
    if (i > 0 && (intPart.length - i) % 3 == 0) buffer.write('٬');
    buffer.write(intPart[i]);
  }
  final sign = n < 0 ? '-' : '';
  return toPersianDigits('$sign$buffer');
}

/// Format a price in tomans, matching the web `formatPrice`.
String formatPrice(dynamic value) {
  final n = _asNum(value);
  if (n == null || n == 0) return 'رایگان';
  return '${formatNumber(n)} تومان';
}
