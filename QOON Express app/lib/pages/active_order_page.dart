import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:cached_network_image/cached_network_image.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:image_picker/image_picker.dart';
import 'package:geolocator/geolocator.dart';
import 'package:internet_connection_checker_plus/internet_connection_checker_plus.dart';
import '../services/api_service.dart';
import '../services/localization_service.dart';

class ActiveOrderPage extends StatefulWidget {
  final String orderId;
  final String driverId;
  final bool isClosed;

  const ActiveOrderPage({
    super.key,
    required this.orderId,
    required this.driverId,
    this.isClosed = false,
  });

  @override
  State<ActiveOrderPage> createState() => _ActiveOrderPageState();
}

class _ActiveOrderPageState extends State<ActiveOrderPage> {
  Map<String, dynamic>? orderDetails;
  bool isLoading = true;
  Timer? _pollingTimer;
  Timer? _chatPollingTimer;
  StreamSubscription<Position>? _positionStream;
  StreamSubscription<InternetStatus>? _internetSubscription;
  bool _hasInternet = true;
  
  List<dynamic> _chatMessages = [];
  final TextEditingController _chatController = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  bool _isSending = false;
  
  bool _driverCancelled = false;
  String _lastOrderState = '';

  @override
  void initState() {
    super.initState();
    _fetchOrderDetails();
    _fetchChatMessages();
    
    // Poll order status every 10 seconds
    _pollingTimer = Timer.periodic(const Duration(seconds: 10), (timer) {
      _fetchOrderDetails(silent: true);
    });

    // Poll chat messages every 5 seconds (3s was too aggressive and caused 'Lost connection')
    _chatPollingTimer = Timer.periodic(const Duration(seconds: 5), (timer) {
      _fetchChatMessages();
    });

    _startLocationTracking();

    _internetSubscription = InternetConnection().onStatusChange.listen((InternetStatus status) {
      if (mounted) {
        setState(() {
          _hasInternet = status == InternetStatus.connected;
        });
      }
    });
  }

  void _startLocationTracking() async {
    bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) return;
    
