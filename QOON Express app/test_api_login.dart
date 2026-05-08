import 'dart:convert';
import 'package:http/http.dart' as http;

void main() async {
  final request = http.MultipartRequest(
    'POST',
    Uri.parse('https://qoon.app/userDriver/UserDriverApi/LoginDriverJibler.php'),
  );
  request.fields['DriverPhone'] = '+212661337052';
  request.fields['DriverPassword'] = '12345678';
  request.fields['FirebaseDriverToken'] = 'dummy';

  final response = await request.send();
  final responseData = await response.stream.bytesToString();
  print(responseData);
}
