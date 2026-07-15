import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/api/api_client.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/glass_card.dart';

const _types = [
  ('sale', 'فروش'),
  ('rent', 'اجاره'),
  ('mortgage', 'رهن'),
  ('pre_sale', 'پیش‌فروش'),
  ('land', 'زمین'),
  ('commercial', 'تجاری'),
];

const _categories = [
  ('apartment', 'آپارتمان'),
  ('villa', 'ویلایی'),
  ('land', 'زمین'),
  ('commercial', 'تجاری'),
  ('office', 'اداری'),
  ('store', 'مغازه'),
];

const _permissions = [
  ('office', 'دفتری'),
  ('exclusive', 'انحصاری'),
  ('shared', 'مشارکتی'),
];

class PropertyFormScreen extends ConsumerStatefulWidget {
  const PropertyFormScreen({super.key});

  @override
  ConsumerState<PropertyFormScreen> createState() => _PropertyFormScreenState();
}

class _PropertyFormScreenState extends ConsumerState<PropertyFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _code = TextEditingController();
  final _ownerName = TextEditingController();
  final _ownerMobile = TextEditingController();
  final _price = TextEditingController();
  final _deposit = TextEditingController();
  final _rent = TextEditingController();
  final _area = TextEditingController();
  final _rooms = TextEditingController();
  final _city = TextEditingController();
  final _district = TextEditingController();
  final _address = TextEditingController();
  final _description = TextEditingController();

  String _type = 'sale';
  String _category = 'apartment';
  String _permission = 'office';
  bool _parking = false;
  bool _elevator = false;
  bool _storage = false;
  bool _loading = false;

  bool get _showsSale => _type == 'sale' || _type == 'pre_sale';
  bool get _showsRent => _type == 'rent' || _type == 'mortgage';

  @override
  void dispose() {
    for (final c in [
      _code, _ownerName, _ownerMobile, _price, _deposit, _rent,
      _area, _rooms, _city, _district, _address, _description,
    ]) {
      c.dispose();
    }
    super.dispose();
  }

  int? _int(TextEditingController c) {
    final v = c.text.trim().replaceAll('٬', '').replaceAll(',', '');
    return v.isEmpty ? null : int.tryParse(v);
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _loading = true);
    try {
      final data = <String, dynamic>{
        'code': _code.text.trim(),
        'type': _type,
        'property_category': _category,
        'permission': _permission,
        if (_ownerName.text.trim().isNotEmpty) 'owner_name': _ownerName.text.trim(),
        if (_ownerMobile.text.trim().isNotEmpty) 'owner_mobile': _ownerMobile.text.trim(),
        if (_showsSale && _int(_price) != null) 'price': _int(_price),
        if (_showsRent && _int(_deposit) != null) 'deposit': _int(_deposit),
        if (_showsRent && _int(_rent) != null) 'rent': _int(_rent),
        if (_int(_area) != null) 'area': _int(_area),
        if (_int(_rooms) != null) 'rooms': _int(_rooms),
        if (_city.text.trim().isNotEmpty) 'city': _city.text.trim(),
        if (_district.text.trim().isNotEmpty) 'district': _district.text.trim(),
        if (_address.text.trim().isNotEmpty) 'address': _address.text.trim(),
        if (_description.text.trim().isNotEmpty) 'description': _description.text.trim(),
        'has_parking': _parking,
        'has_elevator': _elevator,
        'has_storage': _storage,
      };
      await ref.read(apiClientProvider).createProperty(data);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('ملک با موفقیت ثبت شد')),
      );
      context.pop();
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(e.message)));
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(const SnackBar(content: Text('خطا در ثبت ملک')));
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('ثبت ملک جدید')),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 100),
          children: [
            _section('اطلاعات پایه', [
              TextFormField(
                controller: _code,
                decoration: const InputDecoration(labelText: 'کد ملک *'),
                validator: (v) =>
                    (v == null || v.trim().isEmpty) ? 'کد ملک الزامی است' : null,
              ),
              const SizedBox(height: 12),
              _dropdown('نوع معامله', _type, _types,
                  (v) => setState(() => _type = v)),
              const SizedBox(height: 12),
              _dropdown('نوع ملک', _category, _categories,
                  (v) => setState(() => _category = v)),
              const SizedBox(height: 12),
              _dropdown('سطح دسترسی', _permission, _permissions,
                  (v) => setState(() => _permission = v)),
            ]),
            _section('قیمت', [
              if (_showsSale)
                _numField(_price, 'قیمت فروش (تومان)'),
              if (_showsRent) ...[
                _numField(_deposit, 'ودیعه/رهن (تومان)'),
                const SizedBox(height: 12),
                _numField(_rent, 'اجاره ماهانه (تومان)'),
              ],
              if (!_showsSale && !_showsRent)
                _numField(_price, 'قیمت (تومان)'),
            ]),
            _section('مشخصات', [
              Row(
                children: [
                  Expanded(child: _numField(_area, 'متراژ')),
                  const SizedBox(width: 12),
                  Expanded(child: _numField(_rooms, 'تعداد خواب')),
                ],
              ),
              const SizedBox(height: 6),
              _switch('پارکینگ', _parking, (v) => setState(() => _parking = v)),
              _switch('آسانسور', _elevator, (v) => setState(() => _elevator = v)),
              _switch('انباری', _storage, (v) => setState(() => _storage = v)),
            ]),
            _section('موقعیت', [
              Row(
                children: [
                  Expanded(child: _textField(_city, 'شهر')),
                  const SizedBox(width: 12),
                  Expanded(child: _textField(_district, 'منطقه')),
                ],
              ),
              const SizedBox(height: 12),
              _textField(_address, 'آدرس'),
            ]),
            _section('مالک', [
              _textField(_ownerName, 'نام مالک'),
              const SizedBox(height: 12),
              TextFormField(
                controller: _ownerMobile,
                keyboardType: TextInputType.phone,
                textDirection: TextDirection.ltr,
                inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                decoration: const InputDecoration(labelText: 'موبایل مالک'),
              ),
            ]),
            _section('توضیحات', [
              TextFormField(
                controller: _description,
                maxLines: 4,
                decoration:
                    const InputDecoration(labelText: 'توضیحات تکمیلی'),
              ),
            ]),
            const SizedBox(height: 8),
            ElevatedButton(
              onPressed: _loading ? null : _submit,
              child: _loading
                  ? const SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(
                          strokeWidth: 2.4, color: AppColors.primaryFg),
                    )
                  : const Text('ثبت ملک'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _section(String title, List<Widget> children) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: GlassCard(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(title,
                style: const TextStyle(
                    fontWeight: FontWeight.w700, fontSize: 15)),
            const SizedBox(height: 14),
            ...children,
          ],
        ),
      ),
    );
  }

  Widget _textField(TextEditingController c, String label) {
    return TextFormField(
      controller: c,
      decoration: InputDecoration(labelText: label),
    );
  }

  Widget _numField(TextEditingController c, String label) {
    return TextFormField(
      controller: c,
      keyboardType: TextInputType.number,
      textDirection: TextDirection.ltr,
      inputFormatters: [FilteringTextInputFormatter.digitsOnly],
      decoration: InputDecoration(labelText: label),
    );
  }

  Widget _dropdown(String label, String value,
      List<(String, String)> options, ValueChanged<String> onChanged) {
    return DropdownButtonFormField<String>(
      initialValue: value,
      isExpanded: true,
      decoration: InputDecoration(labelText: label),
      dropdownColor: const Color(0xFF141B26),
      items: [
        for (final o in options)
          DropdownMenuItem(value: o.$1, child: Text(o.$2)),
      ],
      onChanged: (v) => onChanged(v ?? value),
    );
  }

  Widget _switch(String label, bool value, ValueChanged<bool> onChanged) {
    return SwitchListTile(
      contentPadding: EdgeInsets.zero,
      dense: true,
      title: Text(label, style: const TextStyle(fontSize: 14)),
      value: value,
      activeThumbColor: AppColors.primary,
      onChanged: onChanged,
    );
  }
}
