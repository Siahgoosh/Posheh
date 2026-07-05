import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../features/auth/login_screen.dart';
import '../../features/dashboard/dashboard_screen.dart';
import '../../features/properties/properties_screen.dart';
import '../../features/properties/property_detail_screen.dart';
import '../../features/properties/property_form_screen.dart';
import '../../features/favorites/favorites_screen.dart';
import '../../features/search/search_screen.dart';
import '../../features/tasks/tasks_screen.dart';
import '../../features/team/team_screen.dart';
import '../../features/subscription/subscription_screen.dart';
import '../../features/settings/settings_screen.dart';

final appRouterProvider = Provider<GoRouter>((ref) {
  return GoRouter(
    initialLocation: '/login',
    routes: [
      GoRoute(
        path: '/login',
        builder: (context, state) => const LoginScreen(),
      ),
      GoRoute(
        path: '/properties/:id',
        builder: (context, state) => PropertyDetailScreen(
          propertyId: int.parse(state.pathParameters['id']!),
        ),
      ),
      GoRoute(
        path: '/properties/new',
        builder: (context, state) => const PropertyFormScreen(),
      ),
      ShellRoute(
        builder: (context, state, child) => MainShell(child: child),
        routes: [
          GoRoute(path: '/dashboard', builder: (context, state) => const DashboardScreen()),
          GoRoute(path: '/properties', builder: (context, state) => const PropertiesScreen()),
          GoRoute(path: '/search', builder: (context, state) => const SearchScreen()),
          GoRoute(path: '/favorites', builder: (context, state) => const FavoritesScreen()),
          GoRoute(path: '/tasks', builder: (context, state) => const TasksScreen()),
          GoRoute(path: '/team', builder: (context, state) => const TeamScreen()),
          GoRoute(path: '/subscription', builder: (context, state) => const SubscriptionScreen()),
          GoRoute(path: '/settings', builder: (context, state) => const SettingsScreen()),
        ],
      ),
    ],
  );
});

class MainShell extends StatelessWidget {
  final Widget child;

  const MainShell({super.key, required this.child});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: child,
      drawer: Drawer(
        child: ListView(
          children: [
            const DrawerHeader(
              decoration: BoxDecoration(
                gradient: LinearGradient(colors: [Color(0xFF6366F1), Color(0xFF8B5CF6)]),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  Text('پوشه', style: TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.bold)),
                  Text('سامانه املاک', style: TextStyle(color: Colors.white70)),
                ],
              ),
            ),
            _drawerItem(context, Icons.dashboard, 'داشبورد', '/dashboard'),
            _drawerItem(context, Icons.apartment, 'املاک', '/properties'),
            _drawerItem(context, Icons.search, 'جستجو', '/search'),
            _drawerItem(context, Icons.star, 'علاقه‌مندی‌ها', '/favorites'),
            _drawerItem(context, Icons.check_box, 'وظایف', '/tasks'),
            _drawerItem(context, Icons.people, 'تیم', '/team'),
            _drawerItem(context, Icons.credit_card, 'اشتراک', '/subscription'),
            _drawerItem(context, Icons.settings, 'تنظیمات', '/settings'),
          ],
        ),
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _getSelectedIndex(context),
        onDestinationSelected: (index) {
          const routes = ['/dashboard', '/properties', '/favorites', '/settings'];
          context.go(routes[index]);
        },
        destinations: const [
          NavigationDestination(icon: Icon(Icons.dashboard_outlined), selectedIcon: Icon(Icons.dashboard), label: 'داشبورد'),
          NavigationDestination(icon: Icon(Icons.apartment_outlined), selectedIcon: Icon(Icons.apartment), label: 'املاک'),
          NavigationDestination(icon: Icon(Icons.star_outline), selectedIcon: Icon(Icons.star), label: 'علاقه‌مندی'),
          NavigationDestination(icon: Icon(Icons.settings_outlined), selectedIcon: Icon(Icons.settings), label: 'تنظیمات'),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => context.go('/properties/new'),
        child: const Icon(Icons.add),
      ),
    );
  }

  Widget _drawerItem(BuildContext context, IconData icon, String label, String route) {
    return ListTile(
      leading: Icon(icon),
      title: Text(label),
      onTap: () {
        Navigator.pop(context);
        context.go(route);
      },
    );
  }

  int _getSelectedIndex(BuildContext context) {
    final location = GoRouterState.of(context).uri.path;
    if (location.startsWith('/properties')) return 1;
    if (location.startsWith('/favorites')) return 2;
    if (location.startsWith('/settings')) return 3;
    return 0;
  }
}
