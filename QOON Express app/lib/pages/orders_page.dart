import 'package:flutter/material.dart';
import 'dart:async';
import 'package:cached_network_image/cached_network_image.dart';
import '../services/api_service.dart';
import '../services/localization_service.dart';
import 'waiting_zone_page.dart';
import 'active_order_page.dart';
import 'dart:convert';
import 'package:intl/intl.dart';
import 'package:shimmer/shimmer.dart';
import 'package:geolocator/geolocator.dart';
import 'package:shared_preferences/shared_preferences.dart';

class OrdersPage extends StatefulWidget {
  const OrdersPage({super.key});

  @override
  State<OrdersPage> createState() => _OrdersPageState();
}

class _OrdersPageState extends State<OrdersPage> with SingleTickerProviderStateMixin {
  Future<Map<String, dynamic>>? _dashboardDataFuture;
  final Set<String> _blockedOrders = {};
  Timer? _pollingTimer;
  
  // Dashboard state
  Map<String, dynamic>? _driverProfile;
  Map<String, dynamic>? _driverStats;
  List<dynamic> _allAvailableOrders = [];
  List<dynamic> _filteredAvailableOrders = [];
  List<dynamic> _allActiveTrips = [];
  List<dynamic> _filteredActiveTrips = [];
  
  int _selectedTab = 0; // 0 = Available, 1 = Active
  bool _isAppLocked = false;
  bool _isOnline = true;
  String _driverId = '';

  // Search & Filter state
  final TextEditingController _searchController = TextEditingController();
  bool _isSearching = false;
  DateTime? _selectedDate;
  double _maxDistanceFilter = 30.0; // Distance slider state
  
  // Real-time location
  Position? _currentPosition;
  StreamSubscription<Position>? _positionStream;

  @override
  void initState() {
    super.initState();
    _loadSavedDistance();
    _loadDashboard();
    _startPolling();
    _searchController.addListener(_applyFilters);
    _initLocation();
  }

