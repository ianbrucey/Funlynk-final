/**
 * Capacitor Navigation Handler
 * 
 * This module intercepts link clicks in the Capacitor mobile app and handles
 * them properly to prevent opening Safari/external browser.
 * 
 * For web browsers, this does nothing and allows normal navigation.
 */

import { Capacitor } from '@capacitor/core';

// Only run this code if we're in a native mobile app
if (Capacitor.isNativePlatform()) {
    console.log('Capacitor: Initializing navigation handler for native platform');

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', () => {
        console.log('Capacitor: Setting up link click interceptor');

        // Intercept all link clicks
        document.addEventListener('click', (event) => {
            // Find the closest <a> tag (in case user clicked on child element)
            const link = event.target.closest('a');
            
            if (!link) return;

            const href = link.getAttribute('href');
            
            // Ignore if no href or it's a hash link
            if (!href || href.startsWith('#')) return;

            // Ignore if it's an external link (starts with http:// or https://)
            if (href.startsWith('http://') || href.startsWith('https://')) {
                // Allow external links to open in system browser
                return;
            }

            // Ignore if it has target="_blank" (user wants new window)
            if (link.getAttribute('target') === '_blank') {
                return;
            }

            // Ignore if it's a download link
            if (link.hasAttribute('download')) {
                return;
            }

            // This is an internal link - prevent default and navigate within the app
            event.preventDefault();
            event.stopPropagation();

            console.log('Capacitor: Navigating to internal route:', href);

            // Navigate within the WebView
            window.location.href = href;
        }, true); // Use capture phase to catch events before other handlers

        console.log('Capacitor: Navigation handler initialized');
    });
} else {
    console.log('Capacitor: Running in web browser, navigation handler not needed');
}

