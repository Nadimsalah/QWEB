import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../core/api_client.dart';
import '../../core/location_service.dart';
import '../orders/models/order_model.dart';
import '../orders/live_orders_list.dart';
import 'map_view.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  String _driverId = '';
  bool _isOnline = false;
  bool _isLoadingOrders = false;
  List<OrderModel> _liveOrders = [];
  LocationService? _locationService;

  @override
  void initState() {
    super.initState();
    _loadDriverData();
  }

  Future<void> _loadDriverData() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      _driverId = prefs.getString('driver_id') ?? '';
    });
    
    // Initialize location service
    _locationService = LocationService(context.read<ApiClient>());
    final hasPermission = await _locationService!.initialize();
    
    if (_isOnline && hasPermission) {
      _locationService!.startTracking(_driverId);
    }

    _fetchLiveOrders();
  }

  Future<void> _fetchLiveOrders() async {
    if (!_isOnline) return;
    setState(() => _isLoadingOrders = true);
    try {
      // In a real app, call GetDriverNearOrders.php or getDriveLiveOrders.php using API client here
      // final response = await context.read<ApiClient>().post('GetDriverNearOrders.php', {'DriverID': _driverId});
      // Parse response into _liveOrders
      
      // Dummy data for now
      await Future.delayed(const Duration(seconds: 1));
      setState(() {
        _liveOrders = [
          OrderModel(
            id: '101', 
            restaurantName: 'Pizza Hut', 
            pickupAddress: 'Downtown Blvd 12', 
            dropoffAddress: 'Main St 45', 
            price: 15.50, 
            distanceKm: 2.3, 
            status: 'pending'
          ),
        ];
      });
    } finally {
      setState(() => _isLoadingOrders = false);
    }
  }

  Future<void> _toggleOnlineStatus() async {
    setState(() {
      _isOnline = !_isOnline;
      if (!_isOnline) {
        _liveOrders.clear();
        _locationService?.stopTracking();
      } else {
        _locationService?.startTracking(_driverId);
        _fetchLiveOrders();
      }
    });
    // In a real app, update server about driver availability here
  }

  void _logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.clear();
    if (mounted) {
      Navigator.pushReplacementNamed(context, '/login');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Driver Dashboard'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: _logout,
          ),
        ],
      ),
      body: Column(
        children: [
          Container(
            padding: const EdgeInsets.all(16.0),
            color: _isOnline ? Colors.green.shade100 : Colors.red.shade100,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  _isOnline ? 'You are ONLINE' : 'You are OFFLINE',
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
                Switch(
                  value: _isOnline,
                  onChanged: (val) => _toggleOnlineStatus(),
                ),
              ],
            ),
          ),
          Expanded(
            child: Stack(
              children: [
                const DriverMapView(),
                if (_isOnline)
                  Align(
                    alignment: Alignment.bottomCenter,
                    child: SizedBox(
                      height: 250, // Height for the orders list
                      child: LiveOrdersList(
                        orders: _liveOrders,
                        isLoading: _isLoadingOrders,
                        onAccept: (order) {
                          // Handle accept (call Take.php)
                          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Accepted order \${order.id}')));
                        },
                        onIgnore: (order) {
                          // Handle ignore
                          setState(() => _liveOrders.remove(order));
                        },
                      ),
                    ),
                  ),
                if (!_isOnline)
                  Container(
                    color: Colors.black54,
                    child: const Center(
                      child: Text(
                        'Go online to receive orders',
                        style: TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ),
              ],
            ),
          ),
        ],
      ),
      floatingActionButton: _isOnline ? FloatingActionButton.extended(
        onPressed: _fetchLiveOrders,
        icon: const Icon(Icons.refresh),
        label: const Text('Refresh Orders'),
      ) : null,
    );
  }
}
