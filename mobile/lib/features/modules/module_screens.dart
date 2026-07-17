import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/api/api_client.dart';
import '../../core/auth/auth_controller.dart';
import '../../core/utils/formatters.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/glass_card.dart';
import '../../core/widgets/jalali_date_field.dart';
import '../../core/widgets/page_shell.dart';
import 'package:url_launcher/url_launcher.dart';
import '../common/data_list_screen.dart';

class VisitsScreen extends ConsumerStatefulWidget {
  const VisitsScreen({super.key});
  @override
  ConsumerState<VisitsScreen> createState() => _VisitsScreenState();
}

class _VisitsScreenState extends ConsumerState<VisitsScreen> {
  final _propertyIdCtrl = TextEditingController();
  final _customerIdCtrl = TextEditingController();
  final _notesCtrl = TextEditingController();
  DateTime _visitAt = DateTime.now().add(const Duration(hours: 1));
  TimeOfDay _visitTime = TimeOfDay.now();
  List<dynamic> _visits = [];
  List<dynamic> _upcoming = [];
  bool _loading = true;
  bool _saving = false;
  bool _showForm = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _propertyIdCtrl.dispose();
    _customerIdCtrl.dispose();
    _notesCtrl.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() => _loading = _visits.isEmpty);
    try {
      final api = ref.read(apiClientProvider);
      final visits = await api.getVisits();
      final upcoming = await api.getVisitsUpcoming();
      if (mounted) setState(() { _visits = visits; _upcoming = upcoming; _loading = false; });
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _create() async {
    final propertyId = int.tryParse(_propertyIdCtrl.text.trim());
    if (propertyId == null) return;
    setState(() => _saving = true);
    try {
      final dt = DateTime(_visitAt.year, _visitAt.month, _visitAt.day, _visitTime.hour, _visitTime.minute);
      await ref.read(apiClientProvider).createVisit({
        'property_id': propertyId,
        if (_customerIdCtrl.text.trim().isNotEmpty) 'customer_id': int.parse(_customerIdCtrl.text.trim()),
        'visit_at': dt.toIso8601String(),
        if (_notesCtrl.text.trim().isNotEmpty) 'notes': _notesCtrl.text.trim(),
      });
      _propertyIdCtrl.clear();
      _customerIdCtrl.clear();
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
      title: 'بازدیدها',
      actions: [
        IconButton(
          icon: Icon(_showForm ? Icons.close_rounded : Icons.add_rounded),
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
                  if (_showForm)
                    GlassCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          const Text('بازدید جدید', style: TextStyle(fontWeight: FontWeight.w700)),
                          const SizedBox(height: 10),
                          TextField(controller: _propertyIdCtrl, decoration: const InputDecoration(labelText: 'شناسه ملک *'), keyboardType: TextInputType.number, textDirection: TextDirection.ltr),
                          const SizedBox(height: 8),
                          TextField(controller: _customerIdCtrl, decoration: const InputDecoration(labelText: 'شناسه مشتری (اختیاری)'), keyboardType: TextInputType.number, textDirection: TextDirection.ltr),
                          const SizedBox(height: 8),
                          JalaliDateField(label: 'تاریخ بازدید', value: _visitAt, onChanged: (d) => setState(() => _visitAt = d)),
                          const SizedBox(height: 8),
                          ListTile(
                            contentPadding: EdgeInsets.zero,
                            title: const Text('ساعت'),
                            subtitle: Text(toPersianDigits('${_visitTime.hour.toString().padLeft(2, '0')}:${_visitTime.minute.toString().padLeft(2, '0')}')),
                            trailing: const Icon(Icons.schedule_rounded),
                            onTap: () async {
                              final t = await showTimePicker(context: context, initialTime: _visitTime);
                              if (t != null) setState(() => _visitTime = t);
                            },
                          ),
                          const SizedBox(height: 8),
                          TextField(controller: _notesCtrl, decoration: const InputDecoration(labelText: 'یادداشت'), maxLines: 2),
                          const SizedBox(height: 10),
                          FilledButton(onPressed: _saving ? null : _create, child: Text(_saving ? 'در حال ثبت…' : 'ثبت بازدید')),
                        ],
                      ),
                    ),
                  if (_upcoming.isNotEmpty) ...[
                    const SizedBox(height: 16),
                    const Text('۷ روز آینده', style: TextStyle(fontWeight: FontWeight.w700)),
                    const SizedBox(height: 8),
                    for (final v in _upcoming)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 8),
                        child: simpleListTile(
                          title: '${v['property']?['code'] ?? 'بازدید'}',
                          subtitle: '${v['visit_at_jalali'] ?? ''} · ${v['customer']?['name'] ?? 'بدون مشتری'}',
                        ),
                      ),
                  ],
                  const SizedBox(height: 16),
                  const Text('همه بازدیدها', style: TextStyle(fontWeight: FontWeight.w700)),
                  const SizedBox(height: 8),
                  if (_visits.isEmpty)
                    const Padding(
                      padding: EdgeInsets.symmetric(vertical: 40),
                      child: Center(child: Text('بازدیدی ثبت نشده', style: TextStyle(color: AppColors.muted))),
                    )
                  else
                    for (final v in _visits)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 10),
                        child: simpleListTile(
                          title: '${v['property']?['code'] ?? v['title'] ?? 'بازدید'}',
                          subtitle: [v['visit_at_jalali'], v['status_label']].where((e) => e != null && '$e'.isNotEmpty).join(' · '),
                        ),
                      ),
                ],
              ),
            ),
    );
  }
}

