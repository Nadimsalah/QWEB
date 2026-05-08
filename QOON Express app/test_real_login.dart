import 'dart:convert';
import 'package:http/http.dart' as http;

void main() async {
  // Test with the exact credentials the user is likely trying
  final tests = [
    ['661337052', '12345678'],
    ['0661337052', '12345678'],
    ['+212661337052', '12345678'],
  ];
  
  for (final test in tests) {
    final request = http.MultipartRequest(
      'POST',
      Uri.parse('https://qoon.app/userDriver/UserDriverApi/LoginDriverJibler.php'),
    );
    request.fields['DriverPhone'] = test[0];
    request.fields['DriverPassword'] = test[1];
    request.fields['FirebaseDriverToken'] = 'dummy';
    
    final response = await request.send();
    final body = await response.stream.bytesToString();
    final decoded = json.decode(body);
    print('Phone: ${test[0]} → success: ${decoded['success']}');
  }
}
