$(document).ready(function() {
    let lastMessageId = 0;
    let isScrolledToBottom = true;
    let picker = null;
    
    // check if scrolled to bottom
    $('#chat-messages').scroll(function() {
        const element = $(this)[0];
        isScrolledToBottom = element.scrollHeight - element.scrollTop === element.clientHeight;
    });
    
    // fetch new messages periodically
    function fetchMessages() {
        $.get('api/messages.php', { last_id: lastMessageId })
            .done(function(response) {
                if (response.messages && response.messages.length > 0) {
                    response.messages.forEach(function(message) {
                        appendMessage(message);
                        lastMessageId = Math.max(lastMessageId, message.id);
                    });
                    if (isScrolledToBottom) {
                        scrollToBottom();
                    }
                }
            })
            .fail(function(xhr, status, error) {
                console.error('failed to get messages', error);
            });
    }
    
    // add message to chat window
    function appendMessage(message) {
        const isSelf = message.user_id == $('#current-user-id').val();
        const html = `
            <div class="message ${isSelf ? 'message-self' : 'message-other'}" data-message-id="${message.id}">
                <img src="${message.avatar || 'assets/default-avatar.png'}" class="avatar" data-user-id="${message.user_id}">
                <div class="message-content">
                    <span class="username" data-user-id="${message.user_id}">
                        ${message.nickname || message.username}
                        ${message.is_admin === '1' ? '<span class="admin-badge"><i class="fas fa-shield-alt"></i>Admin</span>' : ''}
                        <small class="text-muted location-text">${message.location ? `(${message.location})` : ''}</small>
                    </span>
                    <div class="bubble">
                        ${formatMessage(message.content)}
                    </div>
                    <div class="time small text-muted">
                        ${new Date(message.created_at).toLocaleString()}
                    </div>
                </div>
            </div>
        `;
        const $message = $(html);
        $('#chat-messages').append($message);
        $message.hide().fadeIn(200);
        if (isScrolledToBottom) {
            scrollToBottom();
        }
    }
    
    // format message content
    function formatMessage(content) {
        // escape HTML
        content = escapeHtml(content);
        // convert URLs to clickable links
        content = content.replace(
            /(https?:\/\/[^\s<]+[^<.,:;"')\]\s])/g, 
            '<a href="$1" target="_blank">$1</a>'
        );
        // support line breaks
        content = content.replace(/\n/g, '<br>');
        // support emoji display
        return content;
    }
    
    // send message
    $('#message-form').submit(function(e) {
        e.preventDefault();
        const content = $('#message-input').val().trim();
        
        if (content) {
            console.log('Sending message:', content); // add debug log
            
            $.post('api/messages.php', { content: content })
                .done(function(response) {
                    console.log('Server response:', response); // add debug log
                    if (response.success) {
                        $('#message-input').val('');
                        fetchMessages();
                        scrollToBottom();
                    } else {
                        alert(response.error || 'Failed to send message');
                    }
                })
                .fail(function(xhr, status, error) {
                    console.error('Failed to send message:', error);
                    console.error('Status code:', xhr.status);
                    console.error('Response text:', xhr.responseText);
                    alert('Failed to send message, please check network connection');
                });
        }
    });
    
    // update online users list
    function updateOnlineUsers() {
        $.get('api/users.php')
            .done(function(response) {
                if (response.users) {
                    const html = response.users.map(user => `
                        <a href="#" class="list-group-item list-group-item-action d-flex align-items-center" 
                           data-user-id="${user.id}">
                            <img src="${user.avatar || 'assets/default-avatar.png'}" 
                                 class="rounded-circle me-2" style="width: 32px; height: 32px;">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center">
                                    <span class="fw-bold">${user.nickname || user.username}</span>
                                    ${user.is_admin === '1' ? '<span class="admin-badge ms-2"><i class="fas fa-shield-alt"></i>管理员</span>' : ''}
                                    <small class="text-muted ms-2">${user.location ? `(${user.location})` : ''}</small>
                                </div>
                                <small class="text-muted">${user.signature || ''}</small>
                            </div>
                        </a>
                    `).join('');
                    $('#online-users').html(html);
                }
            })
            .fail(function(error) {
                console.error('failed to get online users', error);
            });
    }
    
    // show user profile
    $(document).on('click', '.username, .avatar, .list-group-item', function(e) {
        e.preventDefault();
        const userId = $(this).data('user-id');
        $.get('api/users.php', { user_id: userId })
            .done(function(response) {
                if (response.user) {
                    const user = response.user;
                    const html = `
                        <div class="text-center">
                            <img src="${user.avatar || 'assets/default-avatar.png'}" 
                                 class="rounded-circle mb-3" style="width: 100px; height: 100px;">
                            <h5>${user.nickname || user.username}</h5>
                            <p class="text-muted">${user.signature || ''}</p>
                            <p>Registration time: ${new Date(user.created_at).toLocaleDateString()}</p>
                        </div>
                    `;
                    $('.modal-body').html(html);
                    $('#userModal').modal('show');
                }
            });
    });
    
    // emoji button click event
    $('.emoji-picker-button').click(function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('.emoji-panel').toggle();
    });
    
    // select emoji
    $(document).on('click', '.emoji-item', function(e) {
        e.preventDefault();
        const emoji = $(this).text();
        const input = $('#message-input');
        const cursorPos = input[0].selectionStart;
        const text = input.val();
        const newText = text.slice(0, cursorPos) + emoji + text.slice(cursorPos);
        input.val(newText);
        input.focus();
        input[0].setSelectionRange(cursorPos + emoji.length, cursorPos + emoji.length);
        $('.emoji-panel').hide();
    });
    
    // click other places to close emoji panel
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.emoji-picker-button, .emoji-panel').length) {
            $('.emoji-panel').hide();
        }
    });
    
    // escape HTML special characters
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // scroll to bottom
    function scrollToBottom() {
        const chatMessages = $('#chat-messages');
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
    }
    
    // initialize
    fetchMessages();
    setInterval(fetchMessages, 3000);  // update messages every 3 seconds
    updateOnlineUsers();
    setInterval(updateOnlineUsers, 15000);  // update online users every 15 seconds
    scrollToBottom();
    
    // update user last activity time
    setInterval(function() {
        $.post('api/users.php', { action: 'heartbeat' });
    }, 60000);  // update last activity time every minute
    
    // support sending messages with Enter, and line breaks with Shift+Enter
    $('#message-input').keydown(function(e) {
        if (e.keyCode === 13 && !e.shiftKey) {
            e.preventDefault();
            $('#message-form').submit();
        }
    });
    
    // update user location information
    $.get('api/update_location.php');
}); 