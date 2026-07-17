import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
import '../api/api_client.dart';
import '../theme/app_theme.dart';
import '../utils/app_launcher.dart';

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

  int get _unread => _items.where((n) => n is Map && n['is_read'] != true).length;

  Future<void> _markRead(int id) async {
    try {
      await ref.read(apiClientProvider).markNotificationRead(id);
      await _load();
    } catch (_) {}
  }

  void _openPanel() {
    showModalBottomSheet(
      context: context,
      showDragHandle: true,
      isScrollControlled: true,
      builder: (ctx) => DraggableScrollableSheet(
        expand: false,
        initialChildSize: 0.55,
        minChildSize: 0.35,
        maxChildSize: 0.9,
        builder: (_, controller) => Column(
          children: [
            const Padding(
              padding: EdgeInsets.fromLTRB(16, 4, 16, 12),
              child: Text('اعلان‌ها', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            ),
            Expanded(
              child: _items.isEmpty
                  ? const Center(child: Text('اعلانی ندارید', style: TextStyle(color: AppColors.muted)))
                  : ListView.separated(
                      controller: controller,
                      padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                      itemCount: _items.length,
                      separatorBuilder: (_, __) => const Divider(height: 1),
                      itemBuilder: (_, i) {
                        final n = Map<String, dynamic>.from(_items[i] as Map);
                        final isRead = n['is_read'] == true;
                        return ListTile(
                          contentPadding: EdgeInsets.zero,
                          title: Text(n['title'] ?? '', style: TextStyle(fontWeight: isRead ? FontWeight.w500 : FontWeight.bold)),
                          subtitle: Padding(
                            padding: const EdgeInsets.only(top: 4),
                            child: Text(n['body'] ?? '', style: const TextStyle(fontSize: 13, height: 1.4)),
                          ),
                          trailing: !isRead
                              ? TextButton(onPressed: () => _markRead(n['id'] as int), child: const Text('خواندم'))
                              : null,
                          onTap: () async {
                            final link = n['link_url'] as String?;
                            if (link != null && link.isNotEmpty) {
                              if (link.contains('/renew')) {
                                await openRenewSubscription();
                              } else if (await canLaunchUrl(Uri.parse(link))) {
                                await launchUrl(Uri.parse(link), mode: LaunchMode.externalApplication);
                              }
                            }
                          },
                        );
                      },
                    ),
            ),
          ],
        ),
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
