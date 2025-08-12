/**
 * Buffalo Marathon 2025 - Real-time Updates
 * AJAX-powered real-time availability and notifications
 * Created: 2025-01-09
 */

class BuffaloRealTimeUpdates {
    constructor() {
        this.updateInterval = 30000; // 30 seconds
        this.retryAttempts = 3;
        this.currentRetry = 0;
        this.isVisible = true;
        this.lastUpdate = null;
        
        this.initializeVisibilityTracking();
        this.initializeUpdates();
        this.initializeNotifications();
        this.bindEvents();
    }
    
    /**
     * Initialize visibility tracking to pause updates when tab is inactive
     */
    initializeVisibilityTracking() {
        document.addEventListener('visibilitychange', () => {
            this.isVisible = !document.hidden;
            if (this.isVisible && this.shouldUpdate()) {
                this.updateAll();
            }
        });
    }
    
    /**
     * Initialize automatic updates
     */
    initializeUpdates() {
        this.updateAll();
        setInterval(() => {
            if (this.isVisible && this.shouldUpdate()) {
                this.updateAll();
            }
        }, this.updateInterval);
    }
    
    /**
     * Initialize notification system
     */
    initializeNotifications() {
        // Request notification permission if supported
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }
    
    /**
     * Bind event listeners
     */
    bindEvents() {
        // Update availability when category is selected
        document.addEventListener('change', (e) => {
            if (e.target.matches('select[name="category_id"]')) {
                this.updateCategoryAvailability(e.target.value);
            }
        });
        
        // Real-time form validation
        document.addEventListener('input', (e) => {
            if (e.target.matches('input[name="email"]')) {
                this.validateEmailRealTime(e.target);
            }
        });
        
        // Auto-save form data
        document.addEventListener('input', (e) => {
            if (e.target.closest('form[data-autosave]')) {
                this.autoSaveForm(e.target.closest('form'));
            }
        });
    }
    
    /**
     * Check if updates should run
     */
    shouldUpdate() {
        if (!this.lastUpdate) return true;
        return (Date.now() - this.lastUpdate) >= this.updateInterval;
    }
    
    /**
     * Update all real-time elements
     */
    async updateAll() {
        try {
            await Promise.all([
                this.updateAvailability(),
                this.updateCountdowns(),
                this.updateStatistics(),
                this.checkNotifications()
            ]);
            
            this.currentRetry = 0;
            this.lastUpdate = Date.now();
            
        } catch (error) {
            console.error('Real-time update failed:', error);
            this.handleUpdateError();
        }
    }
    
    /**
     * Update category availability
     */
    async updateAvailability() {
        const availabilityElements = document.querySelectorAll('[data-availability-category]');
        if (availabilityElements.length === 0) return;
        
        try {
            const response = await fetch('/api/availability.php', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) throw new Error('Availability update failed');
            
            const data = await response.json();
            
            availabilityElements.forEach(element => {
                const categoryId = element.dataset.availabilityCategory;
                const categoryData = data.categories.find(c => c.id == categoryId);
                
                if (categoryData) {
                    this.updateAvailabilityElement(element, categoryData);
                }
            });
            
        } catch (error) {
            console.error('Availability update error:', error);
        }
    }
    
