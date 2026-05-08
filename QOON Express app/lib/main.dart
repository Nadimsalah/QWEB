import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'splash_page.dart';
import 'services/notification_service.dart';
import 'services/localization_service.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Initialize Push Notifications via Firebase
  try {
    await NotificationService.initialize();
  } catch (e) {
    debugPrint('Firebase/Notification Init Error: $e');
  }

  SystemChrome.setSystemUIOverlayStyle(
    const SystemUiOverlayStyle(
      statusBarColor: Colors.transparent,
      statusBarIconBrightness: Brightness.dark,
    ),
  );
  
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: localizationService,
      builder: (context, child) {
        final isArabic = localizationService.currentLanguage == 'ar';
        
        return MaterialApp(
          title: 'QOON Delivery',
          debugShowCheckedModeBanner: false,
          builder: (context, child) {
            return Directionality(
              textDirection: isArabic ? TextDirection.rtl : TextDirection.ltr,
              child: child!,
            );
          },
          theme: ThemeData(
            colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFFE65C00)),
            useMaterial3: true,
            scaffoldBackgroundColor: Colors.white,
            fontFamily: isArabic ? 'Cairo' : 'Inter', // Suggesting an Arabic font, fallback works
          ),
          home: const SplashPage(),
        );
      },
    );
  }
}
