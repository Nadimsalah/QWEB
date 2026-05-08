import 'package:http/http.dart' as http;
import 'dart:convert';

void main() async {
  final driverId = "140"; 
  final urls = [
    'https://qoon.app/api_driver_stats.php',
    'https://qoon.app/userDriver/UserDriverApi/GetDriverInfo.php',
    'https://qoon.app/userDriver/UserDriverApi/GetDriverWallet.php'
  ];

  for (var url in urls) {
    print('\nFetching $url');
    try {
      final req = http.MultipartRequest('POST', Uri.parse(url));
      req.fields['DriverID'] = driverId;
      final res = await req.send();
      final body = await res.stream.bytesToString();
      print('Response: $body');
    } catch(e) {
      print('Error: $e');
    }
  }
}
