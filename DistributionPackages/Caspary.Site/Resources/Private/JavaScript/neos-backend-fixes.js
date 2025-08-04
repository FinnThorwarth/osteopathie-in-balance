// Neos Backend Inline Editing Fixes
// This script ensures inline editing works after page changes in the backend

(function() {
    'use strict';
    
    console.log('=== NEOS BACKEND FIXES SCRIPT LOADED ===');
    console.log('Current URL:', window.location.href);
    console.log('Is in iframe:', window.parent !== window);
    console.log('Parent has Neos:', window.parent !== window ? !!window.parent.Neos : 'N/A');
    
    // Check for Neos in different ways
    if (window.parent !== window) {
        console.log('Parent window.Neos:', window.parent.Neos);
        console.log('Parent window.NeosMediaBrowserApp:', window.parent.NeosMediaBrowserApp);
        console.log('Parent window.NeosCKEditor5:', window.parent.NeosCKEditor5);
        console.log('Checking for Neos UI elements...');
        
        try {
            // Check if we're in Neos backend by looking at parent frame
            const parentDoc = window.parent.document;
            const hasNeosUI = parentDoc.querySelector('[class*="neos"]') || 
                             parentDoc.querySelector('#neos-application') ||
                             parentDoc.querySelector('.neos-backend');
            console.log('Parent has Neos UI elements:', !!hasNeosUI);
        } catch (e) {
            console.log('Cannot access parent document (cross-origin)');
        }
    }

    let isReinitializing = false;
    let reinitTimeout = null;

    // Store original Neos attributes for contenteditable elements
    const neosAttributeMap = new WeakMap();

    // Function to preserve Neos attributes
    function preserveNeosAttributes() {
        document.querySelectorAll('[contenteditable="true"]').forEach(el => {
            if (!neosAttributeMap.has(el)) {
                const attributes = {};
                // Store all Neos-related attributes
                Array.from(el.attributes).forEach(attr => {
                    if (attr.name.startsWith('data-__neos-') || attr.name === 'contenteditable') {
                        attributes[attr.name] = attr.value;
                    }
                });
                neosAttributeMap.set(el, attributes);
            }
        });
    }

    // Function to restore Neos attributes
    function restoreNeosAttributes() {
        document.querySelectorAll('[contenteditable], [data-__neos-property]').forEach(el => {
            const storedAttributes = neosAttributeMap.get(el);
            if (storedAttributes) {
                Object.entries(storedAttributes).forEach(([name, value]) => {
                    if (el.getAttribute(name) !== value) {
                        el.setAttribute(name, value);
                    }
                });
            }
        });
    }

    // Function to reinitialize Neos inline editing
    function reinitializeNeosInlineEditing() {
        // Check if we're in the Neos backend
        if (!window.parent || !window.parent.Neos || isReinitializing) {
            return;
        }

        isReinitializing = true;

        try {
            // First restore any lost attributes
            restoreNeosAttributes();

            // Dispatch custom event to notify Neos that the page has changed
            if (typeof Event === 'function') {
                window.dispatchEvent(new Event('Neos.PageLoaded'));
                window.dispatchEvent(new Event('Neos.ContentLoaded'));
            } else {
                // Fallback for older browsers
                var evt = document.createEvent('Event');
                evt.initEvent('Neos.PageLoaded', true, true);
                window.dispatchEvent(evt);
            }

            // Force Neos to reinitialize content elements
            if (window.parent.Neos && window.parent.Neos.Content) {
                console.log('Using Neos API to reinitialize');
                // Try multiple reinitialization methods
                if (window.parent.Neos.Content.Controller && window.parent.Neos.Content.Controller.pageLoaded) {
                    window.parent.Neos.Content.Controller.pageLoaded();
                }
                
                // Trigger content change event
                if (window.parent.Neos.EventDispatcher) {
                    window.parent.Neos.EventDispatcher.triggerEvent('Neos.Content.Changed');
                    window.parent.Neos.EventDispatcher.triggerEvent('Neos.Content.Updated');
                }

                // Re-scan for inline editable elements
                if (window.parent.Neos.Content.Model && window.parent.Neos.Content.Model.initialize) {
                    window.parent.Neos.Content.Model.initialize();
                }
            } else {
                console.log('Neos API not available, trying alternative method');
                // Try to communicate with parent window directly
                try {
                    window.parent.postMessage({
                        type: 'neos:contentReady',
                        origin: window.location.origin
                    }, '*');
                } catch (e) {
                    console.error('Could not post message to parent:', e);
                }
            }

            // Ensure all contenteditable elements are properly initialized
            document.querySelectorAll('[contenteditable="true"]').forEach(el => {
                // Trigger a focus/blur cycle to ensure Neos recognizes the element
                el.focus();
                el.blur();
            });

        } catch (error) {
            console.error('Error reinitializing Neos inline editing:', error);
        } finally {
            isReinitializing = false;
        }
    }

    // Debounced reinitialization
    function debouncedReinit() {
        clearTimeout(reinitTimeout);
        reinitTimeout = setTimeout(() => {
            reinitializeNeosInlineEditing();
        }, 250);
    }

    // Listen for page changes in the backend
    function setupBackendListeners() {
        // Check if we're in the Neos backend iframe OR in preview mode
        const isInIframe = window.parent !== window;
        const isNeosPreview = window.location.href.includes('/neos/preview');
        const isNeosBackend = isInIframe && (window.parent.Neos || isNeosPreview);
        
        console.log('Setup conditions:', { isInIframe, isNeosPreview, isNeosBackend });
        
        if (isNeosBackend) {
            console.log('Neos backend detected, setting up inline editing fixes');
            console.log('Parent window has Neos:', !!window.parent.Neos);
            console.log('Current window location:', window.location.href);

            // Add neos-backend class to body if not present
            if (!document.body.classList.contains('neos-backend')) {
                document.body.classList.add('neos-backend');
                console.log('Added neos-backend class to body');
            }

            console.log('Setting up initial check...');
            
            // Initial preservation of attributes
            setTimeout(() => {
                console.log('Timeout triggered, looking for contenteditable elements...');
                const allElements = document.querySelectorAll('*');
                console.log('Total elements in page:', allElements.length);
                
                const editableElements = document.querySelectorAll('[contenteditable="true"]');
                console.log('Found contenteditable elements:', editableElements.length);
                
                // Also look for Neos editable elements
                const neosEditables = document.querySelectorAll('[data-__neos-property]');
                console.log('Found elements with data-__neos-property:', neosEditables.length);
                
                // Check if any elements have the Neos.Neos:Editable rendered output
                const potentialEditables = document.querySelectorAll('span, div, p, h1, h2, h3, h4, h5, h6');
                let neosEditableCount = 0;
                potentialEditables.forEach(el => {
                    if (el.hasAttribute('data-__neos-property') || el.hasAttribute('data-__neos-editable-node-contextpath')) {
                        neosEditableCount++;
                        console.log('Neos editable element:', el.tagName, el.innerHTML.substring(0, 50), {
                            property: el.getAttribute('data-__neos-property'),
                            contenteditable: el.getAttribute('contenteditable'),
                            contextPath: el.getAttribute('data-__neos-editable-node-contextpath')
                        });
                    }
                });
                console.log('Total Neos editable elements found:', neosEditableCount);
                
                // Add contenteditable attribute to all Neos editable elements
                console.log('Adding contenteditable attributes...');
                neosEditables.forEach(el => {
                    if (!el.hasAttribute('contenteditable')) {
                        el.setAttribute('contenteditable', 'true');
                        console.log('Added contenteditable to:', el.tagName, el.getAttribute('data-__neos-property'));
                    }
                });
                
                preserveNeosAttributes();
                reinitializeNeosInlineEditing();
            }, 500);

            // Listen for Vue component updates
            document.addEventListener('vue:mounted', () => {
                console.log('Vue component mounted, reinitializing Neos');
                debouncedReinit();
            });
            
            // Watch for DOM changes
            const observer = new MutationObserver(function(mutations) {
                let needsReinit = false;

                mutations.forEach(mutation => {
                    // Check if contenteditable elements were affected
                    if (mutation.type === 'attributes' && 
                        (mutation.attributeName === 'contenteditable' || 
                         mutation.attributeName?.startsWith('data-__neos-'))) {
                        needsReinit = true;
                    }
                    
                    // Check if new nodes with contenteditable were added
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            if (node.querySelector && node.querySelector('[contenteditable], [data-__neos-property]')) {
                                needsReinit = true;
                            }
                            if (node.hasAttribute && (node.hasAttribute('contenteditable') || node.hasAttribute('data-__neos-property'))) {
                                needsReinit = true;
                            }
                        }
                    });
                });

                if (needsReinit) {
                    // Preserve attributes before Vue potentially overwrites them
                    preserveNeosAttributes();
                    debouncedReinit();
                }
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['contenteditable', 'data-__neos-property', 'data-__neos-editable-node-contextpath']
            });

            // Listen for Neos backend events
            window.addEventListener('Neos.NodeSelected', debouncedReinit);
            window.addEventListener('Neos.ContentChanged', debouncedReinit);
            
            // Override Vue's nextTick to reinitialize after Vue updates
            if (window.Vue && window.Vue.nextTick) {
                const originalNextTick = window.Vue.nextTick;
                window.Vue.nextTick = function(...args) {
                    return originalNextTick.apply(this, args).then(result => {
                        restoreNeosAttributes();
                        return result;
                    });
                };
            }
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupBackendListeners);
    } else {
        setupBackendListeners();
    }

    // Also initialize on window load
    window.addEventListener('load', function() {
        setupBackendListeners();
        setTimeout(reinitializeNeosInlineEditing, 1000);
    });

    // Expose function globally for debugging
    window.reinitializeNeosInlineEditing = reinitializeNeosInlineEditing;

})();