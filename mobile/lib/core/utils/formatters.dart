import 'package:shamsi_date/shamsi_date.dart';

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

/// Format a [DateTime] or ISO string to Jalali display.
String formatJalaliDate(dynamic value, {bool withTime = false}) {
  if (value == null) return '—';
  DateTime? dt;
  if (value is DateTime) {
    dt = value;
  } else if (value is String && value.isNotEmpty) {
    dt = DateTime.tryParse(value.contains('T') ? value : '${value}T12:00:00');
  }
  if (dt == null) return '—';
  final j = Jalali.fromDateTime(dt);
  const months = [
    'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
    'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند',
  ];
  final date = '${toPersianDigits('${j.day}')} ${months[j.month - 1]} ${toPersianDigits('${j.year}')}';
  if (!withTime) return date;
  final h = dt.hour.toString().padLeft(2, '0');
  final m = dt.minute.toString().padLeft(2, '0');
  return '$date · ${toPersianDigits('$h:$m')}';
}

/// ISO `YYYY-MM-DD` from a [DateTime].
String toIsoDate(DateTime dt) {
  final y = dt.year.toString().padLeft(4, '0');
  final m = dt.month.toString().padLeft(2, '0');
  final d = dt.day.toString().padLeft(2, '0');
  return '$y-$m-$d';
}
