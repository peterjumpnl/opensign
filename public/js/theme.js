/**
 * OpenSign UI Management
 * 
 * This file handles UI interactions for the OpenSign application.
 */

// Dropdown Toggle Functionality
function initDropdowns() {
    const dropdownButtons = document.querySelectorAll('[data-dropdown-toggle]');
    
    dropdownButtons.forEach(button => {
        const targetId = button.getAttribute('data-dropdown-toggle');
        const target = document.getElementById(targetId);
        
        if (target) {
            button.addEventListener('click', (e) => {
                e.stopPropagation();
                target.classList.toggle('hidden');
            });
        }
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        const dropdowns = document.querySelectorAll('[id^="dropdown-"]');
        dropdowns.forEach(dropdown => {
            if (!dropdown.contains(e.target) && !e.target.hasAttribute('data-dropdown-toggle')) {
                dropdown.classList.add('hidden');
            }
        });
    });
}

// Mobile Menu Toggle
function initMobileMenu() {
    const mobileMenuButton = document.querySelector('[data-mobile-menu-toggle]');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    }
}

// Notification Dismissal
function initNotificationDismissal() {
    const dismissButtons = document.querySelectorAll('[data-dismiss]');
    
    dismissButtons.forEach(button => {
        button.addEventListener('click', () => {
            const notification = button.closest('[role="alert"]');
            if (notification) {
                notification.remove();
            }
        });
    });
}

// Initialize all UI components
document.addEventListener('DOMContentLoaded', () => {
    initDropdowns();
    initMobileMenu();
    initNotificationDismissal();
});