class FavoritesScreen extends StatelessWidget {
  const FavoritesScreen({super.key});
  @override
  Widget build(BuildContext context) => DataListScreen(
        title: 'علاقه‌مندی‌ها',
        loader: (api) => api.getFavorites(),
        emptyText: 'علاقه‌مندی ندارید',
        itemBuilder: (_, p) => simpleListTile(
          title: '${p['code'] ?? ''}',
          subtitle: '${p['type_label'] ?? ''} · ${p['city'] ?? ''}',
          onTap: () => context.push('/properties/${p['id']}'),
        ),
      );
}

class CommissionsScreen extends ConsumerStatefulWidget {
  const CommissionsScreen({super.key});
  @override
  ConsumerState<CommissionsScreen> createState() => _CommissionsScreenState();
}

class _CommissionsScreenState extends ConsumerState<CommissionsScreen> {
  Map<String, dynamic>? _data;
  bool _loading = true;
  bool _showForm = false;
  bool _showSettings = false;
  final _userIdCtrl = TextEditingController();
  final _titleCtrl = TextEditingController();
  final _baseCtrl = TextEditingController();
  final _rateCtrl = TextEditingController(text: '30');
  int _saleRate = 30;
  int _rentRate = 50;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _userIdCtrl.dispose();
    _titleCtrl.dispose();
    _baseCtrl.dispose();
    _rateCtrl.dispose();
    super.dispose();
  }

  bool get _isManager {
    final user = ref.read(authControllerProvider).user;
    return user?.canManage ?? false;
  }

  Future<void> _load() async {
    setState(() => _loading = _data == null);
    try {
      final api = ref.read(apiClientProvider);
      final data = await api.getCommissionsFull();
      Map<String, dynamic>? settings;
      if (_isManager) {
        try { settings = await api.getCommissionSettings(); } catch (_) {}
      }
      if (mounted) setState(() { _data = data; _loading = false;
        if (settings != null) {
          _saleRate = (settings['sale_rate_percent'] as num?)?.toInt() ?? 30;
          _rentRate = (settings['rent_rate_percent'] as num?)?.toInt() ?? 50;
        }
      });
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _create() async {
    try {
      await ref.read(apiClientProvider).createCommission({
        'user_id': int.parse(_userIdCtrl.text.trim()),
        'title': _titleCtrl.text.trim(),
        'base_amount': int.parse(_baseCtrl.text.trim()),
        'rate_percent': int.parse(_rateCtrl.text.trim()),
      });
      setState(() => _showForm = false);
      await _load();
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  Future<void> _saveSettings() async {
    try {
      await ref.read(apiClientProvider).updateCommissionSettings({
        'sale_rate_percent': _saleRate,
        'rent_rate_percent': _rentRate,
      });
      setState(() => _showSettings = false);
      await _load();
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final summary = _data?['summary'] as Map<String, dynamic>? ?? {};
    final items = (_data?['data'] as List?) ?? const [];
    return PageShell(
      title: 'کمیسیون',
      actions: [
        if (_isManager) IconButton(icon: const Icon(Icons.settings_outlined), onPressed: () => setState(() => _showSettings = !_showSettings)),
        if (_isManager) IconButton(icon: Icon(_showForm ? Icons.close : Icons.add), onPressed: () => setState(() => _showForm = !_showForm)),
        IconButton(icon: const Icon(Icons.refresh_rounded), onPressed: _load),
      ],
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  Row(
                    children: [
                      Expanded(child: _summaryTile('معوق', formatPrice(summary['pending_total'] as num? ?? 0))),
                      const SizedBox(width: 8),
                      Expanded(child: _summaryTile('پرداخت ماه', formatPrice(summary['paid_month'] as num? ?? 0))),
                    ],
                  ),
                  const SizedBox(height: 12),
                  if (_showSettings && _isManager)
                    GlassCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          const Text('نرخ کمیسیون', style: TextStyle(fontWeight: FontWeight.w700)),
                          TextField(decoration: const InputDecoration(labelText: 'فروش %'), keyboardType: TextInputType.number, controller: TextEditingController(text: '$_saleRate'), onChanged: (v) => _saleRate = int.tryParse(v) ?? _saleRate),
                          TextField(decoration: const InputDecoration(labelText: 'اجاره %'), keyboardType: TextInputType.number, controller: TextEditingController(text: '$_rentRate'), onChanged: (v) => _rentRate = int.tryParse(v) ?? _rentRate),
                          FilledButton(onPressed: _saveSettings, child: const Text('ذخیره نرخ‌ها')),
                        ],
                      ),
                    ),
                  if (_showForm && _isManager) ...[
                    const SizedBox(height: 12),
                    GlassCard(
                      child: Column(
                        children: [
                          TextField(controller: _userIdCtrl, decoration: const InputDecoration(labelText: 'شناسه مشاور *'), keyboardType: TextInputType.number, textDirection: TextDirection.ltr),
                          TextField(controller: _titleCtrl, decoration: const InputDecoration(labelText: 'عنوان *')),
                          TextField(controller: _baseCtrl, decoration: const InputDecoration(labelText: 'مبلغ پایه'), keyboardType: TextInputType.number, textDirection: TextDirection.ltr),
                          TextField(controller: _rateCtrl, decoration: const InputDecoration(labelText: 'درصد'), keyboardType: TextInputType.number, textDirection: TextDirection.ltr),
                          FilledButton(onPressed: _create, child: const Text('ثبت کمیسیون')),
                        ],
                      ),
                    ),
                  ],
                  const SizedBox(height: 16),
                  if (items.isEmpty)
                    const Center(child: Text('کمیسیونی ثبت نشده', style: TextStyle(color: AppColors.muted)))
                  else
                    for (final c in items)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 10),
                        child: GlassCard(
                          child: Row(
                            children: [
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text('${c['title'] ?? 'کمیسیون'}', style: const TextStyle(fontWeight: FontWeight.w600)),
                                    Text('${c['user']?['name'] ?? ''} · ${c['status'] == 'paid' ? 'پرداخت‌شده' : 'در انتظار'}', style: const TextStyle(fontSize: 12, color: AppColors.muted)),
                                  ],
                                ),
                              ),
                              Text(formatPrice(c['commission_amount'] as num?), style: const TextStyle(color: AppColors.primary)),
                              if (_isManager && c['status'] != 'paid')
                                IconButton(
                                  icon: const Icon(Icons.check_circle_outline, color: AppColors.success),
                                  onPressed: () async {
                                    await ref.read(apiClientProvider).payCommission(c['id'] as int);
                                    await _load();
                                  },
                                ),
                            ],
                          ),
                        ),
                      ),
                ],
              ),
            ),
    );
  }

  Widget _summaryTile(String label, String value) {
    return GlassCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(color: AppColors.muted, fontSize: 12)),
          Text(value, style: const TextStyle(fontWeight: FontWeight.bold)),
        ],
      ),
    );
  }
}

