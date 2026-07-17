import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/api/api_client.dart';
import '../../core/widgets/glass_card.dart';
import '../../core/widgets/page_shell.dart';

class PropertyFormScreen extends ConsumerStatefulWidget {
  final int? editId;
  const PropertyFormScreen({super.key, this.editId});

  @override
  ConsumerState<PropertyFormScreen> createState() => _PropertyFormScreenState();
}

class _PropertyFormScreenState extends ConsumerState<PropertyFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _code = TextEditingController();
  final _title = TextEditingController();
  final _ownerName = TextEditingController();
  final _ownerMobile = TextEditingController();
  final _contact2 = TextEditingController();
  final _price = TextEditingController();
  final _deposit = TextEditingController();
  final _rent = TextEditingController();
  final _area = TextEditingController();
  final _rooms = TextEditingController();
  final _province = TextEditingController(text: 'تهران');
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
  bool _schemaLoading = true;

  List<(String, String)> _types = [('sale', 'فروش')];
  List<(String, String)> _categories = [('apartment', 'آپارتمان')];

  static const _permissions = [
    ('office', 'دفتری'),
    ('team', 'تیمی'),
    ('private', 'خصوصی'),
    ('manager_only', 'فقط مدیر'),
  ];

  @override
  void initState() {
    super.initState();
    _loadSchema();
    if (widget.editId != null) _loadProperty();
  }

  Future<void> _loadProperty() async {
    try {
      final res = await ref.read(apiClientProvider).getProperty(widget.editId!);
      final p = Map<String, dynamic>.from((res['data'] ?? res) as Map);
      _code.text = '${p['code'] ?? ''}';
      _title.text = '${p['title'] ?? ''}';
      _ownerName.text = '${p['owner_name'] ?? ''}';
      _ownerMobile.text = '${p['owner_mobile'] ?? ''}';
      _price.text = p['price'] != null ? '${p['price']}' : '';
      _deposit.text = p['deposit'] != null ? '${p['deposit']}' : '';
      _rent.text = p['rent'] != null ? '${p['rent']}' : '';
      _area.text = p['area'] != null ? '${p['area']}' : '';
      _rooms.text = p['rooms'] != null ? '${p['rooms']}' : '';
      _province.text = '${p['province'] ?? 'تهران'}';
      _city.text = '${p['city'] ?? ''}';
      _district.text = '${p['district'] ?? ''}';
      _address.text = '${p['address'] ?? ''}';
      _description.text = '${p['description'] ?? ''}';
      setState(() {
        _type = '${p['type'] ?? _type}';
        _category = '${p['property_category'] ?? _category}';
        _permission = '${p['permission'] ?? _permission}';
        _parking = p['has_parking'] == true;
        _elevator = p['has_elevator'] == true;
        _storage = p['has_storage'] == true;
      });
    } catch (_) {}
  }

  Future<void> _loadSchema() async {
    try {
      final schema = await ref.read(apiClientProvider).getFilingSchema();
      final pt = (schema['property_types'] as List?) ?? [];
      final tt = (schema['transaction_types'] as List?) ?? [];
      setState(() {
        _categories = pt.map((e) => (e['value'].toString(), e['label'].toString())).toList();
        _types = tt.map((e) => (e['value'].toString(), e['label'].toString())).toList();
        if (_categories.isNotEmpty) _category = _categories.first.$1;
        if (_types.isNotEmpty) _type = _types.first.$1;
        _schemaLoading = false;
      });
    } catch (_) {
      if (mounted) setState(() => _schemaLoading = false);
    }
  }

  @override
  void dispose() {
    for (final c in [
      _code, _title, _ownerName, _ownerMobile, _contact2, _price, _deposit, _rent,
      _area, _rooms, _province, _city, _district, _address, _description,
    ]) {
      c.dispose();
    }
    super.dispose();
  }

  bool get _showsSale => ['sale', 'pre_sale', 'installment_sale', 'auction', 'exchange', 'barter', 'construction_partnership'].contains(_type);
  bool get _showsRent => ['rent', 'mortgage_rent', 'full_mortgage', 'mortgage'].contains(_type);

  int? _int(TextEditingController c) {
    final v = c.text.trim().replaceAll('٬', '').replaceAll(',', '');
    return v.isEmpty ? null : int.tryParse(v);
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_ownerMobile.text.trim().length < 11) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('شماره موبایل مالک الزامی است')),
      );
      return;
    }
    setState(() => _loading = true);
    try {
      final data = <String, dynamic>{
        'code': _code.text.trim(),
        'title': _title.text.trim().isNotEmpty ? _title.text.trim() : _code.text.trim(),
        'type': _type,
        'property_category': _category,
        'permission': _permission,
        'owner_name': _ownerName.text.trim(),
        'owner_mobile': _ownerMobile.text.trim(),
        if (_contact2.text.trim().isNotEmpty) 'contact_phone_2': _contact2.text.trim(),
        if (_showsSale && _int(_price) != null) 'price': _int(_price),
        if (_showsRent && _int(_deposit) != null) 'deposit': _int(_deposit),
        if (_showsRent && _int(_rent) != null) 'rent': _int(_rent),
        if (_int(_area) != null) 'area': _int(_area),
        if (_int(_rooms) != null) 'rooms': _int(_rooms),
        'province': _province.text.trim(),
        'city': _city.text.trim(),
        if (_district.text.trim().isNotEmpty) 'district': _district.text.trim(),
        if (_address.text.trim().isNotEmpty) 'address': _address.text.trim(),
        if (_description.text.trim().isNotEmpty) 'description': _description.text.trim(),
        'has_parking': _parking,
        'has_elevator': _elevator,
        'has_storage': _storage,
      };
      final res = widget.editId != null
          ? await ref.read(apiClientProvider).updateProperty(widget.editId!, data)
          : await ref.read(apiClientProvider).createProperty(data);
      final id = widget.editId ?? res['data']?['id'] ?? res['id'];
      if (mounted && id != null) context.go('/properties/$id');
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_schemaLoading) {
      return PageShell(
        title: widget.editId != null ? 'ویرایش ملک' : 'ثبت فایل',
        body: const Center(child: CircularProgressIndicator()),
      );
    }

    return PageShell(
      title: widget.editId != null ? 'ویرایش ملک' : 'ثبت فایل جدید',
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            GlassCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const Text('اطلاعات عمومی', style: TextStyle(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _code,
                    decoration: const InputDecoration(labelText: 'کد فایل *'),
                    validator: (v) => v == null || v.trim().isEmpty ? 'الزامی' : null,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _title,
                    decoration: const InputDecoration(labelText: 'عنوان فایل *'),
                    validator: (v) => v == null || v.trim().isEmpty ? 'الزامی' : null,
                  ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(
                    value: _type,
                    decoration: const InputDecoration(labelText: 'نوع معامله'),
                    items: _types.map((t) => DropdownMenuItem(value: t.$1, child: Text(t.$2))).toList(),
                    onChanged: (v) => setState(() => _type = v ?? _type),
                  ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(
                    value: _category,
                    decoration: const InputDecoration(labelText: 'نوع ملک'),
                    items: _categories.map((c) => DropdownMenuItem(value: c.$1, child: Text(c.$2))).toList(),
                    onChanged: (v) => setState(() => _category = v ?? _category),
                  ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(
                    value: _permission,
                    decoration: const InputDecoration(labelText: 'سطح دسترسی'),
                    items: _permissions.map((p) => DropdownMenuItem(value: p.$1, child: Text(p.$2))).toList(),
                    onChanged: (v) => setState(() => _permission = v ?? _permission),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
            GlassCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const Text('مالک', style: TextStyle(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 12),
                  TextFormField(controller: _ownerName, decoration: const InputDecoration(labelText: 'نام مالک')),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _ownerMobile,
                    decoration: const InputDecoration(labelText: 'موبایل مالک *'),
                    keyboardType: TextInputType.phone,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _contact2,
                    decoration: const InputDecoration(labelText: 'شماره دوم'),
                    keyboardType: TextInputType.phone,
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
            GlassCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const Text('موقعیت', style: TextStyle(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 12),
                  TextFormField(controller: _province, decoration: const InputDecoration(labelText: 'استان *')),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _city,
                    decoration: const InputDecoration(labelText: 'شهر *'),
                    validator: (v) => v == null || v.trim().isEmpty ? 'الزامی' : null,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(controller: _district, decoration: const InputDecoration(labelText: 'منطقه')),
                  const SizedBox(height: 12),
                  TextFormField(controller: _address, decoration: const InputDecoration(labelText: 'آدرس')),
                ],
              ),
            ),
            const SizedBox(height: 12),
            GlassCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const Text('معامله و مشخصات', style: TextStyle(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 12),
                  if (_showsSale)
                    TextFormField(controller: _price, decoration: const InputDecoration(labelText: 'قیمت کل (تومان)'), keyboardType: TextInputType.number),
                  if (_showsRent) ...[
                    TextFormField(controller: _deposit, decoration: const InputDecoration(labelText: 'رهن / ودیعه'), keyboardType: TextInputType.number),
                    const SizedBox(height: 12),
                    TextFormField(controller: _rent, decoration: const InputDecoration(labelText: 'اجاره ماهانه'), keyboardType: TextInputType.number),
                  ],
                  const SizedBox(height: 12),
                  TextFormField(controller: _area, decoration: const InputDecoration(labelText: 'متراژ بنا'), keyboardType: TextInputType.number),
                  const SizedBox(height: 12),
                  TextFormField(controller: _rooms, decoration: const InputDecoration(labelText: 'تعداد خواب'), keyboardType: TextInputType.number),
                  SwitchListTile(title: const Text('پارکینگ'), value: _parking, onChanged: (v) => setState(() => _parking = v)),
                  SwitchListTile(title: const Text('آسانسور'), value: _elevator, onChanged: (v) => setState(() => _elevator = v)),
                  SwitchListTile(title: const Text('انباری'), value: _storage, onChanged: (v) => setState(() => _storage = v)),
                  const SizedBox(height: 12),
                  TextFormField(controller: _description, decoration: const InputDecoration(labelText: 'توضیحات'), maxLines: 4),
                ],
              ),
            ),
            const SizedBox(height: 20),
            ElevatedButton(
              onPressed: _loading ? null : _submit,
              child: _loading
                  ? const SizedBox(height: 22, width: 22, child: CircularProgressIndicator(strokeWidth: 2))
                  : Text(widget.editId != null ? 'ذخیره تغییرات' : 'ثبت فایل'),
            ),
          ],
        ),
      ),
    );
  }
}
