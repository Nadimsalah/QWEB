import 'package:dio/dio.dart';
import 'package:shared_preferences/shared_preferences.dart';

class ApiClient {
  final Dio _dio;

  // The base URL extracted from your PHP project's APP_DOMAIN
  static const String baseUrl = 'https://qoon.app/userDriver/UserDriverApi/';

  ApiClient()
      : _dio = Dio(BaseOptions(
          baseUrl: baseUrl,
          connectTimeout: const Duration(seconds: 15),
          receiveTimeout: const Duration(seconds: 15),
        )) {
    // Add interceptors for logging or adding headers automatically
    _dio.interceptors.add(LogInterceptor(responseBody: true, requestBody: true));
  }

  /// Use this helper to send standard POST requests.
  /// PHP usually expects FormData if files are involved, or regular body parameters.
  Future<Response> post(String endpoint, Map<String, dynamic> data) async {
    try {
      // Using FormData as your PHP files use $_POST and $_FILES
      FormData formData = FormData.fromMap(data);
      return await _dio.post(endpoint, data: formData);
    } catch (e) {
      rethrow;
    }
  }

  /// Specific API call for Driver Login
  Future<Map<String, dynamic>> loginDriver(String phone, String password, String token) async {
    final response = await post('LoginDriverJibler.php', {
      'ShopLogName': phone, // As per API_DOCUMENTATION.md
      'ShopPassword': password,
      'ShopFirebaseToken': token,
    });
    return response.data;
  }

  /// Specific API call for Driver Status / Location update
  Future<Map<String, dynamic>> updateLocation(String driverId, double lat, double long) async {
    final response = await post('UpdateDriverPosition.php', {
      'DriverID': driverId,
      'Latitude': lat,
      'Longitude': long,
    });
    return response.data;
  }
}