class ContractsScreen extends ConsumerStatefulWidget {
  const ContractsScreen({super.key});
  @override
  ConsumerState<ContractsScreen> createState() => _ContractsScreenState();
}

class _ContractsScreenState extends ConsumerState<ContractsScreen> {
  List<dynamic> _contracts = [];
  List<dynamic> _templates = [];
  String? _templateId;
  final _propertyIdCtrl = TextEditingController();
  final _sellerCtrl = TextEditingController();
  final _buyerCtrl = TextEditingController();
  bool _loading = true;
  bool _saving = false;
  bool _showForm = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _propertyIdCtrl.dispose();
    _sellerCtrl.dispose();
    _buyerCtrl.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() => _loading = _contracts.isEmpty);
    try {
      final api = ref.read(apiClientProvider);
      final contracts = await api.getContracts();
      final templates = await api.getContractTemplates();
      if (mounted) {
        setState(() {
          _contracts = contracts;
          _templates = templates;
          _loading = false;
          if (_templateId == null && templates.isNotEmpty) {
            _templateId = '${templates.first['id']}';
          }
        });
      }
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _create() async {
    if (_templateId == null) return;
    setState(() => _saving = true);
    try {
      await ref.read(apiClientProvider).createContract({
        'template_id': int.parse(_templateId!),
        if (_propertyIdCtrl.text.trim().isNotEmpty) 'property_id': int.parse(_propertyIdCtrl.text.trim()),
        'fields': {
          if (_sellerCtrl.text.trim().isNotEmpty) 'seller_name': _sellerCtrl.text.trim(),
          if (_buyerCtrl.text.trim().isNotEmpty) 'buyer_name': _buyerCtrl.text.trim(),
        },
      });
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
    final api = ref.read(apiClientProvider);
    return PageShell(
      title: 'قراردادها',
      actions: [
        IconButton(icon: Icon(_showForm ? Icons.close : Icons.add), onPressed: () => setState(() => _showForm = !_showForm)),
        IconButton(icon: const Icon(Icons.refresh_rounded), onPressed: _load),
      ],
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  if (_showForm)
                    GlassCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          const Text('صدور قرارداد', style: TextStyle(fontWeight: FontWeight.w700)),
                          const SizedBox(height: 10),
                          DropdownButtonFormField<String>(
                            initialValue: _templateId,
                            decoration: const InputDecoration(labelText: 'قالب'),
                            items: _templates.map((t) => DropdownMenuItem(value: '${t['id']}', child: Text('${t['name'] ?? t['slug'] ?? ''}'))).toList(),
                            onChanged: (v) => setState(() => _templateId = v),
                          ),
                          TextField(controller: _propertyIdCtrl, decoration: const InputDecoration(labelText: 'شناسه ملک'), keyboardType: TextInputType.number, textDirection: TextDirection.ltr),
                          TextField(controller: _sellerCtrl, decoration: const InputDecoration(labelText: 'نام فروشنده')),
                          TextField(controller: _buyerCtrl, decoration: const InputDecoration(labelText: 'نام خریدار')),
                          FilledButton(onPressed: _saving ? null : _create, child: Text(_saving ? 'در حال صدور…' : 'صدور قرارداد')),
                        ],
                      ),
                    ),
                  const SizedBox(height: 16),
                  if (_contracts.isEmpty)
                    const Center(child: Text('قراردادی صادر نشده', style: TextStyle(color: AppColors.muted)))
                  else
                    for (final c in _contracts)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 10),
                        child: GlassCard(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('${c['title'] ?? 'قرارداد'}', style: const TextStyle(fontWeight: FontWeight.w600)),
                              Text('${c['status'] ?? ''}', style: const TextStyle(color: AppColors.muted, fontSize: 12)),
                              if (c['created_at_jalali'] != null) Text('${c['created_at_jalali']}', style: const TextStyle(fontSize: 11, color: AppColors.muted)),
                              const SizedBox(height: 8),
                              Wrap(
                                spacing: 8,
                                children: [
                                  if (c['pdf_path'] != null)
                                    OutlinedButton.icon(
                                      onPressed: () => launchContractUrl(api.contractDownloadUrl(c['id'] as int, 'pdf')),
                                      icon: const Icon(Icons.picture_as_pdf, size: 16),
                                      label: const Text('PDF'),
                                    ),
                                  if (c['docx_path'] != null)
                                    OutlinedButton.icon(
                                      onPressed: () => launchContractUrl(api.contractDownloadUrl(c['id'] as int, 'docx')),
                                      icon: const Icon(Icons.description_outlined, size: 16),
                                      label: const Text('Word'),
                                    ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      ),
                ],
              ),
            ),
    );
  }
}

