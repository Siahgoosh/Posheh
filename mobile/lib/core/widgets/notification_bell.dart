import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../api/api_client.dart';
import '../theme/app_theme.dart';
import '../utils/app_launcher.dart';
import '../utils/notification_nav.dart';

class NotificationBell extends ConsumerStatefulWidget {
  const NotificationBell({super.key});

  @override
  ConsumerState<NotificationBell> createState() => _NotificationBellState();
}

class _NotificationBellState extends ConsumerState<NotificationBell> {
  List<dynamic> _items = const [];
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _load();
    _timer = Timer.periodic(const Duration(seconds: 30), (_) => _load());
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  Future<void> _load() async {
    try {
      final items = await ref.read(apiClientProvider).getNotifications();
      if (mounted) setState(() => _items = items);
    } catch (_) {}
  }

  int get _unread =>
      _items.where((n) => n is Map && n['is_read'] != true).length;

  void _openPanel() {
    showModalBottomSheet(
      context: context,
      showDragHandle: true,
      isScrollControlled: true,
      builder: (ctx) => _NotificationPanel(
        initialItems: _items,
        onChanged: () => _load(),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return IconButton(
      tooltip: 'اعلان‌ها',
      onPressed: _openPanel,
      icon: Badge(
        isLabelVisible: _unread > 0,
        label: Text('$_unread'),
        child: const Icon(Icons.notifications_outlined),
      ),
    );
  }
}

class _NotificationPanel extends ConsumerStatefulWidget {
  final List<dynamic> initialItems;
  final VoidCallback onChanged;

  const _NotificationPanel({
    required this.initialItems,
    required this.onChanged,
  });

  @override
  ConsumerState<_NotificationPanel> createState() => _NotificationPanelState();
}

class _NotificationPanelState extends ConsumerState<_NotificationPanel> {
  List<dynamic> _items = const [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _items = widget.initialItems;
    _refresh();
  }

  Future<void> _refresh() async {
    setState(() {
      _loading = _items.isEmpty;
      _error = null;
    });
    try {
      final items = await ref.read(apiClientProvider).getNotifications();
      if (mounted) setState(() { _items = items; _loading = false; });
    } on ApiException catch (e) {
      if (mounted) setState(() { _error = e.message; _loading = false; });
    } catch (_) {
      if (mounted) setState(() { _error = 'خطا در دریافت اعلان‌ها'; _loading = false; });
    }
  }

  Future<void> _markRead(int id) async {
    try {
      await ref.read(apiClientProvider).markNotificationRead(id);
      widget.onChanged();
      await _refresh();
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      }
    }
  }

  Future<void> _openNotification(Map<String, dynamic> n) async {
    final id = notificationIdAsInt(n['id']);
    if (id > 0 && n['is_read'] != true) {
      await _markRead(id);
    }
    if (!mounted) return;
    Navigator.of(context).pop();
    await navigateFromNotification(context, n['link_url'] as String?);
  }

  bool get _hasRenewLink => _items.any((n) {
        if (n is! Map) return false;
        final link = n['link_url'] as String?;
        return link != null && link.contains('/renew') && n['is_read'] != true;
      });

  @override
  Widget build(BuildContext context) {
    final display = _items.take(15).toList();

    return DraggableScrollableSheet(
      expand: false,
      initialChildSize: 0.6,
      minChildSize: 0.35,
      maxChildSize: 0.92,
      builder: (_, controller) => Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 4, 8, 8),
            child: Row(
              children: [
                const Expanded(
                  child: Text('اعلان‌ها',
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                ),
                IconButton(
                  icon: const Icon(Icons.refresh_rounded, size: 20),
                  onPressed: _refresh,
                  tooltip: 'به‌روزرسانی',
                ),
              ],
            ),
          ),
          if (_loading)
            const Expanded(child: Center(child: CircularProgressIndicator()))
          else if (_error != null)
            Expanded(
              child: Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(_error!, style: const TextStyle(color: AppColors.danger)),
                    const SizedBox(height: 12),
                    OutlinedButton(onPressed: _refresh, child: const Text('تلاش مجدد')),
                  ],
                ),
              ),
            )
          else if (display.isEmpty)
            const Expanded(
              child: Center(child: Text('اعلانی ندارید', style: TextStyle(color: AppColors.muted))),
            )
          else
            Expanded(
              child: ListView.separated(
                controller: controller,
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                itemCount: display.length + (_hasRenewLink ? 1 : 0),
                separatorBuilder: (_, __) => const Divider(height: 1),
                itemBuilder: (_, i) {
                  if (_hasRenewLink && i == display.length) {
                    return ListTile(
                      title: const Text('تمدید اشتراک',
                          style: TextStyle(color: AppColors.primary, fontWeight: FontWeight.w600)),
                      leading: const Icon(Icons.credit_card_rounded, color: AppColors.primary),
                      onTap: () async {
                        Navigator.of(context).pop();
                        await openRenewSubscription();
                      },
                    );
                  }

                  final n = Map<String, dynamic>.from(display[i] as Map);
                  final isRead = n['is_read'] == true;
                  final priority = n['priority'] as String?;
                  final isUrgent = priority == 'urgent' || priority == 'high';

                  return ListTile(
                    contentPadding: EdgeInsets.zero,
                    leading: Icon(
                      isUrgent ? Icons.campaign_rounded : Icons.notifications_none_rounded,
                      color: isRead ? AppColors.muted : AppColors.primary,
                      size: 22,
                    ),
                    title: Text(
                      n['title'] ?? '',
                      style: TextStyle(
                        fontWeight: isRead ? FontWeight.w500 : FontWeight.bold,
                      ),
                    ),
                    subtitle: Padding(
                      padding: const EdgeInsets.only(top: 4),
                      child: Text(
                        n['body'] ?? '',
                        style: const TextStyle(fontSize: 13, height: 1.4, color: AppColors.muted),
                      ),
                    ),
                    trailing: !isRead
                        ? TextButton(
                            onPressed: () => _markRead(notificationIdAsInt(n['id'])),
                            child: const Text('خواندم'),
                          )
                        : null,
                    onTap: () => _openNotification(n),
                  );
                },
              ),
            ),
        ],
      ),
    );
  }
}
