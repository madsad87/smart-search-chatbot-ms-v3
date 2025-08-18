/**
 * Smart Search Chatbot Loader
 * Site-agnostic chat widget loader that injects a floating bubble and iframe panel
 */
(function() {
  'use strict';

  // Prevent multiple instances
  if (window.SSGC_LOADER_LOADED) return;
  window.SSGC_LOADER_LOADED = true;

  // UUID generator (inline to avoid external dependencies)
  function generateUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
      const r = Math.random() * 16 | 0;
      const v = c === 'x' ? r : (r & 0x3 | 0x8);
      return v.toString(16);
    });
  }

  // Configuration
  const STORAGE_KEYS = {
    SESSION_ID: 'ssgc:session_id',
    UI_OPEN: 'ssgc:ui_open'
  };

  // Get script element and configuration
  const scriptEl = document.currentScript || document.querySelector('script[src*="ssgc-loader"]');
  const config = {
    configUrl: scriptEl?.getAttribute('data-config-url') || '',
    color: scriptEl?.getAttribute('data-color') || '#007cba',
    position: scriptEl?.getAttribute('data-position') || 'br', // br = bottom-right, bl = bottom-left
    widgetUrl: scriptEl?.getAttribute('data-widget-url') || './ssgc-widget.html'
  };

  // State management
  let sessionId = localStorage.getItem(STORAGE_KEYS.SESSION_ID);
  if (!sessionId) {
    sessionId = generateUUID();
    localStorage.setItem(STORAGE_KEYS.SESSION_ID, sessionId);
  }

  let isOpen = localStorage.getItem(STORAGE_KEYS.UI_OPEN) === '1';
  let bubble = null;
  let panel = null;
  let iframe = null;
  let widgetConfig = null;

  // Create and inject styles
  function injectStyles() {
    if (document.getElementById('ssgc-styles')) return;

    const styles = `
      #ssgc-bubble {
        position: fixed;
        bottom: 20px;
        ${config.position === 'bl' ? 'left' : 'right'}: 20px;
        width: 60px;
        height: 60px;
        background: ${config.color};
        border-radius: 50%;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 999999;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: none;
        outline: none;
      }

      #ssgc-bubble:hover {
        transform: scale(1.05);
        box-shadow: 0 6px 16px rgba(0,0,0,0.2);
      }

      #ssgc-bubble:focus {
        outline: 2px solid #fff;
        outline-offset: 2px;
      }

      #ssgc-bubble svg {
        width: 24px;
        height: 24px;
        fill: white;
      }

      #ssgc-panel {
        position: fixed;
        bottom: 90px;
        ${config.position === 'bl' ? 'left' : 'right'}: 20px;
        width: 350px;
        height: 500px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.12);
        z-index: 999998;
        display: none;
        overflow: hidden;
        border: 1px solid #e1e5e9;
      }

      #ssgc-panel.open {
        display: block;
        animation: ssgc-slideUp 0.3s ease-out;
      }

      @keyframes ssgc-slideUp {
        from {
          opacity: 0;
          transform: translateY(20px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      #ssgc-iframe {
        width: 100%;
        height: 100%;
        border: none;
        border-radius: 12px;
      }

      @media (max-width: 480px) {
        #ssgc-panel {
          width: calc(100vw - 40px);
          height: calc(100vh - 140px);
          bottom: 90px;
          left: 20px !important;
          right: 20px !important;
        }
      }
    `;

    const styleEl = document.createElement('style');
    styleEl.id = 'ssgc-styles';
    styleEl.textContent = styles;
    document.head.appendChild(styleEl);
  }

  // Create floating bubble
  function createBubble() {
    bubble = document.createElement('button');
    bubble.id = 'ssgc-bubble';
    bubble.setAttribute('aria-label', 'Open chat');
    bubble.innerHTML = `
      <svg viewBox="0 0 24 24">
        <path d="M20 2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h4l4 4 4-4h4c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/>
      </svg>
    `;

    bubble.addEventListener('click', togglePanel);
    bubble.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        togglePanel();
      }
    });

    document.body.appendChild(bubble);
  }

  // Create panel with iframe
  function createPanel() {
    panel = document.createElement('div');
    panel.id = 'ssgc-panel';

    iframe = document.createElement('iframe');
    iframe.id = 'ssgc-iframe';
    iframe.src = `${config.widgetUrl}?sid=${sessionId}`;
    iframe.setAttribute('title', 'Smart Search Chatbot');
    iframe.setAttribute('tabindex', '0');

    panel.appendChild(iframe);
    document.body.appendChild(panel);

    // Listen for messages from iframe
    window.addEventListener('message', handleIframeMessage);

    // Handle escape key to close panel
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && isOpen) {
        closePanel();
      }
    });
  }

  // Handle messages from iframe
  function handleIframeMessage(event) {
    if (!iframe || event.source !== iframe.contentWindow) return;

    const { type, data } = event.data;

    switch (type) {
      case 'ssgc:open':
        openPanel();
        break;
      case 'ssgc:close':
        closePanel();
        break;
      case 'ssgc:ready':
        // Send configuration to iframe if available
        if (widgetConfig || config.configUrl) {
          iframe.contentWindow.postMessage({
            type: 'ssgc:config',
            data: {
              config: widgetConfig,
              configUrl: config.configUrl,
              sessionId: sessionId
            }
          }, '*');
        }
        break;
    }
  }

  // Toggle panel open/closed
  function togglePanel() {
    if (isOpen) {
      closePanel();
    } else {
      openPanel();
    }
  }

  // Open panel
  function openPanel() {
    if (!panel) createPanel();
    
    isOpen = true;
    panel.classList.add('open');
    localStorage.setItem(STORAGE_KEYS.UI_OPEN, '1');
    
    // Focus iframe for accessibility
    setTimeout(() => {
      iframe.focus();
    }, 100);
  }

  // Close panel
  function closePanel() {
    isOpen = false;
    if (panel) {
      panel.classList.remove('open');
    }
    localStorage.setItem(STORAGE_KEYS.UI_OPEN, '0');
    
    // Return focus to bubble
    bubble.focus();
  }

  // Fetch widget configuration
  async function fetchWidgetConfig() {
    if (!config.configUrl) return;

    try {
      const response = await fetch(config.configUrl);
      if (response.ok) {
        widgetConfig = await response.json();
        
        // Update bubble color if provided in config
        if (widgetConfig.color && bubble) {
          bubble.style.background = widgetConfig.color;
        }
      }
    } catch (error) {
      console.warn('SSGC: Failed to fetch widget config:', error);
    }
  }

  // Initialize widget
  function init() {
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', init);
      return;
    }

    injectStyles();
    createBubble();
    
    // Fetch configuration
    fetchWidgetConfig();

    // Restore open state
    if (isOpen) {
      openPanel();
    }
  }

  // Start initialization
  init();

})();