Future<void> launchContractUrl(String url) async {
  final uri = Uri.parse(url);
  if (await canLaunchUrl(uri)) {
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }
}

class TeamScreen extends ConsumerStatefulWidget {
  const TeamScreen({super.key});
  @override
  ConsumerState<TeamScreen> createState() => _TeamScreenState();
}

class _TeamScreenState extends ConsumerState<TeamScreen> {
  final _mobileCtrl = TextEditingController();
  List<dynamic> _members = [];
  bool _loading = true;
  String? _error;
  String? _inviteError;
  bool _inviting = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _mobileCtrl.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = _members.isEmpty;
      _error = null;
    });
    try {
      final members = await ref.read(apiClientProvider).getTeam();
      if (mounted) setState(() { _members = members; _loading = false; });
    } on ApiException catch (e) {
      if (mounted) setState(() { _error = e.message; _loading = false; });
    } catch (_) {
      if (mounted) setState(() { _error = 'خطا در دریافت تیم'; _loading = false; });
    }
  }

  Future<void> _invite() async {
    setState(() { _inviteError = null; _inviting = true; });
    try {
      await ref.read(apiClientProvider).inviteTeamMember(_mobileCtrl.text.trim());
      _mobileCtrl.clear();
      await _load();
    } on ApiException catch (e) {
      if (mounted) setState(() => _inviteError = e.message);
    } catch (_) {
      if (mounted) setState(() => _inviteError = 'خطا در دعوت عضو');
    } finally {
      if (mounted) setState(() => _inviting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return PageShell(
      title: 'تیم',
      actions: [IconButton(icon: const Icon(Icons.refresh_rounded), onPressed: _load)],
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  GlassCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        const Text('دعوت عضو جدید', style: TextStyle(fontWeight: FontWeight.w700)),
                        const SizedBox(height: 10),
                        TextField(
                          controller: _mobileCtrl,
                          keyboardType: TextInputType.phone,
                          decoration: const InputDecoration(hintText: '09121234567'),
                          textDirection: TextDirection.ltr,
                        ),
                        if (_inviteError != null) ...[
                          const SizedBox(height: 8),
                          Text(_inviteError!, style: const TextStyle(color: AppColors.danger, fontSize: 12)),
                        ],
                        const SizedBox(height: 10),
                        FilledButton(
                          onPressed: _inviting ? null : _invite,
                          child: Text(_inviting ? 'در حال ارسال…' : 'دعوت'),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  if (_error != null)
                    Text(_error!, style: const TextStyle(color: AppColors.danger))
                  else if (_members.isEmpty)
                    const Padding(
                      padding: EdgeInsets.symmetric(vertical: 40),
                      child: Center(child: Text('عضوی در تیم نیست', style: TextStyle(color: AppColors.muted))),
                    )
                  else
                    for (final m in _members)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 10),
                        child: simpleListTile(
                          title: '${m['name'] ?? ''}',
                          subtitle: '${m['role_label'] ?? ''} · ${toPersianDigits('${m['mobile'] ?? ''}')}',
                        ),
                      ),
                ],
              ),
            ),
    );
  }
}

class TicketsScreen extends ConsumerStatefulWidget {
  const TicketsScreen({super.key});
  @override
  ConsumerState<TicketsScreen> createState() => _TicketsScreenState();
}

