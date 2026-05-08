import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/localization_service.dart';
import 'active_order_page.dart';
import 'package:intl/intl.dart';
import 'package:shimmer/shimmer.dart';
import 'package:cached_network_image/cached_network_image.dart';

class HistoryPage extends StatefulWidget {
  const HistoryPage({super.key});

  @override
  State<HistoryPage> createState() => _HistoryPageState();
}

class _HistoryPageState extends State<HistoryPage> {
  late Future<List<dynamic>> _historyFuture;
  List<dynamic> _allOrders = [];
  List<dynamic> _filteredOrders = [];
  
  // Pagination
  int _currentPage = 0;
  bool _isLoadingMore = false;
  bool _hasMore = true;
  final ScrollController _scrollController = ScrollController();
  
  // Search & Filter state
  final TextEditingController _searchController = TextEditingController();
  bool _isSearching = false;
  DateTime? _selectedDate;

  @override
  void initState() {
    super.initState();
    _loadHistory();
    _searchController.addListener(_applyFilters);
    _scrollController.addListener(() {
      if (_scrollController.position.pixels >= _scrollController.position.maxScrollExtent - 200 && !_isLoadingMore && _hasMore) {
        _loadMore();
      }
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _loadHistory() {
    setState(() {
      _currentPage = 0;
      _hasMore = true;
    });
    _historyFuture = _fetchHistory(page: 0).then((data) {
      setState(() {
        _allOrders = data;
        if (data.isEmpty || data.length < 10) _hasMore = false;
        _applyFilters();
      });
      return data;
    });
  }

  Future<void> _loadMore() async {
    setState(() => _isLoadingMore = true);
    final nextPage = _currentPage + 1;
    final moreData = await _fetchHistory(page: nextPage);
    
    setState(() {
      if (moreData.isEmpty) {
        _hasMore = false;
      } else {
        _currentPage = nextPage;
        _allOrders.addAll(moreData);
        if (moreData.length < 10) _hasMore = false;
      }
      _applyFilters();
      _isLoadingMore = false;
    });
  }

  void _applyFilters() {
    final query = _searchController.text.toLowerCase();
    setState(() {
      _filteredOrders = _allOrders.where((order) {
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

  Future<List<dynamic>> _fetchHistory({int page = 0}) async {
    final driverId = await ApiService.getDriverId();
    if (driverId == null) return [];
    final allMyOrders = await ApiService.getCurrentOrders(driverId, page: page);
    
    // Exclude active/doing orders for history
    const activeStates = {'Doing', 'Order pickup', 'come to take it', 'order processed'};
    return allMyOrders
        .where((o) => !activeStates.contains(o['OrderState']))
        .toList();
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
        title: _isSearching 
          ? TextField(
              controller: _searchController,
              autofocus: true,
              decoration: InputDecoration(
                hintText: 'history_search_hint'.tr,
                border: InputBorder.none,
                hintStyle: const TextStyle(color: Colors.grey, fontSize: 16),
              ),
              style: const TextStyle(color: Colors.black, fontSize: 16),
            )
          : Text(
              'order_history'.tr,
              style: const TextStyle(color: Colors.black, fontSize: 24, fontWeight: FontWeight.w800, letterSpacing: -0.5),
            ),
        actions: [
          IconButton(
            icon: Icon(_isSearching ? Icons.close : Icons.search_rounded, color: Colors.black),
            onPressed: () {
              setState(() {
                if (_isSearching) _searchController.clear();
                _isSearching = !_isSearching;
              });
            },
          ),
          IconButton(
            icon: Icon(
              _selectedDate == null ? Icons.calendar_today_rounded : Icons.event_available_rounded, 
              color: _selectedDate == null ? Colors.black : const Color(0xFF6366F1)
            ),
            onPressed: () => _selectDate(context),
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: Column(
        children: [
          if (_selectedDate != null) _buildFilterStatus(),
          Expanded(
            child: FutureBuilder<List<dynamic>>(
              future: _historyFuture,
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting && _allOrders.isEmpty) {
                  return _buildShimmerLoading();
                }

                if (_filteredOrders.isEmpty) {
                  return _buildEmptyState();
                }

                return RefreshIndicator(
                  onRefresh: () async => _loadHistory(),
                  color: const Color(0xFF6366F1),
                  child: ListView.builder(
                    controller: _scrollController,
                    physics: const AlwaysScrollableScrollPhysics(parent: BouncingScrollPhysics()),
                    padding: const EdgeInsets.all(16),
                    itemCount: _filteredOrders.length + (_isLoadingMore ? 1 : 0),
                    itemBuilder: (context, index) {
                      if (index == _filteredOrders.length) {
                        return Padding(
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          child: _buildSingleShimmer(),
                        );
                      }
                      return _buildHistoryOrderCard(_filteredOrders[index]);
                    },
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
    }); // end ListenableBuilder
  }

  Widget _buildShimmerLoading() {
    return ListView.builder(
      physics: const NeverScrollableScrollPhysics(),
      padding: const EdgeInsets.all(16),
      itemCount: 6,
      itemBuilder: (context, index) {
        return Padding(
          padding: const EdgeInsets.only(bottom: 16),
          child: _buildSingleShimmer(),
        );
      },
    );
  }

  Widget _buildSingleShimmer() {
    return Shimmer.fromColors(
      baseColor: Colors.grey.shade200,
      highlightColor: Colors.grey.shade100,
      child: Container(
        height: 100,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(24),
        ),
      ),
    );
  }

  Widget _buildFilterStatus() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
      child: Row(
        children: [
          Chip(
            label: Text(
              'Date: ${DateFormat('MMM dd, yyyy').format(_selectedDate!)}',
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

  Widget _buildEmptyState() {
    final isFiltered = _isSearching || _selectedDate != null;
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(isFiltered ? Icons.search_off_rounded : Icons.receipt_long_rounded, size: 64, color: Colors.grey.shade200),
          const SizedBox(height: 20),
          Text(
            isFiltered ? 'no_matches'.tr : 'no_history'.tr,
            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: Color(0xFF1E293B)),
          ),
          const SizedBox(height: 8),
          Text(
            isFiltered ? 'try_clear_filters'.tr : 'history_empty_desc'.tr,
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 14, color: Colors.grey.shade500),
          ),
          if (isFiltered)
            TextButton(
              onPressed: () => setState(() { _searchController.clear(); _selectedDate = null; _isSearching = false; _applyFilters(); }),
              child: Text('clear_filters'.tr, style: const TextStyle(color: Color(0xFF6366F1), fontWeight: FontWeight.w700)),
            )
        ],
      ),
    );
  }

  Widget _buildHistoryOrderCard(dynamic order) {
    final orderId = order['OrderID']?.toString() ?? '';
    final shopName = order['DestinationName'] ?? order['ShopName'] ?? 'Shop';
    final state = order['OrderState'] ?? 'Unknown';
    final price = order['OrderPrice']?.toString() ?? '0';
    final date = order['CreatedAtOrders']?.toString() ?? '';
    
    // Formatting the date nicely
    String formattedDate = date;
    if (date.isNotEmpty) {
      try {
        final dt = DateTime.parse(date);
        formattedDate = DateFormat('MMM dd, hh:mm a').format(dt);
      } catch (_) {}
    }

    Color stateColor;
    String stateText;
    final stateStr = state.toString().toLowerCase();
    
    if (stateStr == 'finish' || stateStr == 'rated' || stateStr == 'done') {
      stateColor = const Color(0xFF10B981);
      stateText = 'delivered'.tr.toUpperCase();
    } else if (stateStr == 'cancelled') {
      stateColor = const Color(0xFFEF4444);
      stateText = 'cancelled'.tr.toUpperCase();
    } else if (stateStr == 'returned' || stateStr == 'return') {
      stateColor = const Color(0xFFF59E0B);
      stateText = 'returned'.tr.toUpperCase();
    } else {
      stateColor = const Color(0xFF6366F1); // Indigo
      stateText = state.toString().toUpperCase();
    }

    final shopPhoto = order['DestnationPhoto'] ?? order['ShopLogo'] ?? '';

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 10, offset: const Offset(0, 4))],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(24),
          onTap: () async {
            final driverId = await ApiService.getDriverId();
            if (driverId != null && mounted) {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => ActiveOrderPage(
                    orderId: orderId,
                    driverId: driverId,
                    isClosed: true,
                  ),
                ),
              );
            }
          },
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Row(
              children: [
                Container(
                  width: 48,
                  height: 48,
                  decoration: BoxDecoration(
                    color: stateColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: shopPhoto.isNotEmpty 
                    ? ClipRRect(
                        borderRadius: BorderRadius.circular(14),
                        child: Image.network(
                          shopPhoto,
                          fit: BoxFit.cover,
                          errorBuilder: (context, error, stackTrace) => Icon(
                            stateStr == 'cancelled' ? Icons.close_rounded : Icons.storefront_rounded,
                            color: stateColor,
                            size: 20,
                          ),
                        ),
                      )
                    : Icon(
                        stateStr == 'cancelled' ? Icons.close_rounded : Icons.storefront_rounded,
                        color: stateColor,
                        size: 20,
                      ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(shopName, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: Color(0xFF1E293B))),
                      const SizedBox(height: 4),
                      Text('Order #$orderId', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Colors.grey.shade400)),
                      const SizedBox(height: 4),
                      Text(formattedDate, style: TextStyle(fontSize: 11, color: Colors.grey.shade400)),
                    ],
                  ),
                ),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text('$price MAD', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w900, color: Color(0xFF1E293B))),
                    const SizedBox(height: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: stateColor.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        stateText,
                        style: TextStyle(fontSize: 10, fontWeight: FontWeight.w900, color: stateColor),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
