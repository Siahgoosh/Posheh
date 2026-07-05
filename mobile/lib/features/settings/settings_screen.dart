import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:go_router/go_router.dart';
import '../../core/theme/app_theme.dart';

class SettingsScreen extends ConsumerWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Scaffold(
      appBar: AppBar(title: const Text('تنظیمات')),
      body: ListView(
        children: [
          ListTile(
            leading: const Icon(Icons.search),
            title: const Text('جستجوی پیشرفته'),
            onTap: () => context.go('/search'),
          ),
          ListTile(
            leading: const Icon(Icons.check_box),
            title: const Text('وظایف'),
            onTap: () => context.go('/tasks'),
          ),
          ListTile(
            leading: const Icon(Icons.people),
            title: const Text('مدیریت تیم'),
            onTap: () => context.go('/team'),
          ),
          ListTile(
            leading: const Icon(Icons.credit_card),
            title: const Text('اشتراک و پرداخت'),
            onTap: () => context.go('/subscription'),
          ),
          const Divider(),
          ListTile(
            leading: const Icon(Icons.dark_mode),
            title: const Text('حالت تاریک'),
            trailing: Switch(
              value: ref.watch(themeModeProvider) == ThemeMode.dark,
              onChanged: (v) {
                ref.read(themeModeProvider.notifier).state =
                    v ? ThemeMode.dark : ThemeMode.light;
              },
            ),
          ),
          const Divider(),
          ListTile(
            leading: const Icon(Icons.logout, color: Colors.red),
            title: const Text('خروج', style: TextStyle(color: Colors.red)),
            onTap: () async {
              const storage = FlutterSecureStorage();
              await storage.delete(key: 'token');
              if (context.mounted) context.go('/login');
            },
          ),
        ],
      ),
    );
  }
}