class _TicketsScreenState extends ConsumerState<TicketsScreen> {
  final _subjectCtrl = TextEditingController();
  final _messageCtrl = TextEditingController();
  final _replyCtrls = <int, TextEditingController>{};
  List<dynamic> _tickets = [];
  bool _loading = true;
  String? _error;
  bool _sending = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _subjectCtrl.dispose();
    _messageCtrl.dispose();
    for (final c in _replyCtrls.values) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = _tickets.isEmpty;
      _error = null;
    });
    try {
      final tickets = await ref.read(apiClientProvider).getTickets();
      if (mounted) setState(() { _tickets = tickets; _loading = false; });
    } on ApiException catch (e) {
      if (mounted) setState(() { _error = e.message; _loading = false; });
    } catch (_) {
      if (mounted) setState(() { _error = 'خطا در دریافت تیکت‌ها'; _loading = false; });
    }
  }

  Future<void> _create() async {
    setState(() => _sending = true);
    try {
      await ref.read(apiClientProvider).createTicket(
            subject: _subjectCtrl.text.trim(),
            message: _messageCtrl.text.trim(),
          );
      _subjectCtrl.clear();
      _messageCtrl.clear();
      await _load();
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      }
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  Future<void> _reply(int id) async {
    final ctrl = _replyCtrls.putIfAbsent(id, TextEditingController.new);
    final text = ctrl.text.trim();
    if (text.isEmpty) return;
    try {
      await ref.read(apiClientProvider).replyTicket(id, text);
      ctrl.clear();
      await _load();
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return PageShell(
      title: 'پشتیبانی',
      actions: [IconButton(icon: const Icon(Icons.refresh_rounded), onPressed: _load)],
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  GlassCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        const Text('تیکت جدید', style: TextStyle(fontWeight: FontWeight.w700)),
                        const SizedBox(height: 10),
                        TextField(controller: _subjectCtrl, decoration: const InputDecoration(hintText: 'موضوع')),
                        const SizedBox(height: 8),
                        TextField(
                          controller: _messageCtrl,
                          minLines: 3,
                          maxLines: 5,
                          decoration: const InputDecoration(hintText: 'پیام شما'),
                        ),
                        const SizedBox(height: 10),
                        FilledButton(
                          onPressed: _sending ? null : _create,
                          child: Text(_sending ? 'در حال ارسال…' : 'ارسال تیکت'),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  if (_error != null)
                    Text(_error!, style: const TextStyle(color: AppColors.danger))
                  else if (_tickets.isEmpty)
                    const Padding(
                      padding: EdgeInsets.symmetric(vertical: 40),
                      child: Center(child: Text('تیکتی ثبت نشده', style: TextStyle(color: AppColors.muted))),
                    )
                  else
                    for (final t in _tickets) ...[
                      GlassCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Expanded(child: Text('${t['subject'] ?? 'تیکت'}', style: const TextStyle(fontWeight: FontWeight.w700))),
                                Text('${t['status'] ?? ''}', style: const TextStyle(color: AppColors.muted, fontSize: 12)),
                              ],
                            ),
                            const SizedBox(height: 6),
                            Text('${t['message'] ?? ''}', style: const TextStyle(fontSize: 13, color: AppColors.muted)),
                            if (t['replies'] is List)
                              for (final r in (t['replies'] as List))
                                Padding(
                                  padding: const EdgeInsets.only(top: 8),
                                  child: Container(
                                    padding: const EdgeInsets.all(8),
                                    decoration: BoxDecoration(
                                      color: (r['is_staff'] == true ? AppColors.primary : Colors.white)
                                          .withValues(alpha: 0.08),
                                      borderRadius: BorderRadius.circular(8),
                                    ),
                                    child: Text('${r['message'] ?? ''}', style: const TextStyle(fontSize: 12)),
                                  ),
                                ),
                            const SizedBox(height: 8),
                            Row(
                              children: [
                                Expanded(
                                  child: TextField(
                                    controller: _replyCtrls.putIfAbsent(t['id'] as int, TextEditingController.new),
                                    decoration: const InputDecoration(hintText: 'پاسخ…', isDense: true),
                                  ),
                                ),
                                IconButton(
                                  icon: const Icon(Icons.send_rounded),
                                  onPressed: () => _reply(t['id'] as int),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 10),
                    ],
                ],
              ),
            ),
    );
  }
}

class AccountingScreen extends ConsumerStatefulWidget {
  const AccountingScreen({super.key});
  @override
  ConsumerState<AccountingScreen> createState() => _AccountingScreenState();
}

