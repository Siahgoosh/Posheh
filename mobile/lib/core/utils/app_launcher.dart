import 'package:url_launcher/url_launcher.dart';
import '../constants/app_urls.dart';

Future<void> openAppUrl(String url) async {
  final uri = Uri.parse(url);
  if (!await launchUrl(uri, mode: LaunchMode.externalApplication)) {
    throw Exception('نمی‌توان لینک را باز کرد');
  }
}

Future<void> openRegister() => openAppUrl(AppUrls.register);
Future<void> openDownloadPage() => openAppUrl(AppUrls.download);
Future<void> openBlog() => openAppUrl(AppUrls.blog);
Future<void> openPrivacy() => openAppUrl(AppUrls.privacy);
Future<void> openTerms() => openAppUrl(AppUrls.terms);
Future<void> openRenewSubscription() => openAppUrl('${AppUrls.site}/renew');
