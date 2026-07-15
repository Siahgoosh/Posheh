import 'dart:ui';
import 'package:flutter/material.dart';
import '../theme/app_theme.dart';

/// Ambient gradient glow used behind auth screens, mirroring the web
/// landing/login background blobs.
class AppBackground extends StatelessWidget {
  final Widget child;

  const AppBackground({super.key, required this.child});

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        Positioned.fill(
          child: ColoredBox(color: Theme.of(context).scaffoldBackgroundColor),
        ),
        Positioned(
          top: -140,
          right: -140,
          child: _Blob(color: AppColors.primary.withValues(alpha: 0.20)),
        ),
        Positioned(
          bottom: -140,
          left: -140,
          child: _Blob(color: AppColors.accent.withValues(alpha: 0.20)),
        ),
        Positioned.fill(child: child),
      ],
    );
  }
}

class _Blob extends StatelessWidget {
  final Color color;
  const _Blob({required this.color});

  @override
  Widget build(BuildContext context) {
    return ImageFiltered(
      imageFilter: ImageFilter.blur(sigmaX: 90, sigmaY: 90),
      child: Container(
        width: 320,
        height: 320,
        decoration: BoxDecoration(color: color, shape: BoxShape.circle),
      ),
    );
  }
}
