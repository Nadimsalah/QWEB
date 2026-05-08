import 'package:flutter/material.dart';
import 'dashboard_page.dart';
import 'services/api_service.dart';

class LoginPage extends StatefulWidget {
  const LoginPage({super.key});

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _obscurePassword = true;
  bool _isLoading = false;

  @override
  void dispose() {
    _phoneController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        scrolledUnderElevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_rounded, color: Colors.black, size: 24),
          onPressed: () {},
        ),
      ),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: SingleChildScrollView(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const SizedBox(height: 12),
                      // Logo bypass
                      GestureDetector(
                        onLongPress: () async {
                          // Hidden bypass for testing UI without DB credentials
                          await ApiService.setDriverId('1'); // Use ID 1 or a mock ID
                          if (mounted) {
                            Navigator.pushReplacement(
                              context,
                              MaterialPageRoute(builder: (context) => const DashboardPage()),
                            );
                          }
                        },
                        child: Center(
                          child: Column(
                            children: [
                              Image.network('https://qoon.app/logo_express.png', height: 44, errorBuilder: (_,__,___) => const SizedBox(height: 44)),
                              const SizedBox(height: 12),
                              const Text('Driver Portal — Sign in to your account', style: TextStyle(color: Colors.black54, fontWeight: FontWeight.w500)),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 32),
                      const Text(
                        'Welcome back, Driver 👋',
                        style: TextStyle(
                          fontSize: 24,
                          fontWeight: FontWeight.w800,
                          color: Colors.black,
                          letterSpacing: -0.5,
                        ),
                      ),
                      Text(
                        'Enter your phone number and passcode',
                        style: TextStyle(
                          fontSize: 15,
                          color: Colors.grey.shade600,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                      const SizedBox(height: 40),

                      // Minimalist Phone Input Field
                      Container(
                        decoration: BoxDecoration(
                          color: Colors.grey.shade100,
                          borderRadius: BorderRadius.circular(16),
                        ),
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                        child: Row(
                          children: [
                            // Country Code
                            Row(
                              children: [
                                const Text(
                                  '🇲🇦',
                                  style: TextStyle(fontSize: 22),
                                ),
                                const SizedBox(width: 8),
                                const Text(
                                  '+212',
                                  style: TextStyle(
                                    fontSize: 17,
                                    fontWeight: FontWeight.w600,
                                    color: Colors.black,
                                  ),
                                ),
                                const SizedBox(width: 4),
                                Icon(Icons.keyboard_arrow_down_rounded, color: Colors.grey.shade500, size: 20),
                              ],
                            ),
                            const SizedBox(width: 12),
                            Container(
                              height: 24,
                              width: 1,
                              color: Colors.grey.shade300,
                            ),
                            const SizedBox(width: 12),
                            // Phone Number Input
                            Expanded(
                              child: TextField(
                                controller: _phoneController,
                                keyboardType: TextInputType.phone,
                                style: const TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.w600,
                                  color: Colors.black,
                                  letterSpacing: 1.0,
                                ),
                                decoration: InputDecoration(
                                  hintText: '6XX XX XX XX',
                                  hintStyle: TextStyle(
                                    color: Colors.grey.shade400,
                                    fontWeight: FontWeight.w500,
                                    letterSpacing: 1.0,
                                  ),
                                  border: InputBorder.none,
                                  isDense: true,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),

                      const SizedBox(height: 16),

                      // Minimalist Password Input Field
                      Container(
                        decoration: BoxDecoration(
                          color: Colors.grey.shade100,
                          borderRadius: BorderRadius.circular(16),
                        ),
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                        child: TextField(
                          controller: _passwordController,
                          obscureText: _obscurePassword,
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w600,
                            color: Colors.black,
                            letterSpacing: 1.0,
                          ),
                          decoration: InputDecoration(
                            hintText: 'Passcode',
                            hintStyle: TextStyle(
                              color: Colors.grey.shade400,
                              fontWeight: FontWeight.w500,
                              letterSpacing: 0,
                            ),
                            border: InputBorder.none,
                            isDense: true,
                            contentPadding: const EdgeInsets.symmetric(vertical: 16),
                            suffixIconConstraints: const BoxConstraints(minWidth: 40, minHeight: 40),
                            suffixIcon: GestureDetector(
                              onTap: () {
                                setState(() {
                                  _obscurePassword = !_obscurePassword;
                                });
                              },
                              child: Icon(
                                _obscurePassword
                                    ? Icons.visibility_off_rounded
                                    : Icons.visibility_rounded,
                                color: Colors.grey.shade500,
                                size: 22,
                              ),
                            ),
                          ),
                        ),
                      ),

                      const SizedBox(height: 16),

                      // Forgot Password link
                      TextButton(
                        onPressed: () {},
                        style: TextButton.styleFrom(
                          padding: EdgeInsets.zero,
                          minimumSize: Size.zero,
                          tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                          overlayColor: Colors.transparent,
                        ),
                        child: Text(
                          'Recover passcode',
                          style: TextStyle(
                            color: Colors.grey.shade600,
                            fontSize: 15,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              
              // Bottom Button (Revolut style: full width, very rounded, bold text)
              Padding(
                padding: const EdgeInsets.only(bottom: 16.0, top: 8.0),
                child: SizedBox(
                  width: double.infinity,
                  height: 56,
                  child: ElevatedButton(
                    onPressed: _isLoading ? null : () async {
                      final rawPhone = _phoneController.text.trim();
                      // Remove all spaces, dashes, etc
                      var phone = rawPhone.replaceAll(RegExp(r'[\s\-]'), '');
                      final password = _passwordController.text.trim();
                      
                      if (phone.isEmpty || password.isEmpty) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('Please enter phone and password')),
                        );
                        return;
                      }

                      setState(() {
                        _isLoading = true;
                      });

                      try {
                        // Normalize to the core 9 digits (assuming Moroccan number e.g. 6XXXXXXXX)
                        if (phone.startsWith('+212')) {
                          phone = phone.substring(4);
                        } else if (phone.startsWith('00212')) {
                          phone = phone.substring(5);
                        } else if (phone.startsWith('0')) {
                          phone = phone.substring(1);
                        }

                        // Attempt 1: Core 9 digits (e.g. 6XXXXXXXX)
                        var response = await ApiService.login(phone, password);
                        
                        // Attempt 2: With leading zero (e.g. 06XXXXXXXX)
                        if (response == null || response['DriverID'] == null) {
                          response = await ApiService.login('0$phone', password);
                        }

                        // Attempt 3: With +212 (e.g. +2126XXXXXXXX)
                        if (response == null || response['DriverID'] == null) {
                          response = await ApiService.login('+212$phone', password);
                        }

                        // Attempt 4: Exactly as typed (just in case they typed something totally custom)
                        if (response == null || response['DriverID'] == null) {
                          response = await ApiService.login(rawPhone, password);
                        }
                        
                        if (response != null && response['DriverID'] != null) {
                          await ApiService.setDriverId(
                            response['DriverID'].toString(),
                            lat: response['CurrentLat']?.toString(),
                            lng: response['CurrentLongt']?.toString(),
                          );
                          if (mounted) {
                            Navigator.pushReplacement(
                              context,
                              MaterialPageRoute(builder: (context) => const DashboardPage()),
                            );
                          }
                        } else {
                          if (mounted) {
                            String errorMsg = 'Invalid phone number or password';
                            if (response != null && response['message'] != null) {
                              errorMsg = response['message'];
                            } else if (response == null) {
                              errorMsg = 'Network error. Please try again.';
                            }
                            
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(
                                content: Text(errorMsg),
                                backgroundColor: Colors.red.shade600,
                                duration: const Duration(seconds: 4),
                              ),
                            );
                          }
                        }
                      } catch (e) {
                        if (mounted) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              content: Text('App Error: $e'),
                              backgroundColor: Colors.red.shade900,
                              duration: const Duration(seconds: 6),
                            ),
                          );
                        }
                      } finally {
                        if (mounted) {
                          setState(() {
                            _isLoading = false;
                          });
                        }
                      }
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.black,
                      foregroundColor: Colors.white,
                      elevation: 0,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(28),
                      ),
                    ),
                    child: _isLoading 
                      ? const SizedBox(
                          height: 24,
                          width: 24,
                          child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2.5),
                        )
                      : const Text(
                          'Log in securely',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w700,
                            letterSpacing: 0.2,
                          ),
                        ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
