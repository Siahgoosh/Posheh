import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../api/api_client.dart';

class AppUser {
  final String name;
  final String mobile;
  final String? roleLabel;
  final String role;
  final String? officeName;
  final List<String> features;
  final bool onTrial;
  final String? trialLabel;

  const AppUser({
    required this.name,
    required this.mobile,
    this.roleLabel,
    this.role = '',
    this.officeName,
    this.features = const [],
    this.onTrial = false,
    this.trialLabel,
  });

  factory AppUser.fromJson(Map<String, dynamic> json) {
    final office = json['office'];
    final plan = office is Map ? office['plan'] : null;
    final features = plan is Map
        ? (plan['features'] as List?)?.map((e) => e.toString()).toList() ?? const <String>[]
        : const <String>[];

    return AppUser(
      name: (json['name'] ?? '').toString(),
      mobile: (json['mobile'] ?? '').toString(),
      roleLabel: json['role_label']?.toString(),
      role: (json['role'] ?? '').toString(),
      officeName: office is Map ? office['name']?.toString() : null,
      features: features,
      onTrial: office is Map && office['on_trial'] == true,
      trialLabel: office is Map ? office['trial_label']?.toString() : null,
    );
  }

  bool get canManage => role == 'office_manager' || role == 'super_admin';

  bool hasFeature(String feature) => features.contains(feature);

  String get initial => name.trim().isNotEmpty ? name.trim()[0] : '؟';
}

class AuthState {
  final bool loading;
  final AppUser? user;

  const AuthState({this.loading = true, this.user});

  bool get isAuthenticated => user != null;

  AuthState copyWith({bool? loading, AppUser? user, bool clearUser = false}) {
    return AuthState(
      loading: loading ?? this.loading,
      user: clearUser ? null : (user ?? this.user),
    );
  }
}

final authControllerProvider =
    StateNotifierProvider<AuthController, AuthState>((ref) {
  return AuthController(ref.watch(apiClientProvider))..bootstrap();
});

class AuthController extends StateNotifier<AuthState> {
  final ApiClient _api;

  AuthController(this._api) : super(const AuthState());

  Future<void> bootstrap() async {
    String? token;
    try {
      token = await storage.read(key: 'token');
    } catch (_) {
      token = null;
    }
    if (token == null) {
      state = const AuthState(loading: false);
      return;
    }
    await refreshUser();
  }

  Future<void> refreshUser() async {
    try {
      final res = await _api.me();
      final userJson = res['user'];
      if (userJson is Map) {
        state = AuthState(
          loading: false,
          user: AppUser.fromJson(Map<String, dynamic>.from(userJson)),
        );
      } else {
        state = const AuthState(loading: false);
      }
    } catch (_) {
      await storage.delete(key: 'token');
      state = const AuthState(loading: false);
    }
  }

  Future<void> onLoggedIn(String token, Map<String, dynamic> userJson) async {
    await storage.write(key: 'token', value: token);
    state = AuthState(loading: false, user: AppUser.fromJson(userJson));
  }

  Future<void> logout({bool allDevices = false}) async {
    if (allDevices) {
      await _api.logoutAll();
    } else {
      await _api.logout();
    }
    await storage.delete(key: 'token');
    state = const AuthState(loading: false);
  }
}
