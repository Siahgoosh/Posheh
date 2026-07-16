import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../auth/auth_controller.dart';
import '../navigation/app_menu.dart';
import '../theme/app_theme.dart';
import 'app_logo.dart';
import 'gradient_text.dart';

class AppDrawer extends ConsumerWidget {
  const AppDrawer({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authControllerProvider).user;
    final location = GoRouterState.of(context).uri.path;

    final items = appMenuItems.where((item) {
      if (item.managerOnly && !(user?.canManage ?? false)) return false;
      if (item.feature != null && !(user?.hasFeature(item.feature!) ?? false)) {
        return false;
      }
      return true;
    });

    return Drawer(
      child: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 12),
              child: Row(
                children: [
                  const AppLogo(size: 42),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        GradientText(
                          'پوشه',
                          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                fontWeight: FontWeight.bold,
                              ),
                        ),
                        Text(
                          user?.officeName ?? 'سامانه املاک',
                          style: const TextStyle(color: AppColors.muted, fontSize: 12),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        if (user?.trialLabel != null)
                          Text(
                            user!.trialLabel!,
                            style: const TextStyle(color: AppColors.warning, fontSize: 10),
                          ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const Divider(height: 1),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.symmetric(vertical: 8),
                children: [
                  for (final item in items)
                    ListTile(
                      leading: Icon(
                        item.icon,
                        color: location.startsWith(item.path)
                            ? AppColors.primary
                            : AppColors.muted,
                        size: 22,
                      ),
                      title: Text(
                        item.label,
                        style: TextStyle(
                          fontWeight: location.startsWith(item.path)
                              ? FontWeight.w700
                              : FontWeight.w500,
                          color: location.startsWith(item.path)
                              ? AppColors.primary
                              : null,
                        ),
                      ),
                      selected: location.startsWith(item.path),
                      selectedTileColor: AppColors.primary.withValues(alpha: 0.08),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      onTap: () {
                        Navigator.of(context).pop();
                        if (!location.startsWith(item.path)) {
                          context.go(item.path);
                        }
                      },
                    ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(12),
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 18,
                    backgroundColor: AppColors.primary.withValues(alpha: 0.15),
                    child: Text(user?.initial ?? '؟',
                        style: const TextStyle(
                            color: AppColors.primary, fontWeight: FontWeight.bold)),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(user?.name ?? '—',
                            style: const TextStyle(fontWeight: FontWeight.w600)),
                        Text(user?.roleLabel ?? '',
                            style: const TextStyle(
                                color: AppColors.muted, fontSize: 11)),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
