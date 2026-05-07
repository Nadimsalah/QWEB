import 'package:flutter/material.dart';
import 'models/order_model.dart';
import 'order_card.dart';

class LiveOrdersList extends StatelessWidget {
  final List<OrderModel> orders;
  final bool isLoading;
  final Function(OrderModel) onAccept;
  final Function(OrderModel) onIgnore;

  const LiveOrdersList({
    super.key,
    required this.orders,
    required this.isLoading,
    required this.onAccept,
    required this.onIgnore,
  });

  @override
  Widget build(BuildContext context) {
    if (isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (orders.isEmpty) {
      return const Center(
        child: Text(
          'No live orders nearby.',
          style: TextStyle(fontSize: 16, color: Colors.grey),
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.only(top: 8, bottom: 80),
      itemCount: orders.length,
      itemBuilder: (context, index) {
        final order = orders[index];
        return OrderCard(
          order: order,
          onAccept: () => onAccept(order),
          onIgnore: () => onIgnore(order),
        );
      },
    );
  }
}
