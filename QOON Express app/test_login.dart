import 'dart:convert';
import 'package:http/http.dart' as http;

void main() async {
  try {
    final request = http.MultipartRequest(
      'POST',
      Uri.parse('https://qoon.app/userDriver/UserDriverApi/LoginDriverJibler.php'),
    );
    request.fields['DriverPhone'] = 'invalid_number';
    request.fields['DriverPassword'] = 'invalid_password';
    request.fields['FirebaseDriverToken'] = 'dummy_token';

    final response = await request.send();
    print('HTTP Status: ${response.statusCode}');
    final responseData = await response.stream.bytesToString();
    print('Raw Response:');
    print(responseData);
  } catch (e) {
    print('Exception: $e');
  }
}
