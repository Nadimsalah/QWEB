import 'package:flutter/material.dart';
import 'pages/orders_page.dart';
import 'pages/history_page.dart';
import 'pages/wallet_page.dart';
import 'pages/profile_page.dart';
import 'services/localization_service.dart';

import 'dart:async';
import 'package:internet_connection_checker_plus/internet_connection_checker_plus.dart';

class DashboardPage extends StatefulWidget {
  const DashboardPage({super.key});

  @override
  State<DashboardPage> createState() => _DashboardPageState();
}

class _DashboardPageState extends State<DashboardPage> {
  int _currentIndex = 0; // Start on Home tab
  bool _hasInternet = true;
  StreamSubscription<InternetStatus>? _internetSubscription;

  final List<Widget> _pages = const [
    OrdersPage(),
    HistoryPage(),
    WalletPage(),
    ProfilePage(),
  ];

  @override
  void initState() {
    super.initState();
    _internetSubscription = InternetConnection().onStatusChange.listen((InternetStatus status) {
      if (mounted) {
        setState(() {
          _hasInternet = status == InternetStatus.connected;
        });
      }
    });
  }

  @override
  void dispose() {
    _internetSubscription?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: localizationService,
      builder: (context, child) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: Column(
        children: [
          if (!_hasInternet)
            Container(
              width: double.infinity,
              color: Colors.red.shade600,
              padding: EdgeInsets.only(top: MediaQuery.of(context).padding.top + 8, bottom: 8, left: 16, right: 16),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(Icons.cloud_off_rounded, color: Colors.white, size: 16),
                  const SizedBox(width: 8),
                  Text(
                    'no_internet'.tr,
                    style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 12),
                  ),
                ],
              ),
            ),
          Expanded(child: _pages[_currentIndex]),
        ],
      ),
      bottomNavigationBar: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          border: Border(
            top: BorderSide(
              color: Colors.grey.shade200,
              width: 1,
            ),
          ),
        ),
        child: SafeArea(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _buildNavItem(0, Icons.home_rounded, 'home'.tr),
                _buildNavItem(1, Icons.receipt_long_rounded, 'orders'.tr),
                _buildNavItem(2, Icons.account_balance_wallet_rounded, 'wallet'.tr),
                _buildNavItem(3, Icons.person_rounded, 'profile'.tr),
              ],
            ),
          ),
        ),
      ),
    );
    }); // end ListenableBuilder
  }

  Widget _buildNavItem(int index, IconData icon, String label) {
    final isSelected = _currentIndex == index;
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: () {
        setState(() {
          _currentIndex = index;
        });
      },
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
            decoration: BoxDecoration(
              color: isSelected ? Colors.black : Colors.transparent,
              borderRadius: BorderRadius.circular(20),
            ),
            child: Icon(
              icon,
              color: isSelected ? Colors.white : Colors.grey.shade500,
              size: 24,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            label,
            style: TextStyle(
              fontSize: 12,
              fontWeight: isSelected ? FontWeight.w600 : FontWeight.w500,
              color: isSelected ? Colors.black : Colors.grey.shade500,
            ),
          ),
        ],
      ),
    );
  }
}