class _AccountingScreenState extends ConsumerState<AccountingScreen> {
  final _titleCtrl = TextEditingController();
  final _amountCtrl = TextEditingController();
  String _type = 'income';
  DateTime _txDate = DateTime.now();
  Map<String, dynamic>? _summary;
  List<dynamic> _txs = [];
  bool _loading = true;
  String? _error;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _titleCtrl.dispose();
    _amountCtrl.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = _txs.isEmpty && _summary == null;
      _error = null;
    });
    try {
      final api = ref.read(apiClientProvider);
      final summary = await api.getAccountingSummary();
      final txs = await api.getAccounting();
      if (mounted) {
        setState(() {
          _summary = summary;
          _txs = txs;
          _loading = false;
        });
      }
    } on ApiException catch (e) {
      if (mounted) setState(() { _error = e.message; _loading = false; });
    } catch (_) {
      if (mounted) setState(() { _error = 'خطا در دریافت حسابداری'; _loading = false; });
    }
  }

  Future<void> _save() async {
    final amount = int.tryParse(_amountCtrl.text.replaceAll(',', ''));
    if (amount == null || amount < 1 || _titleCtrl.text.trim().isEmpty) return;
    setState(() => _saving = true);
    try {
      await ref.read(apiClientProvider).createAccounting({
        'type': _type,
        'title': _titleCtrl.text.trim(),
        'amount': amount,
        'transaction_date': toIsoDate(_txDate),
      });
      _titleCtrl.clear();
      _amountCtrl.clear();
      await _load();
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return PageShell(
      title: 'حسابداری',
      actions: [IconButton(icon: const Icon(Icons.refresh_rounded), onPressed: _load)],
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  if (_summary != null) ...[
                    Row(
                      children: [
                        Expanded(child: _summaryTile('درآمد ماه', formatPrice(_summary!['month_income'] as num? ?? 0), AppColors.success)),
                        const SizedBox(width: 8),
                        Expanded(child: _summaryTile('هزینه ماه', formatPrice(_summary!['month_expense'] as num? ?? 0), AppColors.danger)),
                      ],
                    ),
                    const SizedBox(height: 8),
                    _summaryTile('مانده ماه', formatPrice(_summary!['month_balance'] as num? ?? 0), AppColors.primary),
                    const SizedBox(height: 16),
                  ],
                  GlassCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        const Text('تراکنش جدید', style: TextStyle(fontWeight: FontWeight.w700)),
                        const SizedBox(height: 10),
                        DropdownButtonFormField<String>(
                          value: _type,
                          decoration: const InputDecoration(labelText: 'نوع'),
                          items: const [
                            DropdownMenuItem(value: 'income', child: Text('درآمد')),
                            DropdownMenuItem(value: 'expense', child: Text('هزینه')),
                          ],
                          onChanged: (v) { if (v != null) setState(() => _type = v); },
                        ),
                        const SizedBox(height: 8),
                        TextField(
                          controller: _amountCtrl,
                          keyboardType: TextInputType.number,
                          decoration: const InputDecoration(labelText: 'مبلغ (تومان)'),
                          textDirection: TextDirection.ltr,
                        ),
                        const SizedBox(height: 8),
                        TextField(controller: _titleCtrl, decoration: const InputDecoration(labelText: 'عنوان')),
                        const SizedBox(height: 8),
                        JalaliDateField(
                          label: 'تاریخ',
                          value: _txDate,
                          onChanged: (d) => setState(() => _txDate = d),
                        ),
                        const SizedBox(height: 12),
                        FilledButton(
                          onPressed: _saving ? null : _save,
                          child: Text(_saving ? 'در حال ثبت…' : 'ثبت'),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  if (_error != null)
                    Text(_error!, style: const TextStyle(color: AppColors.danger))
                  else if (_txs.isEmpty)
                    const Padding(
                      padding: EdgeInsets.symmetric(vertical: 40),
                      child: Center(child: Text('تراکنشی ثبت نشده', style: TextStyle(color: AppColors.muted))),
                    )
                  else
                    for (final t in _txs)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 10),
                        child: simpleListTile(
                          title: '${t['title'] ?? ''}',
                          subtitle: '${t['transaction_date_jalali'] ?? ''} · ${t['type_label'] ?? ''}',
                          trailing: t['amount'] != null ? formatPrice(t['amount'] as num) : null,
                        ),
                      ),
                ],
              ),
            ),
    );
  }

  Widget _summaryTile(String label, String value, Color color) {
    return GlassCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(color: AppColors.muted, fontSize: 12)),
          const SizedBox(height: 4),
          Text(value, style: TextStyle(fontWeight: FontWeight.bold, color: color, fontSize: 15)),
        ],
      ),
    );
  }
}

class CrmScreen extends ConsumerStatefulWidget {
  const CrmScreen({super.key});
  @override
  ConsumerState<CrmScreen> createState() => _CrmScreenState();
}

class _CrmScreenState extends ConsumerState<CrmScreen> {
  final _titleCtrl = TextEditingController();
  List<dynamic> _deals = [];
  bool _loading = true;
  bool _creating = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _titleCtrl.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() => _loading = _deals.isEmpty);
    try {
      final deals = await ref.read(apiClientProvider).getCrmDeals();
      if (mounted) setState(() { _deals = deals; _loading = false; });
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _create() async {
    final title = _titleCtrl.text.trim();
    if (title.isEmpty) return;
    setState(() => _creating = true);
    try {
      await ref.read(apiClientProvider).createCrmDeal(title: title);
      _titleCtrl.clear();
      await _load();
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      }
    } finally {
      if (mounted) setState(() => _creating = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return PageShell(
      title: 'مدیریت فروش',
      actions: [IconButton(icon: const Icon(Icons.refresh_rounded), onPressed: _load)],
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  GlassCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        const Text('معامله جدید', style: TextStyle(fontWeight: FontWeight.w700)),
                        const SizedBox(height: 10),
                        TextField(
                          controller: _titleCtrl,
                          decoration: const InputDecoration(hintText: 'عنوان معامله'),
                        ),
                        const SizedBox(height: 10),
                        FilledButton(
                          onPressed: _creating ? null : _create,
                          child: Text(_creating ? 'در حال ثبت…' : 'ثبت معامله'),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  if (_deals.isEmpty)
                    const SizedBox(
                      height: 200,
                      child: Center(child: Text('معامله‌ای ثبت نشده', style: TextStyle(color: AppColors.muted))),
                    )
                  else
                    for (final d in _deals) ...[
                      Builder(builder: (_) {
                        final deal = Map<String, dynamic>.from(d as Map);
                        const stages = {
                          'lead': 'سرنخ',
                          'contact': 'تماس',
                          'visit': 'بازدید',
                          'negotiation': 'مذاکره',
                          'closed_won': 'موفق',
                          'closed_lost': 'ناموفق',
                        };
                        return GlassCard(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('${deal['title'] ?? 'معامله'}',
                                  style: const TextStyle(fontWeight: FontWeight.bold)),
                              const SizedBox(height: 6),
                              DropdownButtonFormField<String>(
                                initialValue: '${deal['stage'] ?? 'lead'}',
                                decoration: const InputDecoration(labelText: 'مرحله', isDense: true),
                                items: stages.entries
                                    .map((e) => DropdownMenuItem(value: e.key, child: Text(e.value)))
                                    .toList(),
                                onChanged: (stage) async {
                                  if (stage == null) return;
                                  try {
                                    await ref.read(apiClientProvider).updateCrmDeal(deal['id'] as int, {'stage': stage});
                                    await _load();
                                  } on ApiException catch (e) {
                                    if (context.mounted) {
                                      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
                                    }
                                  }
                                },
                              ),
                              if (deal['value'] != null) ...[
                                const SizedBox(height: 6),
                                Text(formatPrice(deal['value'] as num),
                                    style: const TextStyle(color: AppColors.primary)),
                              ],
                            ],
                          ),
                        );
                      }),
                      const SizedBox(height: 10),
                    ],
                ],
              ),
            ),
    );
  }
}

