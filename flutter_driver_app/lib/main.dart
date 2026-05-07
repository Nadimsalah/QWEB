import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'core/api_client.dart';
import 'features/auth/login_screen.dart';
import 'features/dashboard/dashboard_screen.dart';

void main() {
  runApp(
    MultiProvider(
      providers: [
        Provider<ApiClient>(
          create: (_) => ApiClient(),
        ),
      ],
      child: const DriverApp(),
    ),
  );
}

class DriverApp extends StatelessWidget {
  const DriverApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'QOON Driver',
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(
          seedColor: Colors.deepPurple,
          brightness: Brightness.light,
        ),
        useMaterial3: true,
      ),
      // We will define a simple route system here
      initialRoute: '/login',
      routes: {
        '/login': (context) => const LoginScreen(),
        '/dashboard': (context) => const DashboardScreen(),
      },
    );
  }
}
