    </main>
    
    <!-- Footer -->
    <footer class="bg-dark text-light py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5 class="text-army-green mb-3">
                        <i class="fas fa-running me-2"></i>Buffalo Marathon 2025
                    </h5>
                    <p class="mb-3">Join us for an unforgettable running experience at Buffalo Park Recreation Centre with amazing entertainment, food, and activities for the whole family.</p>
                    <div class="social-links">
                        <a href="#" class="text-light me-3" title="Facebook" aria-label="Facebook">
                            <i class="fab fa-facebook-f fa-lg"></i>
                        </a>
                        <a href="#" class="text-light me-3" title="Twitter" aria-label="Twitter">
                            <i class="fab fa-twitter fa-lg"></i>
                        </a>
                        <a href="#" class="text-light me-3" title="Instagram" aria-label="Instagram">
                            <i class="fab fa-instagram fa-lg"></i>
                        </a>
                        <a href="#" class="text-light me-3" title="YouTube" aria-label="YouTube">
                            <i class="fab fa-youtube fa-lg"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5 class="text-army-green mb-3">
                        <i class="fas fa-calendar-alt me-2"></i>Event Details
                    </h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-calendar me-2 text-army-green"></i>
                            <strong>Date:</strong> Saturday, 11 October 2025
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-clock me-2 text-army-green"></i>
                            <strong>Time:</strong> 7:00 AM Start
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-map-marker-alt me-2 text-army-green"></i>
                            <strong>Venue:</strong> <?php echo EVENT_VENUE; ?>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-road me-2 text-army-green"></i>
                            <strong>Address:</strong> <?php echo EVENT_ADDRESS; ?>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-exclamation-circle me-2 text-army-green"></i>
                            <strong>Registration Deadline:</strong> 30 September 2025
                        </li>
                    </ul>
                </div>
                
                <div class="col-lg-4 col-md-12 mb-4">
                    <h5 class="text-army-green mb-3">
                        <i class="fas fa-link me-2"></i>Quick Links
                    </h5>
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-2"><a href="index.php" class="text-light text-decoration-none">Home</a></li>
                                <li class="mb-2"><a href="categories.php" class="text-light text-decoration-none">Race Categories</a></li>
                                <li class="mb-2"><a href="schedule.php" class="text-light text-decoration-none">Event Schedule</a></li>
                                <li class="mb-2"><a href="info.php" class="text-light text-decoration-none">Event Information</a></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-2"><a href="faq.php" class="text-light text-decoration-none">FAQ</a></li>
                                <li class="mb-2"><a href="contact.php" class="text-light text-decoration-none">Contact Us</a></li>
                                <li class="mb-2"><a href="terms.php" class="text-light text-decoration-none">Terms & Conditions</a></li>
                                <li class="mb-2"><a href="privacy.php" class="text-light text-decoration-none">Privacy Policy</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <h6 class="text-army-green mb-2">
                            <i class="fas fa-phone me-2"></i>Contact Info
                        </h6>
                        <p class="mb-1">
                            <i class="fas fa-envelope me-2"></i>
                            <a href="mailto:<?php echo ADMIN_EMAIL; ?>" class="text-light text-decoration-none">
                                <?php echo ADMIN_EMAIL; ?>
                            </a>
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-phone me-2"></i>
                            <?php echo getSetting('contact_phone', '+260 XXX XXXXXXX'); ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <hr class="my-4 border-secondary">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Buffalo Marathon. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">
                        Developed with <i class="fas fa-heart text-danger"></i> by 
                        <a href="#" class="text-army-green text-decoration-none">Buffalo Marathon Team</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Back to Top Button -->
    <button id="backToTop" class="btn btn-army-green rounded-circle position-fixed" 
            style="bottom: 20px; right: 20px; width: 50px; height: 50px; display: none; z-index: 1000;" 
            title="Back to top" aria-label="Back to top">
        <i class="fas fa-chevron-up"></i>
    </button>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/main.js?v=1.0.0"></script>
    
    <!-- Analytics (Add your tracking code here) -->
    <?php if (!DEBUG_MODE): ?>
    <!-- Google Analytics or other tracking code goes here -->
    <?php endif; ?>
</body>
</html>