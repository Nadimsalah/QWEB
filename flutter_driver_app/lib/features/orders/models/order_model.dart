class OrderModel {
  final String id;
  final String restaurantName;
  final String pickupAddress;
  final String dropoffAddress;
  final double price;
  final double distanceKm;
  final String status;

  OrderModel({
    required this.id,
    required this.restaurantName,
    required this.pickupAddress,
    required this.dropoffAddress,
    required this.price,
    required this.distanceKm,
    required this.status,
  });

  factory OrderModel.fromJson(Map<String, dynamic> json) {
    return OrderModel(
      id: json['id']?.toString() ?? '',
      restaurantName: json['shop_name'] ?? 'Unknown Restaurant',
      pickupAddress: json['pickup_address'] ?? '',
      dropoffAddress: json['dropoff_address'] ?? '',
      price: double.tryParse(json['delivery_price']?.toString() ?? '0') ?? 0.0,
      distanceKm: double.tryParse(json['distance']?.toString() ?? '0') ?? 0.0,
      status: json['status'] ?? 'pending',
    );
  }
}
