import { useState, useEffect, useRef } from 'react';
import { format, formatDistanceToNow, isToday, isYesterday } from 'date-fns';
import { Send, MessageSquare, User, UserCircle } from 'lucide-react';
import { VendorMessage, VendorRequest, MessageData } from '../../api/vendorPortalClient';

interface MessageThreadProps {
  messages: VendorMessage[];
  unreadCount: number;
  vendorRequest: VendorRequest;
  onSendMessage: (data: MessageData) => void;
  onMarkAsRead: () => void;
  isSending: boolean;
}

export default function MessageThread({
  messages,
  unreadCount,
  vendorRequest,
  onSendMessage,
  onMarkAsRead,
  isSending,
}: MessageThreadProps) {
  const [message, setMessage] = useState('');
  const [senderName, setSenderName] = useState(vendorRequest.vendor_name || '');
  const messagesEndRef = useRef<HTMLDivElement>(null);
  const messageContainerRef = useRef<HTMLDivElement>(null);

  // Auto-scroll to bottom when new messages arrive
  useEffect(() => {
    if (messagesEndRef.current) {
      messagesEndRef.current.scrollIntoView({ behavior: 'smooth' });
    }
  }, [messages]);

  // Mark messages as read when component mounts or when new messages arrive
  useEffect(() => {
    if (unreadCount > 0) {
      // Delay marking as read to ensure user sees the messages
      const timer = setTimeout(() => {
        onMarkAsRead();
      }, 1000);
      return () => clearTimeout(timer);
    }
  }, [messages.length, unreadCount, onMarkAsRead]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    if (!message.trim() || !senderName.trim()) {
      return;
    }

    onSendMessage({
      sender_name: senderName.trim(),
      message: message.trim(),
    });

    setMessage('');
  };

  const formatMessageTime = (timestamp: string): string => {
    const date = new Date(timestamp);

    if (isToday(date)) {
      return format(date, 'h:mm a');
    } else if (isYesterday(date)) {
      return `Yesterday ${format(date, 'h:mm a')}`;
    } else {
      return format(date, 'MMM dd, h:mm a');
    }
  };

  const getRelativeTime = (timestamp: string): string => {
    try {
      return formatDistanceToNow(new Date(timestamp), { addSuffix: true });
    } catch {
      return '';
    }
  };

  const isVendorMessage = (msg: VendorMessage) => msg.sender_type === 'vendor';

  return (
    <div className="bg-white rounded-lg shadow-sm overflow-hidden flex flex-col h-[600px]">
      {/* Header */}
      <div className="bg-gradient-to-r from-purple-600 to-pink-600 px-6 py-4 text-white flex items-center justify-between">
        <div className="flex items-center gap-3">
          <MessageSquare className="w-6 h-6" />
          <div>
            <h2 className="text-xl font-bold">Messages</h2>
            <p className="text-purple-100 text-sm">
              Chat with your agent
            </p>
          </div>
        </div>
        {unreadCount > 0 && (
          <div className="bg-white text-purple-600 px-3 py-1 rounded-full text-sm font-bold">
            {unreadCount} new
          </div>
        )}
      </div>

      {/* Messages List */}
      <div
        ref={messageContainerRef}
        className="flex-1 overflow-y-auto p-6 space-y-4 bg-gray-50"
      >
        {messages.length === 0 ? (
          <div className="flex flex-col items-center justify-center h-full text-center">
            <div className="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mb-4">
              <MessageSquare className="w-8 h-8 text-purple-600" />
            </div>
            <h3 className="text-lg font-semibold text-gray-900 mb-2">
              No messages yet
            </h3>
            <p className="text-sm text-gray-600 max-w-xs">
              Start a conversation with your agent. They'll be notified when you send a message.
            </p>
          </div>
        ) : (
          <>
            {messages.map((msg, index) => {
              const isVendor = isVendorMessage(msg);
              const showDateDivider = index === 0 ||
                format(new Date(messages[index - 1].created_at), 'yyyy-MM-dd') !==
                format(new Date(msg.created_at), 'yyyy-MM-dd');

              return (
                <div key={msg.id}>
                  {/* Date Divider */}
                  {showDateDivider && (
                    <div className="flex items-center justify-center my-4">
                      <div className="bg-gray-200 text-gray-600 text-xs font-medium px-3 py-1 rounded-full">
                        {isToday(new Date(msg.created_at))
                          ? 'Today'
                          : isYesterday(new Date(msg.created_at))
                          ? 'Yesterday'
                          : format(new Date(msg.created_at), 'MMMM dd, yyyy')}
                      </div>
                    </div>
                  )}

                  {/* Message Bubble */}
                  <div className={`flex ${isVendor ? 'justify-end' : 'justify-start'}`}>
                    <div className={`flex gap-3 max-w-[80%] ${isVendor ? 'flex-row-reverse' : 'flex-row'}`}>
                      {/* Avatar */}
                      <div className={`
                        flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center
                        ${isVendor ? 'bg-blue-100 text-blue-600' : 'bg-purple-100 text-purple-600'}
                      `}>
                        {isVendor ? (
                          <User className="w-5 h-5" />
                        ) : (
                          <UserCircle className="w-5 h-5" />
                        )}
                      </div>

                      {/* Message Content */}
                      <div className={`flex flex-col ${isVendor ? 'items-end' : 'items-start'}`}>
                        <div className="flex items-baseline gap-2 mb-1">
                          <span className={`text-xs font-medium ${isVendor ? 'text-blue-700' : 'text-purple-700'}`}>
                            {msg.sender_name}
                          </span>
                          <span className="text-xs text-gray-500" title={format(new Date(msg.created_at), 'PPpp')}>
                            {formatMessageTime(msg.created_at)}
                          </span>
                        </div>

                        <div className={`
                          rounded-lg px-4 py-2.5 shadow-sm
                          ${isVendor
                            ? 'bg-blue-600 text-white rounded-tr-none'
                            : 'bg-white text-gray-900 border border-gray-200 rounded-tl-none'
                          }
                        `}>
                          <p className="text-sm whitespace-pre-wrap break-words">
                            {msg.message}
                          </p>
                        </div>

                        {!msg.is_read && isVendor && (
                          <span className="text-xs text-gray-500 mt-1">Sent</span>
                        )}
                        {msg.is_read && isVendor && (
                          <span className="text-xs text-green-600 mt-1">Read</span>
                        )}
                      </div>
                    </div>
                  </div>
                </div>
              );
            })}
            <div ref={messagesEndRef} />
          </>
        )}
      </div>

      {/* Message Input Form */}
      <div className="border-t border-gray-200 bg-white p-4">
        <form onSubmit={handleSubmit} className="space-y-3">
          {/* Sender Name Input (only if not set) */}
          {!vendorRequest.vendor_name && (
            <div>
              <input
                type="text"
                value={senderName}
                onChange={(e) => setSenderName(e.target.value)}
                placeholder="Your name"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm"
                disabled={isSending}
                required
              />
            </div>
          )}

          {/* Message Input */}
          <div className="flex gap-2">
            <textarea
              value={message}
              onChange={(e) => setMessage(e.target.value)}
              placeholder="Type your message..."
              rows={2}
              maxLength={2000}
              className="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent resize-none text-sm"
              disabled={isSending}
              required
            />
            <button
              type="submit"
              disabled={isSending || !message.trim() || !senderName.trim()}
              className="px-4 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2 self-end"
            >
              {isSending ? (
                <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
              ) : (
                <>
                  <Send className="w-5 h-5" />
                  <span className="hidden sm:inline">Send</span>
                </>
              )}
            </button>
          </div>

          {/* Character Counter */}
          <div className="flex items-center justify-between text-xs text-gray-500">
            <span>
              {message.length > 1800 && (
                <span className={message.length >= 2000 ? 'text-red-600 font-medium' : 'text-yellow-600'}>
                  {message.length}/2000 characters
                </span>
              )}
            </span>
            <span className="text-gray-400">
              Messages are delivered in real-time
            </span>
          </div>
        </form>
      </div>
    </div>
  );
}
