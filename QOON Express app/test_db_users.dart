import 'dart:convert';
import 'package:http/http.dart' as http;

void main() async {
  for (int i = 1; i <= 50; i++) {
    try {
      final request = http.MultipartRequest(
        'POST',
        Uri.parse('https://qoon.app/userDriver/UserDriverApi/GetDriverInfo.php'),
      );
      request.fields['DriverID'] = i.toString();

      final response = await request.send();
      if (response.statusCode == 200) {
        final responseData = await response.stream.bytesToString();
        final decoded = json.decode(responseData);
        if (decoded['success'] == true) {
            print(responseData);
        }
      }
    } catch (e) {
      // ignore
    }
  }
}
