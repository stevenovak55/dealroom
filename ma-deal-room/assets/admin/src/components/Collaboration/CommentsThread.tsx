import React, { useState, useRef, useEffect } from 'react';
import { MessageSquare, Send, Paperclip, AtSign, Smile, MoreVertical, Edit, Trash2, Reply, Heart, ThumbsUp } from 'lucide-react';
import { Card, CardContent } from '@/components/shared/Card';
import { Button } from '@/components/shared/Button';
import { formatDistanceToNow } from 'date-fns';
import toast from 'react-hot-toast';

export interface Comment {
  id: number;
  transaction_id?: number;
  task_id?: number;
  user_id: number;
  user_name: string;
  user_avatar?: string;
  content: string;
  mentions?: string[]; // Array of mentioned user IDs or names
  attachments?: Array<{
    id: number;
    name: string;
    url: string;
    type: string;
  }>;
  parent_comment_id?: number;
  reactions?: Record<string, number>; // { "like": 5, "love": 2 }
  is_edited?: boolean;
  created_at: string;
  updated_at?: string;
}

interface CommentsThreadProps {
  comments: Comment[];
  entityType: 'transaction' | 'task';
  entityId: number;
  currentUserId: number;
  currentUserName: string;
  onAddComment: (content: string, mentions?: string[], parentId?: number) => Promise<void>;
  onEditComment?: (commentId: number, content: string) => Promise<void>;
  onDeleteComment?: (commentId: number) => Promise<void>;
  onReactToComment?: (commentId: number, reaction: string) => Promise<void>;
}

interface User {
  id: number;
  name: string;
  avatar?: string;
}

// Mock users for mentions (in real app, this would come from props or API)
const MOCK_USERS: User[] = [
  { id: 1, name: 'John Smith' },
  { id: 2, name: 'Sarah Johnson' },
  { id: 3, name: 'Mike Williams' },
  { id: 4, name: 'Emily Brown' },
];

const REACTIONS = [
  { type: 'like', icon: ThumbsUp, label: 'Like' },
  { type: 'love', icon: Heart, label: 'Love' },
];

