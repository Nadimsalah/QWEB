import 'dart:async';
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/localization_service.dart';

import '../dashboard_page.dart';
import 'active_order_page.dart';

class WaitingZonePage extends StatefulWidget {
  final String orderId;
  final String driverId;
  final String offerPrice;
  final int? offerTimestamp;

  const WaitingZonePage({
    super.key,
    required this.orderId,
    required this.driverId,
    required this.offerPrice,
    this.offerTimestamp,
  });

  @override
  State<WaitingZonePage> createState() => _WaitingZonePageState();
}

// Enum for the different states of the waiting zone
enum _OfferStatus { waiting, accepted, lost, timeout, cancelled }

class _WaitingZonePageState extends State<WaitingZonePage>
    with TickerProviderStateMixin {
  late Timer _timer;
  late Timer _pollingTimer;
  int _secondsRemaining = 120; // 2 minutes
  _OfferStatus _status = _OfferStatus.waiting;

  // Pulse animation for waiting state
  late AnimationController _pulseController;
  late Animation<double> _pulseAnimation;

  // Result animation (scale-in for accepted/lost icons)
  late AnimationController _resultController;
  late Animation<double> _resultScale;
  late Animation<double> _resultOpacity;

  @override
  void initState() {
    super.initState();

    if (widget.offerTimestamp != null) {
      final now = DateTime.now().millisecondsSinceEpoch;
      final elapsed = ((now - widget.offerTimestamp!) / 1000).floor();
      _secondsRemaining = (120 - elapsed).clamp(0, 120);
    }

    _pulseController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 2),
    )..repeat(reverse: true);

    _pulseAnimation = Tween<double>(begin: 1.0, end: 1.18).animate(
      CurvedAnimation(parent: _pulseController, curve: Curves.easeInOut),
    );

    _resultController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 600),
    );

    _resultScale = Tween<double>(begin: 0.5, end: 1.0).animate(
      CurvedAnimation(parent: _resultController, curve: Curves.elasticOut),
    );
    _resultOpacity = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(parent: _resultController, curve: Curves.easeIn),
    );

    _startTimers();
  }

  void _startTimers() {
    // Countdown timer
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (!mounted) return;
      if (_status != _OfferStatus.waiting) {
        timer.cancel();
        return;
      }
      setState(() {
        if (_secondsRemaining > 0) {
          _secondsRemaining--;
        } else {
          _timer.cancel();
          _pollingTimer.cancel();
          _showResult(_OfferStatus.timeout);
        }
      });
    });

    // Poll order status every 3 seconds
    _pollingTimer = Timer.periodic(const Duration(seconds: 3), (timer) async {
      if (_status != _OfferStatus.waiting) {
        timer.cancel();
        return;
      }
      
      // 1. Fast path: Check Firebase for instantaneous cancellation state
      try {
        final fbRes = await http.get(Uri.parse('https://jibler-37339-default-rtdb.firebaseio.com/Offers/${widget.orderId}.json'));
        if (fbRes.statusCode == 200) {
          final fbData = jsonDecode(fbRes.body);
          if (fbData is Map && fbData['OrderStatus'] == 'CANCELLED') {
            _timer.cancel();
            _pollingTimer.cancel();
            _showResult(_OfferStatus.cancelled);
            return;
          }
        }
      } catch (e) {
        debugPrint('Firebase poll error: $e');
      }

      // 2. Standard path: Poll MySQL API for other states
      final status = await ApiService.getOrderStatus(widget.orderId);
      if (status != null && mounted && _status == _OfferStatus.waiting) {
        final orderState = status['OrderState']?.toString().toUpperCase().trim() ?? '';
        final delvryId = status['DelvryId']?.toString() ?? '';

        if (orderState == 'CANCELLED') {
          _timer.cancel();
          _pollingTimer.cancel();
          _showResult(_OfferStatus.cancelled);
        } else if (orderState == 'DOING' ||
            orderState == 'DONE' ||
            orderState == 'FINISH' ||
            ( (orderState == 'WAITING' || orderState == 'PLACED') && delvryId == widget.driverId ) ) {
          _timer.cancel();
          _pollingTimer.cancel();
          if (delvryId == widget.driverId) {
            _showResult(_OfferStatus.accepted);
          } else {
            _showResult(_OfferStatus.lost);
          }
        }
      }
    });
  }

  void _closePage({String status = 'cancelled'}) {
    if (Navigator.canPop(context)) {
      Navigator.of(context).pop({'status': status});
    } else {
      if (status == 'success') {
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(builder: (_) => ActiveOrderPage(orderId: widget.orderId, driverId: widget.driverId)),
        );
      } else {
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(builder: (_) => const DashboardPage()),
        );
      }
    }
  }

  void _showResult(_OfferStatus newStatus) {
    if (!mounted) return;
    _pulseController.stop();
    setState(() => _status = newStatus);
    _resultController.forward();
    
    // Clear active offer from preferences
    ApiService.clearActiveOffer();

    // Auto-pop after 3 seconds for lost/cancelled (BUT NOT timeout, so they can click resend)
    if (newStatus == _OfferStatus.lost || newStatus == _OfferStatus.cancelled) {
      Future.delayed(const Duration(seconds: 3), () {
        if (mounted) _closePage(status: newStatus.name);
      });
    }
  }



  @override
  void dispose() {
    _timer.cancel();
    _pollingTimer.cancel();
    _pulseController.dispose();
    _resultController.dispose();
    super.dispose();
  }

  String get _formattedTime {
    final minutes = (_secondsRemaining / 60).floor();
    final seconds = _secondsRemaining % 60;
    return '${minutes.toString().padLeft(2, '0')}:${seconds.toString().padLeft(2, '0')}';
  }

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: localizationService,
      builder: (context, child) {
    return PopScope(
      canPop: false,
      child: Scaffold(
        backgroundColor: Colors.white,
        body: SafeArea(
          child: AnimatedSwitcher(
            duration: const Duration(milliseconds: 400),
            child: _status == _OfferStatus.waiting
                ? _buildWaitingUI()
                : _buildResultUI(),
          ),
        ),
      ),
    );
    }); // end ListenableBuilder
  }

  Widget _buildWaitingUI() {
    return SizedBox(
      width: double.infinity,
      child: Column(
        key: const ValueKey('waiting'),
        mainAxisAlignment: MainAxisAlignment.center,
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          const Spacer(flex: 2),

        // Pulsing ring animation
        ScaleTransition(
          scale: _pulseAnimation,
          child: Container(
            width: 140,
            height: 140,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: const Color(0xFF6366F1).withOpacity(0.08),
              border: Border.all(color: const Color(0xFF6366F1).withOpacity(0.25), width: 2),
            ),
            child: const Center(
              child: Icon(
                Icons.access_time_filled_rounded,
                size: 56,
                color: Color(0xFF6366F1),
              ),
            ),
          ),
        ),

        const SizedBox(height: 32),

        Text(
          'waiting_for_response'.tr,
          style: const TextStyle(fontSize: 28, fontWeight: FontWeight.w800, color: Colors.black),
        ),
        const SizedBox(height: 12),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 40),
          child: Text(
            '${'offer_sent_prefix'.tr}${widget.offerPrice}${'offer_sent_suffix'.tr}',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 16, color: Colors.grey.shade600, height: 1.5),
          ),
        ),

        const SizedBox(height: 32),

        // Countdown badge
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 16),
          decoration: BoxDecoration(
            color: Colors.grey.shade100,
            borderRadius: BorderRadius.circular(30),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.timer_outlined, size: 24, color: Colors.black54),
              const SizedBox(width: 12),
              Text(
                _formattedTime,
                style: const TextStyle(
                  fontSize: 28,
                  fontWeight: FontWeight.w700,
                  color: Colors.black,
                  fontFeatures: [FontFeature.tabularFigures()],
                ),
              ),
            ],
          ),
        ),

        const Spacer(flex: 3),
        ],
      ),
    );
  }

  Widget _buildResultUI() {
    late IconData icon;
    late Color iconColor;
    late Color bgColor;
    late String title;
    late String subtitle;

    switch (_status) {
      case _OfferStatus.accepted:
        icon = Icons.check_circle_rounded;
        iconColor = const Color(0xFF22C55E);
        bgColor = const Color(0xFF22C55E).withOpacity(0.08);
        title = 'offer_accepted'.tr;
        subtitle = 'offer_accepted_desc'.tr;
        break;
      case _OfferStatus.lost:
        icon = Icons.person_off_rounded;
        iconColor = const Color(0xFFF59E0B);
        bgColor = const Color(0xFFF59E0B).withOpacity(0.08);
        title = 'another_driver'.tr;
        subtitle = 'another_driver_desc'.tr;
        break;
      case _OfferStatus.cancelled:
        icon = Icons.cancel_rounded;
        iconColor = Colors.red.shade400;
        bgColor = Colors.red.withOpacity(0.06);
        title = 'order_cancelled'.tr;
        subtitle = 'order_cancelled_desc'.tr;
        break;
      case _OfferStatus.timeout:
        icon = Icons.hourglass_disabled_rounded;
        iconColor = Colors.grey.shade500;
        bgColor = Colors.grey.withOpacity(0.08);
        title = 'offer_expired'.tr;
        subtitle = 'offer_expired_desc'.tr;
        break;
      default:
        icon = Icons.info_outline_rounded;
        iconColor = Colors.grey;
        bgColor = Colors.grey.shade100;
        title = '';
        subtitle = '';
    }

    return FadeTransition(
      key: ValueKey(_status.name),
      opacity: _resultOpacity,
      child: SizedBox(
        width: double.infinity,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            const Spacer(),

          // Icon circle with scale animation
          ScaleTransition(
            scale: _resultScale,
            child: Container(
              width: 130,
              height: 130,
              decoration: BoxDecoration(shape: BoxShape.circle, color: bgColor),
              child: Icon(icon, size: 64, color: iconColor),
            ),
          ),

          const SizedBox(height: 40),

          Text(
            title,
            style: const TextStyle(fontSize: 26, fontWeight: FontWeight.w800, color: Colors.black),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 14),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 36),
            child: Text(
              subtitle,
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 15, color: Colors.grey.shade500, height: 1.6),
            ),
          ),

          if (_status != _OfferStatus.accepted) ...[
            const SizedBox(height: 32),
            Text(
              'going_back'.tr,
              style: TextStyle(fontSize: 13, color: Colors.grey.shade400),
            ),
          ],

          const Spacer(),

          if (_status != _OfferStatus.accepted && _status != _OfferStatus.timeout)
            Padding(
              padding: const EdgeInsets.fromLTRB(24, 0, 24, 32),
              child: SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: () => _closePage(status: _status.name),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF6366F1),
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                    elevation: 0,
                  ),
                  child: Text(
                    'back_to_orders'.tr,
                    style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700, color: Colors.white),
                  ),
                ),
              ),
            ),

          if (_status == _OfferStatus.timeout)
            Padding(
              padding: const EdgeInsets.fromLTRB(24, 0, 24, 32),
              child: Column(
                children: [
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: () => _closePage(status: 'resend'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF6366F1),
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                        elevation: 0,
                      ),
                      child: Text(
                        'resend_offer'.tr,
                        style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w900, color: Colors.white),
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  SizedBox(
                    width: double.infinity,
                    child: TextButton(
                      onPressed: () => _closePage(status: _status.name),
                      style: TextButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        foregroundColor: Colors.grey.shade600,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                      ),
                      child: Text(
                        'back_to_orders'.tr,
                        style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700),
                      ),
                    ),
                  ),
                ],
              ),
            ),

          if (_status == _OfferStatus.accepted)
            Padding(
              padding: const EdgeInsets.fromLTRB(24, 0, 24, 32),
              child: SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: () => _closePage(status: 'success'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF22C55E),
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                    elevation: 0,
                  ),
                  child: Text(
                    'view_active_order'.tr,
                    style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700, color: Colors.white),
                  ),
                ),
              ),
            ),
        ],
      ),
      ),
    );
  }
}
