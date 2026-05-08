import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'services/api_service.dart';
import 'login_page.dart';
import 'dashboard_page.dart';

import 'pages/waiting_zone_page.dart';
import 'package:geolocator/geolocator.dart';

class SplashPage extends StatefulWidget {
  const SplashPage({super.key});

  @override
  State<SplashPage> createState() => _SplashPageState();
}

class _SplashPageState extends State<SplashPage> {
  @override
  void initState() {
    super.initState();
    _initializeApp();
  }

  Future<void> _initializeApp() async {
    // Request location permissions immediately on startup
    await _requestLocationPermission();

    // Run the initialization and minimum delay concurrently
    final results = await Future.wait([
      _loadDriverData(),
      ApiService.getActiveOffer(),
      Future.delayed(const Duration(milliseconds: 2000)), // Minimum 2s splash
    ]);

    final String? driverId = results[0] as String?;
    final Map<String, dynamic>? activeOffer = results[1] as Map<String, dynamic>?;

    if (mounted) {
      Navigator.pushReplacement(
        context,
        PageRouteBuilder(
          pageBuilder: (context, animation, secondaryAnimation) {
            if (driverId == null) {
              return const LoginPage();
            } else if (activeOffer != null) {
              return WaitingZonePage(
                orderId: activeOffer['orderId'],
                driverId: driverId,
                offerPrice: activeOffer['price'],
                offerTimestamp: activeOffer['timestamp'],
              );
            } else {
              return const DashboardPage();
            }
          },
          transitionsBuilder: (context, animation, secondaryAnimation, child) {
            return FadeTransition(opacity: animation, child: child);
          },
          transitionDuration: const Duration(milliseconds: 500),
        ),
      );
    }
  }

  Future<void> _requestLocationPermission() async {
    bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      await Geolocator.openLocationSettings();
    }

    LocationPermission permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }

    // If still denied, show a blocking modern popup
    while (permission == LocationPermission.denied || permission == LocationPermission.deniedForever) {
      await _showModernLocationPopup();
      permission = await Geolocator.checkPermission();
    }
  }

  Future<void> _showModernLocationPopup() async {
    return showDialog(
      context: context,
      barrierDismissible: false,
      builder: (BuildContext context) {
        return PopScope(
          canPop: false,
          child: Dialog(
            backgroundColor: Colors.white,
            surfaceTintColor: Colors.transparent,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)),
            child: Padding(
              padding: const EdgeInsets.all(28.0),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: const Color(0xFF6366F1).withOpacity(0.1),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.pin_drop_rounded, color: Color(0xFF6366F1), size: 48),
                  ),
                  const SizedBox(height: 24),
                  const Text(
                    "Location Access",
                    style: TextStyle(fontSize: 22, fontWeight: FontWeight.w900, fontFamily: 'Inter'),
                  ),
                  const SizedBox(height: 12),
                  const Text(
                    "QOON Express requires location access (Always Allow) to send you nearby delivery orders and track your active trips in the background.",
                    textAlign: TextAlign.center,
                    style: TextStyle(fontSize: 14, color: Colors.black54, height: 1.5),
                  ),
                  const SizedBox(height: 32),
                  SizedBox(
                    width: double.infinity,
                    height: 54,
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF6366F1),
                        elevation: 0,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                      ),
                      onPressed: () => Geolocator.openAppSettings(),
                      child: const Text("Open Settings", style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 16)),
                    ),
                  ),
                  const SizedBox(height: 12),
                  SizedBox(
                    width: double.infinity,
                    height: 54,
                    child: TextButton(
                      style: TextButton.styleFrom(
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                      ),
                      onPressed: () async {
                        final perm = await Geolocator.checkPermission();
                        if (perm == LocationPermission.always || perm == LocationPermission.whileInUse) {
                          Navigator.pop(context);
                        }
                      },
                      child: const Text("I've Enabled It", style: TextStyle(color: Colors.black, fontWeight: FontWeight.w800, fontSize: 15)),
                    ),
                  ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }

  Future<String?> _loadDriverData() async {
    final driverId = await ApiService.getDriverId();
    if (driverId != null) {
      await ApiService.loadCachedDriverData();
    }
    return driverId;
  }

  @override
  Widget build(BuildContext context) {
    // Ensuring status bar is dark (visible on white)
    SystemChrome.setSystemUIOverlayStyle(
      const SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: Brightness.dark,
      ),
    );

    return Scaffold(
      backgroundColor: Colors.white,
      body: Center(
        child: TweenAnimationBuilder<double>(
          tween: Tween(begin: 0.0, end: 1.0),
          duration: const Duration(milliseconds: 1000),
          curve: Curves.easeOutCubic,
          builder: (context, value, child) {
            return Transform.scale(
              scale: 0.8 + (0.2 * value),
              child: Opacity(
                opacity: value,
                child: child,
              ),
            );
          },
          child: Image.network(
            'https://qoon.app/logo_express.png',
            height: 60,
            errorBuilder: (_, __, ___) => const Text(
              'QOON Express',
              style: TextStyle(
                fontSize: 28,
                fontWeight: FontWeight.w900,
                letterSpacing: -1,
              ),
            ),
          ),
        ),
      ),
    );
  }
}