    /**
     * Update specific category availability
     */
    async updateCategoryAvailability(categoryId) {
        if (!categoryId) return;
        
        try {
            const response = await fetch(`/api/availability.php?category=${categoryId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) throw new Error('Category availability update failed');
            
            const data = await response.json();
            
            // Update availability display
            const availabilityElement = document.querySelector(`[data-availability-category="${categoryId}"]`);
            if (availabilityElement && data.category) {
                this.updateAvailabilityElement(availabilityElement, data.category);
            }
            
            // Update registration button state
            this.updateRegistrationButton(categoryId, data.category);
            
        } catch (error) {
            console.error('Category availability update error:', error);
        }
    }
    
    /**
     * Update availability element
     */
    updateAvailabilityElement(element, categoryData) {
        const { registration_count, max_participants, is_full } = categoryData;
        
        // Update count text
        const countElement = element.querySelector('.availability-count');
        if (countElement) {
            countElement.textContent = `${registration_count}/${max_participants}`;
        }
        
        // Update progress bar
        const progressBar = element.querySelector('.availability-fill');
        if (progressBar) {
            const percentage = max_participants > 0 ? (registration_count / max_participants) * 100 : 0;
            progressBar.style.width = `${Math.min(percentage, 100)}%`;
            
            // Update color based on availability
            progressBar.className = progressBar.className.replace(/bg-\w+/, '');
            if (percentage >= 90) {
                progressBar.classList.add('bg-danger');
            } else if (percentage >= 75) {
                progressBar.classList.add('bg-warning');
            } else {
                progressBar.classList.add('bg-success');
            }
        }
        
        // Update status
        const statusElement = element.querySelector('.availability-status');
        if (statusElement) {
            if (is_full) {
                statusElement.innerHTML = '<i class="fas fa-times-circle text-danger"></i> Fully Booked';
                statusElement.className = 'availability-status text-danger';
            } else if ((registration_count / max_participants) >= 0.9) {
                statusElement.innerHTML = '<i class="fas fa-exclamation-triangle text-warning"></i> Almost Full';
                statusElement.className = 'availability-status text-warning';
            } else {
                statusElement.innerHTML = '<i class="fas fa-check-circle text-success"></i> Available';
                statusElement.className = 'availability-status text-success';
            }
        }
    }
    
    /**
     * Update registration button state
     */
    updateRegistrationButton(categoryId, categoryData) {
        const buttons = document.querySelectorAll(`[data-category-button="${categoryId}"]`);
        
        buttons.forEach(button => {
            if (categoryData.is_full) {
                button.disabled = true;
                button.classList.remove('btn-primary');
                button.classList.add('btn-secondary');
                button.innerHTML = '<i class="fas fa-times-circle me-2"></i>Fully Booked';
            } else {
                button.disabled = false;
                button.classList.remove('btn-secondary');
                button.classList.add('btn-primary');
                button.innerHTML = '<i class="fas fa-running me-2"></i>Register Now';
            }
        });
    }
    
    /**
     * Update countdown timers
     */
    updateCountdowns() {
        const countdownElements = document.querySelectorAll('[data-countdown]');
        
        countdownElements.forEach(element => {
            const targetDate = new Date(element.dataset.countdown);
            const now = new Date();
            const diff = targetDate - now;
            
            if (diff <= 0) {
                element.textContent = 'Expired';
                element.classList.add('text-danger');
                return;
            }
            
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);
            
            let countdownText = '';
            if (days > 0) {
                countdownText = `${days}d ${hours}h ${minutes}m`;
            } else if (hours > 0) {
                countdownText = `${hours}h ${minutes}m ${seconds}s`;
            } else {
                countdownText = `${minutes}m ${seconds}s`;
            }
            
            element.textContent = countdownText;
            
            // Add urgency classes
            if (days <= 1) {
                element.classList.add('text-danger', 'fw-bold');
            } else if (days <= 7) {
                element.classList.add('text-warning', 'fw-bold');
            }
        });
    }
    
    /**
     * Update statistics
     */
    async updateStatistics() {
        const statsElements = document.querySelectorAll('[data-live-stat]');
        if (statsElements.length === 0) return;
        
        try {
            const response = await fetch('/api/stats.php', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) throw new Error('Stats update failed');
            
            const data = await response.json();
            
            statsElements.forEach(element => {
                const statType = element.dataset.liveStat;
                if (data[statType] !== undefined) {
                    this.animateNumberUpdate(element, data[statType]);
                }
            });
            
        } catch (error) {
            console.error('Statistics update error:', error);
        }
    }
    
    /**
     * Animate number updates
     */
    animateNumberUpdate(element, newValue) {
        const currentValue = parseInt(element.textContent.replace(/[^\d]/g, '')) || 0;
        
        if (currentValue === newValue) return;
        
        const duration = 1000; // 1 second
        const steps = 30;
        const stepValue = (newValue - currentValue) / steps;
        const stepDuration = duration / steps;
        
        let currentStep = 0;
        
        const animation = setInterval(() => {
            currentStep++;
            const value = Math.round(currentValue + (stepValue * currentStep));
            
            // Format the number (add commas for thousands)
            element.textContent = value.toLocaleString();
            
            if (currentStep >= steps) {
                clearInterval(animation);
                element.textContent = newValue.toLocaleString();
            }
        }, stepDuration);
    }
    
    /**
     * Check for notifications
     */
    async checkNotifications() {
        if (!('Notification' in window) || Notification.permission !== 'granted') {
            return;
        }
        
        try {
            const response = await fetch('/api/notifications.php', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) return;
            
            const data = await response.json();
            
            data.notifications.forEach(notification => {
                this.showNotification(notification);
            });
            
        } catch (error) {
            console.error('Notifications check error:', error);
        }
    }
    
    /**
     * Show notification
     */
    showNotification(notification) {
        new Notification(notification.title, {
            body: notification.message,
            icon: '/assets/images/logo-icon.png',
            badge: '/assets/images/logo-icon.png',
            tag: notification.id
        });
    }
    
    /**
     * Real-time email validation
     */
    async validateEmailRealTime(input) {
        const email = input.value.trim();
        
        if (!email || !this.isValidEmailFormat(email)) {
            this.clearEmailValidation(input);
            return;
        }
        
        // Debounce validation
        clearTimeout(input.validationTimeout);
        input.validationTimeout = setTimeout(async () => {
            try {
                const response = await fetch('/api/validate-email.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ email })
                });
                
                const data = await response.json();
                this.showEmailValidation(input, data);
                
            } catch (error) {
                console.error('Email validation error:', error);
            }
        }, 500);
    }
    
    /**
     * Check email format
     */
    isValidEmailFormat(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    /**
     * Show email validation result
     */
    showEmailValidation(input, data) {
        let feedback = input.parentNode.querySelector('.email-feedback');
        
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'email-feedback mt-1';
            input.parentNode.appendChild(feedback);
        }
        
        if (data.available) {
            feedback.innerHTML = '<small class="text-success"><i class="fas fa-check me-1"></i>Email available</small>';
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
        } else {
            feedback.innerHTML = '<small class="text-danger"><i class="fas fa-times me-1"></i>Email already registered</small>';
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
        }
    }
    
    /**
     * Clear email validation
     */
    clearEmailValidation(input) {
        const feedback = input.parentNode.querySelector('.email-feedback');
        if (feedback) {
            feedback.remove();
        }
        input.classList.remove('is-valid', 'is-invalid');
    }
    
    /**
     * Auto-save form data
     */
    autoSaveForm(form) {
        if (!form.dataset.autosave) return;
        
        clearTimeout(form.autosaveTimeout);
        form.autosaveTimeout = setTimeout(() => {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            
            localStorage.setItem(`autosave_${form.id || 'form'}`, JSON.stringify({
                data,
                timestamp: Date.now()
            }));
            
            this.showAutoSaveIndicator(form);
        }, 1000);
    }
    
    /**
     * Show auto-save indicator
     */
    showAutoSaveIndicator(form) {
        let indicator = form.querySelector('.autosave-indicator');
        
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'autosave-indicator text-muted mt-2';
            form.appendChild(indicator);
        }
        
        indicator.innerHTML = '<small><i class="fas fa-check text-success me-1"></i>Auto-saved</small>';
        
        setTimeout(() => {
            indicator.innerHTML = '';
        }, 2000);
    }
    
    /**
     * Restore auto-saved form data
     */
    restoreAutoSavedData(formId) {
        const saved = localStorage.getItem(`autosave_${formId}`);
        if (!saved) return false;
        
        try {
            const { data, timestamp } = JSON.parse(saved);
            
            // Only restore if saved within last hour
            if (Date.now() - timestamp > 3600000) {
                localStorage.removeItem(`autosave_${formId}`);
                return false;
            }
            
            const form = document.getElementById(formId);
            if (!form) return false;
            
            Object.entries(data).forEach(([name, value]) => {
                const input = form.querySelector(`[name="${name}"]`);
                if (input) {
                    input.value = value;
                }
            });
            
            this.showRestoreNotification(form);
            return true;
            
        } catch (error) {
            console.error('Error restoring auto-saved data:', error);
            return false;
        }
    }
    
    /**
     * Show restore notification
     */
    showRestoreNotification(form) {
        const notification = document.createElement('div');
        notification.className = 'alert alert-info alert-dismissible fade show';
        notification.innerHTML = `
            <i class="fas fa-info-circle me-2"></i>
            Form data has been restored from auto-save.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        form.parentNode.insertBefore(notification, form);
    }
    
    /**
     * Handle update errors
     */
    handleUpdateError() {
        this.currentRetry++;
        
        if (this.currentRetry < this.retryAttempts) {
            setTimeout(() => {
                this.updateAll();
            }, 5000 * this.currentRetry); // Exponential backoff
        } else {
            console.warn('Real-time updates temporarily disabled due to errors');
            
            // Show connection error indicator
            this.showConnectionError();
        }
    }
    
    /**
     * Show connection error
     */
    showConnectionError() {
        const existingError = document.querySelector('.connection-error');
        if (existingError) return;
        
        const errorElement = document.createElement('div');
        errorElement.className = 'connection-error alert alert-warning position-fixed top-0 end-0 m-3';
        errorElement.style.zIndex = '9999';
        errorElement.innerHTML = `
            <i class="fas fa-exclamation-triangle me-2"></i>
            Connection issues detected. Some data may not be current.
            <button type="button" class="btn-close" onclick="this.parentNode.remove()"></button>
        `;
        
        document.body.appendChild(errorElement);
        
        // Auto-hide after 10 seconds
        setTimeout(() => {
            if (errorElement.parentNode) {
                errorElement.remove();
            }
        }, 10000);
    }
}

// Initialize real-time updates when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.buffaloRealTime = new BuffaloRealTimeUpdates();
    
    // Restore auto-saved data for registration form
    const registrationForm = document.getElementById('registration-form');
    if (registrationForm) {
        window.buffaloRealTime.restoreAutoSavedData('registration-form');
    }
});

// Export for manual initialization if needed
window.BuffaloRealTimeUpdates = BuffaloRealTimeUpdates;