import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import '../services/api_service.dart';

class PersonalInfoPage extends StatefulWidget {
  final Map<String, dynamic> initialData;
  const PersonalInfoPage({super.key, required this.initialData});

  @override
  State<PersonalInfoPage> createState() => _PersonalInfoPageState();
}

class _PersonalInfoPageState extends State<PersonalInfoPage> {
  late TextEditingController _fnameController;
  late TextEditingController _lnameController;
  late TextEditingController _emailController;
  late TextEditingController _phoneController;
  late TextEditingController _cityController;
  late TextEditingController _birthdayController;

  final ImagePicker _picker = ImagePicker();
  String? _personalPhotoBase64;
  String? _nidFrontBase64;
  String? _nidBackBase64;
  String? _autoCardBase64;
  
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    final data = widget.initialData;
    _fnameController = TextEditingController(text: data['FName'] ?? '');
    _lnameController = TextEditingController(text: data['LName'] ?? '');
    _emailController = TextEditingController(text: data['DriverEmail'] ?? '');
    _phoneController = TextEditingController(text: data['DriverPhone'] ?? '');
    _cityController = TextEditingController(text: data['City'] ?? '');
    _birthdayController = TextEditingController(text: data['AGE'] ?? '');
  }

  @override
  void dispose() {
    _fnameController.dispose();
    _lnameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _cityController.dispose();
    _birthdayController.dispose();
    super.dispose();
  }

  Future<void> _pickImage(String type) async {
    try {
      final XFile? image = await _picker.pickImage(
        source: ImageSource.gallery, 
        imageQuality: 60,
        maxWidth: 600,
        maxHeight: 600,
      );
      if (image != null) {
        final bytes = await File(image.path).readAsBytes();
        final base64 = 'data:image/png;base64,${base64Encode(bytes)}';
        setState(() {
          if (type == 'personal') _personalPhotoBase64 = base64;
          if (type == 'nid_front') _nidFrontBase64 = base64;
          if (type == 'nid_back') _nidBackBase64 = base64;
          if (type == 'auto') _autoCardBase64 = base64;
        });
      }
    } catch (e) {
      debugPrint('Image pick error: $e');
    }
  }

  Future<void> _handleSave() async {
    setState(() => _isLoading = true);
    
    final driverId = await ApiService.getDriverId() ?? '';
    
    final result = await ApiService.updateProfile(
      driverId: driverId,
      fname: _fnameController.text,
      lname: _lnameController.text,
      email: _emailController.text,
      phone: _phoneController.text,
      city: _cityController.text,
      age: _birthdayController.text,
      personalPhotoBase64: _personalPhotoBase64,
      nidPhotoBase64: _nidFrontBase64,
      carPhotoBase64: _nidBackBase64,
      licensePhotoBase64: _autoCardBase64,
    );

    setState(() => _isLoading = false);

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result['message'] ?? 'Profile updated!'),
          backgroundColor: result['success'] == true ? Colors.green : Colors.red,
        ),
      );
      if (result['success'] == true) {
        Navigator.pop(context);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final photo = widget.initialData['PersonalPhoto'] ?? '';

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      body: Stack(
        children: [
          CustomScrollView(
            physics: const BouncingScrollPhysics(),
            slivers: [
              _buildSliverAppBar(photo),
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const SizedBox(height: 32),
                      _buildSectionHeader('ACCOUNT DETAILS'),
                      _buildInfoCard([
                        _buildInputField(Icons.person_rounded, 'First Name', _fnameController),
                        _buildInputField(Icons.person_outline_rounded, 'Last Name', _lnameController),
                        _buildInputField(Icons.email_rounded, 'Email Address', _emailController),
                        _buildInputField(Icons.phone_iphone_rounded, 'Phone Number', _phoneController),
                      ]),
                      
                      const SizedBox(height: 24),
                      _buildSectionHeader('LOCATION & IDENTITY'),
                      _buildInfoCard([
                        _buildInputField(Icons.location_city_rounded, 'Current City', _cityController),
                        _buildInputField(Icons.cake_rounded, 'Birthday / Age', _birthdayController, isLast: true),
                      ]),

                      const SizedBox(height: 24),
                      _buildSectionHeader('VERIFICATION DOCUMENTS'),
                      const SizedBox(height: 12),
                      _buildDocumentSection(),
                      const SizedBox(height: 120),
                    ],
                  ),
                ),
              ),
            ],
          ),
          if (_isLoading)
            Container(
              color: Colors.black26,
              child: const Center(child: CircularProgressIndicator(color: Colors.white)),
            ),
        ],
      ),
      bottomNavigationBar: _buildBottomAction(),
    );
  }

  Widget _buildSliverAppBar(String photo) {
    return SliverAppBar(
      expandedHeight: 240,
      pinned: true,
      backgroundColor: const Color(0xFF6366F1),
      elevation: 0,
      leading: IconButton(
        icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white),
        onPressed: () => Navigator.pop(context),
      ),
      flexibleSpace: FlexibleSpaceBar(
        background: Stack(
          fit: StackFit.expand,
          children: [
            Container(
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [Color(0xFF6366F1), Color(0xFF4F46E5)],
                ),
              ),
            ),
            Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const SizedBox(height: 40),
                  GestureDetector(
                    onTap: () => _pickImage('personal'),
                    child: Stack(
                      alignment: Alignment.bottomRight,
                      children: [
                        Container(
                          padding: const EdgeInsets.all(4),
                          decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle),
                          child: CircleAvatar(
                            radius: 50,
                            backgroundColor: Colors.grey.shade100,
                            backgroundImage: _personalPhotoBase64 != null 
                              ? MemoryImage(base64Decode(_personalPhotoBase64!.split(',').last))
                              : (photo.toString().isNotEmpty && photo.toString().startsWith('http') ? NetworkImage(photo) : null),
                            onBackgroundImageError: (_personalPhotoBase64 != null || (photo.toString().isNotEmpty && photo.toString().startsWith('http'))) ? (exception, stackTrace) {} : null,
                            child: (photo.toString().isEmpty && _personalPhotoBase64 == null) ? const Icon(Icons.person, size: 50, color: Colors.grey) : null,
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            shape: BoxShape.circle,
                            boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.1), blurRadius: 8)],
                          ),
                          child: const Icon(Icons.camera_alt_rounded, color: Color(0xFF6366F1), size: 18),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),
                  const Text('Change Photo', style: TextStyle(color: Colors.white70, fontSize: 13, fontWeight: FontWeight.w600)),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSectionHeader(String title) {
    return Padding(
      padding: const EdgeInsets.only(left: 4, bottom: 12),
      child: Text(
        title,
        style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: Colors.grey.shade400, letterSpacing: 1.2),
      ),
    );
  }

  Widget _buildInfoCard(List<Widget> children) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: Colors.grey.shade100),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 20, offset: const Offset(0, 10))],
      ),
      child: Column(children: children),
    );
  }

  Widget _buildInputField(IconData icon, String label, TextEditingController controller, {bool isLast = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
      child: Column(
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(color: const Color(0xFFF1F5F9), borderRadius: BorderRadius.circular(12)),
                child: Icon(icon, size: 20, color: const Color(0xFF6366F1)),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(label, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Colors.grey.shade400)),
                    TextField(
                      controller: controller,
                      style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600, color: Color(0xFF1E293B)),
                      decoration: const InputDecoration(border: InputBorder.none, isDense: true, contentPadding: EdgeInsets.symmetric(vertical: 4)),
                    ),
                  ],
                ),
              ),
            ],
          ),
          if (!isLast) ...[
            const SizedBox(height: 12),
            Divider(height: 1, color: Colors.grey.shade50),
          ],
        ],
      ),
    );
  }

  Widget _buildDocumentSection() {
    return Column(
      children: [
        Row(
          children: [
            Expanded(child: _buildDocCard('ID FRONT', 'Identity Card', Icons.badge_outlined, () => _pickImage('nid_front'), _nidFrontBase64 != null)),
            const SizedBox(width: 16),
            Expanded(child: _buildDocCard('ID BACK', 'Identity Card', Icons.badge_outlined, () => _pickImage('nid_back'), _nidBackBase64 != null)),
          ],
        ),
        const SizedBox(height: 16),
        _buildDocCard('AUTO-ENTREPRENEUR', 'Status Certificate', Icons.assignment_ind_outlined, () => _pickImage('auto'), _autoCardBase64 != null, isFullWidth: true),
      ],
    );
  }

  Widget _buildDocCard(String label, String subtitle, IconData icon, VoidCallback onTap, bool hasSelected, {bool isFullWidth = false}) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(24),
      child: Container(
        width: isFullWidth ? double.infinity : null,
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(24),
          border: Border.all(color: hasSelected ? const Color(0xFF6366F1) : Colors.grey.shade100, width: hasSelected ? 2 : 1),
          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 10)],
        ),
        child: Column(
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(color: hasSelected ? const Color(0xFF6366F1).withOpacity(0.1) : const Color(0xFFEEF2FF), shape: BoxShape.circle),
              child: Icon(hasSelected ? Icons.check_circle_rounded : icon, color: const Color(0xFF6366F1), size: 24),
            ),
            const SizedBox(height: 12),
            Text(label, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: Color(0xFF6366F1))),
            const SizedBox(height: 4),
            Text(subtitle, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: Color(0xFF1E293B))),
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
              decoration: BoxDecoration(color: hasSelected ? const Color(0xFF10B981) : const Color(0xFFF1F5F9), borderRadius: BorderRadius.circular(99)),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(hasSelected ? Icons.done_all_rounded : Icons.upload_rounded, size: 14, color: hasSelected ? Colors.white : const Color(0xFF6366F1)),
                  const SizedBox(width: 4),
                  Text(hasSelected ? 'READY' : 'UPLOAD', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w800, color: hasSelected ? Colors.white : const Color(0xFF6366F1))),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildBottomAction() {
    return Container(
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 20, offset: const Offset(0, -5))],
      ),
      child: SizedBox(
        height: 60,
        child: ElevatedButton(
          onPressed: _isLoading ? null : _handleSave,
          style: ElevatedButton.styleFrom(
            backgroundColor: const Color(0xFF1E293B),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
            elevation: 0,
          ),
          child: Text(
            _isLoading ? 'SAVING...' : 'SAVE CHANGES',
            style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w900, letterSpacing: 1)
          ),
        ),
      ),
    );
  }
}
