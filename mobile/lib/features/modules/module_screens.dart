import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/api/api_client.dart';
import '../../core/auth/auth_controller.dart';
import '../../core/utils/app_launcher.dart';
import '../../core/utils/formatters.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/glass_card.dart';
import '../../core/widgets/jalali_date_field.dart';
import '../../core/widgets/page_shell.dart';
import '../common/data_list_screen.dart';

class OwnersScreen extends StatelessWidget {
  const OwnersScreen({super.key});
  @override
  Widget build(BuildContext context) => DataListScreen(
        title: 'مالکین',
        loader: (api) => api.getOwners(),
        emptyText: 'مالکی ثبت نشده',
        itemBuilder: (_, o) => simpleListTile(
          title: '${o['name'] ?? '—'}',
          subtitle: [o['mobile'], o['properties_count'] != null ? '${formatNumber(o['properties_count'] as num)} ملک' : null]
              .where((e) => e != null && '$e'.isNotEmpty)
              .join(' · '),
        ),
      );
}

class CustomersScreen extends StatelessWidget {
  const CustomersScreen({super.key});
  @override
  Widget build(BuildContext context) => DataListScreen(
        title: 'مشتریان',
        loader: (api) => api.getCustomers(),
        emptyText: 'مشتری ثبت نشده',
        itemBuilder: (_, c) => simpleListTile(
          title: '${c['name'] ?? '—'}',
          subtitle: [c['mobile'], c['need_label']].where((e) => e != null && '$e'.isNotEmpty).join(' · '),
        ),
      );
}

class VisitsScreen extends StatelessWidget {
  const VisitsScreen({super.key});
  @override
  Widget build(BuildContext context) => DataListScreen(
        title: 'بازدیدها',
        loader: (api) => api.getVisits(),
        emptyText: 'بازدیدی ثبت نشده',
        itemBuilder: (_, v) => simpleListTile(
          title: '${v['property']?['code'] ?? v['title'] ?? 'بازدید'}',
          subtitle: [v['visit_at_jalali'], v['status_label']].where((e) => e != null && '$e'.isNotEmpty).join(' · '),
        ),
      );
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

class CommissionsScreen extends StatelessWidget {
  const CommissionsScreen({super.key});
  @override
  Widget build(BuildContext context) => DataListScreen(
        title: 'کمیسیون',
        loader: (api) => api.getCommissions(),
        emptyText: 'کمیسیونی ثبت نشده',
        itemBuilder: (_, c) => simpleListTile(
          title: '${c['user']?['name'] ?? c['title'] ?? 'کمیسیون'}',
          subtitle: c['status'] == 'paid' ? 'پرداخت‌شده' : 'در انتظار',
          trailing: c['commission_amount'] != null ? formatPrice(c['commission_amount'] as num) : null,
        ),
      );
}

class ContractsScreen extends StatelessWidget {
  const ContractsScreen({super.key});
  @override
  Widget build(BuildContext context) => DataListScreen(
        title: 'قراردادها',
        loader: (api) => api.getContracts(),
        emptyText: 'قراردادی صادر نشده',
        itemBuilder: (_, c) => simpleListTile(
          title: '${c['title'] ?? 'قرارداد'}',
          subtitle: '${c['status'] ?? ''}',
        ),
      );
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
                        return GlassCard(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('${deal['title'] ?? 'معامله'}',
                                  style: const TextStyle(fontWeight: FontWeight.bold)),
                              const SizedBox(height: 6),
                              Text('${deal['stage_label'] ?? deal['stage'] ?? ''}',
                                  style: const TextStyle(color: AppColors.muted, fontSize: 12)),
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
    return PageShell(
      title: 'گزارش‌ها',
      actions: [IconButton(icon: const Icon(Icons.refresh_rounded), onPressed: _load)],
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                _statCard('ملک فعال', formatNumber(props['active'] as num? ?? 0)),
                _statCard('معامله باز', formatNumber(crm['open_deals'] as num? ?? 0)),
                _statCard('نرخ تبدیل', '${crm['conversion_rate'] ?? 0}%'),
                _statCard('درآمد ماه', formatPrice(accounting['month_income'] as num? ?? 0)),
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

class SubscriptionScreen extends ConsumerStatefulWidget {
  const SubscriptionScreen({super.key});
  @override
  ConsumerState<SubscriptionScreen> createState() => _SubscriptionScreenState();
}

class _SubscriptionScreenState extends ConsumerState<SubscriptionScreen> {
  Map<String, dynamic>? _current;
  List<dynamic> _plans = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final api = ref.read(apiClientProvider);
      final current = await api.getSubscription();
      final plans = await api.getPlans();
      if (mounted) setState(() { _current = current; _plans = plans; _loading = false; });
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final user = ref.watch(authControllerProvider).user;
    return PageShell(
      title: 'اشتراک',
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                if (user?.onTrial == true && user?.trialLabel != null)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: GlassCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(user!.trialLabel!,
                              style: const TextStyle(
                                  color: AppColors.warning,
                                  fontWeight: FontWeight.bold)),
                          if (user.trialHoursRemaining != null)
                            Padding(
                              padding: const EdgeInsets.only(top: 4),
                              child: Text(
                                '${toPersianDigits('${user.trialHoursRemaining}')} ساعت باقی‌مانده',
                                style: const TextStyle(color: AppColors.muted, fontSize: 13),
                              ),
                            ),
                        ],
                      ),
                    ),
                  ),
                if (_current != null && _current!.isNotEmpty)
                  GlassCard(
                    child: Text(
                      'اشتراک فعال: ${_current!['plan']?['name'] ?? '—'}',
                      style: const TextStyle(fontWeight: FontWeight.bold),
                    ),
                  ),
                const SizedBox(height: 12),
                for (final p in _plans)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: GlassCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('${p['name']}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                          const SizedBox(height: 4),
                          Text(formatPrice(p['monthly_price'] as num? ?? 0),
                              style: const TextStyle(color: AppColors.primary, fontWeight: FontWeight.w700)),
                          if (p['slug'] == 'solo')
                            const Padding(
                              padding: EdgeInsets.only(top: 6),
                              child: Text('۳ روز رایگان — فقط پنل فردی', style: TextStyle(color: AppColors.warning, fontSize: 12)),
                            ),
                        ],
                      ),
                    ),
                  ),
                const SizedBox(height: 8),
                GlassCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      const Text('تمدید و پرداخت',
                          style: TextStyle(fontWeight: FontWeight.w700)),
                      const SizedBox(height: 8),
                      const Text(
                        'خرید و تمدید اشتراک از وب‌سایت پوشه انجام می‌شود.',
                        style: TextStyle(color: AppColors.muted, fontSize: 13, height: 1.5),
                      ),
                      const SizedBox(height: 12),
                      OutlinedButton(
                        onPressed: () => openRenewSubscription(),
                        child: const Text('رفتن به صفحه اشتراک در وب'),
                      ),
                    ],
                  ),
                ),
              ],
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
  List<dynamic> _results = [];
  bool _loading = false;

  Future<void> _search() async {
    setState(() => _loading = true);
    try {
      final data = await ref.read(apiClientProvider).getProperties(
        params: {'q': _q.text.trim()},
      );
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
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return PageShell(
      title: 'جستجو',
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: TextField(
              controller: _q,
              decoration: InputDecoration(
                hintText: 'کد ملک، محله، شهر...',
                prefixIcon: const Icon(Icons.search_rounded),
                suffixIcon: IconButton(icon: const Icon(Icons.search_rounded), onPressed: _search),
              ),
              onSubmitted: (_) => _search(),
            ),
          ),
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
