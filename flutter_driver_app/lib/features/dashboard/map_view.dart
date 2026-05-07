import 'package:flutter/material.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:geolocator/geolocator.dart';

class DriverMapView extends StatefulWidget {
  const DriverMapView({super.key});

  @override
  State<DriverMapView> createState() => _DriverMapViewState();
}

class _DriverMapViewState extends State<DriverMapView> {
  GoogleMapController? _mapController;
  Position? _currentPosition;
  Set<Marker> _markers = {};

  static const CameraPosition _initialPosition = CameraPosition(
    target: LatLng(33.5731, -7.5898), // Default to Casablanca, Morocco as placeholder
    zoom: 14.0,
  );

  @override
  void initState() {
    super.initState();
    _getCurrentLocation();
  }

  Future<void> _getCurrentLocation() async {
    try {
      Position position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      );
      setState(() {
        _currentPosition = position;
        _updateMarker();
      });

      _mapController?.animateCamera(
        CameraUpdate.newCameraPosition(
          CameraPosition(
            target: LatLng(position.latitude, position.longitude),
            zoom: 16.0,
          ),
        ),
      );
    } catch (e) {
      debugPrint("Error getting location for map: $e");
    }
  }

  void _updateMarker() {
    if (_currentPosition == null) return;
    setState(() {
      _markers = {
        Marker(
          markerId: const MarkerId('driver_location'),
          position: LatLng(_currentPosition!.latitude, _currentPosition!.longitude),
          icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueBlue),
          infoWindow: const InfoWindow(title: 'You are here'),
        )
      };
    });
  }

  @override
  Widget build(BuildContext context) {
    return GoogleMap(
      initialCameraPosition: _initialPosition,
      onMapCreated: (controller) => _mapController = controller,
      markers: _markers,
      myLocationEnabled: true,
      myLocationButtonEnabled: true,
      zoomControlsEnabled: false,
    );
  }
}
