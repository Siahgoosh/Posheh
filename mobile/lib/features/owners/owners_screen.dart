import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/api/api_client.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/formatters.dart';
import '../../core/widgets/glass_card.dart';
import '../../core/widgets/page_shell.dart';
import '../common/data_list_screen.dart';

class OwnersScreen extends ConsumerStatefulWidget {
  const OwnersScreen({super.key});

  @override
  ConsumerState<OwnersScreen> createState() => _OwnersScreenState();
}

class _OwnersScreenState extends ConsumerState<OwnersScreen> {
  final _searchCtrl = TextEditingController();
  final _nameCtrl = TextEditingController();
  final _mobileCtrl = TextEditingController();
  final _nationalIdCtrl = TextEditingController();
  final _notesCtrl = TextEditingController();
  List<dynamic> _owners = [];
  bool _loading = true;
  bool _saving = false;
  bool _showForm = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    _nameCtrl.dispose();
    _mobileCtrl.dispose();
    _nationalIdCtrl.dispose();
    _notesCtrl.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = _owners.isEmpty;
      _error = null;
    });
    try {
      final items = await ref.read(apiClientProvider).getOwners(q: _searchCtrl.text.trim());
      if (mounted) setState(() { _owners = items; _loading = false; });
    } on ApiException catch (e) {
      if (mounted) setState(() { _error = e.message; _loading = false; });
    } catch (_) {
      if (mounted) setState(() { _error = 'خطا در دریافت مالکین'; _loading = false; });
    }
  }

  Future<void> _create() async {
    if (_nameCtrl.text.trim().isEmpty) return;
    setState(() => _saving = true);
    try {
      await ref.read(apiClientProvider).createOwner({
        'name': _nameCtrl.text.trim(),
        if (_mobileCtrl.text.trim().isNotEmpty) 'mobile': _mobileCtrl.text.trim(),
        if (_nationalIdCtrl.text.trim().isNotEmpty) 'national_id': _nationalIdCtrl.text.trim(),
        if (_notesCtrl.text.trim().isNotEmpty) 'notes': _notesCtrl.text.trim(),
      });
      _nameCtrl.clear();
      _mobileCtrl.clear();
      _nationalIdCtrl.clear();
      _notesCtrl.clear();
      setState(() => _showForm = false);
      await _load();
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return PageShell(
      title: 'مالکین',
      actions: [
        IconButton(
          icon: Icon(_showForm ? Icons.close_rounded : Icons.person_add_outlined),
          onPressed: () => setState(() => _showForm = !_showForm),
        ),
        IconButton(icon: const Icon(Icons.refresh_rounded), onPressed: _load),
      ],
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  TextField(
                    controller: _searchCtrl,
                    decoration: InputDecoration(
                      hintText: 'جستجو نام، موبایل، کدملی…',
                      prefixIcon: const Icon(Icons.search_rounded),
                      suffixIcon: IconButton(icon: const Icon(Icons.search), onPressed: _load),
                    ),
                    onSubmitted: (_) => _load(),
                  ),
                  if (_showForm) ...[
                    const SizedBox(height: 12),
                    GlassCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          const Text('مالک جدید', style: TextStyle(fontWeight: FontWeight.w700)),
                          const SizedBox(height: 10),
                          TextField(controller: _nameCtrl, decoration: const InputDecoration(labelText: 'نام *')),
                          const SizedBox(height: 8),
                          TextField(controller: _mobileCtrl, decoration: const InputDecoration(labelText: 'موبایل'), keyboardType: TextInputType.phone, textDirection: TextDirection.ltr),
                          const SizedBox(height: 8),
                          TextField(controller: _nationalIdCtrl, decoration: const InputDecoration(labelText: 'کد ملی'), textDirection: TextDirection.ltr),
                          const SizedBox(height: 8),
                          TextField(controller: _notesCtrl, decoration: const InputDecoration(labelText: 'یادداشت'), maxLines: 2),
                          const SizedBox(height: 10),
                          FilledButton(onPressed: _saving ? null : _create, child: Text(_saving ? 'در حال ثبت…' : 'ثبت مالک')),
                        ],
                      ),
                    ),
                  ],
                  const SizedBox(height: 16),
                  if (_error != null)
                    Text(_error!, style: const TextStyle(color: AppColors.danger))
                  else if (_owners.isEmpty)
                    const Padding(
                      padding: EdgeInsets.symmetric(vertical: 40),
                      child: Center(child: Text('مالکی ثبت نشده', style: TextStyle(color: AppColors.muted))),
                    )
                  else
                    for (final o in _owners)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 10),
                        child: simpleListTile(
                          title: '${o['name'] ?? '—'}',
                          subtitle: [o['mobile'], o['properties_count'] != null ? '${formatNumber(o['properties_count'] as num)} ملک' : null]
                              .where((e) => e != null && '$e'.isNotEmpty)
                              .join(' · '),
                          onTap: () => context.push('/owners/${o['id']}'),
                        ),
                      ),
                ],
              ),
            ),
    );
  }
}

class OwnerDetailScreen extends ConsumerStatefulWidget {
  final int id;
  const OwnerDetailScreen({super.key, required this.id});

  @override
  ConsumerState<OwnerDetailScreen> createState() => _OwnerDetailScreenState();
}

class _OwnerDetailScreenState extends ConsumerState<OwnerDetailScreen> {
  Map<String, dynamic>? _owner;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final data = await ref.read(apiClientProvider).getOwner(widget.id);
      if (mounted) setState(() { _owner = data; _loading = false; });
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final o = _owner;
    final properties = (o?['properties'] as List?) ?? const [];
    return PageShell(
      title: o?['name']?.toString() ?? 'مالک',
      actions: [IconButton(icon: const Icon(Icons.refresh_rounded), onPressed: _load)],
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : o == null
              ? const Center(child: Text('مالک یافت نشد', style: TextStyle(color: AppColors.muted)))
              : ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    GlassCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          if (o['mobile'] != null) Text('موبایل: ${o['mobile']}', textDirection: TextDirection.ltr),
                          if (o['national_id'] != null) Text('کد ملی: ${o['national_id']}', textDirection: TextDirection.ltr),
                          if (o['notes'] != null) ...[
                            const SizedBox(height: 8),
                            Text('${o['notes']}', style: const TextStyle(color: AppColors.muted)),
                          ],
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                    Text('املاک (${properties.length})', style: const TextStyle(fontWeight: FontWeight.w700)),
                    const SizedBox(height: 8),
                    if (properties.isEmpty)
                      const Text('ملکی ثبت نشده', style: TextStyle(color: AppColors.muted))
                    else
                      for (final p in properties)
                        Padding(
                          padding: const EdgeInsets.only(bottom: 8),
                          child: simpleListTile(
                            title: '${p['code'] ?? ''}',
                            subtitle: '${p['type_label'] ?? ''} · ${p['city'] ?? ''}',
                            onTap: () => context.push('/properties/${p['id']}'),
                          ),
                        ),
                  ],
                ),
    );
  }
}