    LocationPermission permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied) return;
    }
    
    _positionStream = Geolocator.getPositionStream(
      locationSettings: const LocationSettings(
        accuracy: LocationAccuracy.high,
        distanceFilter: 10,
      )
    ).listen((Position position) {
      _updateBackendLocation(position);
    });
  }

  Future<void> _updateBackendLocation(Position pos) async {
    try {
      // 1. Update backend MySQL
      await http.post(
        Uri.parse('${ApiService.baseUrl}/UpdateDriverPosition.php'),
        body: {
          'DriverID': widget.driverId,
          'CurrentLat': pos.latitude.toString(),
          'CurrentLongt': pos.longitude.toString(),
          'FirebaseDriverToken': '',
        },
      );

      // 2. Update Firebase Realtime DB for live tracking on user web map
      final fbUrl = Uri.parse('https://jibler-37339-default-rtdb.firebaseio.com/Location/${widget.driverId}.json');
      await http.put(
        fbUrl, 
        body: json.encode({
          'lat': pos.latitude,
          'lng': pos.longitude,
          'timestamp': DateTime.now().millisecondsSinceEpoch,
        }),
      );
    } catch (e) {
      print('Location update error: $e');
    }
  }

  @override
  void dispose() {
    _pollingTimer?.cancel();
    _chatPollingTimer?.cancel();
    _positionStream?.cancel();
    _internetSubscription?.cancel();
    _chatController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _fetchOrderDetails({bool silent = false}) async {
    try {
      final response = await http.post(
        Uri.parse('${ApiService.baseUrl}/GetOneOrdersDetails.php'),
        body: {'OrderID': widget.orderId},
      );

      if (response.statusCode == 200) {
        final responseData = response.body;
        final decoded = json.decode(responseData);
        if (decoded['success'] == true && decoded['data'] != null) {
          if (mounted) {
            final newState = decoded['data']['OrderState']?.toString().toLowerCase() ?? '';
            
            if (_lastOrderState.isNotEmpty && _lastOrderState != 'cancelled' && newState == 'cancelled' && !_driverCancelled) {
              _showCustomerCancelledPopup();
            }

            setState(() {
              var data = Map<String, dynamic>.from(decoded['data']);
              data['DestnationPhoto'] = ApiService.resolvePhotoUrl(data['DestnationPhoto']?.toString());
              data['ShopLogo'] = ApiService.resolvePhotoUrl(data['ShopLogo']?.toString());
              data['UserPhoto'] = ApiService.resolvePhotoUrl(data['UserPhoto']?.toString());
              data['PersonalPhoto'] = ApiService.resolvePhotoUrl(data['PersonalPhoto']?.toString());
              
              orderDetails = data;
              _lastOrderState = newState;
              if (!silent) isLoading = false;
            });
          }
        }
      }
    } catch (e) {
      print('Fetch order details error: $e');
    }
  }

  Future<void> _fetchChatMessages() async {
    try {
      final url = Uri.parse('https://jibler-37339-default-rtdb.firebaseio.com/Messages/${widget.orderId}.json');
      final response = await http.get(url);
      
      if (response.statusCode == 200 && response.body != 'null') {
        final dynamic decoded = json.decode(response.body);
        List<dynamic> rawMessages = [];
        
        if (decoded is Map) {
          rawMessages = decoded.values.toList();
        } else if (decoded is List) {
          rawMessages = decoded.where((e) => e != null).toList();
        }
        
        final List<dynamic> newMessages = rawMessages
          ..sort((a, b) {
            if (a is! Map || b is! Map) return 0;
            final dynamic rawA = a['CreatedTime'] ?? a['height'] ?? a['timestamp'];
            final dynamic rawB = b['CreatedTime'] ?? b['height'] ?? b['timestamp'];
            final int timeA = int.tryParse(rawA?.toString() ?? '0') ?? 0;
            final int timeB = int.tryParse(rawB?.toString() ?? '0') ?? 0;
            return timeA.compareTo(timeB);
          });
        
        if (mounted) {
          final bool shouldScroll = _chatMessages.length < newMessages.length;
          setState(() {
            _chatMessages = newMessages;
          });
          if (shouldScroll) {
            _scrollToBottom();
          }
        }
      }
    } catch (e) {
      debugPrint('Fetch chat error: $e');
    }
  }

  Future<void> _sendMessage() async {
    final text = _chatController.text.trim();
    if (text.isEmpty) return;

    setState(() => _isSending = true);
    _chatController.clear();

    final userId = orderDetails?['UserID']?.toString() ?? '';

    // 1. Post to Firebase Realtime DB via REST API
    try {
      final url = Uri.parse('https://jibler-37339-default-rtdb.firebaseio.com/Messages/${widget.orderId}.json');
      await http.post(
        url,
        body: json.encode({
          'message': text,
          'sender': 'Driver',
          'MessageType': 'words',
          'CreatedTime': DateTime.now().millisecondsSinceEpoch,
        }),
      );
    } catch (e) {
      print('Firebase push error: $e');
    }

    // 2. Trigger push notification via existing PHP API
    try {
      await http.post(
        Uri.parse('${ApiService.baseUrl}/DriverSendMessage.php'),
        body: {
          'OrderID': widget.orderId,
          'UserID': userId,
          'messsage': text, // Match PHP API typo
        },
      );
    } catch (e) {
      print('Send message notification error: $e');
    }

    setState(() => _isSending = false);
    _fetchChatMessages();
    _scrollToBottom();
  }

  int _crc32(String data) {
    List<int> table = List<int>.filled(256, 0);
    for (int i = 0; i < 256; i++) {
      int c = i;
      for (int j = 0; j < 8; j++) {
        if ((c & 1) != 0) {
          c = 0xEDB88320 ^ (c >> 1);
        } else {
          c = c >> 1;
        }
      }
      table[i] = c;
    }

    int c = 0xFFFFFFFF;
    for (int i = 0; i < data.length; i++) {
      c = table[(c ^ data.codeUnitAt(i)) & 0xFF] ^ (c >> 8);
    }
    return (c ^ 0xFFFFFFFF) & 0xFFFFFFFF;
  }

  String _getTranslatedState(String rawState) {
    final key = 'status_${rawState.toLowerCase().replaceAll(' ', '_')}';
    final translated = key.tr;
    if (translated == key) {
      if (rawState.toLowerCase() == 'ready') return 'status_picked'.tr;
      return rawState.toUpperCase();
    }
    return translated.toUpperCase();
  }

  String _generateShopPickupPin() {
    final token = "${widget.orderId}QOON_SHOP_PICKUP_TOKEN";
    final hash = _crc32(token);
    final pinNum = hash.abs() % 10000;
    return pinNum.toString().padLeft(4, '0');
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  Future<bool> _changeOrderStatus(String endpoint, String loadingMessage, {String? pin}) async {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(loadingMessage)));
    
    try {
      final bodyData = {
        'OrderID': widget.orderId,
        'DriverID': widget.driverId,
        'AppType': 'QOON',
      };
      if (pin != null) {
        bodyData['DeliveryPIN'] = pin;
      }

      final response = await http.post(
        Uri.parse('${ApiService.baseUrl}/$endpoint'),
        body: bodyData,
      );

      if (response.statusCode == 200) {
        _fetchOrderDetails(silent: true);
        return true;
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Failed to update status. Please ensure $endpoint is uploaded to the server! (Code: ${response.statusCode})')));
        }
        return false;
      }
    } catch (e) {
      print('Change status error: $e');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Network error: $e')));
      }
      return false;
    }
  }

  void _showDeliveryPinPopup() {
    final correctPin = (orderDetails?['FourDigit']?.toString() ?? '').padLeft(4, '0');
    if (correctPin.isEmpty || correctPin == '0' || correctPin == '0000') {
      _changeOrderStatus('FinishOrderDriver.php', 'Marking as Delivered...').then((success) async {
        if (success && mounted) {
          await _updateStatusViaChat('Delivered', 'Syncing with Web UI...');
          if (mounted) Navigator.pop(context);
        }
      });
      return;
    }

    final TextEditingController pinController = TextEditingController();
    String errorMsg = '';

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setStateDialog) {
            return AlertDialog(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
              contentPadding: const EdgeInsets.all(24),
              content: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(color: Colors.green.withOpacity(0.1), shape: BoxShape.circle),
                    child: const Icon(Icons.verified_user_rounded, color: Colors.green, size: 48),
                  ),
                  const SizedBox(height: 20),
                  const Text('Delivery PIN Required', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 20, color: Colors.black87)),
                  const SizedBox(height: 8),
                  Text('Ask the customer for their 4-digit Delivery PIN to confirm this delivery.', textAlign: TextAlign.center, style: TextStyle(fontSize: 14, color: Colors.grey.shade600)),
                  const SizedBox(height: 24),
                  TextField(
                    controller: pinController,
                    keyboardType: TextInputType.number,
                    maxLength: 4,
                    textAlign: TextAlign.center,
                    style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w900, letterSpacing: 8),
                    decoration: InputDecoration(
                      counterText: '',
                      hintText: '0000',
                      filled: true,
                      fillColor: Colors.grey.shade100,
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: BorderSide.none),
                      errorText: errorMsg.isNotEmpty ? errorMsg : null,
                    ),
                    onChanged: (val) {
                      if (errorMsg.isNotEmpty) setStateDialog(() => errorMsg = '');
                    },
                  ),
                  const SizedBox(height: 24),
                  Row(
                    children: [
                      Expanded(
                        child: TextButton(
                          onPressed: () => Navigator.pop(context),
                          style: TextButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 16)),
                          child: const Text('Cancel', style: TextStyle(color: Colors.grey, fontWeight: FontWeight.w800)),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: ElevatedButton(
                          onPressed: () async {
                            if (pinController.text.trim() == correctPin) {
                              Navigator.pop(context);
                              bool success = await _changeOrderStatus('FinishOrderDriver.php', 'Marking as Delivered...', pin: pinController.text.trim());
                              if (success && mounted) {
                                await _updateStatusViaChat('Delivered', 'Syncing with Web UI...');
                                if (mounted) Navigator.pop(context);
                              }
                            } else {
                              setStateDialog(() => errorMsg = 'Incorrect PIN');
                            }
                          },
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.green,
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                            elevation: 0,
                          ),
                          child: const Text('Confirm', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800)),
                        ),
                      ),
                    ],
                  )
                ],
              ),
            );
          }
        );
      }
    );
  }

  void _makePhoneCall(String phoneNumber) async {
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Call $phoneNumber (Native dialer requires a physical device build)'),
          duration: const Duration(seconds: 3),
        ),
      );
    }
  }

  void _showCustomerCancelledPopup() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
        contentPadding: const EdgeInsets.fromLTRB(24, 32, 24, 24),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(color: Colors.red.withOpacity(0.1), shape: BoxShape.circle),
              child: const Icon(Icons.sentiment_dissatisfied_rounded, color: Colors.red, size: 64),
            ),
            const SizedBox(height: 24),
            const Text(
              'Order Cancelled',
              style: TextStyle(fontWeight: FontWeight.w900, fontSize: 24, color: Colors.black87),
            ),
            const SizedBox(height: 12),
            Text(
              'The customer has cancelled this order.\nYou can now return to the home screen and find new orders.',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 15, height: 1.5, color: Colors.grey.shade600, fontWeight: FontWeight.w500),
            ),
            const SizedBox(height: 32),
            SizedBox(
              width: double.infinity,
              height: 54,
              child: ElevatedButton(
                onPressed: () {
                  Navigator.of(context).pop(); // Close dialog
                  Navigator.of(context).pop(); // Go back to Home
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF6366F1),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                  elevation: 0,
                ),
                child: const Text('Find New Orders', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 16)),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _updateStatusViaChat(String newStatus, String loadingMessage) async {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(loadingMessage)));
    
    try {
      final response = await http.post(
        Uri.parse('https://qoon.app/driver_chat.php'),
        body: {
          'ajax_status_update': '1',
          'new_status': newStatus,
          'order_id': widget.orderId,
        },
      );

      if (response.statusCode == 200) {
        _fetchOrderDetails(silent: true);
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Failed to update status. (Code: ${response.statusCode})')));
        }
      }
    } catch (e) {
      print('Chat status update error: $e');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Network error: $e')));
      }
    }
  }

  Future<void> _openMaps(bool isShop) async {
    String lat = '';
    String lng = '';
    
    if (isShop) {
      lat = orderDetails?['ShopLat']?.toString() ?? orderDetails?['DestnationLat']?.toString() ?? '';
      lng = orderDetails?['ShopLongt']?.toString() ?? orderDetails?['DestnationLongt']?.toString() ?? '';
    } else {
      lat = orderDetails?['UserLat']?.toString() ?? orderDetails?['Lat']?.toString() ?? '';
      lng = orderDetails?['UserLongt']?.toString() ?? orderDetails?['Longt']?.toString() ?? '';
    }

    if (lat.isEmpty || lng.isEmpty || lat == '0' || lng == '0' || lat == 'null' || lng == 'null') {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Location coordinates not available')));
      return;
    }

    final url = Uri.parse('https://www.google.com/maps/search/?api=1&query=$lat,$lng');
    try {
      if (await canLaunchUrl(url)) {
        await launchUrl(url, mode: LaunchMode.externalApplication);
      } else {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Could not open Maps application')));
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
    }
  }

  void _showCancelWarningPopup() {
    showDialog(
      context: context,
      builder: (context) => Dialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
        elevation: 0,
        backgroundColor: Colors.transparent,
        child: Container(
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(24),
            boxShadow: [BoxShadow(color: Colors.black26, blurRadius: 20, offset: const Offset(0, 10))],
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TweenAnimationBuilder<double>(
                tween: Tween(begin: 0.0, end: 1.0),
                duration: const Duration(milliseconds: 600),
                curve: Curves.elasticOut,
                builder: (context, value, child) {
                  return Transform.scale(
                    scale: value,
                    child: Container(
                      padding: const EdgeInsets.all(20),
                      decoration: BoxDecoration(
                        color: Colors.red.shade50,
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.location_on_rounded, color: Colors.red, size: 48),
                    ),
                  );
                },
              ),
              const SizedBox(height: 24),
              const Text(
                'Location Required',
                style: TextStyle(fontSize: 22, fontWeight: FontWeight.w900, color: Colors.black87),
              ),
              const SizedBox(height: 12),
              Text(
                'You must be at the client\'s location to cancel this order. You will be required to upload a proof photo from your current location.',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 14, color: Colors.grey.shade600, height: 1.5),
              ),
              const SizedBox(height: 32),
              Row(
                children: [
                  Expanded(
                    child: TextButton(
                      onPressed: () => Navigator.pop(context),
                      style: TextButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      child: Text('Go Back', style: TextStyle(color: Colors.grey.shade600, fontWeight: FontWeight.w700, fontSize: 16)),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: ElevatedButton(
                      onPressed: () {
                        Navigator.pop(context);
                        _cancelDelivery();
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.red,
                        elevation: 0,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      child: const Text('Continue', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 16)),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _cancelDelivery() async {
    final picker = ImagePicker();
    XFile? image;
    
    // Show bottom sheet to pick source (since iOS Simulator doesn't support camera)
    final source = await showModalBottomSheet<ImageSource>(
      context: context,
      builder: (BuildContext context) {
        return SafeArea(
          child: Wrap(
            children: <Widget>[
              ListTile(
                leading: const Icon(Icons.photo_library),
                title: const Text('Photo Library'),
                onTap: () => Navigator.of(context).pop(ImageSource.gallery),
              ),
              ListTile(
                leading: const Icon(Icons.photo_camera),
                title: const Text('Camera'),
                onTap: () => Navigator.of(context).pop(ImageSource.camera),
              ),
            ],
          ),
        );
      },
    );

    if (source == null) return;

    try {
      image = await picker.pickImage(source: source, imageQuality: 70);
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Camera error (Simulator does not support camera): $e')));
      return;
    }

    if (image == null) return;

    setState(() => isLoading = true);

    try {
      bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Please enable location services')));
        setState(() => isLoading = false);
        return;
      }
      LocationPermission permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
        if (permission == LocationPermission.denied) {
          if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Location permission denied')));
          setState(() => isLoading = false);
          return;
        }
      }
      
      Position position = await Geolocator.getCurrentPosition(desiredAccuracy: LocationAccuracy.high);
      
      final bytes = await File(image.path).readAsBytes();
      final base64Image = base64Encode(bytes);
      
      final request = http.MultipartRequest('POST', Uri.parse('${ApiService.baseUrl}/uploadImageChat.php'));
      request.fields['photochat'] = base64Image;
      final response = await request.send();
      final responseData = await response.stream.bytesToString();
      final decoded = json.decode(responseData);
      final imgUrl = decoded['data']?.toString() ?? '';

      final time = DateTime.now().millisecondsSinceEpoch;
      final chatUrl = Uri.parse('https://jibler-37339-default-rtdb.firebaseio.com/Messages/${widget.orderId}.json');
      
      if (imgUrl.isNotEmpty) {
        await http.post(chatUrl, body: json.encode({
          'message': imgUrl, 'sender': 'driver', 'MessageType': 'Image', 'CreatedTime': time, 'id': widget.driverId
        }));
      }

      await http.post(chatUrl, body: json.encode({
        'message': 'Live Location: ${position.latitude},${position.longitude}',
        'lat': position.latitude, 'lng': position.longitude, 'sender': 'driver', 'MessageType': 'Location', 'CreatedTime': time + 100, 'id': widget.driverId
      }));

      final returnPin = _generateReturnPin();
      await http.post(chatUrl, body: json.encode({
        'message': 'Order Cancelled. Driver has submitted proof. Return PIN: $returnPin', 'sender': 'system', 'MessageType': 'words', 'CreatedTime': time + 200, 'id': 'system'
      }));

      _driverCancelled = true;
      await _updateStatusViaChat('Cancelled', 'Cancelling Order...');
      
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
    } finally {
      if (mounted) setState(() => isLoading = false);
    }
  }

  void _showSpecificCallBottomSheet(String name, String phone, bool isShop) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (context) {
        return Padding(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 48,
                height: 5,
                decoration: BoxDecoration(
                  color: Colors.grey.shade300,
                  borderRadius: BorderRadius.circular(10),
                ),
              ),
              const SizedBox(height: 24),
              Text(
                isShop ? 'Call Shop' : 'Call Customer',
                style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800),
              ),
              const SizedBox(height: 24),
              ListTile(
                contentPadding: EdgeInsets.zero,
                leading: CircleAvatar(
                  backgroundColor: isShop ? const Color(0xFFF59E0B) : const Color(0xFF3B82F6),
                  child: Icon(isShop ? Icons.store_rounded : Icons.person_rounded, color: Colors.white),
                ),
                title: Text(name, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
                subtitle: Text(phone, style: const TextStyle(fontSize: 16, color: Colors.black87, fontWeight: FontWeight.w600, letterSpacing: 1.0)),
              ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                height: 56,
                child: ElevatedButton.icon(
                  onPressed: () {
                    Navigator.pop(context);
                    _makePhoneCall(phone);
                  },
                  icon: const Icon(Icons.call, color: Colors.white),
                  label: Text('Call $phone', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 16)),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF22C55E),
                    elevation: 0,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                  ),
                ),
              ),
              const SizedBox(height: 12),
            ],
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    if (isLoading) {
      return const Scaffold(
        backgroundColor: Colors.white,
        body: Center(child: CircularProgressIndicator(color: Colors.black)),
      );
    }

    if (orderDetails == null) {
      return Scaffold(
        appBar: AppBar(
          leading: IconButton(
            icon: const Icon(Icons.arrow_back, color: Colors.black),
            onPressed: () => Navigator.pop(context),
          ),
        ),
        body: const Center(child: Text("Failed to load order details")),
      );
    }

    final shopName = orderDetails?['ShopName']?.toString().isNotEmpty == true ? orderDetails!['ShopName'] : (orderDetails?['DestinationName'] ?? 'Shop');
    final shopLogo = (orderDetails?['ShopLogo']?.toString().isNotEmpty == true) ? orderDetails!['ShopLogo'] : (orderDetails?['DestnationPhoto'] ?? '');
    String userName = orderDetails?['UserName']?.toString() ?? '';
    if (userName.isEmpty) userName = orderDetails?['name']?.toString() ?? '';
    if (userName.isEmpty) userName = orderDetails?['FName']?.toString() ?? '';
    if (userName.isEmpty) userName = orderDetails?['UserPhone']?.toString() ?? '';
    if (userName.isEmpty) userName = 'Customer';
    
    String userPhoto = '';
    if (orderDetails?['UserPhoto'] != null && orderDetails!['UserPhoto'].toString().isNotEmpty && orderDetails!['UserPhoto'].toString() != '0' && orderDetails!['UserPhoto'].toString() != 'null') {
      userPhoto = orderDetails!['UserPhoto'].toString();
    }
    
    if (userPhoto.isNotEmpty) {
      userPhoto = ApiService.resolvePhotoUrl(userPhoto);
    }
    
    if (userPhoto.isEmpty) {
      userPhoto = 'https://ui-avatars.com/api/?name=${Uri.encodeComponent(userName)}&background=2cb5e8&color=fff&size=64';
    }
    final orderState = orderDetails?['OrderState'] ?? 'Doing';
    
    // Status Logic
    final lowercaseState = orderState.toLowerCase();
    bool showOnWayButton = ['order pickup', 'picked', 'ready', 'arrived at shop'].contains(lowercaseState);
    bool showDeliveredButton = ['on way', 'on the way', 'come to take it', 'found', 'arrived'].contains(lowercaseState);
    
    final isReturnedOrCancelled = lowercaseState == 'cancelled' || lowercaseState == 'returned';
    final pinCode = isReturnedOrCancelled ? _generateReturnPin() : _generateShopPickupPin();
    
    String shopPhone = orderDetails?['ShopPhone']?.toString() 
                    ?? orderDetails?['DestnationPhone']?.toString() 
                    ?? orderDetails?['DestinationPhone']?.toString() 
                    ?? orderDetails?['Phone']?.toString() 
                    ?? '';
    if (shopPhone == '0') shopPhone = '';
    
    String userPhone = orderDetails?['PhoneNumber']?.toString() ?? orderDetails?['UserPhone']?.toString() ?? '';
    if (userPhone == '0') userPhone = '';

    return ListenableBuilder(
      listenable: localizationService,
      builder: (context, child) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA), // Match chat background
      appBar: AppBar(
        backgroundColor: const Color(0xFFF8F9FA),
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        centerTitle: false,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.black),
          onPressed: () => Navigator.pop(context),
        ),
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'active_order'.tr,
              style: const TextStyle(color: Colors.black, fontWeight: FontWeight.w800, fontFamily: 'Inter', fontSize: 18),
            ),
            Text(
              _getTranslatedState(orderState),
              style: const TextStyle(color: Color(0xFF3B82F6), fontWeight: FontWeight.w700, fontSize: 11, letterSpacing: 0.5),
            ),
          ],
        ),
        actions: [
          if (pinCode.isNotEmpty)
            Container(
              margin: const EdgeInsets.only(right: 16),
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
              decoration: BoxDecoration(
                color: (isReturnedOrCancelled ? const Color(0xFFEF4444) : const Color(0xFF6366F1)).withOpacity(0.08),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: (isReturnedOrCancelled ? const Color(0xFFEF4444) : const Color(0xFF6366F1)).withOpacity(0.2)),
              ),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    isReturnedOrCancelled ? 'return_pin'.tr : 'pickup_pin'.tr,
                    style: TextStyle(
                      color: isReturnedOrCancelled ? const Color(0xFFEF4444) : const Color(0xFF6366F1),
                      fontSize: 8,
                      fontWeight: FontWeight.w900,
                      letterSpacing: 0.8,
                    ),
                  ),
                  Text(
                    pinCode,
                    style: TextStyle(
                      color: isReturnedOrCancelled ? const Color(0xFFEF4444) : const Color(0xFF6366F1),
                      fontSize: 16,
                      fontWeight: FontWeight.w900,
                      letterSpacing: 1.5,
                    ),
                  ),
                ],
              ),
            ),
        ],
      ),
      body: Column(
        children: [
          if (!_hasInternet)
            Container(
              width: double.infinity,
              color: Colors.red.shade600,
              padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 16),
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
            
          // HEADER (Profiles)
          Container(
            width: double.infinity,
            margin: const EdgeInsets.fromLTRB(16, 12, 16, 24),
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(24),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.05),
                  blurRadius: 30,
                  offset: const Offset(0, 10),
                )
              ],
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(child: _buildModernProfileBadge(shopName, shopLogo, isShop: true, phone: shopPhone, onTap: () => _openMaps(true))),
                
                // Animated Route Divider
                Container(
                  width: 60,
                  child: Column(
                    children: [
                      const Icon(Icons.local_shipping_outlined, color: Color(0xFF3B82F6), size: 28),
                      const SizedBox(height: 8),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: List.generate(5, (index) => Container(
                          width: 4, height: 4,
                          margin: const EdgeInsets.symmetric(horizontal: 2.5),
                          decoration: BoxDecoration(color: Colors.grey.shade300, shape: BoxShape.circle),
                        )),
                      ),
                    ],
                  ),
                ),
                
                Expanded(child: _buildModernProfileBadge(userName, userPhoto, isShop: false, phone: userPhone, onTap: () => _openMaps(false))),
              ],
            ),
          ),
          
          // STATUS TRACKER
          if (!widget.isClosed && lowercaseState != 'doing')
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              child: Column(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                    decoration: BoxDecoration(
                      color: const Color(0xFFEEF2FF), // Soft Indigo
                      borderRadius: BorderRadius.circular(30),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                         // Pulse effect simulation
                         TweenAnimationBuilder<double>(
                           tween: Tween(begin: 0.5, end: 1.0),
                           duration: const Duration(milliseconds: 1000),
                           curve: Curves.easeInOut,
                           builder: (context, value, child) {
                             return Container(
                               width: 10, height: 10,
                               decoration: BoxDecoration(
                                 color: const Color(0xFF6366F1).withOpacity(value),
                                 shape: BoxShape.circle,
                                 boxShadow: [BoxShadow(color: const Color(0xFF6366F1).withOpacity(value * 0.5), blurRadius: 8 * value)],
                               ),
                             );
                           },
                         ),
                         const SizedBox(width: 12),
                         Text(
                           '${'status_label'.tr}${_getTranslatedState(orderState)}',
                           style: const TextStyle(color: Color(0xFF4F46E5), fontWeight: FontWeight.w800, fontSize: 13, letterSpacing: 0.5),
                         ),
                      ],
                    ),
                  ),
                  if (showOnWayButton || showDeliveredButton) ...[
                    const SizedBox(height: 20),
                    Container(
                      width: double.infinity,
                      height: 52,
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          colors: showOnWayButton ? [const Color(0xFFF97316), const Color(0xFFEA580C)] : [const Color(0xFF22C55E), const Color(0xFF16A34A)],
                        ),
                        borderRadius: BorderRadius.circular(16),
                        boxShadow: [
                          BoxShadow(
                            color: (showOnWayButton ? const Color(0xFFF97316) : const Color(0xFF22C55E)).withOpacity(0.3),
                            blurRadius: 15,
                            offset: const Offset(0, 8),
                          )
                        ],
                      ),
                      child: ElevatedButton(
                        onPressed: () {
                          if (showOnWayButton) {
                            _updateStatusViaChat('On Way', 'Marking as On Way...');
                          } else if (showDeliveredButton) {
                            _showDeliveryPinPopup();
                          }
                        },
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.transparent,
                          shadowColor: Colors.transparent,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                        ),
                        child: Text(
                          showOnWayButton ? 'mark_on_way'.tr : 'mark_delivered'.tr,
                          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 16),
                        ),
                      ),
                    ),
                  ],
                  if (showDeliveredButton) ...[
                    const SizedBox(height: 12),
                    SizedBox(
                      width: double.infinity,
                      height: 52,
                      child: TextButton(
                        onPressed: _showCancelWarningPopup,
                        style: TextButton.styleFrom(
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                          backgroundColor: Colors.red.shade50,
                        ),
                        child: Text(
                          'cancel_delivery'.tr,
                          style: const TextStyle(color: Colors.red, fontWeight: FontWeight.w800, fontSize: 14),
                        ),
                      ),
                    ),
                  ]
                ],
              ),
            ),

          // CHAT AREA
          Expanded(child: _buildChatTab()),
        ],
      ),
    );
    }); // end ListenableBuilder
  }

  Widget _buildPinCard(String pin, {String title = 'Collection PIN', String desc = 'Show this code to the shop to pick up the order', Color color = const Color(0xFF6366F1)}) {
    return Container(
      margin: const EdgeInsets.only(bottom: 24),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: color.withOpacity(0.08),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: color.withOpacity(0.2)),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(color: color, shape: BoxShape.circle),
            child: const Icon(Icons.key_rounded, color: Colors.white, size: 20),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: TextStyle(color: color, fontWeight: FontWeight.w800, fontSize: 13)),
                Text(desc, style: TextStyle(color: Colors.grey.shade600, fontSize: 11)),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: color.withOpacity(0.1))),
            child: Text(
              pin,
              style: TextStyle(color: color, fontWeight: FontWeight.w900, fontSize: 20, letterSpacing: 2),
            ),
          ),
        ],
      ),
    );
  }

  String _generateReturnPin() {
    int crc32(String str) {
      int crc = 0xFFFFFFFF;
      for (int i = 0; i < str.length; i++) {
        crc ^= str.codeUnitAt(i);
        for (int j = 0; j < 8; j++) {
          crc = (crc & 1) != 0 ? (crc >> 1) ^ 0xEDB88320 : crc >> 1;
        }
      }
      return crc ^ 0xFFFFFFFF;
    }
    return (crc32(widget.orderId + 'RETURN') % 9000 + 1000).toString();
  }

  // Format timestamp for chat
  String _formatChatTime(int timestamp) {
    final date = DateTime.fromMillisecondsSinceEpoch(timestamp);
    final hour = date.hour > 12 ? date.hour - 12 : (date.hour == 0 ? 12 : date.hour);
    final ampm = date.hour >= 12 ? 'PM' : 'AM';
    final min = date.minute.toString().padLeft(2, '0');
    return '$hour:$min $ampm';
  }

  Widget _buildOrderSummaryCard() {
    final productsStr = orderDetails?['OrderDetails']?.toString() ?? '';
    if (productsStr.isEmpty) return const SizedBox.shrink();

    List<String> items = [];
    if (productsStr.contains('\n')) {
      items = productsStr.split('\n').where((s) => s.trim().isNotEmpty).toList();
    } else if (productsStr.contains(',')) {
      items = productsStr.split(',').where((s) => s.trim().isNotEmpty).toList();
    } else {
      items = [productsStr];
    }

    final shopName = orderDetails?['ShopName']?.toString().isNotEmpty == true ? orderDetails!['ShopName'] : (orderDetails?['DestinationName'] ?? 'Shop');
    String shopLogo = orderDetails?['ShopLogo']?.toString().isNotEmpty == true ? orderDetails!['ShopLogo'].toString() : (orderDetails?['DestnationPhoto']?.toString() ?? '');
    if (shopLogo.isNotEmpty) shopLogo = ApiService.resolvePhotoUrl(shopLogo);
    String shopPhone = orderDetails?['ShopPhone']?.toString() 
                    ?? orderDetails?['DestnationPhone']?.toString() 
                    ?? orderDetails?['DestinationPhone']?.toString() 
                    ?? orderDetails?['Phone']?.toString() 
                    ?? '';
    if (shopPhone == '0') shopPhone = '';

    final userName = orderDetails?['UserName'] ?? orderDetails?['name'] ?? 'Customer';
    String userPhoto = orderDetails?['UserPhoto']?.toString() ?? '';
    if (userPhoto.isNotEmpty) userPhoto = ApiService.resolvePhotoUrl(userPhoto);
    if (userPhoto.isEmpty) {
      userPhoto = 'https://ui-avatars.com/api/?name=${Uri.encodeComponent(userName)}&background=2cb5e8&color=fff&size=64';
    }
    String userPhone = orderDetails?['PhoneNumber']?.toString() ?? orderDetails?['UserPhone']?.toString() ?? '';
    if (userPhone == '0') userPhone = '';

    return Container(
      margin: const EdgeInsets.only(bottom: 24),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Colors.grey.shade200),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 15, offset: const Offset(0, 8))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.receipt_long_rounded, color: Color(0xFF6366F1), size: 20),
              const SizedBox(width: 8),
              Text('order_summary'.tr, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15, color: Colors.black87)),
            ],
          ),
          const SizedBox(height: 16),
          // Table Header
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(color: const Color(0xFFF8FAFC), borderRadius: BorderRadius.circular(8)),
            child: Row(
              children: [
                Expanded(flex: 1, child: Text('qty'.tr, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: Colors.grey.shade500))),
                Expanded(flex: 4, child: Text('item'.tr, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: Colors.grey.shade500))),
              ],
            ),
          ),
          const SizedBox(height: 8),
          // Items
          ...items.map((item) {
            String qty = '-';
            String name = item.trim();
            final match = RegExp(r'^(\d+)[xX\s]*(.*)$').firstMatch(name);
            if (match != null) {
              qty = match.group(1) ?? '-';
              name = match.group(2)?.trim() ?? name;
            } else {
              // Try matching formats like "Burger x2"
              final matchEnd = RegExp(r'^(.*?)[\s]+[xX](\d+)$').firstMatch(name);
              if (matchEnd != null) {
                name = matchEnd.group(1)?.trim() ?? name;
                qty = matchEnd.group(2) ?? '-';
              }
            }
            return Padding(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              child: Row(
                children: [
                  Expanded(flex: 1, child: Text('${qty}x', style: const TextStyle(fontWeight: FontWeight.w800, color: Color(0xFF3B82F6)))),
                  Expanded(flex: 4, child: Text(name, style: const TextStyle(fontWeight: FontWeight.w600, color: Colors.black87))),
                ],
              ),
            );
          }),
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 16),
            child: Divider(height: 1, color: Color(0xFFF1F5F9)),
          ),
          // Contacts
          Row(
            children: [
              Expanded(child: _buildContactRow(shopName, shopLogo, shopPhone, isShop: true)),
              Container(width: 1, height: 40, color: Colors.grey.shade200, margin: const EdgeInsets.symmetric(horizontal: 16)),
              Expanded(child: _buildContactRow(userName, userPhoto, userPhone, isShop: false)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildContactRow(String name, String photo, String phone, {required bool isShop}) {
    final color = isShop ? const Color(0xFFF59E0B) : const Color(0xFF3B82F6);
    return GestureDetector(
      onTap: () {
        if (phone.isNotEmpty) _showSpecificCallBottomSheet(name, phone, isShop);
      },
      child: Row(
        children: [
          CircleAvatar(
            radius: 18,
            backgroundColor: color.withOpacity(0.1),
            backgroundImage: photo.isNotEmpty ? NetworkImage(photo) : null,
            child: photo.isEmpty ? Icon(isShop ? Icons.storefront_rounded : Icons.person_rounded, size: 18, color: color) : null,
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(name, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13, color: Colors.black87)),
                if (phone.isNotEmpty)
                  Text(phone, maxLines: 1, overflow: TextOverflow.ellipsis, style: TextStyle(fontWeight: FontWeight.w600, fontSize: 11, color: Colors.grey.shade500)),
              ],
            ),
          ),
          if (phone.isNotEmpty)
            const Icon(Icons.call, size: 16, color: Color(0xFF22C55E)),
        ],
      ),
    );
  }

  // Modern dark chat UI — WhatsApp/iMessage inspired
  Widget _buildChatTab() {
    String customerName = orderDetails?['UserName']?.toString() ?? '';
    if (customerName.isEmpty) customerName = orderDetails?['name']?.toString() ?? '';
    if (customerName.isEmpty) customerName = orderDetails?['FName']?.toString() ?? '';
    if (customerName.isEmpty) customerName = orderDetails?['UserPhone']?.toString() ?? '';
    if (customerName.isEmpty) customerName = 'Customer';

    String customerPhoto = '';
    if (orderDetails?['UserPhoto'] != null && orderDetails!['UserPhoto'].toString().isNotEmpty && orderDetails!['UserPhoto'].toString() != '0' && orderDetails!['UserPhoto'].toString() != 'null') {
      customerPhoto = orderDetails!['UserPhoto'].toString();
    }
    
    if (customerPhoto.isNotEmpty) {
      customerPhoto = ApiService.resolvePhotoUrl(customerPhoto);
    }
    
    if (customerPhoto.isEmpty) {
      customerPhoto = 'https://ui-avatars.com/api/?name=${Uri.encodeComponent(customerName)}&background=2cb5e8&color=fff&size=64';
    }
    final shopName = orderDetails?['ShopName']?.toString().isNotEmpty == true ? orderDetails!['ShopName'] : (orderDetails?['DestinationName'] ?? 'Shop');
    String shopLogo = orderDetails?['ShopLogo']?.toString().isNotEmpty == true ? orderDetails!['ShopLogo'].toString() : (orderDetails?['DestnationPhoto']?.toString() ?? '');
    if (shopLogo.isNotEmpty) shopLogo = ApiService.resolvePhotoUrl(shopLogo);
    String rawDriverPhoto = orderDetails?['PersonalPhoto']?.toString().isNotEmpty == true 
        ? orderDetails!['PersonalPhoto'].toString() 
        : (ApiService.cachedDriverPhoto ?? '');
    final driverPhoto = ApiService.resolvePhotoUrl(rawDriverPhoto);

    return Container(
      color: const Color(0xFFF8F9FA), // Light grey background
      child: Column(
        children: [
          // Header removed as requested

          // ── Messages
          Expanded(
            child: ListView.builder(
              physics: const BouncingScrollPhysics(),
              controller: _scrollController,
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 20),
              itemCount: _chatMessages.length + 1,
              itemBuilder: (context, index) {
                if (index == 0) return _buildOrderSummaryCard();
                
                final msg = _chatMessages[index - 1];
                      final sender = msg['sender']?.toString().toLowerCase() ?? '';
                      final content = msg['message']?.toString() ?? '';
                      final isSystem = sender == 'system' || content.contains('Order Status Updated:');
                      final isMe = sender == 'driver' || sender == 'jibler';
                      final isUser = sender == 'user' || sender == 'customer';
                      final isShop = !isSystem && !isMe && !isUser; 

                      final timestamp = msg['CreatedTime'] ?? msg['height'] ?? msg['timestamp'] ?? 0;
                      final timeStr = timestamp > 0 ? _formatChatTime(timestamp) : '';
                      final msgType = msg['MessageType']?.toString() ?? 'words';

                      if (isSystem) {
                        return Padding(
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          child: Center(
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                              decoration: BoxDecoration(
                                color: Colors.grey.shade200,
                                borderRadius: BorderRadius.circular(24),
                                border: Border.all(color: Colors.grey.shade300, width: 0.5),
                              ),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  const Icon(Icons.info_outline_rounded, size: 16, color: Colors.black54),
                                  const SizedBox(width: 8),
                                  Flexible(child: Text(content, textAlign: TextAlign.center, style: const TextStyle(color: Colors.black87, fontSize: 12, fontWeight: FontWeight.w700, letterSpacing: 0.3))),
                                ],
                              ),
                            ),
                          ),
                        );
                      }

                      String avatarUrl = '';
                      String dispName = '';
                      Color accentColor;
                      if (isMe) {
                        avatarUrl = driverPhoto; dispName = 'you'.tr; accentColor = const Color(0xFF6366F1);
                      } else if (isShop) {
                        avatarUrl = shopLogo; dispName = shopName; accentColor = const Color(0xFFF59E0B);
                      } else {
                        avatarUrl = customerPhoto; dispName = customerName; accentColor = const Color(0xFF22C55E);
                      }

                      Widget contentWidget;
                      if (msgType == 'Image' || (content.contains('http') && (content.contains('.png') || content.contains('.jpg')))) {
                        contentWidget = ClipRRect(
                          borderRadius: BorderRadius.circular(16), 
                          child: CachedNetworkImage(
                            imageUrl: ApiService.resolvePhotoUrl(content), 
                            width: 240, height: 200, fit: BoxFit.cover, 
                            placeholder: (context, url) => Container(color: Colors.white10, width: 240, height: 200), 
                            errorWidget: (context, url, error) => const Icon(Icons.error)
                          )
                        );
                      } else if (msgType == 'Location' || content.contains('Live Location')) {
                        final latLngStr = content.replaceAll('Live Location: ', '');
                        final parts = latLngStr.split(',');
                        final lat = parts.isNotEmpty ? parts[0].trim() : '';
                        final lng = parts.length > 1 ? parts[1].trim() : '';
                        
                        contentWidget = GestureDetector(
                          onTap: () async {
                            if (lat.isNotEmpty && lng.isNotEmpty) {
                              final url = Uri.parse('https://www.google.com/maps/search/?api=1&query=$lat,$lng');
                              if (await canLaunchUrl(url)) {
                                await launchUrl(url, mode: LaunchMode.externalApplication);
                              }
                            }
                          },
                          child: Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                            child: Row(mainAxisSize: MainAxisSize.min, children: [
                              Container(
                                padding: const EdgeInsets.all(8),
                                decoration: BoxDecoration(color: isMe ? Colors.white.withOpacity(0.2) : accentColor.withOpacity(0.1), shape: BoxShape.circle),
                                child: Icon(Icons.location_on_rounded, color: isMe ? Colors.white : accentColor, size: 22),
                              ),
                              const SizedBox(width: 12),
                              Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                                Text('live_location'.tr, style: TextStyle(color: isMe ? Colors.white : Colors.black87, fontWeight: FontWeight.w800, fontSize: 14)),
                                const SizedBox(height: 2),
                                Text('tap_to_navigate'.tr, style: TextStyle(color: isMe ? Colors.white70 : Colors.grey.shade600, fontSize: 12)),
                              ]),
                            ]),
                          ),
                        );
                      } else {
                        contentWidget = Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
                          child: Text(content, style: TextStyle(color: isMe ? Colors.white : Colors.black87, fontSize: 15, height: 1.4, letterSpacing: 0.2)),
                        );
                      }

                      return Padding(
                        padding: const EdgeInsets.only(bottom: 20),
                        child: Row(
                          mainAxisAlignment: isMe ? MainAxisAlignment.end : MainAxisAlignment.start,
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: [
                            if (!isMe) ...[
                              Container(
                                width: 36, height: 36,
                                decoration: BoxDecoration(shape: BoxShape.circle, color: Colors.white, boxShadow: [BoxShadow(color: accentColor.withOpacity(0.2), blurRadius: 8, offset: const Offset(0, 3))]),
                                child: ClipOval(
                                  child: avatarUrl.isNotEmpty 
                                      ? Image.network(avatarUrl, fit: BoxFit.cover, errorBuilder: (_,__,___) => Image.network('https://ui-avatars.com/api/?name=${Uri.encodeComponent(dispName)}&background=random', fit: BoxFit.cover))
                                      : Image.network('https://ui-avatars.com/api/?name=${Uri.encodeComponent(dispName)}&background=random', fit: BoxFit.cover),
                                ),
                              ),
                              const SizedBox(width: 12),
                            ],
                            Flexible(
                              child: Column(
                                crossAxisAlignment: isMe ? CrossAxisAlignment.end : CrossAxisAlignment.start,
                                children: [
                                  if (!isMe)
                                    Padding(
                                      padding: const EdgeInsets.only(left: 8, bottom: 6),
                                      child: Text(isShop ? '$dispName 🏪' : dispName, style: TextStyle(color: accentColor, fontSize: 13, fontWeight: FontWeight.w800, letterSpacing: 0.3)),
                                    ),
                                  Container(
                                    decoration: BoxDecoration(
                                      gradient: isMe ? const LinearGradient(colors: [Color(0xFF6366F1), Color(0xFF4F46E5)], begin: Alignment.topLeft, end: Alignment.bottomRight) : null,
                                      color: isMe ? null : Colors.white,
                                      borderRadius: BorderRadius.only(
                                        topLeft: const Radius.circular(20), topRight: const Radius.circular(20),
                                        bottomLeft: Radius.circular(isMe ? 20 : 6),
                                        bottomRight: Radius.circular(isMe ? 6 : 20),
                                      ),
                                      border: isMe ? null : Border.all(color: Colors.grey.shade200, width: 1.5),
                                      boxShadow: [BoxShadow(color: (isMe ? const Color(0xFF6366F1) : Colors.black).withOpacity(0.12), blurRadius: 15, offset: const Offset(0, 5))],
                                    ),
                                    child: contentWidget,
                                  ),
                                  if (timeStr.isNotEmpty)
                                    Padding(
                                      padding: const EdgeInsets.only(top: 6, left: 6, right: 6),
                                      child: Row(mainAxisSize: MainAxisSize.min, children: [
                                        Text(timeStr, style: TextStyle(fontSize: 11, color: Colors.grey.shade500, fontWeight: FontWeight.w700)),
                                        if (isMe) ...[const SizedBox(width: 4), Icon(Icons.done_all_rounded, size: 14, color: const Color(0xFF6366F1).withOpacity(0.9))],
                                      ]),
                                    ),
                                ],
                              ),
                            ),
                            if (isMe) ...[
                              const SizedBox(width: 12),
                              Container(
                                width: 36, height: 36,
                                decoration: BoxDecoration(shape: BoxShape.circle, color: Colors.white, boxShadow: [BoxShadow(color: const Color(0xFF6366F1).withOpacity(0.3), blurRadius: 8, offset: const Offset(0, 3))]),
                                child: ClipOval(
                                  child: driverPhoto.isNotEmpty 
                                      ? Image.network(driverPhoto, fit: BoxFit.cover, errorBuilder: (_,__,___) => const Icon(Icons.person_rounded, color: Color(0xFF6366F1), size: 20))
                                      : const Icon(Icons.person_rounded, color: Color(0xFF6366F1), size: 20),
                                ),
                              ),
                            ],
                          ],
                        ),
                      );
                    },
                  ),
          ),

          // ── Input Bar
          if (!widget.isClosed && _lastOrderState.toLowerCase() != 'returned' && !_lastOrderState.toLowerCase().contains('cancel'))
            Container(
              padding: EdgeInsets.only(left: 16, right: 16, top: 12, bottom: MediaQuery.of(context).padding.bottom + 12),
            decoration: BoxDecoration(
              color: Colors.white,
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.04),
                  blurRadius: 16,
                  offset: const Offset(0, -4),
                )
              ],
            ),
            child: Row(
              children: [
                Expanded(
                  child: Container(
                    decoration: BoxDecoration(
                      color: const Color(0xFFF8FAFC).withOpacity(0.9),
                      borderRadius: BorderRadius.circular(30),
                      border: Border.all(color: Colors.grey.shade200, width: 1.5),
                      boxShadow: [
                        BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 10, offset: const Offset(0, 4), blurStyle: BlurStyle.inner),
                      ],
                    ),
                    child: TextField(
                      controller: _chatController,
                      style: const TextStyle(color: Colors.black87, fontSize: 15, fontWeight: FontWeight.w500),
                      decoration: InputDecoration(
                        hintText: 'message_hint'.tr,
                        hintStyle: TextStyle(color: Colors.grey.shade500, fontSize: 14, fontWeight: FontWeight.w500),
                        border: InputBorder.none,
                        contentPadding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
                      ),
                      onSubmitted: (_) => _sendMessage(),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                GestureDetector(
                  onTap: _isSending ? null : _sendMessage,
                  child: Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(colors: [Color(0xFF6366F1), Color(0xFF4F46E5)], begin: Alignment.topLeft, end: Alignment.bottomRight),
                      shape: BoxShape.circle,
                      boxShadow: [BoxShadow(color: const Color(0xFF6366F1).withOpacity(0.3), blurRadius: 10, offset: const Offset(0, 4))],
                    ),
                    child: _isSending
                        ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2.5))
                        : const Padding(
                            padding: EdgeInsets.only(left: 2), // slightly adjust paper plane icon
                            child: Icon(Icons.send_rounded, color: Colors.white, size: 20),
                          ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildModernProfileBadge(String name, String imageUrl, {required bool isShop, required String phone, VoidCallback? onTap}) {
    final themeColor = isShop ? const Color(0xFFF59E0B) : const Color(0xFF3B82F6);
    final icon = isShop ? Icons.storefront_rounded : Icons.person_rounded;
    
    return Column(
      children: [
        Stack(
          clipBehavior: Clip.none,
          children: [
            GestureDetector(
              onTap: onTap,
              child: Container(
                width: 72,
                height: 72,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: themeColor.withOpacity(0.08),
                  border: Border.all(color: themeColor.withOpacity(0.3), width: 2),
                ),
                child: ClipOval(
                  child: imageUrl.isNotEmpty
                      ? Image.network(
                          imageUrl,
                          fit: BoxFit.cover,
                          errorBuilder: (context, error, stackTrace) => Icon(icon, color: themeColor, size: 32),
                        )
                      : Icon(icon, color: themeColor, size: 32),
                ),
              ),
            ),
            Positioned(
              bottom: -6,
              left: -6,
              child: GestureDetector(
                onTap: onTap,
                child: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    shape: BoxShape.circle,
                    border: Border.all(color: Colors.grey.shade200, width: 1.5),
                    boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 8, offset: const Offset(0, 4))],
                  ),
                  child: Icon(Icons.map_rounded, color: themeColor, size: 14),
                ),
              ),
            ),
            if (phone.isNotEmpty)
              Positioned(
                bottom: -6,
                right: -6,
                child: GestureDetector(
                  onTap: () {
                    _showSpecificCallBottomSheet(name, phone, isShop);
                  },
                  child: Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: const Color(0xFF22C55E),
                      shape: BoxShape.circle,
                      border: Border.all(color: Colors.white, width: 2),
                      boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.1), blurRadius: 8, offset: const Offset(0, 4))],
                    ),
                    child: const Icon(Icons.call, color: Colors.white, size: 14),
                  ),
                ),
              ),
          ],
        ),
        const SizedBox(height: 16),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
          decoration: BoxDecoration(
            color: themeColor.withOpacity(0.1),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Text(
            isShop ? 'shop_label'.tr : 'customer_label'.tr,
            style: TextStyle(fontSize: 9, color: themeColor, fontWeight: FontWeight.w900, letterSpacing: 0.8),
          ),
        ),
        Text(
          name,
          textAlign: TextAlign.center,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: Colors.black87),
        ),
        if (phone.isNotEmpty) ...[
          const SizedBox(height: 2),
          GestureDetector(
            onTap: () => _showSpecificCallBottomSheet(name, phone, isShop),
            child: Text(
              phone,
              textAlign: TextAlign.center,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Colors.grey.shade500),
            ),
          ),
        ],
      ],
    );
  }
}
