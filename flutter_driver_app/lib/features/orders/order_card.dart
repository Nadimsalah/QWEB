import 'package:flutter/material.dart';
import 'models/order_model.dart';

class OrderCard extends StatelessWidget {
  final OrderModel order;
  final VoidCallback onAccept;
  final VoidCallback onIgnore;

  const OrderCard({
    super.key,
    required this.order,
    required this.onAccept,
    required this.onIgnore,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 4,
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  order.restaurantName,
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
                Text(
                  '\$${order.price.toStringAsFixed(2)}',
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.green),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                const Icon(Icons.store, color: Colors.grey, size: 20),
                const SizedBox(width: 8),
                Expanded(child: Text(order.pickupAddress, style: const TextStyle(color: Colors.black87))),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                const Icon(Icons.location_on, color: Colors.red, size: 20),
                const SizedBox(width: 8),
                Expanded(child: Text(order.dropoffAddress, style: const TextStyle(color: Colors.black87))),
              ],
            ),
            const SizedBox(height: 12),
            Text(
              'Distance: ${order.distanceKm.toStringAsFixed(1)} km',
              style: TextStyle(color: Colors.grey.shade600, fontSize: 14),
            ),
            const Divider(height: 32),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: onIgnore,
                    style: OutlinedButton.styleFrom(foregroundColor: Colors.red),
                    child: const Text('Ignore'),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: ElevatedButton(
                    onPressed: onAccept,
                    style: ElevatedButton.styleFrom(backgroundColor: Colors.green, foregroundColor: Colors.white),
                    child: const Text('Accept'),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
