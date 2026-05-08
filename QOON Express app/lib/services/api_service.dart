import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiService {
  static const String baseUrl = 'https://qoon.app/userDriver/UserDriverApi';
  
  // In-memory storage — persists as long as the app process is alive
  static String? _driverId;
  static String _driverLat = '33.5731';  // fallback: Casablanca
  static String _driverLng = '-7.5898';
  static String firebaseDriverToken = 'dummy_token';

  static String resolvePhotoUrl(String? path) {
    if (path == null || path.isEmpty || path == 'NONE' || path == '0' || path == 'null') {
      return '';
    }
    
    String result = '';
    if (path.startsWith('http')) {
      result = path;
    } else {
      // Remove leading slash if present
      String cleanPath = path.startsWith('/') ? path.substring(1) : path;
      
      // Most photos are in the root /photo/ directory
      if (cleanPath.startsWith('photo/')) {
        cleanPath = cleanPath.substring(6);
      }
      result = 'https://qoon.app/photo/$cleanPath';
    }
    
    // DEBUG PRINT - This will show up in your terminal
    debugPrint('📸 PHOTO_URL: $result');
    return result;
  }

  static Future<String?> getDriverId() async {
    if (_driverId != null) return _driverId;
    final prefs = await SharedPreferences.getInstance();
    _driverId = prefs.getString('driver_id');
    return _driverId;
  }

  static Future<void> setDriverId(String id, {String? lat, String? lng}) async {
    _driverId = id;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('driver_id', id);
    // For testing purposes, we hardcode the driver's location to Casablanca
    // because the driver's profile in the DB is currently set to Egypt,
    // which causes the server to filter out all the nearby orders in Morocco.
    _driverLat = '33.5731';
    _driverLng = '-7.5898';
  }

  static String get driverLat => _driverLat;
  static String get driverLng => _driverLng;

  static void updateDriverLocation(String lat, String lng) {
    _driverLat = lat;
    _driverLng = lng;
  }

  static String? _cachedDriverPhoto;
  static String? _cachedDriverName;

  static Future<void> loadCachedDriverData() async {
    final prefs = await SharedPreferences.getInstance();
    _cachedDriverPhoto = prefs.getString('driver_photo');
    _cachedDriverName = prefs.getString('driver_name');
  }

  static Future<void> cacheDriverData(String name, String photo) async {
    _cachedDriverName = name;
    _cachedDriverPhoto = photo;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('driver_name', name);
    await prefs.setString('driver_photo', photo);
  }

  static String? get cachedDriverPhoto => _cachedDriverPhoto;
  static String? get cachedDriverName => _cachedDriverName;

  static Future<void> logout() async {
    _driverId = null;
    _cachedDriverPhoto = null;
    _cachedDriverName = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('driver_id');
    await prefs.remove('driver_photo');
    await prefs.remove('driver_name');
  }

  static Future<Map<String, dynamic>?> login(String phone, String password) async {
    try {
      final request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/LoginDriverJibler.php'),
      );
      request.fields['DriverPhone'] = phone;
      request.fields['DriverPassword'] = password;
      request.fields['FirebaseDriverToken'] = firebaseDriverToken;

      final response = await request.send();
      if (response.statusCode == 200) {
        final responseData = await response.stream.bytesToString();
        print('Login response: $responseData');
        final decoded = json.decode(responseData);
        if (decoded is Map && decoded['data'] != null) {
          return decoded['data'];
        }
        return decoded;
      }
    } catch (e) {
      print('Login error: $e');
    }
    return null;
  }

  static Future<List<dynamic>> getNearOrders(String lat, String lng) async {
    try {
      final response = await http.get(
        Uri.parse('https://qoon.app/api_driver_orders.php?lat=$lat&lng=$lng'),
      ).timeout(const Duration(seconds: 12));
      debugPrint('NEAR ORDERS RESP: ${response.body}');
      if (response.statusCode == 200) {
        final decoded = json.decode(response.body);
        if (decoded is Map && decoded['data'] != null) {
          return _resolveOrderListPhotos(decoded['data']);
        }
      }
    } catch (e) {
      print('Near orders error: $e');
    }
    return [];
  }

  static Future<Map<String, dynamic>?> getWallet(String driverId) async {
    try {
      final request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/GetDriverWallet.php'),
      );
      request.fields['DriverID'] = driverId;

      final response = await request.send().timeout(const Duration(seconds: 12));
      if (response.statusCode == 200) {
        final responseData = await response.stream.bytesToString();
        print('Wallet response: $responseData');
        final decoded = json.decode(responseData);
        if (decoded is Map && decoded['data'] != null) {
          return decoded['data'];
        }
        return decoded;
      }
    } catch (e) {
      print('Wallet error: $e');
    }
    return null;
  }

  static Future<List<dynamic>> getTransactions(String driverId, {int page = 0}) async {
    try {
      final request = http.MultipartRequest(
        'POST',
        Uri.parse('https://qoon.app/api_driver_transactions.php'),
      );
      request.fields['DriverID'] = driverId;
      request.fields['Page'] = page.toString();

      final response = await request.send().timeout(const Duration(seconds: 12));
      if (response.statusCode == 200) {
        final responseData = await response.stream.bytesToString();
        final decoded = json.decode(responseData);
        if (decoded is List) return decoded;
        if (decoded is Map && decoded['data'] != null) return decoded['data'];
      }
    } catch (e) {
      print('Transaction error: $e');
    }
    return [];
  }

  static Future<Map<String, dynamic>?> getProfile(String driverId) async {
    try {
      final request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/GetDriverInfo.php'),
      );
      request.fields['DriverID'] = driverId;

      final response = await request.send().timeout(const Duration(seconds: 12));
      if (response.statusCode == 200) {
        final responseData = await response.stream.bytesToString();
        final decoded = json.decode(responseData);
        print('PROFILE RESP: $responseData');
        if (decoded is Map && decoded['data'] != null) {
          var profile = Map<String, dynamic>.from(decoded['data']);
          profile['PersonalPhoto'] = resolvePhotoUrl(profile['PersonalPhoto']);
          profile['NationalIDPhoto'] = resolvePhotoUrl(profile['NationalIDPhoto']);
          profile['CarPhoto'] = resolvePhotoUrl(profile['CarPhoto']);
          profile['licensePhoto'] = resolvePhotoUrl(profile['licensePhoto']);
          
          final fname = profile['FName']?.toString() ?? 'Driver';
          final pphoto = profile['PersonalPhoto']?.toString() ?? '';
          cacheDriverData(fname, pphoto);
          
          return profile;
        }
        return decoded;
      }
    } catch (e) {
      print('Profile error: $e');
    }
    return null;
  }

  static Future<void> setActiveOffer(String orderId, String price) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('active_offer_order_id', orderId);
    await prefs.setString('active_offer_price', price);
    await prefs.setInt('active_offer_timestamp', DateTime.now().millisecondsSinceEpoch);
  }

  static Future<Map<String, dynamic>?> getActiveOffer() async {
    final prefs = await SharedPreferences.getInstance();
    final orderId = prefs.getString('active_offer_order_id');
    final price = prefs.getString('active_offer_price');
    final timestamp = prefs.getInt('active_offer_timestamp');

    if (orderId != null && price != null && timestamp != null) {
      // Check if offer is still valid (e.g., within 2 minutes = 120000 ms)
      final now = DateTime.now().millisecondsSinceEpoch;
      if (now - timestamp < 120000) {
        return {'orderId': orderId, 'price': price, 'timestamp': timestamp};
      } else {
        // Expired
        await clearActiveOffer();
      }
    }
    return null;
  }

  static Future<void> clearActiveOffer() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('active_offer_order_id');
    await prefs.remove('active_offer_price');
    await prefs.remove('active_offer_timestamp');
  }

  // Returns {success, message} so the caller can show proper feedback
  static Future<Map<String, dynamic>> sendOffer(String orderId, String driverId, String price) async {
    try {
      final request = http.MultipartRequest(
        'POST',
        Uri.parse('https://qoon.app/addoffer.php'), // root path, NOT inside userDriver/
      );
      request.fields['DriverID'] = driverId;
      request.fields['OrderId'] = orderId;
      request.fields['Price'] = price;
      request.fields['AppType'] = 'QOON';
      request.fields['OrderType'] = '';

      final response = await request.send().timeout(const Duration(seconds: 20));
      if (response.statusCode == 200) {
        final responseData = await response.stream.bytesToString();
        print('Send offer response: $responseData');
        final decoded = json.decode(responseData);
        final bool success = decoded['success'] == true;
        if (success) {
          await setActiveOffer(orderId, price);
        }
        final String message = decoded['message']?.toString() ?? (success ? 'Offer sent!' : 'Failed to send offer.');
        return {'success': success, 'message': message};
      }
    } catch (e) {
      print('Send offer error: $e');
    }
    return {'success': false, 'message': 'Network error. Please try again.'};
  }

  static Future<Map<String, dynamic>?> getOrderStatus(String orderId) async {
    try {
      final request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/GetOneOrdersDetails.php'),
      );
      request.fields['OrderID'] = orderId;

      final response = await request.send().timeout(const Duration(seconds: 12));
      if (response.statusCode == 200) {
        final responseData = await response.stream.bytesToString();
        // Don't print this to avoid spamming the console during polling
        final decoded = json.decode(responseData);
        if (decoded is Map && decoded['data'] != null) {
          var order = Map<String, dynamic>.from(decoded['data']);
          order['DestnationPhoto'] = resolvePhotoUrl(order['DestnationPhoto']?.toString());
          order['ShopLogo'] = resolvePhotoUrl(order['ShopLogo']?.toString());
          order['UserPhoto'] = resolvePhotoUrl(order['UserPhoto']?.toString());
          return order;
        }
        return decoded;
      }
    } catch (e) {
      print('Get order status error: $e');
    }
    return null;
  }
  static Future<List<dynamic>> getDriveLiveOrders(String driverId) async {
    try {
      final request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/getDriveLiveOrders.php'),
      );
      request.fields['DelvryId'] = driverId;

      final response = await request.send().timeout(const Duration(seconds: 12));
      if (response.statusCode == 200) {
        final responseData = await response.stream.bytesToString();
        final decoded = json.decode(responseData);
        if (decoded is Map && decoded['data'] != null) {
          return _resolveOrderListPhotos(decoded['data']);
        }
      }
    } catch (e) {
      print('Get live orders error: $e');
    }
    return [];
  }

  static Future<List<dynamic>> getCurrentOrders(String driverId, {int page = 0}) async {
    try {
      final request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/getDriveCurrentOrders.php'),
      );
      request.fields['DelvryId'] = driverId;
      request.fields['Page'] = page.toString();

      final response = await request.send().timeout(const Duration(seconds: 12));
      if (response.statusCode == 200) {
        final responseData = await response.stream.bytesToString();
        final decoded = json.decode(responseData);
        if (decoded is Map && decoded['data'] != null) {
          return _resolveOrderListPhotos(decoded['data']);
        }
      }
    } catch (e) {
      print('Get current orders error: $e');
    }
    return [];
  }

  static Future<Map<String, dynamic>?> getDriverStats(String driverId) async {
    try {
      final request = http.MultipartRequest(
        'POST',
        Uri.parse('https://qoon.app/api_driver_stats.php'),
      );
      request.fields['DriverID'] = driverId;

      final response = await request.send().timeout(const Duration(seconds: 12));
      if (response.statusCode == 200) {
        final responseData = await response.stream.bytesToString();
        print('STATS RESP: $responseData');
        final decoded = json.decode(responseData);
        if (decoded is Map && decoded['data'] != null) {
          final statsData = Map<String, dynamic>.from(decoded['data']);
          
          // Fetch profile to get total orders
          try {
            final profile = await getProfile(driverId);
            if (profile != null && profile.containsKey('DriverOrdersNum')) {
              statsData['totalTrips'] = profile['DriverOrdersNum'];
            }
          } catch (e) {
            print('Error fetching profile for totalTrips: $e');
          }
          
          return statsData;
        }
      }
    } catch (e) {
      print('Stats error: $e');
    }
    return null;
  }

  static Future<Map<String, dynamic>> updateProfile({
    required String driverId,
    required String fname,
    required String lname,
    required String email,
    required String phone,
    required String city,
    required String age,
    String? personalPhotoBase64,
    String? nidPhotoBase64,
    String? carPhotoBase64,
    String? licensePhotoBase64,
  }) async {
    try {
      final request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/UpdateDriverProfile.php'),
      );
      request.fields['DriverId'] = driverId;
      request.fields['Fname'] = fname;
      request.fields['LName'] = lname;
      request.fields['DriverEmail'] = email;
      request.fields['DriverPhone'] = phone;
      request.fields['City'] = city;
      request.fields['AGE'] = age;

      if (personalPhotoBase64 != null) request.fields['PersonalPhoto'] = personalPhotoBase64;
      if (nidPhotoBase64 != null) request.fields['NationalIDPhoto'] = nidPhotoBase64;
      if (carPhotoBase64 != null) request.fields['CarPhoto'] = carPhotoBase64;
      if (licensePhotoBase64 != null) request.fields['licensePhoto'] = licensePhotoBase64;

      final response = await request.send().timeout(const Duration(seconds: 40));
      if (response.statusCode == 200) {
        final responseData = await response.stream.bytesToString();
        print('Update profile response: $responseData');
        final data = json.decode(responseData);
        if (data['data'] != null) {
          var driver = Map<String, dynamic>.from(data['data']);
          driver['PersonalPhoto'] = resolvePhotoUrl(driver['PersonalPhoto']);
          driver['NationalIDPhoto'] = resolvePhotoUrl(driver['NationalIDPhoto']);
          driver['CarPhoto'] = resolvePhotoUrl(driver['CarPhoto']);
          driver['licensePhoto'] = resolvePhotoUrl(driver['licensePhoto']);
          data['data'] = driver;
        }
        return data;
      }
    } catch (e) {
      print('Update profile error: $e');
    }
    return {'success': false, 'message': 'Network error. Update failed.'};
  }

  static List<dynamic> _resolveOrderListPhotos(List<dynamic> orders) {
    return orders.map((order) {
      if (order is Map) {
        var o = Map<String, dynamic>.from(order);
        o['UserPhoto'] = resolvePhotoUrl(o['UserPhoto']?.toString());
        // Resolve both photo fields, use DestnationPhoto as fallback for ShopLogo (which can be null)
        final destnationPhoto = resolvePhotoUrl(o['DestnationPhoto']?.toString());
        final shopLogo = resolvePhotoUrl(o['ShopLogo']?.toString());
        o['DestnationPhoto'] = destnationPhoto;
        o['ShopLogo'] = shopLogo.isNotEmpty ? shopLogo : destnationPhoto;
        o['PersonalPhoto'] = resolvePhotoUrl(o['PersonalPhoto']?.toString());
        return o;
      }
      return order;
    }).toList();
  }
}
