import 'package:flutter/material.dart';

class AppMenuItem {
  final String path;
  final String label;
  final IconData icon;
  final String? feature;
  final bool managerOnly;

  const AppMenuItem({
    required this.path,
    required this.label,
    required this.icon,
    this.feature,
    this.managerOnly = false,
  });
}

const appMenuItems = <AppMenuItem>[
  AppMenuItem(path: '/dashboard', label: 'داشبورد', icon: Icons.dashboard_rounded),
  AppMenuItem(path: '/properties', label: 'املاک', icon: Icons.apartment_rounded),
  AppMenuItem(path: '/owners', label: 'مالکین', icon: Icons.person_outline_rounded),
  AppMenuItem(path: '/customers', label: 'مشتریان', icon: Icons.people_outline_rounded),
  AppMenuItem(path: '/visits', label: 'بازدیدها', icon: Icons.event_rounded),
  AppMenuItem(path: '/search', label: 'جستجو', icon: Icons.search_rounded),
  AppMenuItem(path: '/favorites', label: 'علاقه‌مندی‌ها', icon: Icons.star_outline_rounded),
  AppMenuItem(path: '/crm', label: 'مدیریت فروش', icon: Icons.view_kanban_rounded, feature: 'crm'),
  AppMenuItem(path: '/accounting', label: 'حسابداری', icon: Icons.account_balance_wallet_outlined, feature: 'accounting'),
  AppMenuItem(path: '/reports', label: 'گزارش‌ها', icon: Icons.bar_chart_rounded),
  AppMenuItem(path: '/commissions', label: 'کمیسیون', icon: Icons.percent_rounded),
  AppMenuItem(path: '/contracts', label: 'قراردادها', icon: Icons.description_outlined),
  AppMenuItem(path: '/team', label: 'تیم', icon: Icons.groups_outlined, feature: 'team'),
  AppMenuItem(path: '/tickets', label: 'پشتیبانی', icon: Icons.support_agent_rounded),
  AppMenuItem(path: '/subscription', label: 'اشتراک', icon: Icons.credit_card_rounded, managerOnly: true),
  AppMenuItem(path: '/settings', label: 'تنظیمات', icon: Icons.settings_outlined),
];