class ReportsScreen extends ConsumerStatefulWidget {
  const ReportsScreen({super.key});
  @override
  ConsumerState<ReportsScreen> createState() => _ReportsScreenState();
}

class _ReportsScreenState extends ConsumerState<ReportsScreen> {
  Map<String, dynamic>? _data;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final data = await ref.read(apiClientProvider).getReports();
      if (mounted) setState(() { _data = data; _loading = false; });
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final props = _data?['properties'] as Map<String, dynamic>? ?? {};
    final crm = _data?['crm'] as Map<String, dynamic>? ?? {};
    final accounting = _data?['accounting'] as Map<String, dynamic>? ?? {};
    final commissions = _data?['commissions'] as Map<String, dynamic>? ?? {};
    final visits = _data?['visits'] as Map<String, dynamic>? ?? {};
    final consultants = (_data?['consultants'] as List?) ?? const [];
    final monthlyTrend = (accounting['monthly_trend'] as List?) ?? const [];
    final pipeline = (crm['pipeline'] as List?) ?? const [];
    final byType = (props['by_type'] as List?) ?? const [];

    return PageShell(
      title: 'گزارش‌ها',
      actions: [IconButton(icon: const Icon(Icons.refresh_rounded), onPressed: _load)],
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      _statCard('ملک فعال', formatNumber(props['active'] as num? ?? 0)),
                      _statCard('کل املاک', formatNumber(props['total'] as num? ?? 0)),
                      _statCard('معامله باز', formatNumber(crm['open_deals'] as num? ?? 0)),
                      _statCard('نرخ تبدیل', '${crm['conversion_rate'] ?? 0}%'),
                      _statCard('درآمد ماه', formatPrice(accounting['month_income'] as num? ?? 0)),
                      _statCard('هزینه ماه', formatPrice(accounting['month_expense'] as num? ?? 0)),
                      _statCard('کمیسیون معوق', formatPrice(commissions['pending_total'] as num? ?? 0)),
                      _statCard('بازدید ماه', formatNumber(visits['month_count'] as num? ?? 0)),
                    ],
                  ),
                  if (monthlyTrend.isNotEmpty) ...[
                    const SizedBox(height: 16),
                    const Text('روند مالی ۶ ماه', style: TextStyle(fontWeight: FontWeight.w700)),
                    const SizedBox(height: 8),
                    for (final m in monthlyTrend)
                      _barRow('${m['label'] ?? ''}', (m['income'] as num?)?.toDouble() ?? 0,
                          (monthlyTrend.map((x) => (x['income'] as num?)?.toDouble() ?? 0).fold<double>(0, (a, b) => a > b ? a : b))),
                  ],
                  if (pipeline.isNotEmpty) ...[
                    const SizedBox(height: 16),
                    const Text('قیف فروش', style: TextStyle(fontWeight: FontWeight.w700)),
                    const SizedBox(height: 8),
                    for (final p in pipeline)
                      _barRow('${p['stage_label'] ?? p['stage'] ?? ''}', (p['count'] as num?)?.toDouble() ?? 0, 20),
                  ],
                  if (byType.isNotEmpty) ...[
                    const SizedBox(height: 16),
                    const Text('توزیع نوع ملک', style: TextStyle(fontWeight: FontWeight.w700)),
                    const SizedBox(height: 8),
                    for (final t in byType)
                      _barRow('${t['type_label'] ?? t['type'] ?? ''}', (t['count'] as num?)?.toDouble() ?? 0, 50),
                  ],
                  if (consultants.isNotEmpty) ...[
                    const SizedBox(height: 16),
                    const Text('عملکرد مشاوران', style: TextStyle(fontWeight: FontWeight.w700)),
                    const SizedBox(height: 8),
                    for (final c in consultants)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 8),
                        child: simpleListTile(
                          title: '${c['name'] ?? ''}',
                          subtitle: '${formatNumber(c['properties_count'] as num? ?? 0)} ملک · ${formatNumber(c['deals_won'] as num? ?? 0)} معامله',
                          trailing: formatPrice(c['commission_total'] as num?),
                        ),
                      ),
                  ],
                ],
              ),
            ),
    );
  }

  Widget _barRow(String label, double value, double max) {
    final pct = max > 0 ? (value / max).clamp(0.0, 1.0) : 0.0;
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(label, style: const TextStyle(fontSize: 13)),
              Text(value > 1000 ? formatPrice(value) : formatNumber(value), style: const TextStyle(color: AppColors.muted, fontSize: 12)),
            ],
          ),
          const SizedBox(height: 4),
          LinearProgressIndicator(value: pct, backgroundColor: AppColors.muted.withValues(alpha: 0.15)),
        ],
      ),
    );
  }

  Widget _statCard(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: GlassCard(
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(label, style: const TextStyle(color: AppColors.muted)),
            Text(value, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
          ],
        ),
      ),
    );
  }
}

