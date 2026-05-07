import 'dart:async';
import 'package:geolocator/geolocator.dart';
import 'package:flutter/foundation.dart';
import 'api_client.dart';

class LocationService {
  final ApiClient _apiClient;
  StreamSubscription<Position>? _positionStream;

  LocationService(this._apiClient);

  /// Requests permissions and ensures location services are enabled
  Future<bool> initialize() async {
    bool serviceEnabled;
    LocationPermission permission;

    serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      debugPrint('Location services are disabled.');
      return false;
    }

    permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied) {
        debugPrint('Location permissions are denied');
        return false;
      }
    }

    if (permission == LocationPermission.deniedForever) {
      debugPrint('Location permissions are permanently denied.');
      return false;
    }

    return true;
  }

  /// Starts listening to the driver's location and sends it to the API
  void startTracking(String driverId) {
    if (_positionStream != null) return;

    final locationSettings = const LocationSettings(
      accuracy: LocationAccuracy.high,
      distanceFilter: 10, // Updates every 10 meters
    );

    _positionStream = Geolocator.getPositionStream(locationSettings: locationSettings)
        .listen((Position? position) {
      if (position != null) {
        debugPrint('Driver moved to: ${position.latitude}, ${position.longitude}');
        _sendLocationToApi(driverId, position.latitude, position.longitude);
      }
    });
  }

  /// Stops background tracking
  void stopTracking() {
    _positionStream?.cancel();
    _positionStream = null;
  }

  Future<void> _sendLocationToApi(String driverId, double lat, double long) async {
    try {
      await _apiClient.updateLocation(driverId, lat, long);
    } catch (e) {
      debugPrint('Failed to update location on server: $e');
    }
  }
}