  Future<void> _loadSavedDistance() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      _maxDistanceFilter = prefs.getDouble('driver_max_distance') ?? 30.0;
    });
    _applyFilters();
  }

  Future<void> _initLocation() async {
    bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) return;

    LocationPermission permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied) return;
    }
    if (permission == LocationPermission.deniedForever) return;

    try {
      _currentPosition = await Geolocator.getCurrentPosition(desiredAccuracy: LocationAccuracy.high);
      
      // MOCK LOCATION FOR SIMULATOR: 
      // If the simulator is physically in Cupertino, California (Apple HQ), teleport the driver to Casablanca
      // so you don't see 9,600 km away when testing.
      if (_currentPosition != null && _currentPosition!.latitude > 37 && _currentPosition!.longitude < -121) {
         _currentPosition = Position(
           latitude: 33.9716, // Rabat/Casablanca general area
           longitude: -6.8498,
           timestamp: DateTime.now(),
           accuracy: 0.0,
           altitude: 0.0,
           altitudeAccuracy: 0.0,
           heading: 0.0,
           headingAccuracy: 0.0,
           speed: 0.0,
           speedAccuracy: 0.0,
         );
      }

      if (_currentPosition != null) {
        ApiService.updateDriverLocation(_currentPosition!.latitude.toString(), _currentPosition!.longitude.toString());
      }
      if (mounted) setState(() {});
    } catch (_) {}

    _positionStream = Geolocator.getPositionStream(
      locationSettings: const LocationSettings(accuracy: LocationAccuracy.high, distanceFilter: 10),
    ).listen((Position position) {
      if (position.latitude > 37 && position.longitude < -121) {
         position = Position(
           latitude: 33.9716, 
           longitude: -6.8498,
           timestamp: DateTime.now(),
           accuracy: 0.0,
           altitude: 0.0,
           altitudeAccuracy: 0.0,
           heading: 0.0,
           headingAccuracy: 0.0,
           speed: 0.0,
           speedAccuracy: 0.0,
         );
      }
      ApiService.updateDriverLocation(position.latitude.toString(), position.longitude.toString());
      if (mounted) {
        setState(() {
          _currentPosition = position;
        });
      }
    });
  }

  @override
  void dispose() {
    _pollingTimer?.cancel();
    _searchController.dispose();
    _positionStream?.cancel();
    super.dispose();
  }

  void _applyFilters() {
    final query = _searchController.text.toLowerCase();
    
    setState(() {
      // Filter Available Orders
      _filteredAvailableOrders = _allAvailableOrders.where((order) {
        final shopName = (order['ShopName'] ?? order['DestinationName'] ?? '').toString().toLowerCase();
        final shopId = (order['ShopID'] ?? '').toString().toLowerCase();
        final orderId = (order['OrderID'] ?? '').toString().toLowerCase();
        
        final matchesSearch = query.isEmpty || 
                             shopName.contains(query) || 
                             shopId.contains(query) || 
                             orderId.contains(query);

        bool matchesDate = true;
        if (_selectedDate != null) {
          final txDateStr = order['CreatedAtOrders']?.toString() ?? '';
          try {
            final txDate = DateTime.parse(txDateStr);
            matchesDate = txDate.year == _selectedDate!.year &&
                          txDate.month == _selectedDate!.month &&
                          txDate.day == _selectedDate!.day;
          } catch (e) {
            matchesDate = false;
          }
        }

        // Calculate Distance for Filtering
        bool matchesDistance = true;
        String distanceStr = double.tryParse(order['distance']?.toString() ?? '0')?.toStringAsFixed(1) ?? '0.0';
        if (_currentPosition != null) {
          final shopLat = double.tryParse(order['ShopLat']?.toString() ?? order['DestnationLat']?.toString() ?? '');
          final shopLng = double.tryParse(order['ShopLongt']?.toString() ?? order['DestnationLongt']?.toString() ?? '');
          if (shopLat != null && shopLng != null && shopLat != 0.0 && shopLng != 0.0) {
            final distanceInMeters = Geolocator.distanceBetween(
              _currentPosition!.latitude, 
              _currentPosition!.longitude, 
              shopLat, 
              shopLng
            );
            distanceStr = (distanceInMeters / 1000).toStringAsFixed(1);
          }
        }
        
        // Save computed distance so we don't recalculate it later in the UI
        order['computed_distance'] = distanceStr;
        
        double dist = double.tryParse(distanceStr) ?? 0.0;
        // Include orders without coords, otherwise filter by distance
        if (distanceStr != '--') {
          matchesDistance = dist <= _maxDistanceFilter || _maxDistanceFilter >= 30.0;
        }
        
        return matchesSearch && matchesDate && matchesDistance;
      }).toList();

      // Filter Active Trips
      _filteredActiveTrips = _allActiveTrips.where((order) {
        final shopName = (order['ShopName'] ?? order['DestinationName'] ?? '').toString().toLowerCase();
        final orderId = (order['OrderID'] ?? '').toString().toLowerCase();
        
        final matchesSearch = query.isEmpty || 
                             shopName.contains(query) || 
                             orderId.contains(query);

        bool matchesDate = true;
        if (_selectedDate != null) {
          final txDateStr = order['CreatedAtOrders']?.toString() ?? '';
          try {
            final txDate = DateTime.parse(txDateStr);
            matchesDate = txDate.year == _selectedDate!.year &&
                          txDate.month == _selectedDate!.month &&
                          txDate.day == _selectedDate!.day;
          } catch (e) {
            matchesDate = false;
          }
        }
        return matchesSearch && matchesDate;
      }).toList();
    });
  }

  void _startPolling() {
    _pollingTimer = Timer.periodic(const Duration(seconds: 5), (timer) {
      if (_isOnline && mounted && !_isAppLocked) {
        _loadDashboard(isPolling: true);
      }
    });
  }

  Future<void> _loadDashboard({bool isPolling = false}) async {
    final driverId = await ApiService.getDriverId();
    if (driverId == null) return;
    
    if (!mounted) return;

    final future = _fetchData(driverId);
    
    // Update state when data arrives
    future.then((data) {
      if (mounted) {
        setState(() {
          _updateStateWithData(data);
        });
      }
    });

    if (!isPolling) {
      setState(() {
        _dashboardDataFuture = future;
      });
    }
  }

  Future<Map<String, dynamic>> _fetchData(String driverId) async {
    _driverId = driverId;
    final results = await Future.wait([
      ApiService.getProfile(driverId),
      ApiService.getDriverStats(driverId),
      ApiService.getNearOrders(ApiService.driverLat, ApiService.driverLng),
      ApiService.getCurrentOrders(driverId),
    ]);

    return {
      'profile': results[0],
      'stats': results[1],
      'available': results[2],
      'active': results[3] as List<dynamic>? ?? [],
    };
  }

  void _updateStateWithData(Map<String, dynamic> data) {
    _driverProfile = data['profile'];
    _driverStats = data['stats'] ?? {};
    
    final List<dynamic> newAvailableOrders = data['available'] ?? [];
    
    // Detect removed orders to show notification
    if (_allAvailableOrders.isNotEmpty && mounted) {
      final newOrderIds = newAvailableOrders.map((o) => o['OrderID']?.toString()).where((id) => id != null).cast<String>().toSet();
      final oldOrderIds = _allAvailableOrders.map((o) => o['OrderID']?.toString()).where((id) => id != null).cast<String>().toSet();
      
      final removedIds = oldOrderIds.difference(newOrderIds);
      
      final activeOrderIds = (data['active'] as List<dynamic>? ?? []).map((o) => o['OrderID']?.toString()).where((id) => id != null).cast<String>().toSet();

      for (final id in removedIds) {
        // Only show if the order is not blocked AND the order wasn't accepted by THIS driver (i.e. it's not in their active list)
        if (!_blockedOrders.contains(id) && !activeOrderIds.contains(id)) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Row(
                children: [
                  const Icon(Icons.cancel_outlined, color: Colors.white),
                  const SizedBox(width: 12),
                  Expanded(child: Text('Order #$id was cancelled or assigned to someone else.')),
                ],
              ),
              backgroundColor: Colors.orange.shade800,
              behavior: SnackBarBehavior.floating,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              margin: const EdgeInsets.only(bottom: 20, left: 16, right: 16),
              duration: const Duration(seconds: 4),
            ),
          );
        }
      }
    }

    _allAvailableOrders = newAvailableOrders;
    
    final List<dynamic> allMyOrders = data['active'] ?? [];
    const activeStates = {
      'waiting', 'placed', 'accepted', 'doing', 'order pickup', 'come to take it', 'order processed', 'preparing',
      'ready', 'picked', 'picked up', 'arrived at shop', 
      'on way', 'on the way', 'found', 'arrived'
    };
    _allActiveTrips = allMyOrders.where((o) {
      final state = o['OrderState']?.toString().toLowerCase() ?? '';
      return activeStates.contains(state);
    }).toList();

    _applyFilters();

    // Check App Lock limit based on Net Debt (Cash Collected - Wallet/Online Earnings)
    final wallet = double.tryParse(_driverStats?['walletBalance']?.toString() ?? '0') ?? 0;
    final cashCollected = double.tryParse(_driverStats?['cashCollected']?.toString() ?? '0') ?? 0;
    final cashLimit = double.tryParse(_driverStats?['cashLimit']?.toString() ?? '350') ?? 350;
    
    final netDebt = cashCollected - wallet;
    _isAppLocked = netDebt >= cashLimit;
  }

  void _showDebtWarningPopup(BuildContext context) {
    final wallet = double.tryParse(_driverStats?['walletBalance']?.toString() ?? '0.00') ?? 0;
    final cash = double.tryParse(_driverStats?['cashCollected']?.toString() ?? '0.00') ?? 0;
    final netDebt = cash - wallet;
    final limit = _driverStats?['cashLimit']?.toString() ?? '350';

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
        title: const Row(
          children: [
            Icon(Icons.warning_amber_rounded, color: Colors.red),
            SizedBox(width: 10),
            Text('Debt Limit Reached', style: TextStyle(fontWeight: FontWeight.w900)),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Your net debt is ${netDebt.toStringAsFixed(2)} MAD, which exceeds your limit of $limit MAD.',
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 16),
            const Text(
              'To continue sending offers, please transfer the collected cash minus your online earnings.',
              style: TextStyle(fontSize: 14, color: Colors.black87),
            ),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('CANCEL')),
          ElevatedButton(
            onPressed: () => Navigator.pop(context),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            child: const Text('HOW TO PAY', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }

  Future<void> _selectDate(BuildContext context) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate ?? DateTime.now(),
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: Color(0xFF6366F1),
              onPrimary: Colors.white,
              onSurface: Color(0xFF1E293B),
            ),
          ),
          child: child!,
        );
      },
    );
    if (picked != null && picked != _selectedDate) {
      setState(() {
        _selectedDate = picked;
        _applyFilters();
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: localizationService,
      builder: (context, child) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        scrolledUnderElevation: 0,
        title: Image.network('https://qoon.app/logo_express.png', height: 28, errorBuilder: (_,__,___) => const SizedBox(height: 28)),
        actions: [
          _buildOnlineToggle(),
        ],
      ),
      body: FutureBuilder<Map<String, dynamic>?>(
        future: _dashboardDataFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting && _driverProfile == null) {
            return _buildShimmerLoading();
          }
          if (_isAppLocked) return _buildAppLockedView();
          return RefreshIndicator(
            onRefresh: () async => await _loadDashboard(),
            color: const Color(0xFF6366F1),
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(parent: BouncingScrollPhysics()),
              padding: const EdgeInsets.only(top: 8, bottom: 100),
              children: [
                if (_selectedDate != null) _buildFilterStatus(),
                _buildTabs(),
                if (_selectedTab == 0) _buildDistanceFilter(),
                _buildMainList(),
              ],
            ),
          );
        },
      ),
    );
    }); // end ListenableBuilder
  }

  Widget _buildShimmerLoading() {
    return ListView(
      physics: const NeverScrollableScrollPhysics(),
      padding: const EdgeInsets.only(top: 8, bottom: 100),
      children: [
        // Tabs Shimmer
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          child: Shimmer.fromColors(
            baseColor: Colors.grey.shade200,
            highlightColor: Colors.grey.shade100,
            child: Container(
              height: 48,
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(20)),
            ),
          ),
        ),
        // Cards Shimmer
        ListView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          padding: const EdgeInsets.symmetric(horizontal: 16),
          itemCount: 4,
          itemBuilder: (context, index) {
            return Padding(
              padding: const EdgeInsets.only(bottom: 16),
              child: Shimmer.fromColors(
                baseColor: Colors.grey.shade200,
                highlightColor: Colors.grey.shade100,
                child: Container(
                  height: 200,
                  decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(24)),
                ),
              ),
            );
          },
        ),
      ],
    );
  }

  Widget _buildOnlineToggle() {
    return GestureDetector(
      onTap: () => setState(() => _isOnline = !_isOnline),
      child: Container(
        margin: const EdgeInsets.only(right: 16, top: 10, bottom: 10),
        padding: const EdgeInsets.symmetric(horizontal: 12),
        decoration: BoxDecoration(
          color: _isOnline ? Colors.green.shade50 : Colors.grey.shade100,
          borderRadius: BorderRadius.circular(99),
          border: Border.all(color: _isOnline ? Colors.green.shade200 : Colors.grey.shade300),
        ),
        child: Row(
          children: [
            Container(width: 6, height: 6, decoration: BoxDecoration(color: _isOnline ? Colors.green : Colors.grey, shape: BoxShape.circle)),
            const SizedBox(width: 6),
            Text(
              _isOnline ? 'online'.tr : 'offline'.tr,
              style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: _isOnline ? Colors.green.shade700 : Colors.grey.shade700),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFilterStatus() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
      child: Row(
        children: [
          Chip(
            label: Text(
              'Date: ${DateFormat('MMM dd').format(_selectedDate!)}',
              style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w700),
            ),
            backgroundColor: const Color(0xFF6366F1),
            deleteIcon: const Icon(Icons.close, size: 14, color: Colors.white),
            onDeleted: () => setState(() { _selectedDate = null; _applyFilters(); }),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          ),
        ],
      ),
    );
  }

  Widget _buildAppLockedView() {
    final wallet = double.tryParse(_driverStats?['walletBalance']?.toString() ?? '0.00') ?? 0;
    final cash = double.tryParse(_driverStats?['cashCollected']?.toString() ?? '0.00') ?? 0;
    final netDebt = cash - wallet;
    final limit = _driverStats?['cashLimit']?.toString() ?? '350';

    return Container(
      width: double.infinity,
      color: const Color(0xFFF8FAFC),
      padding: const EdgeInsets.symmetric(horizontal: 32),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              color: Colors.white, shape: BoxShape.circle,
              boxShadow: [BoxShadow(color: Colors.red.withOpacity(0.1), blurRadius: 30, spreadRadius: 10)],
            ),
            child: const Icon(Icons.lock_person_rounded, size: 64, color: Color(0xFFEF4444)),
          ),
          const SizedBox(height: 32),
          Text('account_restricted'.tr, style: const TextStyle(fontSize: 28, fontWeight: FontWeight.w900, color: Color(0xFF1E293B))),
          const SizedBox(height: 12),
          Text('Your debt of ${netDebt.toStringAsFixed(0)} MAD exceeds the $limit MAD limit.', textAlign: TextAlign.center, style: const TextStyle(fontSize: 16, color: Color(0xFF64748B))),
          const SizedBox(height: 40),
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(24), border: Border.all(color: const Color(0xFFE2E8F0))),
            child: Column(
              children: [
                _buildDebtRow('cash_collected'.tr, '${cash.toStringAsFixed(0)} MAD', Colors.black87),
                const Divider(height: 24),
                _buildDebtRow('online_earnings'.tr, '- ${wallet.toStringAsFixed(0)} MAD', Colors.green),
                const Divider(height: 24),
                _buildDebtRow('total_owed'.tr, '${netDebt.toStringAsFixed(0)} MAD', const Color(0xFFEF4444), isBold: true),
              ],
            ),
          ),
          const SizedBox(height: 24),
          SizedBox(
            width: double.infinity, height: 64,
            child: ElevatedButton(
              onPressed: () {},
              style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF1E293B), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20))),
              child: Text('resolve_debt'.tr, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: Colors.white)),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDebtRow(String label, String value, Color valueColor, {bool isBold = false}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(fontSize: 14, color: Color(0xFF64748B))),
        Text(value, style: TextStyle(fontSize: 16, fontWeight: isBold ? FontWeight.w900 : FontWeight.w700, color: valueColor)),
      ],
    );
  }


  Widget _buildTabs() {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      padding: const EdgeInsets.all(6),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(20), border: Border.all(color: const Color(0xFFE2E8F0))),
      child: Row(
        children: [
          _buildTabItem(0, 'tab_available'.tr, Icons.bolt, _filteredAvailableOrders.length),
          _buildTabItem(1, 'tab_active'.tr, Icons.route, _filteredActiveTrips.length),
        ],
      ),
    );
  }

  Widget _buildDistanceFilter() {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 10)],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Max Distance',
                style: TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: Colors.grey.shade700),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: const Color(0xFF6366F1).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  _maxDistanceFilter >= 30.0 ? '30+ km' : '${_maxDistanceFilter.toStringAsFixed(0)} km',
                  style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w900, color: Color(0xFF6366F1)),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          SliderTheme(
            data: SliderTheme.of(context).copyWith(
              trackHeight: 6,
              activeTrackColor: const Color(0xFF6366F1),
              inactiveTrackColor: const Color(0xFFE0E7FF),
              thumbColor: Colors.white,
              overlayColor: const Color(0xFF6366F1).withOpacity(0.2),
              thumbShape: const RoundSliderThumbShape(enabledThumbRadius: 12, elevation: 4),
              overlayShape: const RoundSliderOverlayShape(overlayRadius: 24),
            ),
            child: Slider(
              value: _maxDistanceFilter,
              min: 1.0,
              max: 30.0,
              divisions: 29,
              onChanged: (value) async {
                setState(() {
                  _maxDistanceFilter = value;
                  _applyFilters();
                });
                final prefs = await SharedPreferences.getInstance();
                await prefs.setDouble('driver_max_distance', value);
              },

            ),
          ),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('1 km', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Colors.grey.shade400)),
              Text('30+ km', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Colors.grey.shade400)),
            ],
          ),
        ],
      ),
    );
  }


  Widget _buildTabItem(int index, String label, IconData icon, int count) {
    final isSelected = _selectedTab == index;
    return Expanded(
      child: GestureDetector(
        onTap: () => setState(() => _selectedTab = index),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 12),
          decoration: BoxDecoration(color: isSelected ? const Color(0xFF6366F1) : Colors.transparent, borderRadius: BorderRadius.circular(14)),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(icon, size: 18, color: isSelected ? Colors.white : Colors.grey.shade500),
              const SizedBox(width: 8),
              Text(label, style: TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: isSelected ? Colors.white : Colors.grey.shade500)),
              if (count > 0) ...[
                const SizedBox(width: 8),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  decoration: BoxDecoration(color: Colors.white.withOpacity(isSelected ? 0.2 : 0.1), borderRadius: BorderRadius.circular(8)),
                  child: Text('$count', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w900, color: isSelected ? Colors.white : Colors.grey.shade600)),
                )
              ]
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildMainList() {
    if (!_isOnline && _selectedTab == 0) return _buildEmptyState(Icons.wifi_off_rounded, 'you_offline'.tr, 'go_online'.tr);
    final list = _selectedTab == 0 ? _filteredAvailableOrders : _filteredActiveTrips;
    if (list.isEmpty) {
      final isFiltered = _isSearching || _selectedDate != null;
      return _buildEmptyState(
        isFiltered ? Icons.search_off_rounded : Icons.radar, 
        isFiltered ? 'no_matches'.tr : 'no_orders'.tr, 
        isFiltered ? 'try_clear_filters'.tr : 'scanning'.tr
      );
    }

    return ListView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      padding: const EdgeInsets.symmetric(horizontal: 16),
      itemCount: list.length,
      itemBuilder: (context, index) {
        final order = list[index];
        return Padding(
          padding: const EdgeInsets.only(bottom: 16),
          child: _selectedTab == 0 ? _buildAvailableOrderCard(order) : _buildActiveTripCard(order),
        );
      },
    );
  }

  Widget _buildEmptyState(IconData icon, String title, String desc) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 60),
      child: Column(
        children: [
          Icon(icon, size: 64, color: Colors.grey.shade200),
          const SizedBox(height: 20),
          Text(title, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: Color(0xFF1E293B))),
          const SizedBox(height: 8),
          Text(desc, textAlign: TextAlign.center, style: TextStyle(fontSize: 14, color: Colors.grey.shade500)),
          if (_isSearching || _selectedDate != null)
            TextButton(
              onPressed: () => setState(() { _searchController.clear(); _selectedDate = null; _isSearching = false; _applyFilters(); }),
              child: Text('clear_filters'.tr, style: const TextStyle(color: Color(0xFF6366F1), fontWeight: FontWeight.w700)),
            )
        ],
      ),
    );
  }

  Widget _buildAvailableOrderCard(dynamic order) {
    final orderId = order['OrderID']?.toString() ?? '';
    if (_blockedOrders.contains(orderId)) return const SizedBox.shrink();

    final shopName = order['ShopName'] ?? order['DestinationName'] ?? 'Shop';
    final shopPhoto = (order['ShopLogo']?.toString().isNotEmpty == true) ? order['ShopLogo'] : (order['DestnationPhoto'] ?? '');
    
    // Improved Customer Name Fallback
    String userName = order['UserName']?.toString() ?? '';
    if (userName.isEmpty) userName = order['name']?.toString() ?? '';
    if (userName.isEmpty) userName = order['FName']?.toString() ?? '';
    if (userName.isEmpty) userName = order['UserPhone']?.toString() ?? '';
    if (userName.isEmpty) userName = 'Customer';
    String userPhoto = '';
    if (order['UserPhoto'] != null && order['UserPhoto'].toString().isNotEmpty && order['UserPhoto'].toString() != '0' && order['UserPhoto'].toString() != 'null') {
      userPhoto = order['UserPhoto'].toString();
    } else if (order['PersonalPhoto'] != null && order['PersonalPhoto'].toString().isNotEmpty) {
      userPhoto = order['PersonalPhoto'].toString();
    }
    
    // Only generate initials avatar if we have absolutely nothing — this will render as a colored circle
    if (userPhoto.isEmpty) {
      userPhoto = 'https://ui-avatars.com/api/?name=${Uri.encodeComponent(userName)}&background=2cb5e8&color=fff&size=64';
    }
    
    final orderDetails = order['OrderDetails'] ?? 'Unknown Items';
    
    // Use the computed distance from the filter logic to avoid recalculation
    String distanceStr = order['computed_distance'] ?? double.tryParse(order['distance']?.toString() ?? '0')?.toStringAsFixed(1) ?? '0.0';


    return Container(
      decoration: BoxDecoration(
        color: Colors.white, borderRadius: BorderRadius.circular(24),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 15, offset: const Offset(0, 8))],
      ),
      child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Shop Row
                Row(
                  children: [
                    Container(
                      width: 48, height: 48,
                      decoration: BoxDecoration(color: const Color(0xFFF1F5F9), borderRadius: BorderRadius.circular(14)),
                      child: shopPhoto.isNotEmpty 
                        ? ClipRRect(
                            borderRadius: BorderRadius.circular(14),
                            child: Image.network(
                              shopPhoto, 
                              fit: BoxFit.cover,
                              errorBuilder: (context, error, stackTrace) => const Icon(Icons.storefront_rounded, color: Color(0xFF6366F1), size: 28),
                            ),
                          )
                        : const Icon(Icons.storefront_rounded, color: Color(0xFF6366F1), size: 28),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Text(shopName, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w900)),
                        Text('Order #$orderId', style: TextStyle(fontSize: 12, color: Colors.grey.shade500)),
                      ]),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(color: const Color(0xFFF0F9FF), borderRadius: BorderRadius.circular(99)),
                      child: Text('$distanceStr km', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: Color(0xFF0EA5E9))),
                    ),
                  ],
                ),
                
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  child: Divider(color: Colors.grey.shade100, height: 1),
                ),

                // Customer Row
                Row(
                  children: [
                    Container(
                      width: 32, height: 32,
                      decoration: BoxDecoration(shape: BoxShape.circle, color: Colors.grey.shade200),
                      clipBehavior: Clip.antiAlias,
                      child: userPhoto.isNotEmpty 
                        ? Image.network(
                            userPhoto, fit: BoxFit.cover,
                            errorBuilder: (_, __, ___) => const Icon(Icons.person, color: Colors.grey, size: 20),
                          )
                        : const Icon(Icons.person, color: Colors.grey, size: 20),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text('${'deliver_to'.tr} $userName', style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: Color(0xFF334155))),
                    ),
                  ],
                ),

                const SizedBox(height: 12),

                // Products Box
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF8FAFC),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: const Color(0xFFF1F5F9)),
                  ),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Icon(Icons.shopping_bag_outlined, size: 16, color: Color(0xFF64748B)),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          orderDetails,
                          style: const TextStyle(fontSize: 13, color: Color(0xFF475569), height: 1.4),
                        ),
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 20),

                // Actions
                Row(
                  children: [
                    Expanded(
                      flex: 2,
                      child: SizedBox(
                        height: 52,
                        child: ElevatedButton(
                          onPressed: () {
                            if (_isAppLocked) _showDebtWarningPopup(context);
                            else _showOfferBottomSheet(context, order, _driverId);
                          },
                          style: ElevatedButton.styleFrom(backgroundColor: _isAppLocked ? Colors.grey : const Color(0xFF6366F1), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)), elevation: 0),
                          child: Text('send_offer'.tr, style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: Colors.white)),
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: SizedBox(
                        height: 52,
                        child: OutlinedButton(
                          onPressed: () => setState(() => _blockedOrders.add(orderId)),
                          style: OutlinedButton.styleFrom(side: BorderSide(color: Colors.grey.shade200), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16))),
                          child: Text('hide'.tr, style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700, color: Colors.grey.shade600)),
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildActiveTripCard(dynamic order) {
    final orderId = order['OrderID']?.toString() ?? '';
    final shopName = order['DestinationName'] ?? order['ShopName'] ?? 'Shop';
    
    final String rawState = order['OrderState']?.toString() ?? 'DOING';
    String state = rawState.toUpperCase();
    if (state == 'DONE' || state == 'FINISH' || state == 'RATED') {
      state = 'DELIVERED';
    } else if (state == 'RETURN' || state == 'RETURNED') {
      state = 'RETURNED';
    }
    
    final shopPhoto = order['DestnationPhoto'] ?? order['ShopLogo'] ?? '';
    
    return GestureDetector(
      onTap: () => Navigator.push(context, MaterialPageRoute(builder: (context) => ActiveOrderPage(orderId: orderId, driverId: _driverId))).then((_) => _loadDashboard()),
      child: Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(24), border: Border.all(color: const Color(0xFFE2E8F0))),
        child: Row(
          children: [
            Container(
              width: 48, height: 48,
              decoration: BoxDecoration(color: const Color(0xFFF1F5F9), borderRadius: BorderRadius.circular(14)),
              child: shopPhoto.isNotEmpty 
                ? ClipRRect(
                    borderRadius: BorderRadius.circular(14),
                    child: Image.network(
                      shopPhoto, 
                      fit: BoxFit.cover,
                      errorBuilder: (context, error, stackTrace) => const Icon(Icons.storefront_rounded, color: Color(0xFF6366F1), size: 28),
                    ),
                  )
                : const Icon(Icons.storefront_rounded, color: Color(0xFF6366F1), size: 28),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(shopName, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
                Text('Order #$orderId', style: TextStyle(fontSize: 12, color: Colors.grey.shade500)),
              ]),
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(color: const Color(0xFFFEF3C7), borderRadius: BorderRadius.circular(99)),
              child: Text(state, style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w900, color: Color(0xFFD97706))),
            ),
          ],
        ),
      ),
    );
  }

  void _showOfferBottomSheet(BuildContext context, dynamic order, String driverId) {
    final orderId = order['OrderID'].toString();
    final basePrice = order['OrderPrice'] ?? '10';
    final TextEditingController priceController = TextEditingController(text: basePrice.toString());

    showModalBottomSheet(
      context: context, isScrollControlled: true, backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(32))),
      builder: (BuildContext sheetContext) {
        return Padding(
          padding: EdgeInsets.only(bottom: MediaQuery.of(sheetContext).viewInsets.bottom + 24, left: 24, right: 24, top: 32),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text('make_offer'.tr, style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w900)),
              const SizedBox(height: 24),
              TextField(
                controller: priceController, keyboardType: TextInputType.number,
                decoration: InputDecoration(labelText: 'delivery_fee'.tr, border: OutlineInputBorder(borderRadius: BorderRadius.circular(16))),
              ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity, height: 60,
                child: ElevatedButton(
                  onPressed: () async {
                    final price = priceController.text;
                    Navigator.pop(sheetContext); // Close bottom sheet
                    
                    // Show a quick loading indicator if needed, but sendOffer is usually fast
                    final result = await ApiService.sendOffer(orderId, driverId, price);
                    
                    if (result['success'] == true) {
                      if (context.mounted) {
                        // Launch the Waiting Zone!
                        final outcome = await Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (context) => WaitingZonePage(
                              orderId: orderId,
                              driverId: driverId,
                              offerPrice: price,
                            ),
                          ),
                        );
                        
                        // Refresh the dashboard immediately when they return
                        _loadDashboard();
                        
                        // If they accepted, automatically go to the Active Order Page
                        if (outcome != null) {
                          if (outcome['status'] == 'success') {
                            if (context.mounted) {
                              Navigator.push(context, MaterialPageRoute(builder: (_) => ActiveOrderPage(orderId: orderId, driverId: driverId)));
                            }
                          } else if (outcome['status'] == 'resend') {
                            if (context.mounted) {
                              // Small delay to ensure the bottom sheet opens smoothly after navigation transition
                              Future.delayed(const Duration(milliseconds: 300), () {
                                if (context.mounted) {
                                  _showOfferBottomSheet(context, order, driverId);
                                }
                              });
                            }
                          }
                        }
                      }
                    } else {
                      if (context.mounted) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(content: Text(result['message'] ?? 'Failed to send offer'), backgroundColor: Colors.red),
                        );
                      }
                    }
                  },
                  style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF6366F1), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16))),
                  child: Text('send_offer_btn'.tr, style: const TextStyle(fontWeight: FontWeight.w900, color: Colors.white)),
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}