class SearchScreen extends ConsumerStatefulWidget {
  const SearchScreen({super.key});
  @override
  ConsumerState<SearchScreen> createState() => _SearchScreenState();
}

class _SearchScreenState extends ConsumerState<SearchScreen> {
  final _q = TextEditingController();
  final _minPrice = TextEditingController();
  final _maxPrice = TextEditingController();
  final _minArea = TextEditingController();
  final _maxArea = TextEditingController();
  final _rooms = TextEditingController();
  final _city = TextEditingController();
  String _type = '';
  bool _showAdvanced = false;
  bool _parking = false;
  bool _elevator = false;
  List<dynamic> _results = [];
  bool _loading = false;

  Future<void> _search() async {
    setState(() => _loading = true);
    try {
      final params = <String, dynamic>{
        if (_q.text.trim().isNotEmpty) 'q': _q.text.trim(),
        if (_type.isNotEmpty) 'type': _type,
        if (_city.text.trim().isNotEmpty) 'city': _city.text.trim(),
        if (_minPrice.text.trim().isNotEmpty) 'min_price': _minPrice.text.trim(),
        if (_maxPrice.text.trim().isNotEmpty) 'max_price': _maxPrice.text.trim(),
        if (_minArea.text.trim().isNotEmpty) 'min_area': _minArea.text.trim(),
        if (_maxArea.text.trim().isNotEmpty) 'max_area': _maxArea.text.trim(),
        if (_rooms.text.trim().isNotEmpty) 'rooms': _rooms.text.trim(),
        if (_parking) 'has_parking': true,
        if (_elevator) 'has_elevator': true,
      };
      final data = await ref.read(apiClientProvider).getProperties(params: params);
      if (mounted) {
        setState(() {
          _results = (data['data'] as List?) ?? const [];
          _loading = false;
        });
      }
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  void dispose() {
    _q.dispose();
    _minPrice.dispose();
    _maxPrice.dispose();
    _minArea.dispose();
    _maxArea.dispose();
    _rooms.dispose();
    _city.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return PageShell(
      title: 'جستجو',
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _q,
                    decoration: const InputDecoration(
                      hintText: 'کد، آدرس، نام مالک…',
                      prefixIcon: Icon(Icons.search_rounded),
                    ),
                    onSubmitted: (_) => _search(),
                  ),
                ),
                IconButton(icon: const Icon(Icons.tune_rounded), onPressed: () => setState(() => _showAdvanced = !_showAdvanced)),
              ],
            ),
          ),
          if (_showAdvanced)
            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  DropdownButtonFormField<String>(
                    initialValue: _type.isEmpty ? null : _type,
                    decoration: const InputDecoration(labelText: 'نوع معامله'),
                    items: const [
                      DropdownMenuItem(value: '', child: Text('همه')),
                      DropdownMenuItem(value: 'sale', child: Text('فروش')),
                      DropdownMenuItem(value: 'rent', child: Text('اجاره')),
                      DropdownMenuItem(value: 'full_mortgage', child: Text('رهن کامل')),
                    ],
                    onChanged: (v) => setState(() => _type = v ?? ''),
                  ),
                  TextField(controller: _city, decoration: const InputDecoration(labelText: 'شهر')),
                  Row(children: [
                    Expanded(child: TextField(controller: _minPrice, decoration: const InputDecoration(labelText: 'قیمت از'), keyboardType: TextInputType.number)),
                    const SizedBox(width: 8),
                    Expanded(child: TextField(controller: _maxPrice, decoration: const InputDecoration(labelText: 'قیمت تا'), keyboardType: TextInputType.number)),
                  ]),
                  Row(children: [
                    Expanded(child: TextField(controller: _minArea, decoration: const InputDecoration(labelText: 'متراژ از'), keyboardType: TextInputType.number)),
                    const SizedBox(width: 8),
                    Expanded(child: TextField(controller: _maxArea, decoration: const InputDecoration(labelText: 'متراژ تا'), keyboardType: TextInputType.number)),
                  ]),
                  TextField(controller: _rooms, decoration: const InputDecoration(labelText: 'تعداد خواب'), keyboardType: TextInputType.number),
                  SwitchListTile(contentPadding: EdgeInsets.zero, title: const Text('پارکینگ'), value: _parking, onChanged: (v) => setState(() => _parking = v)),
                  SwitchListTile(contentPadding: EdgeInsets.zero, title: const Text('آسانسور'), value: _elevator, onChanged: (v) => setState(() => _elevator = v)),
                ],
              ),
            ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: SizedBox(
              width: double.infinity,
              child: FilledButton(onPressed: _loading ? null : _search, child: const Text('جستجو')),
            ),
          ),
          const SizedBox(height: 8),
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator())
                : ListView.separated(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    itemCount: _results.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 10),
                    itemBuilder: (_, i) {
                      final p = Map<String, dynamic>.from(_results[i] as Map);
                      return simpleListTile(
                        title: '${p['code']}',
                        subtitle: '${p['type_label']} · ${p['city'] ?? ''}',
                        trailing: p['price'] != null ? formatPrice(p['price'] as num) : null,
                        onTap: () => context.push('/properties/${p['id']}'),
                      );
                    },
                  ),
          ),
        ],
      ),
    );
  }
}
