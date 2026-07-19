import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';
import 'app_launcher.dart';

/// Resolve broadcast [linkUrl] to in-app navigation or external browser.
Future<void> navigateFromNotification(BuildContext context, String? linkUrl) async {
  if (linkUrl == null || linkUrl.isEmpty) return;

  if (linkUrl.contains('/renew')) {
    await openRenewSubscription();
    return;
  }

  String path = linkUrl;
  if (linkUrl.startsWith('http://') || linkUrl.startsWith('https://')) {
    final uri = Uri.tryParse(linkUrl);
    if (uri == null) return;
    final host = uri.host;
    if (host.contains('posheapp.ir') || host.contains('localhost')) {
      path = uri.path;
      if (uri.hasQuery) path = '$path?${uri.query}';
    } else {
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      }
      return;
    }
  }

  if (!path.startsWith('/')) path = '/$path';

  final segments = path.split('/').where((s) => s.isNotEmpty).toList();
  if (segments.isEmpty) {
    context.go('/dashboard');
    return;
  }

  if (segments.first == 'properties' && segments.length >= 2) {
    final id = int.tryParse(segments[1]);
    if (id != null) {
      if (segments.length >= 3 && segments[2] == 'edit') {
        context.push('/properties/$id/edit');
      } else {
        context.push('/properties/$id');
      }
      return;
    }
  }

  if (segments.first == 'owners' && segments.length >= 2) {
    final id = int.tryParse(segments[1]);
    if (id != null) {
      context.push('/owners/$id');
      return;
    }
  }

  if (segments.first == 'customers' && segments.length >= 2) {
    final id = int.tryParse(segments[1]);
    if (id != null) {
      context.push('/customers/$id');
      return;
    }
  }

  const directRoutes = [
    '/dashboard',
    '/properties',
    '/owners',
    '/customers',
    '/visits',
    '/search',
    '/favorites',
    '/crm',
    '/accounting',
    '/reports',
    '/commissions',
    '/contracts',
    '/team',
    '/tickets',
    '/subscription',
    '/office-website',
    '/renew',
    '/settings',
  ];

  for (final route in directRoutes) {
    if (path == route || path.startsWith('$route/')) {
      context.go(route);
      return;
    }
  }

  final uri = Uri.tryParse(linkUrl);
  if (uri != null && await canLaunchUrl(uri)) {
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }
}

int notificationIdAsInt(dynamic id) {
  if (id is int) return id;
  if (id is num) return id.toInt();
  return int.tryParse('$id') ?? 0;
}
