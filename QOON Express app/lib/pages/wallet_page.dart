import 'package:flutter/material.dart';
import 'dart:async';
import '../services/api_service.dart';
import '../services/localization_service.dart';
import 'package:intl/intl.dart';
import 'package:shimmer/shimmer.dart';

class WalletPage extends StatefulWidget {
  const WalletPage({super.key});

  @override
  State<WalletPage> createState() => _WalletPageState();
}

class _WalletPageState extends State<WalletPage> {
  late Future<Map<String, dynamic>?> _walletFuture;
  late Future<List<dynamic>> _transactionsFuture;
  List<dynamic> _allTransactions = [];
  List<dynamic> _filteredTransactions = [];
  
  // Pagination
  int _currentPage = 0;
  bool _isLoadingMore = false;
  bool _hasMore = true;
  final ScrollController _scrollController = ScrollController();
  
  final TextEditingController _searchController = TextEditingController();
  bool _isSearching = false;
  DateTime? _selectedDate;

  Timer? _refreshTimer;

  @override
  void initState() {
    super.initState();
    _loadData();
    _searchController.addListener(_applyFilters);
    _scrollController.addListener(() {
      if (_scrollController.position.pixels >= _scrollController.position.maxScrollExtent - 200 && !_isLoadingMore && _hasMore) {
        _loadMoreTransactions();
      }
    });
    
    // High-frequency polling for real-time balance updates
    _refreshTimer = Timer.periodic(const Duration(seconds: 2), (timer) {
      if (mounted) _silentRefresh();
    });
  }