export const CommentsThread: React.FC<CommentsThreadProps> = ({
  comments,
  // entityType and entityId reserved for future filtering/organization
  currentUserId,
  // currentUserName reserved for future avatar/display
  onAddComment,
  onEditComment,
  onDeleteComment,
  onReactToComment,
}) => {
  const [newComment, setNewComment] = useState('');
  const [replyingTo, setReplyingTo] = useState<number | null>(null);
  const [editingComment, setEditingComment] = useState<number | null>(null);
  const [editContent, setEditContent] = useState('');
  const [showMentions, setShowMentions] = useState(false);
  const [mentionFilter, setMentionFilter] = useState('');
  const [selectedMentions, setSelectedMentions] = useState<string[]>([]);
  const [showReactions, setShowReactions] = useState<number | null>(null);
  const textareaRef = useRef<HTMLTextAreaElement>(null);

  // Auto-resize textarea
  useEffect(() => {
    if (textareaRef.current) {
      textareaRef.current.style.height = 'auto';
      textareaRef.current.style.height = textareaRef.current.scrollHeight + 'px';
    }
  }, [newComment]);

  // Handle @ mention trigger
  const handleTextChange = (value: string) => {
    setNewComment(value);

    // Check for @ symbol
    const lastAtIndex = value.lastIndexOf('@');
    if (lastAtIndex !== -1) {
      const textAfterAt = value.slice(lastAtIndex + 1);
      if (!textAfterAt.includes(' ')) {
        setMentionFilter(textAfterAt);
        setShowMentions(true);
      } else {
        setShowMentions(false);
      }
    } else {
      setShowMentions(false);
    }
  };

  const handleSelectMention = (user: User) => {
    const lastAtIndex = newComment.lastIndexOf('@');
    const beforeAt = newComment.slice(0, lastAtIndex);
    const newText = `${beforeAt}@${user.name} `;
    setNewComment(newText);
    setSelectedMentions([...selectedMentions, user.name]);
    setShowMentions(false);
    textareaRef.current?.focus();
  };

  const handleSubmit = async () => {
    if (!newComment.trim()) {
      toast.error('Please enter a comment');
      return;
    }

    try {
      await onAddComment(newComment, selectedMentions, replyingTo || undefined);
      setNewComment('');
      setSelectedMentions([]);
      setReplyingTo(null);
      toast.success('Comment added');
    } catch (error: any) {
      toast.error('Failed to add comment');
    }
  };

  const handleEdit = async (commentId: number) => {
    if (!editContent.trim()) {
      toast.error('Comment cannot be empty');
      return;
    }

    try {
      if (onEditComment) {
        await onEditComment(commentId, editContent);
        setEditingComment(null);
        setEditContent('');
        toast.success('Comment updated');
      }
    } catch (error: any) {
      toast.error('Failed to update comment');
    }
  };

  const handleDelete = async (commentId: number) => {
    if (!confirm('Delete this comment?')) return;

    try {
      if (onDeleteComment) {
        await onDeleteComment(commentId);
        toast.success('Comment deleted');
      }
    } catch (error: any) {
      toast.error('Failed to delete comment');
    }
  };

  const handleReact = async (commentId: number, reactionType: string) => {
    try {
      if (onReactToComment) {
        await onReactToComment(commentId, reactionType);
        setShowReactions(null);
      }
    } catch (error: any) {
      toast.error('Failed to add reaction');
    }
  };

  // Group comments into threads (top-level + replies)
  const commentThreads = React.useMemo(() => {
    const topLevel = comments.filter(c => !c.parent_comment_id);
    return topLevel.map(comment => ({
      comment,
      replies: comments.filter(c => c.parent_comment_id === comment.id),
    }));
  }, [comments]);

  const filteredUsers = MOCK_USERS.filter(user =>
    user.name.toLowerCase().includes(mentionFilter.toLowerCase())
  );

  const renderComment = (comment: Comment, isReply = false) => {
    const isEditing = editingComment === comment.id;
    const isAuthor = comment.user_id === currentUserId;

    return (
      <div
        key={comment.id}
        className={`flex gap-3 ${isReply ? 'ml-12 mt-3' : ''}`}
      >
        {/* Avatar */}
        <div className="flex-shrink-0">
          <div className="h-10 w-10 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center text-white font-semibold text-sm">
            {comment.user_name.charAt(0).toUpperCase()}
          </div>
        </div>

        {/* Content */}
        <div className="flex-1 min-w-0">
          <Card className="hover:shadow-md transition-shadow">
            <CardContent className="p-3">
              {/* Header */}
              <div className="flex items-center justify-between mb-2">
                <div>
                  <span className="font-semibold text-gray-900 text-sm">
                    {comment.user_name}
                  </span>
                  <span className="text-xs text-gray-500 ml-2">
                    {formatDistanceToNow(new Date(comment.created_at), { addSuffix: true })}
                  </span>
                  {comment.is_edited && (
                    <span className="text-xs text-gray-400 ml-2">(edited)</span>
                  )}
                </div>

                {isAuthor && !isEditing && (
                  <div className="relative">
                    <button className="p-1 text-gray-400 hover:text-gray-600 rounded">
                      <MoreVertical className="h-4 w-4" />
                    </button>
                    <div className="absolute right-0 mt-1 w-32 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-10 hidden group-hover:block">
                      <button
                        onClick={() => {
                          setEditingComment(comment.id);
                          setEditContent(comment.content);
                        }}
                        className="w-full px-3 py-1.5 text-left text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2"
                      >
                        <Edit className="h-3 w-3" />
                        Edit
                      </button>
                      <button
                        onClick={() => handleDelete(comment.id)}
                        className="w-full px-3 py-1.5 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-2"
                      >
                        <Trash2 className="h-3 w-3" />
                        Delete
                      </button>
                    </div>
                  </div>
                )}
              </div>

              {/* Comment content */}
              {isEditing ? (
                <div className="space-y-2">
                  <textarea
                    value={editContent}
                    onChange={(e) => setEditContent(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500"
                    rows={3}
                  />
                  <div className="flex items-center gap-2">
                    <Button
                      size="sm"
                      variant="primary"
                      onClick={() => handleEdit(comment.id)}
                    >
                      Save
                    </Button>
                    <Button
                      size="sm"
                      variant="secondary"
                      onClick={() => {
                        setEditingComment(null);
                        setEditContent('');
                      }}
                    >
                      Cancel
                    </Button>
                  </div>
                </div>
              ) : (
                <>
                  <p className="text-sm text-gray-700 whitespace-pre-wrap">
                    {comment.content}
                  </p>

                  {/* Attachments */}
                  {comment.attachments && comment.attachments.length > 0 && (
                    <div className="mt-2 space-y-1">
                      {comment.attachments.map((attachment) => (
                        <a
                          key={attachment.id}
                          href={attachment.url}
                          className="flex items-center gap-2 text-xs text-blue-600 hover:text-blue-700"
                        >
                          <Paperclip className="h-3 w-3" />
                          {attachment.name}
                        </a>
                      ))}
                    </div>
                  )}

                  {/* Reactions */}
                  {comment.reactions && Object.keys(comment.reactions).length > 0 && (
                    <div className="flex items-center gap-2 mt-2">
                      {Object.entries(comment.reactions).map(([type, count]) => {
                        const reaction = REACTIONS.find(r => r.type === type);
                        const Icon = reaction?.icon || ThumbsUp;
                        return (
                          <button
                            key={type}
                            onClick={() => handleReact(comment.id, type)}
                            className="px-2 py-0.5 bg-gray-100 hover:bg-gray-200 rounded-full text-xs font-medium flex items-center gap-1"
                          >
                            <Icon className="h-3 w-3" />
                            {count}
                          </button>
                        );
                      })}
                    </div>
                  )}

                  {/* Actions */}
                  <div className="flex items-center gap-3 mt-3 text-xs">
                    <button
                      onClick={() => setReplyingTo(comment.id)}
                      className="text-gray-600 hover:text-gray-900 font-medium flex items-center gap-1"
                    >
                      <Reply className="h-3 w-3" />
                      Reply
                    </button>
                    <div className="relative">
                      <button
                        onClick={() => setShowReactions(showReactions === comment.id ? null : comment.id)}
                        className="text-gray-600 hover:text-gray-900 font-medium flex items-center gap-1"
                      >
                        <Smile className="h-3 w-3" />
                        React
                      </button>
                      {showReactions === comment.id && (
                        <div className="absolute left-0 mt-1 bg-white rounded-lg shadow-lg border border-gray-200 p-2 flex gap-2 z-10">
                          {REACTIONS.map((reaction) => {
                            const Icon = reaction.icon;
                            return (
                              <button
                                key={reaction.type}
                                onClick={() => handleReact(comment.id, reaction.type)}
                                className="p-2 hover:bg-gray-100 rounded transition-colors"
                                title={reaction.label}
                              >
                                <Icon className="h-5 w-5" />
                              </button>
                            );
                          })}
                        </div>
                      )}
                    </div>
                  </div>
                </>
              )}
            </CardContent>
          </Card>
        </div>
      </div>
    );
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center gap-2">
        <MessageSquare className="h-5 w-5 text-gray-600" />
        <h3 className="text-lg font-semibold text-gray-900">
          Comments ({comments.length})
        </h3>
      </div>

      {/* New Comment */}
      <Card>
        <CardContent className="p-4">
          {replyingTo && (
            <div className="mb-2 p-2 bg-blue-50 rounded-lg flex items-center justify-between">
              <span className="text-sm text-blue-700">
                Replying to {comments.find(c => c.id === replyingTo)?.user_name}
              </span>
              <button
                onClick={() => setReplyingTo(null)}
                className="text-blue-600 hover:text-blue-700"
              >
                ✕
              </button>
            </div>
          )}

          <div className="relative">
            <textarea
              ref={textareaRef}
              value={newComment}
              onChange={(e) => handleTextChange(e.target.value)}
              placeholder="Add a comment... (Use @ to mention someone)"
              className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 resize-none"
              rows={3}
            />

            {/* Mention suggestions */}
            {showMentions && filteredUsers.length > 0 && (
              <div className="absolute bottom-full left-0 mb-2 w-full bg-white rounded-lg shadow-lg border border-gray-200 max-h-40 overflow-y-auto z-10">
                {filteredUsers.map((user) => (
                  <button
                    key={user.id}
                    onClick={() => handleSelectMention(user)}
                    className="w-full px-3 py-2 text-left text-sm hover:bg-gray-100 flex items-center gap-2"
                  >
                    <div className="h-6 w-6 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center text-white text-xs">
                      {user.name.charAt(0).toUpperCase()}
                    </div>
                    {user.name}
                  </button>
                ))}
              </div>
            )}
          </div>

          <div className="flex items-center justify-between mt-3">
            <div className="flex items-center gap-2">
              <button className="p-2 text-gray-400 hover:text-gray-600 rounded transition-colors">
                <Paperclip className="h-4 w-4" />
              </button>
              <button className="p-2 text-gray-400 hover:text-gray-600 rounded transition-colors">
                <AtSign className="h-4 w-4" />
              </button>
              <button className="p-2 text-gray-400 hover:text-gray-600 rounded transition-colors">
                <Smile className="h-4 w-4" />
              </button>
            </div>
            <Button
              size="sm"
              variant="primary"
              onClick={handleSubmit}
              disabled={!newComment.trim()}
            >
              <Send className="h-4 w-4 mr-2" />
              {replyingTo ? 'Reply' : 'Comment'}
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Comments list */}
      <div className="space-y-4">
        {commentThreads.length === 0 ? (
          <div className="text-center py-12 text-gray-500">
            <MessageSquare className="h-16 w-16 mx-auto mb-4 text-gray-300" />
            <p>No comments yet</p>
            <p className="text-sm mt-1">Be the first to comment!</p>
          </div>
        ) : (
          commentThreads.map(({ comment, replies }) => (
            <div key={comment.id}>
              {renderComment(comment)}
              {replies.map(reply => renderComment(reply, true))}
            </div>
          ))
        )}
      </div>
    </div>
  );
};
