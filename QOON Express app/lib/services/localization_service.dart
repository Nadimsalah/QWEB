import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

class LocalizationService extends ChangeNotifier {
  static const String _langKey = 'app_lang';
  String _currentLanguage = 'en';
  
  String get currentLanguage => _currentLanguage;

  LocalizationService() {
    _loadLanguage();
  }

  Future<void> _loadLanguage() async {
    final prefs = await SharedPreferences.getInstance();
    _currentLanguage = prefs.getString(_langKey) ?? 'en';
    notifyListeners();
  }

  Future<void> setLanguage(String langCode) async {
    _currentLanguage = langCode;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_langKey, langCode);
    notifyListeners();
  }

  // Very simple dictionary mapping
  static final Map<String, Map<String, String>> _localizedValues = {
    'en': {
      // General
      'language': 'Language',
      'english': 'English',
      'french': 'French',
      'arabic': 'Arabic',
      'no_internet': 'No Internet Connection',
      'home': 'Home',
      'orders': 'Orders',
      'wallet': 'Wallet',
      'profile': 'Profile',
      
      // Profile Page
      'logout': 'Logout',
      'settings': 'Settings',
      'personal_info': 'Personal Info',
      'support': 'Support',
      
      // Orders
      'my_orders': 'My Orders',
      'active_order': 'Active Order',
      'delivered': 'Delivered',
      'cancelled': 'Cancelled',
      
      // Common actions
      'cancel': 'Cancel',
      'confirm': 'Confirm',
      'save': 'Save',
      'account_settings': 'Account Settings',
      'vehicle_details': 'Vehicle Details',
      'personal_information': 'Personal Information',
      'help_center': 'Help Center',
      'privacy_policy': 'Privacy Policy',
      'support_legal': 'Support & Legal',
      'rating': 'rating',
      // Wallet
      'my_wallet': 'My Wallet',
      'available_balance': 'Available Balance',
      'cash_debt': 'Cash Debt',
      'limit': 'Limit',
      'trips': 'Trips',
      'total_trips': 'Total Trips',
      'total': 'Total',
      'recent_transactions': 'Recent Transactions',
      'no_transactions': 'No transactions yet',
      'no_matches': 'No matches found',
      'clear_filters': 'Clear all filters',
      'search_hint': 'Search by Shop Name or ID...',
      // History
      'order_history': 'Order History',
      'history_search_hint': 'Search order ID or shop...',
      'no_history': 'No history yet',
      'history_empty_desc': 'Your completed deliveries will appear here.',
      'try_clear_filters': 'Try clearing your filters',
      'returned': 'Returned',
      // Home / Orders
      'online': 'Online',
      'offline': 'Offline',
      'welcome_back': 'Welcome back,',
      'tab_available': 'Available',
      'tab_active': 'Active',
      'deliver_to': 'Deliver to',
      'send_offer': 'Send Offer',
      'hide': 'Hide',
      'make_offer': 'Make an Offer',
      'delivery_fee': 'Delivery Fee (MAD)',
      'send_offer_btn': 'SEND OFFER',
      'no_orders': 'No orders available',
      'scanning': "We're scanning for new orders near you.",
      'you_offline': 'You are offline',
      'go_online': 'Go online to receive new orders.',
      'account_restricted': 'Account Restricted',
      'resolve_debt': 'RESOLVE DEBT NOW',
      'cash_collected': 'Cash Collected',
      'online_earnings': 'Online Earnings',
      'total_owed': 'Total Owed',
      'debt_limit_title': 'Debt Limit Reached',
      // Waiting Zone
      'waiting_for_response': 'Waiting for Response',
      'offer_sent_prefix': 'Your offer of DH ',
      'offer_sent_suffix': ' has been sent.\nWaiting for the customer to respond...',
      'offer_accepted': '🎉 Offer Accepted!',
      'offer_accepted_desc': 'The customer chose you.\nGet ready for the delivery!',
      'another_driver': 'Another Driver Was Chosen',
      'another_driver_desc': 'The customer accepted a different\ndriver\'s offer. Better luck next time!',
      'order_cancelled': 'Order Cancelled',
      'order_cancelled_desc': 'The customer cancelled their order.\nLook for the next one!',
      'offer_expired': 'Offer Expired',
      'offer_expired_desc': 'The customer didn\'t respond in time.\nYour offer has been withdrawn.',
      'going_back': 'Going back automatically...',
      'back_to_orders': 'Back to Orders',
      'resend_offer': 'Resend Another Offer',
      'view_active_order': 'View Active Order',
      // Active Order / Chat
      'active_order': 'Active Order',
      'return_pin': 'RETURN PIN',
      'pickup_pin': 'PICKUP PIN',
      'no_internet': 'No Internet Connection',
      'status_label': 'Status: ',
      'mark_on_way': 'Mark as On Way',
      'mark_delivered': 'Mark as Delivered',
      'cancel_delivery': 'Cancel Delivery',
      'order_summary': 'Order Summary',
      'qty': 'QTY',
      'item': 'ITEM',
      'message_hint': 'Message...',
      'shop_label': 'SHOP',
      'customer_label': 'CUSTOMER',
      'live_location': 'Live Location',
      'tap_to_navigate': 'Tap to navigate',
      'you': 'You',
      // Order Statuses
      'status_doing': 'Doing',
      'status_order_pickup': 'Order Pickup',
      'status_picked': 'Picked Up',
      'status_ready': 'Picked Up',
      'status_arrived_at_shop': 'Arrived at Shop',
      'status_on_way': 'On Way',
      'status_on_the_way': 'On the Way',
      'status_come_to_take_it': 'Come to Take it',
      'status_found': 'Found',
      'status_arrived': 'Arrived',
      'status_delivered': 'Delivered',
      'status_cancelled': 'Cancelled',
      'status_returned': 'Returned',
    },
    'fr': {
      // General
      'language': 'Langue',
      'english': 'Anglais',
      'french': 'Français',
      'arabic': 'Arabe',
      'no_internet': 'Pas de connexion Internet',
      'home': 'Accueil',
      'orders': 'Commandes',
      'wallet': 'Portefeuille',
      'profile': 'Profil',
      
      // Profile Page
      'logout': 'Déconnexion',
      'settings': 'Paramètres',
      'personal_info': 'Infos personnelles',
      'support': 'Support',
      
      // Orders
      'my_orders': 'Mes Commandes',
      'active_order': 'Commande active',
      'delivered': 'Livré',
      'cancelled': 'Annulé',
      
      // Common actions
      'cancel': 'Annuler',
      'confirm': 'Confirmer',
      'save': 'Enregistrer',
      'account_settings': 'Paramètres du compte',
      'vehicle_details': 'Détails du véhicule',
      'personal_information': 'Informations personnelles',
      'help_center': 'Centre d\'aide',
      'privacy_policy': 'Politique de confidentialité',
      'support_legal': 'Support & Légal',
      'rating': 'évaluation',
      // Wallet
      'my_wallet': 'Mon Portefeuille',
      'available_balance': 'Solde Disponible',
      'cash_debt': 'Dette en Espèces',
      'limit': 'Limite',
      'trips': 'Courses',
      'total_trips': 'Total des Courses',
      'total': 'Total',
      'recent_transactions': 'Transactions Récentes',
      'no_transactions': 'Aucune transaction',
      'no_matches': 'Aucun résultat',
      'clear_filters': 'Effacer les filtres',
      'search_hint': 'Rechercher par nom de boutique ou ID...',
      // History
      'order_history': 'Historique des commandes',
      'history_search_hint': 'Rechercher ID ou boutique...',
      'no_history': 'Aucun historique',
      'history_empty_desc': 'Vos livraisons complétées apparaîtront ici.',
      'try_clear_filters': 'Essayez de supprimer vos filtres',
      'returned': 'Retourné',
      // Home / Orders
      'online': 'En ligne',
      'offline': 'Hors ligne',
      'welcome_back': 'Bon retour,',
      'tab_available': 'Disponibles',
      'tab_active': 'En cours',
      'deliver_to': 'Livrer à',
      'send_offer': 'Envoyer une offre',
      'hide': 'Masquer',
      'make_offer': 'Faire une offre',
      'delivery_fee': 'Frais de livraison (MAD)',
      'send_offer_btn': 'ENVOYER L\'OFFRE',
      'no_orders': 'Aucune commande disponible',
      'scanning': 'Nous recherchons des commandes proches.',
      'you_offline': 'Vous êtes hors ligne',
      'go_online': 'Passez en ligne pour recevoir des commandes.',
      'account_restricted': 'Compte restreint',
      'resolve_debt': 'RÉGLER LA DETTE',
      'cash_collected': 'Espèces collectées',
      'online_earnings': 'Gains en ligne',
      'total_owed': 'Total dû',
      'debt_limit_title': 'Limite de dette atteinte',
      // Waiting Zone
      'waiting_for_response': 'En attente de réponse',
      'offer_sent_prefix': 'Votre offre de DH ',
      'offer_sent_suffix': ' a été envoyée.\nEn attente de la réponse du client...',
      'offer_accepted': '🎉 Offre Acceptée!',
      'offer_accepted_desc': 'Le client vous a choisi.\nPréparez-vous pour la livraison!',
      'another_driver': 'Un autre chauffeur a été choisi',
      'another_driver_desc': 'Le client a accepté l\'offre d\'un autre\nchauffeur. Bonne chance la prochaine fois!',
      'order_cancelled': 'Commande Annulée',
      'order_cancelled_desc': 'Le client a annulé sa commande.\nCherchez la suivante!',
      'offer_expired': 'Offre Expirée',
      'offer_expired_desc': 'Le client n\'a pas répondu à temps.\nVotre offre a été retirée.',
      'going_back': 'Retour automatique...',
      'back_to_orders': 'Retour aux commandes',
      'resend_offer': 'Renvoyer une autre offre',
      'view_active_order': 'Voir la commande active',
      // Active Order / Chat
      'active_order': 'Commande active',
      'return_pin': 'CODE RETOUR',
      'pickup_pin': 'CODE RETRAIT',
      'no_internet': 'Pas de connexion Internet',
      'status_label': 'Statut : ',
      'mark_on_way': 'Marquer en route',
      'mark_delivered': 'Marquer comme livré',
      'cancel_delivery': 'Annuler la livraison',
      'order_summary': 'Résumé de la commande',
      'qty': 'QTÉ',
      'item': 'ARTICLE',
      'message_hint': 'Message...',
      'shop_label': 'BOUTIQUE',
      'customer_label': 'CLIENT',
      'live_location': 'Position en direct',
      'tap_to_navigate': 'Appuyez pour naviguer',
      'you': 'Vous',
      // Order Statuses
      'status_doing': 'En cours',
      'status_order_pickup': 'Récupération',
      'status_picked': 'Récupéré',
      'status_ready': 'Récupéré',
      'status_arrived_at_shop': 'Arrivé au magasin',
      'status_on_way': 'En route',
      'status_on_the_way': 'En route',
      'status_come_to_take_it': 'À récupérer',
      'status_found': 'Trouvé',
      'status_arrived': 'Arrivé',
      'status_delivered': 'Livré',
      'status_cancelled': 'Annulé',
      'status_returned': 'Retourné',
    },
    'ar': {
      // General
      'language': 'اللغة',
      'english': 'الإنجليزية',
      'french': 'الفرنسية',
      'arabic': 'العربية (المغربية)',
      'no_internet': 'لا يوجد اتصال بالإنترنت',
      'home': 'الرئيسية',
      'orders': 'الطلبات',
      'wallet': 'المحفظة',
      'profile': 'حسابي',
      
      // Profile Page
      'logout': 'تسجيل الخروج',
      'settings': 'الإعدادات',
      'personal_info': 'المعلومات الشخصية',
      'support': 'الدعم',
      
      // Orders
      'my_orders': 'طلباتي',
      'active_order': 'الطلب الحالي',
      'delivered': 'تم التوصيل',
      'cancelled': 'ملغى',
      
      // Common actions
      'cancel': 'إلغاء',
      'confirm': 'تأكيد',
      'save': 'حفظ',
      'account_settings': 'إعدادات الحساب',
      'vehicle_details': 'تفاصيل المركبة',
      'personal_information': 'المعلومات الشخصية',
      'help_center': 'مركز المساعدة',
      'privacy_policy': 'سياسة الخصوصية',
      'support_legal': 'الدعم والقانوني',
      'rating': 'التقييم',
      // Wallet
      'my_wallet': 'محفظتي',
      'available_balance': 'الرصيد المتاح',
      'cash_debt': 'الدين نقداً',
      'limit': 'الحد الأقصى',
      'trips': 'الرحلات',
      'total_trips': 'إجمالي الرحلات',
      'total': 'الإجمالي',
      'recent_transactions': 'آخر المعاملات',
      'no_transactions': 'لا توجد معاملات بعد',
      'no_matches': 'لا توجد نتائج',
      'clear_filters': 'مسح كل الفلاتر',
      'search_hint': 'البحث باسم المتجر أو المعرف ...',
      // History
      'order_history': 'سجل الطلبيات',
      'history_search_hint': 'البحث برقم الطلب أو المتجر',
      'no_history': 'لا يوجد سجل بعد',
      'history_empty_desc': 'ستظهر توصيلاتك المكتملة هنا.',
      'try_clear_filters': 'جرب مسح الفلاتر',
      'returned': 'معاد',
      // Home / Orders
      'online': 'متصل',
      'offline': 'غير متصل',
      'welcome_back': 'مرحباً بعودتك,',
      'tab_available': 'متاحة',
      'tab_active': 'نشطة',
      'deliver_to': 'توصيل إلى',
      'send_offer': 'إرسال عرض',
      'hide': 'إخفاء',
      'make_offer': 'تقديم عرض',
      'delivery_fee': 'رسوم التوصيل (MAD)',
      'send_offer_btn': 'إرسال العرض',
      'no_orders': 'لا توجد طلبيات متاحة',
      'scanning': 'نبحث عن طلبيات قريبة منك.',
      'you_offline': 'أنت غير متصل',
      'go_online': 'اتصل لاستقبال طلبيات.',
      'account_restricted': 'الحساب مقيد',
      'resolve_debt': 'تسوية الدين الآن',
      'cash_collected': 'النقد المحصل',
      'online_earnings': 'المكاسب الإلكترونية',
      'total_owed': 'إجمالي ما يجب دفعه',
      'debt_limit_title': 'تم بلوغ حد الدين',
      // Waiting Zone
      'waiting_for_response': 'في انتظار الرد',
      'offer_sent_prefix': 'تم إرسال عرضك بـ DH ',
      'offer_sent_suffix': ' .\nفي انتظار رد العميل...',
      'offer_accepted': '🎉 تم قبول العرض!',
      'offer_accepted_desc': 'اختارك العميل.\nاستعد للتوصيل!',
      'another_driver': 'تم اختيار سائق آخر',
      'another_driver_desc': 'قبل العميل عرض سائق آخر.\nحظ أوفر في المرة القادمة!',
      'order_cancelled': 'تم إلغاء الطلب',
      'order_cancelled_desc': 'ألغى العميل طلبه.\nابحث عن الطلب التالي!',
      'offer_expired': 'انتهت صلاحية العرض',
      'offer_expired_desc': 'لم يرد العميل في الوقت المناسب.\nتم سحب عرضك.',
      'going_back': 'العودة تلقائيا...',
      'back_to_orders': 'العودة إلى الطلبيات',
      'resend_offer': 'إرسال عرض آخر',
      'view_active_order': 'عرض الطلب النشط',
      // Active Order / Chat
      'active_order': 'طلب نشط',
      'return_pin': 'رمز الإرجاع',
      'pickup_pin': 'رمز الاستلام',
      'no_internet': 'لا يوجد اتصال',
      'status_label': 'الحالة: ',
      'mark_on_way': 'في الطريق',
      'mark_delivered': 'تم التوصيل',
      'cancel_delivery': 'إلغاء التوصيل',
      'order_summary': 'ملخص الطلب',
      'qty': 'الكمية',
      'item': 'العنصر',
      'message_hint': 'رسالة...',
      'shop_label': 'المتجر',
      'customer_label': 'العميل',
      'live_location': 'الموقع المباشر',
      'tap_to_navigate': 'اضغط للتنقل',
      'you': 'أنت',
      // Order Statuses
      'status_doing': 'قيد التنفيذ',
      'status_order_pickup': 'استلام الطلب',
      'status_picked': 'تم الاستلام',
      'status_ready': 'تم الاستلام',
      'status_arrived_at_shop': 'وصل للمتجر',
      'status_on_way': 'في الطريق',
      'status_on_the_way': 'في الطريق',
      'status_come_to_take_it': 'جاهز للاستلام',
      'status_found': 'تم العثور عليه',
      'status_arrived': 'وصل',
      'status_delivered': 'تم التوصيل',
      'status_cancelled': 'ملغى',
      'status_returned': 'مرتجع',
    }
  };

  String translate(String key) {
    return _localizedValues[_currentLanguage]?[key] ?? key;
  }
}

// Global singleton instance
final localizationService = LocalizationService();

// Simple String extension to translate easily in UI
extension StringTranslateExtension on String {
  String get tr => localizationService.translate(this);
}