  @override
  void dispose() {
    _refreshTimer?.cancel();
    _searchController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _applyFilters() {
    final query = _searchController.text.toLowerCase();
    setState(() {
      _filteredTransactions = _allTransactions.where((tx) {
        // Search filter
        final shopName = (tx['ShopName'] ?? '').toString().toLowerCase();
        final destName = (tx['DestinationName'] ?? '').toString().toLowerCase();
        final shopId = (tx['ShopID'] ?? '').toString().toLowerCase();
        final orderId = (tx['OrderID'] ?? '').toString().toLowerCase();
        
        final matchesSearch = query.isEmpty || 
                             shopName.contains(query) || 
                             destName.contains(query) || 
                             shopId.contains(query) || 
                             orderId.contains(query);

        // Date filter
        bool matchesDate = true;
        if (_selectedDate != null) {
          final txDateStr = tx['CreatedAtOrders']?.toString() ?? '';
          try {
            // Expecting format like "2026-05-03 20:26:59"
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

  void _loadData() {
    _walletFuture = _fetchWallet();
    setState(() {
      _currentPage = 0;
      _hasMore = true;
    });
    _transactionsFuture = _fetchTransactions(page: 0).then((txs) {
      if (mounted) {
        setState(() {
          _allTransactions = txs;
          if (txs.isEmpty || txs.length < 10) _hasMore = false;
          _applyFilters();
        });
      }
      return txs;
    });
  }

  void _silentRefresh() {
    setState(() {
      _walletFuture = _fetchWallet();
    });
    
    // Only silently refresh transactions if we are on the first page,
    // so we don't ruin the driver's infinite scroll position.
    if (_currentPage == 0) {
      _transactionsFuture = _fetchTransactions(page: 0).then((txs) {
        if (mounted) {
          setState(() {
            _allTransactions = txs;
            if (txs.isEmpty || txs.length < 10) _hasMore = false;
            _applyFilters();
          });
        }
        return txs;
      });
    }
  }

  Future<void> _loadMoreTransactions() async {
    setState(() => _isLoadingMore = true);
    final nextPage = _currentPage + 1;
    final moreData = await _fetchTransactions(page: nextPage);
    
    if (mounted) {
      setState(() {
        if (moreData.isEmpty) {
          _hasMore = false;
        } else {
          _currentPage = nextPage;
          _allTransactions.addAll(moreData);
          if (moreData.length < 10) _hasMore = false;
        }
        _applyFilters();
        _isLoadingMore = false;
      });
    }
  }

  Future<Map<String, dynamic>?> _fetchWallet() async {
    final driverId = await ApiService.getDriverId() ?? '1';
    return await ApiService.getDriverStats(driverId);
  }

  Future<List<dynamic>> _fetchTransactions({int page = 0}) async {
    final driverId = await ApiService.getDriverId() ?? '1';
    return await ApiService.getTransactions(driverId, page: page);
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
            textButtonTheme: TextButtonThemeData(
              style: TextButton.styleFrom(foregroundColor: const Color(0xFF6366F1)),
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
                hintText: 'search_hint'.tr,
                border: InputBorder.none,
                hintStyle: const TextStyle(color: Colors.grey, fontSize: 16),
              ),
              style: const TextStyle(color: Colors.black, fontSize: 16),
            )
          : Text(
              'my_wallet'.tr,
              style: const TextStyle(
                color: Colors.black,
                fontSize: 24,
                fontWeight: FontWeight.w900,
                letterSpacing: -0.5,
              ),
            ),
        actions: [
          IconButton(
            icon: Icon(_isSearching ? Icons.close : Icons.search_rounded, color: Colors.black),
            onPressed: () {
              setState(() {
                if (_isSearching) {
                  _searchController.clear();
                }
                _isSearching = !_isSearching;
              });
            },
          ),
          IconButton(
            icon: Icon(
              _selectedDate == null ? Icons.calendar_month_rounded : Icons.event_available_rounded, 
              color: _selectedDate == null ? Colors.black : const Color(0xFF6366F1)
            ),
            onPressed: () => _selectDate(context),
          ),
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: Colors.black),
            onPressed: () {
              setState(() {
                _loadData();
              });
            },
          ),
        ],
      ),
      body: CustomScrollView(
        controller: _scrollController,
        physics: const BouncingScrollPhysics(),
        slivers: [
          // Finance Summary
          SliverToBoxAdapter(
            child: FutureBuilder<Map<String, dynamic>?>(
              future: _walletFuture,
              builder: (context, snapshot) {
                if (snapshot.hasData && snapshot.data != null) {
                  return Padding(
                    padding: const EdgeInsets.fromLTRB(20, 16, 20, 0),
                    child: Column(
                      children: [
                        _buildMainBalance(snapshot.data!),
                        const SizedBox(height: 16),
                        _buildStatsGrid(snapshot.data!),
                      ],
                    ),
                  );
                }
                return _buildWalletShimmer();
              },
            ),
          ),

          // Filters Status
          if (_selectedDate != null)
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(20, 16, 20, 0),
                child: Row(
                  children: [
                    Chip(
                      label: Text(
                        'Date: ${DateFormat('MMM dd, yyyy').format(_selectedDate!)}',
                        style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600),
                      ),
                      backgroundColor: const Color(0xFF6366F1),
                      deleteIcon: const Icon(Icons.close, size: 14, color: Colors.white),
                      onDeleted: () {
                        setState(() {
                          _selectedDate = null;
                          _applyFilters();
                        });
                      },
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                    ),
                  ],
                ),
              ),
            ),

          // Transactions Header
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(24, 32, 24, 16),
            sliver: SliverToBoxAdapter(
              child: Text(
                'recent_transactions'.tr,
                style: const TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.w800,
                  color: Color(0xFF1E293B),
                ),
              ),
            ),
          ),

          // Transactions List
          FutureBuilder<List<dynamic>>(
            future: _transactionsFuture,
            builder: (context, snapshot) {
              if (snapshot.connectionState == ConnectionState.waiting && _allTransactions.isEmpty) {
                return _buildTransactionsShimmer();
              }

              if (_filteredTransactions.isEmpty) {
                return SliverToBoxAdapter(
                  child: Center(
                    child: Padding(
                      padding: const EdgeInsets.all(64.0),
                      child: Column(
                        children: [
                          Icon(Icons.search_off_rounded, size: 64, color: Colors.grey.shade300),
                          const SizedBox(height: 16),
                          Text(
                            (_isSearching || _selectedDate != null) ? 'no_matches'.tr : 'no_transactions'.tr,
                            style: TextStyle(color: Colors.grey.shade500, fontSize: 16, fontWeight: FontWeight.w500),
                          ),
                          if (_isSearching || _selectedDate != null)
                            TextButton(
                              onPressed: () {
                                setState(() {
                                  _searchController.clear();
                                  _selectedDate = null;
                                  _isSearching = false;
                                  _applyFilters();
                                });
                              },
                              child: Text('clear_filters'.tr, style: const TextStyle(color: Color(0xFF6366F1))),
                            ),
                        ],
                      ),
                    ),
                  ),
                );
              }

              return SliverPadding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                sliver: SliverList(
                  delegate: SliverChildBuilderDelegate(
                    (context, index) {
                      if (index == _filteredTransactions.length) {
                        return Padding(
                          padding: const EdgeInsets.only(bottom: 12),
                          child: _buildSingleTransactionShimmer(),
                        );
                      }
                      final tx = _filteredTransactions[index];
                      return _buildTransactionCard(tx);
                    },
                    childCount: _filteredTransactions.length + (_isLoadingMore ? 1 : 0),
                  ),
                ),
              );
            },
          ),
          const SliverToBoxAdapter(child: SizedBox(height: 100)),
        ],
      ),
    );
    }); // end ListenableBuilder
  }

  Widget _buildMainBalance(Map<String, dynamic> stats) {
    final double walletBalance = double.tryParse(stats['walletBalance']?.toString() ?? '0') ?? 0;
    final double cashCollected = double.tryParse(stats['cashCollected']?.toString() ?? '0') ?? 0;
    final double onlineEarnings = double.tryParse(stats['onlineEarnings']?.toString() ?? '0') ?? 0;
    final double limit = double.tryParse(stats['cashLimit']?.toString() ?? '350') ?? 350.0;
    
    final double netDebt = cashCollected - walletBalance - onlineEarnings;
    final bool isDanger = netDebt >= limit;

    return Container(
      width: double.infinity,
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: isDanger 
              ? [const Color(0xFFEF4444), const Color(0xFFB91C1C)] // Red gradient for danger
              : [const Color(0xFF6366F1), const Color(0xFF4F46E5)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(28),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF6366F1).withOpacity(0.3),
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Stack(
        children: [
          Positioned(
            right: -30,
            top: -30,
            child: CircleAvatar(
              radius: 80,
              backgroundColor: Colors.white.withOpacity(0.05),
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(24.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'available_balance'.tr,
                  style: const TextStyle(color: Colors.white70, fontSize: 14, fontWeight: FontWeight.w500),
                ),
                const SizedBox(height: 8),
                Row(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(
                      walletBalance.toStringAsFixed(2),
                      style: const TextStyle(color: Colors.white, fontSize: 36, fontWeight: FontWeight.w900),
                    ),
                    const Padding(
                      padding: EdgeInsets.only(bottom: 6, left: 8),
                      child: Text('MAD', style: TextStyle(color: Colors.white60, fontSize: 16, fontWeight: FontWeight.w700)),
                    ),
                  ],
                ),
                const SizedBox(height: 24),
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Row(
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('cash_debt'.tr, style: const TextStyle(color: Colors.white70, fontSize: 12)),
                          const SizedBox(height: 4),
                          Text(
                            '${cashCollected.toStringAsFixed(0)} MAD',
                            style: TextStyle(
                              color: isDanger ? Colors.orangeAccent : Colors.white, 
                              fontSize: 18, 
                              fontWeight: FontWeight.w800
                            ),
                          ),
                        ],
                      ),
                      const Spacer(),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Text('limit'.tr, style: const TextStyle(color: Colors.white70, fontSize: 12)),
                          const SizedBox(height: 4),
                          Text('${limit.toStringAsFixed(0)} MAD', style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800)),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatsGrid(Map<String, dynamic> stats) {
    final totalTrips = stats['totalTrips']?.toString() ?? stats['todayTrips']?.toString() ?? '0';
    final rating = stats['driverRating'] != null ? double.tryParse(stats['driverRating'].toString())?.toStringAsFixed(1) ?? '5.0' : '5.0';
    final earnings = stats['totalEarnings']?.toString() ?? '0';

    return Row(
      children: [
              _buildStatCard(totalTrips, 'total_trips'.tr, Icons.directions_bike_rounded, const Color(0xFFF0F9FF), const Color(0xFF0EA5E9)),
        const SizedBox(width: 12),
        _buildStatCard(rating, 'rating'.tr, Icons.star_rounded, const Color(0xFFFFFBEB), const Color(0xFFF59E0B)),
        const SizedBox(width: 12),
        _buildStatCard(earnings, 'total'.tr, Icons.account_balance_wallet_rounded, const Color(0xFFF0FDF4), const Color(0xFF10B981)),
      ],
    );
  }

  Widget _buildStatCard(String value, String label, IconData icon, Color bgColor, Color iconColor) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 16),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: const Color(0xFFE2E8F0)),
        ),
        child: Column(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(color: bgColor, shape: BoxShape.circle),
              child: Icon(icon, color: iconColor, size: 20),
            ),
            const SizedBox(height: 8),
            Text(value, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: Color(0xFF1E293B))),
            Text(label, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: Color(0xFF64748B))),
          ],
        ),
      ),
    );
  }

  Widget _buildTransactionCard(dynamic tx) {
    final earnings = double.tryParse(tx['Earnings']?.toString() ?? '0') ?? 0;
    final cashCollected = double.tryParse(tx['CashCollected']?.toString() ?? '0') ?? 0;
    final showCash = tx['ShopRecive'] == 'NO' && cashCollected > 0;
    final shopName = tx['ShopName'] ?? tx['DestinationName'] ?? 'Order';
    final date = tx['CreatedAtOrders'] ?? 'Recent';
    final shopId = tx['ShopID']?.toString();

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: const Color(0xFFF1F5F9),
              borderRadius: BorderRadius.circular(14),
            ),
            child: const Icon(Icons.receipt_rounded, color: Color(0xFF64748B)),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  shopName,
                  style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700, color: Color(0xFF1E293B)),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 4),
                Row(
                  children: [
                    if (shopId != null) ...[
                      Text('ID: #$shopId', style: TextStyle(fontSize: 12, color: Colors.blue.shade700, fontWeight: FontWeight.w600)),
                      const SizedBox(width: 8),
                    ],
                    Text(date, style: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8))),
                  ],
                ),
              ],
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              if (earnings > 0)
                Text(
                  '+${earnings.toStringAsFixed(0)} MAD',
                  style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w900, color: Color(0xFF10B981)),
                ),
              if (showCash)
                Text(
                  '-${cashCollected.toStringAsFixed(0)} MAD',
                  style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: Color(0xFFEF4444)),
                ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildWalletShimmer() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 0),
      child: Column(
        children: [
          Shimmer.fromColors(
            baseColor: Colors.grey.shade200,
            highlightColor: Colors.grey.shade100,
            child: Container(
              height: 140,
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(30)),
            ),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: Shimmer.fromColors(
                  baseColor: Colors.grey.shade200,
                  highlightColor: Colors.grey.shade100,
                  child: Container(
                    height: 100,
                    decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(24)),
                  ),
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Shimmer.fromColors(
                  baseColor: Colors.grey.shade200,
                  highlightColor: Colors.grey.shade100,
                  child: Container(
                    height: 100,
                    decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(24)),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildTransactionsShimmer() {
    return SliverPadding(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      sliver: SliverList(
        delegate: SliverChildBuilderDelegate(
          (context, index) {
            return Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: _buildSingleTransactionShimmer(),
            );
          },
          childCount: 5,
        ),
      ),
    );
  }

  Widget _buildSingleTransactionShimmer() {
    return Shimmer.fromColors(
      baseColor: Colors.grey.shade200,
      highlightColor: Colors.grey.shade100,
      child: Container(
        height: 80,
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(20)),
      ),
    );
  }
}


