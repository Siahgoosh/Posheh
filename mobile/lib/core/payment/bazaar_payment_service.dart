import 'dart:io' show Platform;

import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter_poolakey/flutter_poolakey.dart';

import '../constants/bazaar_config.dart';

bool get isAndroidPlatform => !kIsWeb && Platform.isAndroid;

class BazaarPaymentService {
  bool _connected = false;

  Future<void> connect() async {
    if (!isAndroidPlatform) return;
    if (_connected) return;
    if (bazaarRsaPublicKey.isEmpty) {
      throw BazaarPaymentException(
        'پرداخت درون‌برنامه‌ای هنوز پیکربندی نشده است. لطفاً با پشتیبانی تماس بگیرید.',
      );
    }
    await FlutterPoolakey.connect(
      bazaarRsaPublicKey,
      onDisconnected: () => _connected = false,
    );
    _connected = true;
  }

  Future<void> disconnect() async {
    if (!isAndroidPlatform || !_connected) return;
    try {
      await FlutterPoolakey.disconnect();
    } catch (_) {}
    _connected = false;
  }

  Future<PurchaseInfo> subscribe(String productId, {String payload = ''}) async {
    await connect();
    return FlutterPoolakey.subscribe(
      productId,
      payload: payload,
      dynamicPriceToken: '',
    );
  }
}

class BazaarPaymentException implements Exception {
  final String message;
  BazaarPaymentException(this.message);
  @override
  String toString() => message;
}
