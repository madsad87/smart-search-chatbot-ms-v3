/**
 * Smart Search Chatbot Frontend JavaScript
 * 
 * Handles the shortcode chatbot interface interactions
 */

(function($) {
    'use strict';
    
    // Wait for DOM ready
    $(document).ready(function() {
        initChatbot();
    });
    
    /**
     * Initialize chatbot functionality
     */
    function initChatbot() {
        const $container = $('.ssgc-chatbot-container');
        const $messages = $('#ssgc-messages');
        const $input = $('#ssgc-input');
        const $sendBtn = $('#ssgc-send');
        
        if (!$container.length) {
            return; // No chatbot on this page
        }
        
        // Generate or get session ID
        let sessionId = getSessionId();
        
        // Add welcome message
        addMessage('bot', 'Hello! How can I help you today?');
        
        // Handle send button click
        $sendBtn.on('click', function() {
            sendMessage();
        });
        
        // Handle enter key press
        $input.on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                sendMessage();
            }
        });
        
        /**
         * Send a message
         */
        function sendMessage() {
            const message = $input.val().trim();
            
            if (!message) {
                return;
            }
            
            // Add user message to chat
            addMessage('user', message);
            
            // Clear input
            $input.val('');
            
            // Show typing indicator
            showTyping();
            
            // Send to server
            $.ajax({
                url: ssgc_ajax.rest_url + 'chat',
                method: 'POST',
                headers: {
                    'X-WP-Nonce': ssgc_ajax.nonce
                },
                data: JSON.stringify({
                    session_id: sessionId,
                    prompt: message
                }),
                contentType: 'application/json',
                success: function(response) {
                    hideTyping();
                    
                    if (response.text) {
                        addMessage('bot', response.text, response.citations);
                    } else {
                        addMessage('bot', 'Sorry, I encountered an error. Please try again.');
                    }
                },
                error: function(xhr, status, error) {
                    hideTyping();
                    console.error('Chat error:', error);
                    addMessage('bot', 'Sorry, I\'m having trouble connecting. Please try again later.');
                }
            });
        }
        
        /**
         * Add a message to the chat
         */
        function addMessage(type, text, citations) {
            const $message = $('<div class="ssgc-message ssgc-message-' + type + '"></div>');
            const $content = $('<div class="ssgc-message-content"></div>').text(text);
            
            $message.append($content);
            
            // Add citations if present
            if (citations && citations.length > 0) {
                const $citations = $('<div class="ssgc-citations"></div>');
                $citations.append('<div class="ssgc-citations-title">Sources:</div>');
                
                citations.forEach(function(citation) {
                    const $citation = $('<a class="ssgc-citation" target="_blank"></a>')
                        .attr('href', citation.url)
                        .text(citation.title);
                    $citations.append($citation);
                });
                
                $message.append($citations);
            }
            
            $messages.append($message);
            scrollToBottom();
        }
        
        /**
         * Show typing indicator
         */
        function showTyping() {
            const $typing = $('<div class="ssgc-message ssgc-message-bot ssgc-typing"></div>');
            $typing.append('<div class="ssgc-message-content">Thinking...</div>');
            $messages.append($typing);
            scrollToBottom();
        }
        
        /**
         * Hide typing indicator
         */
        function hideTyping() {
            $('.ssgc-typing').remove();
        }
        
        /**
         * Scroll to bottom of messages
         */
        function scrollToBottom() {
            $messages.scrollTop($messages[0].scrollHeight);
        }
        
        /**
         * Get or generate session ID
         */
        function getSessionId() {
            let sessionId = localStorage.getItem('ssgc_session_id');
            
            if (!sessionId) {
                sessionId = generateUUID();
                localStorage.setItem('ssgc_session_id', sessionId);
            }
            
            return sessionId;
        }
        
        /**
         * Generate a simple UUID
         */
        function generateUUID() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                const r = Math.random() * 16 | 0;
                const v = c === 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        }
    }
    
})(jQuery);
