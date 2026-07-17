import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../auth/auth_controller.dart';
import '../../features/auth/login_screen.dart';
import '../../features/auth/register_screen.dart';
import '../../features/dashboard/dashboard_screen.dart';
import '../../features/properties/properties_screen.dart';
import '../../features/properties/property_form_screen.dart';
import '../../features/properties/property_detail_screen.dart';
import '../../features/settings/settings_screen.dart';
import '../../features/modules/module_screens.dart';
import '../../features/owners/owners_screen.dart';
import '../../features/customers/customers_screen.dart';

final appRouterProvider = Provider<GoRouter>((ref) {
  final auth = ref.watch(authControllerProvider);

  return GoRouter(
    initialLocation: '/dashboard',
    redirect: (context, state) {
      if (auth.loading) return null;
      final loggingIn = state.matchedLocation == '/login';
      final registering = state.matchedLocation == '/register';
      final onSubscription = state.matchedLocation == '/subscription';
      if (!auth.isAuthenticated) return (loggingIn || registering) ? null : '/login';
      if (loggingIn || registering) return '/dashboard';
      final user = auth.user;
      if (user != null && user.role != 'super_admin' && (!user.hasAccess || user.subscriptionExpired)) {
        if (!onSubscription) return '/subscription';
      }
      return null;
    },
    routes: [
      GoRoute(path: '/login', builder: (_, __) => const LoginScreen()),
      GoRoute(path: '/register', builder: (_, __) => const RegisterScreen()),
      GoRoute(path: '/properties/new', builder: (_, __) => const PropertyFormScreen()),
      GoRoute(
        path: '/properties/:id/edit',
        builder: (_, state) => PropertyFormScreen(
          editId: int.parse(state.pathParameters['id']!),
        ),
      ),
      GoRoute(
        path: '/properties/:id',
        builder: (_, state) => PropertyDetailScreen(
          id: int.parse(state.pathParameters['id']!),
        ),
      ),
      GoRoute(path: '/dashboard', builder: (_, __) => const DashboardScreen()),
      GoRoute(path: '/properties', builder: (_, __) => const PropertiesScreen()),
      GoRoute(
        path: '/owners/:id',
        builder: (_, state) => OwnerDetailScreen(id: int.parse(state.pathParameters['id']!)),
      ),
      GoRoute(path: '/owners', builder: (_, __) => const OwnersScreen()),
      GoRoute(
        path: '/customers/:id',
        builder: (_, state) => CustomerDetailScreen(id: int.parse(state.pathParameters['id']!)),
      ),
      GoRoute(path: '/customers', builder: (_, __) => const CustomersScreen()),
      GoRoute(path: '/visits', builder: (_, __) => const VisitsScreen()),
      GoRoute(path: '/search', builder: (_, __) => const SearchScreen()),
      GoRoute(path: '/favorites', builder: (_, __) => const FavoritesScreen()),
      GoRoute(path: '/crm', builder: (_, __) => const CrmScreen()),
      GoRoute(path: '/accounting', builder: (_, __) => const AccountingScreen()),
      GoRoute(path: '/reports', builder: (_, __) => const ReportsScreen()),
      GoRoute(path: '/commissions', builder: (_, __) => const CommissionsScreen()),
      GoRoute(path: '/contracts', builder: (_, __) => const ContractsScreen()),
      GoRoute(path: '/team', builder: (_, __) => const TeamScreen()),
      GoRoute(path: '/tickets', builder: (_, __) => const TicketsScreen()),
      GoRoute(path: '/subscription', builder: (_, __) => const SubscriptionScreen()),
      GoRoute(path: '/settings', builder: (_, __) => const SettingsScreen()),
    ],
  );
});
