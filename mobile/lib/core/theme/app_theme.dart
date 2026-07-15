import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';

final themeModeProvider = StateProvider<ThemeMode>((ref) => ThemeMode.dark);

/// Design tokens mirrored from the web app (frontend/src/index.css).
class AppColors {
  static const Color primary = Color(0xFF22D3EE); // cyan
  static const Color primaryFg = Color(0xFF042F3A);
  static const Color accent = Color(0xFFA78BFA); // violet
  static const Color cyanLight = Color(0xFF67E8F9);

  // Dark theme
  static const Color background = Color(0xFF080C14);
  static const Color foreground = Color(0xFFE8F4FF);
  static const Color muted = Color(0xFF8BA3B8);

  // Light theme
  static const Color lightBackground = Color(0xFFF0F9FF);
  static const Color lightForeground = Color(0xFF0C1929);

  static const Color success = Color(0xFF4ADE80);
  static const Color warning = Color(0xFFFBBF24);
  static const Color danger = Color(0xFFFB7185);

  static Color cardFill(bool dark) =>
      (dark ? primary : primary).withValues(alpha: dark ? 0.04 : 0.06);
  static Color cardBorder(bool dark) =>
      primary.withValues(alpha: dark ? 0.14 : 0.18);

  static const LinearGradient brandGradient = LinearGradient(
    colors: [primary, accent],
    begin: Alignment.topRight,
    end: Alignment.bottomLeft,
  );

  static const LinearGradient textGradient = LinearGradient(
    colors: [primary, cyanLight, accent],
    begin: Alignment.centerRight,
    end: Alignment.centerLeft,
  );
}

class AppTheme {
  static ThemeData get dark => _build(dark: true);
  static ThemeData get light => _build(dark: false);

  static ThemeData _build({required bool dark}) {
    final bg = dark ? AppColors.background : AppColors.lightBackground;
    final fg = dark ? AppColors.foreground : AppColors.lightForeground;
    final border = AppColors.cardBorder(dark);

    final base = dark ? ThemeData.dark() : ThemeData.light();
    final textTheme = GoogleFonts.vazirmatnTextTheme(base.textTheme).apply(
      bodyColor: fg,
      displayColor: fg,
    );

    return ThemeData(
      useMaterial3: true,
      brightness: dark ? Brightness.dark : Brightness.light,
      scaffoldBackgroundColor: bg,
      canvasColor: bg,
      textTheme: textTheme,
      colorScheme: (dark ? const ColorScheme.dark() : const ColorScheme.light())
          .copyWith(
        primary: AppColors.primary,
        onPrimary: AppColors.primaryFg,
        secondary: AppColors.accent,
        surface: bg,
        onSurface: fg,
        error: AppColors.danger,
      ),
      dividerColor: border,
      appBarTheme: AppBarTheme(
        backgroundColor: bg,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        centerTitle: false,
        iconTheme: IconThemeData(color: fg),
        titleTextStyle: GoogleFonts.vazirmatn(
          fontSize: 20,
          fontWeight: FontWeight.w700,
          color: fg,
        ),
      ),
      cardTheme: CardThemeData(
        color: AppColors.cardFill(dark),
        elevation: 0,
        margin: EdgeInsets.zero,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: BorderSide(color: border),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: fg.withValues(alpha: 0.04),
        hintStyle: TextStyle(color: AppColors.muted.withValues(alpha: 0.7)),
        labelStyle: const TextStyle(color: AppColors.muted),
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: border),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: border),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.primary, width: 1.5),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.danger),
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.primary,
          foregroundColor: AppColors.primaryFg,
          disabledBackgroundColor: AppColors.primary.withValues(alpha: 0.4),
          disabledForegroundColor: AppColors.primaryFg.withValues(alpha: 0.6),
          elevation: 0,
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
          textStyle: GoogleFonts.vazirmatn(
            fontSize: 15,
            fontWeight: FontWeight.w700,
          ),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: fg,
          side: BorderSide(color: border),
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
          textStyle: GoogleFonts.vazirmatn(
            fontSize: 14,
            fontWeight: FontWeight.w600,
          ),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: AppColors.primary,
          textStyle: GoogleFonts.vazirmatn(fontWeight: FontWeight.w600),
        ),
      ),
      chipTheme: ChipThemeData(
        backgroundColor: AppColors.primary.withValues(alpha: 0.1),
        side: BorderSide(color: AppColors.primary.withValues(alpha: 0.2)),
        labelStyle: const TextStyle(color: AppColors.primary, fontSize: 12),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(999),
        ),
      ),
      snackBarTheme: SnackBarThemeData(
        backgroundColor: dark ? const Color(0xFF141B26) : Colors.white,
        contentTextStyle: TextStyle(color: fg),
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
          side: BorderSide(color: border),
        ),
      ),
    );
  }
}
