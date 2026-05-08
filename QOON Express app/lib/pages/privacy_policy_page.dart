import 'package:flutter/material.dart';

class PrivacyPolicyPage extends StatelessWidget {
  const PrivacyPolicyPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        scrolledUnderElevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.black, size: 20),
          onPressed: () => Navigator.pop(context),
        ),
        title: const Text(
          'Politique de Confidentialité',
          style: TextStyle(color: Colors.black, fontSize: 18, fontWeight: FontWeight.w800),
        ),
        centerTitle: true,
      ),
      body: ListView(
        physics: const BouncingScrollPhysics(),
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
        children: [
          // Header Section
          Center(
            child: Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: const Color(0xFFEFF6FF),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.shield_rounded, size: 48, color: Color(0xFF3B82F6)),
            ),
          ),
          const SizedBox(height: 24),
          const Text(
            'Livreurs Partenaires QOON Express',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 22, fontWeight: FontWeight.w900, color: Color(0xFF1E293B)),
          ),
          const SizedBox(height: 8),
          const Text(
            'Dernière mise à jour : Mai 2026\nSite Web : https://qoon.app/',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 14, color: Color(0xFF64748B), height: 1.5),
          ),
          const SizedBox(height: 32),

          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: const Color(0xFFE2E8F0)),
            ),
            child: const Text(
              "Bienvenue chez QOON Express. Cette politique détaille la manière dont nous traitons les données des Livreurs Indépendants (Auto-entrepreneurs) utilisant notre plateforme pour proposer leurs services de livraison aux clients de l'écosystème QOON.",
              style: TextStyle(fontSize: 15, color: Color(0xFF475569), height: 1.6),
            ),
          ),
          const SizedBox(height: 24),

          _buildSection(
            icon: Icons.gavel_rounded,
            title: '1. Nature de la Relation (Loi Marocaine)',
            content: "L’application QOON Express est un outil de mise en relation technologique.\n\n"
                     "• Indépendance : Le Livreur opère sous le statut légal d'Auto-entrepreneur. Il n'existe aucun lien de subordination entre QOON et le Livreur.\n\n"
                     "• Liberté Tarifaire : Le Livreur est seul maître de ses tarifs. Via l'application, il propose un prix de livraison pour chaque commande reçue. Le contrat de prestation de service se forme directement entre le Client QOON et le Livreur.",
          ),

          _buildSection(
            icon: Icons.data_usage_rounded,
            title: '2. Collecte des Données',
            content: "Conformément à la Loi 09-08, nous collectons les informations strictement nécessaires à la fourniture du service :\n\n"
                     "• Profil Professionnel : Nom, prénom, photo, copie de la CIN et justificatif du statut d'Auto-entrepreneur (ICE/Identifiant Fiscal).\n\n"
                     "• Géolocalisation : Collectée en temps réel lorsque l'application est en mode \"En ligne\" pour permettre l'attribution des commandes et le suivi par le client.\n\n"
                     "• Données de Transaction : Historique des tarifs proposés, livraisons effectuées et avis clients.\n\n"
                     "• Données Financières : Informations bancaires (RIB) pour le reversement des fonds perçus via l'application.",
          ),

          _buildSection(
            icon: Icons.fact_check_rounded,
            title: '3. Finalités du Traitement',
            content: "Vos données sont traitées pour :\n\n"
                     "• L'intermédiation : Transmettre votre offre de prix et votre profil aux clients.\n\n"
                     "• Le Suivi : Permettre au client de suivre l'avancement de sa livraison sur une carte.\n\n"
                     "• La Facturation : Générer les rapports d'activité nécessaires à votre comptabilité d'Auto-entrepreneur.\n\n"
                     "• La Sécurité : Vérifier l'identité des prestataires pour protéger la communauté QOON.",
          ),

          _buildSection(
            icon: Icons.share_rounded,
            title: '4. Partage et Destinataires',
            content: "Vos données sont partagées uniquement de la manière suivante :\n\n"
                     "• Aux Clients QOON : Votre prénom, votre note de satisfaction, votre position GPS et votre numéro de téléphone (uniquement durant la course).\n\n"
                     "• Aux Autorités : En cas de réquisition judiciaire ou pour se conformer aux obligations fiscales marocaines.\n\n"
                     "• Aucune revente : QOON s'engage à ne jamais vendre vos données personnelles à des tiers à des fins marketing.",
          ),

          _buildSection(
            icon: Icons.security_rounded,
            title: '5. Vos Droits et Sécurité',
            content: "Vous disposez d'un droit d'accès, de rectification et d'opposition concernant vos données (Loi 09-08).\n\n"
                     "• Accès : Toutes vos données sont consultables via votre espace \"Profil\" dans l'application.\n\n"
                     "• Sécurité : Nous utilisons des protocoles de chiffrement avancés pour protéger vos documents d'identité et vos coordonnées bancaires.",
          ),

          _buildSection(
            icon: Icons.support_agent_rounded,
            title: '6. Contact et Support',
            content: "Pour toute question relative à vos données personnelles ou pour exercer vos droits, vous pouvez nous contacter :\n\n"
                     "• Téléphone : 0707777721\n"
                     "• Site Web : https://qoon.app/\n"
                     "• Support Application : Section \"Aide & Support\" de votre interface conducteur.",
          ),

          const SizedBox(height: 16),
          _buildInfoBanner(
            title: 'Engagement du Livreur',
            content: "En activant votre compte sur QOON Express, vous certifiez l'exactitude de vos informations professionnelles et acceptez que votre géolocalisation soit partagée avec le client final durant l'exécution de la prestation de service que vous avez tarifée.",
            color: const Color(0xFF6366F1),
            icon: Icons.handshake_rounded,
          ),

          const SizedBox(height: 16),
          _buildInfoBanner(
            title: 'Rappel Juridique',
            content: "En tant qu'Auto-entrepreneur au Maroc, vous restez responsable du paiement de vos taxes (Impôt sur le Revenu et Taxe Professionnelle) sur les montants perçus via la plateforme.",
            color: const Color(0xFFF59E0B),
            icon: Icons.warning_rounded,
          ),

          const SizedBox(height: 40),
        ],
      ),
    );
  }

  Widget _buildSection({required IconData icon, required String title, required String content}) {
    return Container(
      margin: const EdgeInsets.only(bottom: 20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.02),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(20),
            decoration: const BoxDecoration(
              border: Border(bottom: BorderSide(color: Color(0xFFF1F5F9))),
            ),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF8FAFC),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Icon(icon, size: 20, color: const Color(0xFF334155)),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Text(
                    title,
                    style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: Color(0xFF1E293B)),
                  ),
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(20),
            child: Text(
              content,
              style: const TextStyle(fontSize: 14, color: Color(0xFF475569), height: 1.6),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildInfoBanner({required String title, required String content, required Color color, required IconData icon}) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: color.withOpacity(0.08),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: color.withOpacity(0.2)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, color: color, size: 24),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  title,
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: color),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            content,
            style: const TextStyle(fontSize: 14, color: Color(0xFF334155), height: 1.5),
          ),
        ],
      ),
    );
  }
}
