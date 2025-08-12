<?php
/**
 * Buffalo Marathon 2025 - Terms of Service
 * Production Ready - 2025-08-08 13:48:27 UTC
 */

define('BUFFALO_SECURE_ACCESS', true);
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - Buffalo Marathon 2025</title>
    <meta name="description" content="Terms of Service for Buffalo Marathon 2025. Read our terms and conditions for registration and participation.">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --army-green: #4B5320;
            --army-green-dark: #222B1F;
            --gold: #FFD700;
        }
        
        .terms-header {
            background: linear-gradient(135deg, var(--army-green), var(--army-green-dark));
            color: white;
            padding: 3rem 0;
        }
        
        .terms-content {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 3rem;
            margin-top: -50px;
            position: relative;
            z-index: 10;
        }
        
        .terms-nav {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            position: sticky;
            top: 20px;
        }
        
        .terms-nav a {
            color: var(--army-green);
            text-decoration: none;
            display: block;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .terms-nav a:hover {
            color: var(--army-green-dark);
            padding-left: 0.5rem;
        }
        
        .terms-nav a:last-child {
            border-bottom: none;
        }
        
        .section-title {
            color: var(--army-green);
            border-bottom: 2px solid var(--gold);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .highlight-box {
            background: rgba(75, 83, 32, 0.1);
            border-left: 4px solid var(--army-green);
            padding: 1.5rem;
            border-radius: 0 10px 10px 0;
            margin: 1.5rem 0;
        }
        
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 1.5rem;
            border-radius: 0 10px 10px 0;
            margin: 1.5rem 0;
        }
        
        .text-army-green { color: var(--army-green) !important; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-army-green">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/">
                <i class="fas fa-running me-2"></i>Buffalo Marathon 2025
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/">
                    <i class="fas fa-arrow-left me-1"></i>Back to Home
                </a>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <section class="terms-header">
        <div class="container text-center">
            <h1 class="display-5 fw-bold mb-3">Terms of Service</h1>
            <p class="lead">Buffalo Marathon 2025 Registration Terms and Conditions</p>
            <small class="opacity-75">Last updated: August 8, 2025 | Effective Date: August 8, 2025</small>
        </div>
    </section>

    <!-- Terms Content -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-3">
                    <div class="terms-nav">
                        <h6 class="text-army-green fw-bold mb-3">Quick Navigation</h6>
                        <a href="#acceptance">1. Acceptance of Terms</a>
                        <a href="#registration">2. Registration</a>
                        <a href="#payment">3. Payment Terms</a>
                        <a href="#participation">4. Participation Requirements</a>
                        <a href="#safety">5. Safety & Medical</a>
                        <a href="#liability">6. Liability Waiver</a>
                        <a href="#conduct">7. Code of Conduct</a>
                        <a href="#intellectual">8. Intellectual Property</a>
                        <a href="#privacy">9. Privacy</a>
                        <a href="#modifications">10. Modifications</a>
                        <a href="#contact">11. Contact Information</a>
                    </div>
                </div>
                
                <div class="col-lg-9">
                    <div class="terms-content">
                        <div class="highlight-box">
                            <h5 class="text-army-green mb-3"><i class="fas fa-info-circle me-2"></i>Important Notice</h5>
                            <p class="mb-0">By registering for Buffalo Marathon 2025, you agree to these Terms of Service. Please read them carefully before completing your registration. These terms constitute a legally binding agreement between you and Buffalo Marathon Organization.</p>
                        </div>

                        <!-- Section 1 -->
                        <section id="acceptance" class="mb-5">
                            <h3 class="section-title">1. Acceptance of Terms</h3>
                            <p>By accessing our website and registering for Buffalo Marathon 2025, you acknowledge that you have read, understood, and agree to be bound by these Terms of Service and all applicable laws and regulations.</p>
                            
                            <p>These terms apply to all participants, visitors, and users of our website and services. If you do not agree with any part of these terms, you may not register for or participate in the event.</p>
                            
                            <ul>
                                <li>You must be at least 5 years old to participate (with parental consent for minors)</li>
                                <li>You confirm that all information provided during registration is accurate and complete</li>
                                <li>You agree to comply with all event rules and regulations</li>
                            </ul>
                        </section>

                        <!-- Section 2 -->
                        <section id="registration" class="mb-5">
                            <h3 class="section-title">2. Registration Terms</h3>
                            <p><strong>Registration Process:</strong></p>
                            <ul>
                                <li>Registration opens on August 8, 2025, and closes on September 30, 2025, at 11:59 PM</li>
                                <li>Registration is on a first-come, first-served basis</li>
                                <li>Some categories have participant limits and may close before the deadline</li>
                                <li>Only complete registrations with confirmed payment will be accepted</li>
                            </ul>
                            
                            <p><strong>Registration Requirements:</strong></p>
                            <ul>
                                <li>Valid email address for communication</li>
                                <li>Accurate personal information including emergency contact details</li>
                                <li>Medical information disclosure if applicable</li>
                                <li>Agreement to event waiver and terms</li>
                            </ul>
                            
                            <div class="warning-box">
                                <p class="mb-0"><strong>Important:</strong> Registration transfers between individuals are not permitted. Name changes are only allowed for spelling corrections with proper documentation.</p>
                            </div>
                        </section>

                        <!-- Section 3 -->
                        <section id="payment" class="mb-5">
                            <h3 class="section-title">3. Payment Terms</h3>
                            <p><strong>Registration Fees:</strong></p>
                            <ul>
                                <li>Full Marathon (42K): K500</li>
                                <li>Half Marathon (21K): K500</li>
                                <li>Power Challenge (10K): K500</li>
                                <li>Family Fun Run (5K): K500</li>
                                <li>VIP Run (Any Distance): K600</li>
                                <li>Kid Run (1K): K450</li>
                            </ul>
                            
                            <p><strong>Payment Methods:</strong></p>
                            <ul>
                                <li>Mobile Money (MTN, Airtel, Zamtel)</li>
                                <li>Bank Transfer</li>
                                <li>Cash Payment (at designated locations)</li>
                            </ul>
                            
                            <p><strong>Payment Terms:</strong></p>
                            <ul>
                                <li>Payment must be completed within 48 hours of registration</li>
                                <li>Registrations with unpaid fees will be automatically cancelled</li>
                                <li>All fees are in Zambian Kwacha (ZMW)</li>
                                <li>Payment confirmation may take 24-48 hours to process</li>
                            </ul>
                            
                            <p><strong>Refund Policy:</strong></p>
                            <ul>
                                <li>Refunds available up to 14 days before the event (September 27, 2025)</li>
                                <li>Refund processing fee of K50 applies</li>
                                <li>No refunds for no-shows or weather-related cancellations</li>
                                <li>Event postponement due to circumstances beyond our control does not qualify for refunds</li>
                            </ul>
                        </section>

                        <!-- Section 4 -->
                        <section id="participation" class="mb-5">
                            <h3 class="section-title">4. Participation Requirements</h3>
                            <p><strong>Age Requirements:</strong></p>
                            <ul>
                                <li>Kid Run (1K): Ages 5-17</li>
                                <li>Family Fun Run (5K): Ages 12 and above</li>
                                <li>Power Challenge (10K): Ages 14 and above</li>
                                <li>Half Marathon (21K): Ages 16 and above</li>
                                <li>Full Marathon (42K): Ages 18 and above</li>
                                <li>VIP Run: Ages 18 and above</li>
                            </ul>
                            
                            <p><strong>Fitness Requirements:</strong></p>
                            <ul>
                                <li>Participants must be in adequate physical condition</li>
                                <li>Medical clearance recommended for participants over 50 or with health conditions</li>
                                <li>Participants with known medical conditions must disclose during registration</li>
                            </ul>
                            
                            <p><strong>Race Day Requirements:</strong></p>
                            <ul>
                                <li>Valid race number bib must be worn and visible</li>
                                <li>Timing chip must remain attached throughout the race</li>
                                <li>Follow all course markings and marshal instructions</li>
                                <li>Complete race within cut-off times</li>
                            </ul>
                        </section>

                        <!-- Section 5 -->
                        <section id="safety" class="mb-5">
                            <h3 class="section-title">5. Safety & Medical</h3>
                            <p><strong>Medical Support:</strong></p>
                            <ul>
                                <li>Medical teams will be stationed throughout the course</li>
                                <li>Emergency medical services available on-site</li>
                                <li>Participants must report any medical emergencies immediately</li>
                            </ul>
                            
                            <p><strong>Safety Measures:</strong></p>
                            <ul>
                                <li>Course will be monitored by trained marshals</li>
                                <li>Water stations provided at regular intervals</li>
                                <li>Weather conditions monitored continuously</li>
                                <li>Participants must follow all safety instructions</li>
                            </ul>
                            
                            <div class="warning-box">
                                <p class="mb-0"><strong>Important:</strong> The event may be cancelled, postponed, or modified due to severe weather, security concerns, or other circumstances beyond our control. Participants will be notified immediately of any changes.</p>
                            </div>
                        </section>

                        <!-- Section 6 -->
                        <section id="liability" class="mb-5">
                            <h3 class="section-title">6. Liability Waiver</h3>
                            <p>By participating in Buffalo Marathon 2025, you acknowledge and agree that:</p>
                            
                            <ul>
                                <li>You participate at your own risk and assume all risks associated with the event</li>
                                <li>Buffalo Marathon Organization is not liable for any injuries, accidents, or losses</li>
                                <li>You waive all claims against organizers, sponsors, volunteers, and officials</li>
                                <li>You are responsible for your own safety and well-being</li>
                                <li>You have adequate insurance coverage for participation</li>
                            </ul>
                            
                            <div class="highlight-box">
                                <p class="mb-0"><strong>Release of Liability:</strong> You release Buffalo Marathon Organization, its affiliates, sponsors, volunteers, and all associated parties from any and all liability, claims, demands, actions, and causes of action whatsoever arising out of or related to any loss, damage, or injury that may be sustained while participating in the event.</p>
                            </div>
                        </section>

                        <!-- Section 7 -->
                        <section id="conduct" class="mb-5">
                            <h3 class="section-title">7. Code of Conduct</h3>
                            <p>All participants must adhere to the following code of conduct:</p>
                            
                            <p><strong>Acceptable Behavior:</strong></p>
                            <ul>
                                <li>Respect for other participants, volunteers, and officials</li>
                                <li>Compliance with all race rules and regulations</li>
                                <li>Sportsmanlike conduct throughout the event</li>
                                <li>Following course directions and marshal instructions</li>
                            </ul>
                            
                            <p><strong>Prohibited Behavior:</strong></p>
                            <ul>
                                <li>Unsportsmanlike conduct or harassment</li>
                                <li>Use of unauthorized assistance or shortcuts</li>
                                <li>Littering on the course</li>
                                <li>Interfering with other participants</li>
                                <li>Use of unauthorized equipment or substances</li>
                            </ul>
                            
                            <p><strong>Consequences:</strong></p>
                            <p>Violation of the code of conduct may result in disqualification from the event, removal from the venue, and forfeiture of registration fees without refund.</p>
                        </section>

                        <!-- Section 8 -->
                        <section id="intellectual" class="mb-5">
                            <h3 class="section-title">8. Intellectual Property</h3>
                            <p><strong>Media Rights:</strong></p>
                            <ul>
                                <li>Buffalo Marathon Organization reserves the right to use photographs, videos, and recordings taken during the event</li>
                                <li>Participants consent to use of their likeness for promotional purposes</li>
                                <li>No compensation will be provided for use of participant images</li>
                            </ul>
                            
                            <p><strong>Trademark:</strong></p>
                            <ul>
                                <li>"Buffalo Marathon" is a trademark of Buffalo Marathon Organization</li>
                                <li>Unauthorized use of event logos or trademarks is prohibited</li>
                                <li>Commercial use of event materials requires written permission</li>
                            </ul>
                        </section>

                        <!-- Section 9 -->
                        <section id="privacy" class="mb-5">
                            <h3 class="section-title">9. Privacy</h3>
                            <p>Your privacy is important to us. Please refer to our <a href="/privacy.php" class="text-army-green">Privacy Policy</a> for detailed information about how we collect, use, and protect your personal information.</p>
                            
                            <p><strong>Key Points:</strong></p>
                            <ul>
                                <li>Personal information used only for event administration</li>
                                <li>Contact information may be used for event communications</li>
                                <li>Medical information kept confidential and secure</li>
                                <li>Results may be published with participant names and times</li>
                            </ul>
                        </section>

                        <!-- Section 10 -->
                        <section id="modifications" class="mb-5">
                            <h3 class="section-title">10. Modifications to Terms</h3>
                            <p>Buffalo Marathon Organization reserves the right to modify these Terms of Service at any time. Changes will be effective immediately upon posting on our website.</p>
                            
                            <ul>
                                <li>Participants will be notified of significant changes via email</li>
                                <li>Continued participation constitutes acceptance of modified terms</li>
                                <li>Current version always available on our website</li>
                            </ul>
                            
                            <p><strong>Event Changes:</strong></p>
                            <p>We reserve the right to modify event details, including but not limited to date, time, location, or format, due to circumstances beyond our control. Participants will be notified of any changes as soon as possible.</p>
                        </section>

                        <!-- Section 11 -->
                        <section id="contact" class="mb-5">
                            <h3 class="section-title">11. Contact Information</h3>
                            <p>For questions about these Terms of Service or the event, please contact us:</p>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <p><strong>Email:</strong><br><?php echo SITE_EMAIL; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Phone:</strong><br>+260 972 545 658 / +260 770 809 062 / +260 771 470 868</p>
                                </div>
                                <div class="col-12">
                                    <p><strong>Address:</strong><br>
                                    Buffalo Marathon Organization<br>
                                    <?php echo EVENT_ADDRESS; ?><br>
                                    <?php echo EVENT_CITY; ?></p>
                                </div>
                            </div>
                        </section>

                        <!-- Footer -->
                        <div class="highlight-box mt-5">
                            <h5 class="text-army-green mb-3">Agreement Confirmation</h5>
                            <p class="mb-2">By registering for Buffalo Marathon 2025, you confirm that:</p>
                            <ul class="mb-2">
                                <li>You have read and understood these Terms of Service</li>
                                <li>You agree to be bound by all terms and conditions</li>
                                <li>You understand the risks associated with participation</li>
                                <li>You meet all eligibility requirements for your chosen category</li>
                            </ul>
                            <p class="mb-0"><strong>Document Version:</strong> 1.0 | <strong>Effective Date:</strong> August 8, 2025</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2025 Buffalo Marathon Organization. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="/privacy.php" class="text-light text-decoration-none me-3">Privacy Policy</a>
                    <a href="/contact.php" class="text-light text-decoration-none me-3">Contact Us</a>
                    <a href="/faq.php" class="text-light text-decoration-none">FAQ</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('.terms-nav a[href^="#"]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Highlight current section in navigation
        window.addEventListener('scroll', function() {
            const sections = document.querySelectorAll('section[id]');
            const navLinks = document.querySelectorAll('.terms-nav a');
            
            let currentSection = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop - 100;
                if (window.pageYOffset >= sectionTop) {
                    currentSection = section.getAttribute('id');
                }
            });
            
            navLinks.forEach(link => {
                link.classList.remove('fw-bold');
                if (link.getAttribute('href') === '#' + currentSection) {
                    link.classList.add('fw-bold');
                }
            });
        });
    </script>
</body>
</html>