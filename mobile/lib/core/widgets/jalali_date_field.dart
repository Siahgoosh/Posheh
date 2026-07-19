import 'package:flutter/material.dart';
import 'package:shamsi_date/shamsi_date.dart';
import '../theme/app_theme.dart';
import '../utils/formatters.dart';

class JalaliDateField extends StatefulWidget {
  final DateTime value;
  final ValueChanged<DateTime> onChanged;
  final String? label;

  const JalaliDateField({
    super.key,
    required this.value,
    required this.onChanged,
    this.label,
  });

  @override
  State<JalaliDateField> createState() => _JalaliDateFieldState();
}

class _JalaliDateFieldState extends State<JalaliDateField> {
  late Jalali _jalali;

  @override
  void initState() {
    super.initState();
    _jalali = Jalali.fromDateTime(widget.value);
  }

  @override
  void didUpdateWidget(covariant JalaliDateField oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.value != widget.value) {
      _jalali = Jalali.fromDateTime(widget.value);
    }
  }

  int _daysInMonth(int year, int month) {
    if (month <= 6) return 31;
    if (month <= 11) return 30;
    return Jalali(year).isLeapYear() ? 30 : 29;
  }

  void _update({int? year, int? month, int? day}) {
    final y = year ?? _jalali.year;
    final m = month ?? _jalali.month;
    final maxDay = _daysInMonth(y, m);
    final d = (day ?? _jalali.day).clamp(1, maxDay);
    setState(() => _jalali = Jalali(y, m, d));
    widget.onChanged(_jalali.toDateTime());
  }

  @override
  Widget build(BuildContext context) {
    final years = List.generate(11, (i) => Jalali.now().year - 5 + i);
    final days = List.generate(_daysInMonth(_jalali.year, _jalali.month), (i) => i + 1);
    const months = [
      'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
      'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند',
    ];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (widget.label != null)
          Padding(
            padding: const EdgeInsets.only(bottom: 6),
            child: Text(widget.label!, style: const TextStyle(fontSize: 12, color: AppColors.muted)),
          ),
        Row(
          children: [
            Expanded(
              child: _Picker(
                value: _jalali.day,
                items: days.map((d) => MapEntry(d, toPersianDigits('$d'))).toList(),
                onChanged: (d) => _update(day: d),
              ),
            ),
            const SizedBox(width: 8),
            Expanded(
              flex: 2,
              child: _Picker(
                value: _jalali.month,
                items: List.generate(12, (i) => MapEntry(i + 1, months[i])),
                onChanged: (m) => _update(month: m),
              ),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: _Picker(
                value: _jalali.year,
                items: years.map((y) => MapEntry(y, toPersianDigits('$y'))).toList(),
                onChanged: (y) => _update(year: y),
              ),
            ),
          ],
        ),
        const SizedBox(height: 4),
        Text(
          formatJalaliDate(widget.value),
          style: const TextStyle(fontSize: 11, color: AppColors.muted),
        ),
      ],
    );
  }
}

class _Picker extends StatelessWidget {
  final int value;
  final List<MapEntry<int, String>> items;
  final ValueChanged<int> onChanged;

  const _Picker({
    required this.value,
    required this.items,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return DropdownButtonFormField<int>(
      value: value,
      decoration: InputDecoration(
        contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
      ),
      isExpanded: true,
      items: items
          .map((e) => DropdownMenuItem(value: e.key, child: Text(e.value, style: const TextStyle(fontSize: 13))))
          .toList(),
      onChanged: (v) {
        if (v != null) onChanged(v);
      },
    );
  }
}
