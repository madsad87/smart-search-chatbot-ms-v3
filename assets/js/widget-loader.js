/**
 * Smart Search Chatbot Widget Loader
 * 
 * Loads the floating chat widget with proper guards and config validation
 */

(function(){
    // Never run inside iframes
    if (window.top !== window.self) { 
        console.log('[SSGC] Widget loader skipped - running inside iframe');
        return; 
    }

    // Prevent double injection
    if (window.__SSGC_LOADER_LOADED__) { 
        console.log('[SSGC] Widget loader already loaded');
        return; 
    }
    window.__SSGC_LOADER_LOADED__ = true;

    var cfg = window.SSGC_WIDGET || {};
    if (!cfg.iframeSrc || !/^https?:\/\//i.test(cfg.iframeSrc)) {
        console.error('[SSGC] Missing or invalid iframeSrc from PHP. Aborting widget init.', cfg);
        return;
    }

    console.log('[SSGC] Initializing widget with config:', cfg);

    // UUID v4 generator
    function uuidv4() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    // Session management
    var LS = window.localStorage;
    var sidKey = (cfg.sessionPrefix || 'ssgc:') + 'session_id';
    var uiOpenKey = (cfg.sessionPrefix || 'ssgc:') + 'ui_open';
    var sid = LS.getItem(sidKey);
    if (!sid) { 
        sid = uuidv4(); 
        LS.setItem(sidKey, sid); 
    }

    // Build iframe URL with session ID
    var iframeSrc = cfg.iframeSrc + '?sid=' + encodeURIComponent(sid);
    console.log('[SSGC] Loading widget iframe:', iframeSrc);

    // Widget state
    var isOpen = LS.getItem(uiOpenKey) === '1';
    var bubble, panel, iframe;

    // Create widget elements
    function createWidget() {
        // Create bubble
        bubble = document.createElement('div');
        bubble.className = 'ssgc-bubble';
        bubble.innerHTML = '<svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M20 2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h4l4 4 4-4h4c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/></svg>';
        
        // Create panel
        panel = document.createElement('div');
        panel.className = 'ssgc-panel';
        
        // Create iframe
        iframe = document.createElement('iframe');
        iframe.src = iframeSrc;
        iframe.className = 'ssgc-iframe';
        iframe.setAttribute('frameborder', '0');
        iframe.setAttribute('allow', 'microphone; camera');
        
        panel.appendChild(iframe);
        
        // Add to page
        document.body.appendChild(bubble);
        document.body.appendChild(panel);
        
        // Apply styles
        applyStyles();
        
        // Set initial state
        updateUI();
        
        // Add event listeners
        bubble.addEventListener('click', toggleWidget);
        
        // Listen for messages from iframe
        window.addEventListener('message', handleMessage);
        
        // Proactive greeting
        if (cfg.proactive && cfg.proactive.enabled && !isOpen) {
            setTimeout(showProactiveGreeting, cfg.proactive.delay_ms || 8000);
        }
    }

    // Apply widget styles
    function applyStyles() {
        var position = cfg.position || 'br';
        var themeColor = cfg.themeColor || '#007cba';
        var zIndex = cfg.zIndex || 999999;
        
        // Bubble styles
        bubble.style.cssText = `
            position: fixed;
            ${position.includes('r') ? 'right' : 'left'}: 20px;
            bottom: 20px;
            width: 60px;
            height: 60px;
            background: ${themeColor};
            border-radius: 50%;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            cursor: pointer;
            z-index: ${zIndex};
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        `;
        
        // Panel styles
        panel.style.cssText = `
            position: fixed;
            ${position.includes('r') ? 'right' : 'left'}: 20px;
            bottom: 90px;
            width: 350px;
            height: 500px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            z-index: ${zIndex - 1};
            display: none;
            overflow: hidden;
        `;
        
        // Iframe styles
        iframe.style.cssText = `
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 12px;
        `;
        
        // Hover effects
        bubble.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
            this.style.boxShadow = '0 6px 16px rgba(0,0,0,0.2)';
        });
        
        bubble.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
            this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
        });
        
        // Responsive adjustments
        if (window.innerWidth < 400) {
            panel.style.width = (window.innerWidth - 40) + 'px';
            panel.style.left = '20px';
            panel.style.right = '20px';
        }
    }

    // Toggle widget open/closed
    function toggleWidget() {
        isOpen = !isOpen;
        LS.setItem(uiOpenKey, isOpen ? '1' : '0');
        updateUI();
        
        // Send message to iframe
        if (iframe && iframe.contentWindow) {
            iframe.contentWindow.postMessage({
                type: isOpen ? 'ssgc:open' : 'ssgc:close'
            }, '*');
        }
    }

    // Update UI state
    function updateUI() {
        if (panel) {
            panel.style.display = isOpen ? 'block' : 'none';
        }
        
        if (bubble) {
            bubble.setAttribute('aria-label', isOpen ? 'Close chat' : 'Open chat');
            bubble.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }
    }

    // Handle messages from iframe
    function handleMessage(event) {
        if (!event.data || typeof event.data !== 'object') return;
        
        var message = event.data;
        
        switch (message.type) {
            case 'ssgc:open':
                if (!isOpen) toggleWidget();
                break;
            case 'ssgc:close':
                if (isOpen) toggleWidget();
                break;
            case 'ssgc:resize':
                if (message.height && panel) {
                    panel.style.height = Math.min(message.height, window.innerHeight - 120) + 'px';
                }
                break;
        }
    }

    // Show proactive greeting
    function showProactiveGreeting() {
        if (isOpen || !cfg.proactive || !cfg.proactive.enabled) return;
        
        var greeting = document.createElement('div');
        greeting.className = 'ssgc-greeting';
        greeting.textContent = cfg.proactive.message || 'Need help?';
        greeting.style.cssText = `
            position: fixed;
            ${cfg.position && cfg.position.includes('r') ? 'right' : 'left'}: 90px;
            bottom: 40px;
            background: white;
            padding: 12px 16px;
            border-radius: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: ${(cfg.zIndex || 999999) - 2};
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 14px;
            color: #333;
            cursor: pointer;
            animation: ssgc-fade-in 0.3s ease;
        `;
        
        // Add animation keyframes
        if (!document.getElementById('ssgc-animations')) {
            var style = document.createElement('style');
            style.id = 'ssgc-animations';
            style.textContent = `
                @keyframes ssgc-fade-in {
                    from { opacity: 0; transform: translateY(10px); }
                    to { opacity: 1; transform: translateY(0); }
                }
            `;
            document.head.appendChild(style);
        }
        
        document.body.appendChild(greeting);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            if (greeting.parentNode) {
                greeting.style.animation = 'ssgc-fade-in 0.3s ease reverse';
                setTimeout(function() {
                    if (greeting.parentNode) {
                        greeting.parentNode.removeChild(greeting);
                    }
                }, 300);
            }
        }, 5000);
        
        // Click to open widget
        greeting.addEventListener('click', function() {
            if (!isOpen) toggleWidget();
            if (greeting.parentNode) {
                greeting.parentNode.removeChild(greeting);
            }
        });
    }

    // Handle escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isOpen) {
            toggleWidget();
        }
    });

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', createWidget);
    } else {
        createWidget();
    }

})();
