import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
import React, { useState, useRef, useEffect } from 'react';
import { MessageSquare, Send, Paperclip, AtSign, Smile, MoreVertical, Edit, Trash2, Reply, Heart, ThumbsUp } from 'lucide-react';
import { Card, CardContent } from '@/components/shared/Card';
import { Button } from '@/components/shared/Button';
import { formatDistanceToNow } from 'date-fns';
import toast from 'react-hot-toast';
// Mock users for mentions (in real app, this would come from props or API)
const MOCK_USERS = [
    { id: 1, name: 'John Smith' },
    { id: 2, name: 'Sarah Johnson' },
    { id: 3, name: 'Mike Williams' },
    { id: 4, name: 'Emily Brown' },
];
const REACTIONS = [
    { type: 'like', icon: ThumbsUp, label: 'Like' },
    { type: 'love', icon: Heart, label: 'Love' },
];
export const CommentsThread = ({ comments, 
// entityType and entityId reserved for future filtering/organization
currentUserId, 
// currentUserName reserved for future avatar/display
onAddComment, onEditComment, onDeleteComment, onReactToComment, }) => {
    const [newComment, setNewComment] = useState('');
    const [replyingTo, setReplyingTo] = useState(null);
    const [editingComment, setEditingComment] = useState(null);
    const [editContent, setEditContent] = useState('');
    const [showMentions, setShowMentions] = useState(false);
    const [mentionFilter, setMentionFilter] = useState('');
    const [selectedMentions, setSelectedMentions] = useState([]);
    const [showReactions, setShowReactions] = useState(null);
    const textareaRef = useRef(null);
    // Auto-resize textarea
    useEffect(() => {
        if (textareaRef.current) {
            textareaRef.current.style.height = 'auto';
            textareaRef.current.style.height = textareaRef.current.scrollHeight + 'px';
        }
    }, [newComment]);
    // Handle @ mention trigger
    const handleTextChange = (value) => {
        setNewComment(value);
        // Check for @ symbol
        const lastAtIndex = value.lastIndexOf('@');
        if (lastAtIndex !== -1) {
            const textAfterAt = value.slice(lastAtIndex + 1);
            if (!textAfterAt.includes(' ')) {
                setMentionFilter(textAfterAt);
                setShowMentions(true);
            }
            else {
                setShowMentions(false);
            }
        }
        else {
            setShowMentions(false);
        }
    };
    const handleSelectMention = (user) => {
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
        }
        catch (error) {
            toast.error('Failed to add comment');
        }
    };
    const handleEdit = async (commentId) => {
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
        }
        catch (error) {
            toast.error('Failed to update comment');
        }
    };
    const handleDelete = async (commentId) => {
        if (!confirm('Delete this comment?'))
            return;
        try {
            if (onDeleteComment) {
                await onDeleteComment(commentId);
                toast.success('Comment deleted');
            }
        }
        catch (error) {
            toast.error('Failed to delete comment');
        }
    };
    const handleReact = async (commentId, reactionType) => {
        try {
            if (onReactToComment) {
                await onReactToComment(commentId, reactionType);
                setShowReactions(null);
            }
        }
        catch (error) {
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
    const filteredUsers = MOCK_USERS.filter(user => user.name.toLowerCase().includes(mentionFilter.toLowerCase()));
    const renderComment = (comment, isReply = false) => {
        const isEditing = editingComment === comment.id;
        const isAuthor = comment.user_id === currentUserId;
        return (_jsxs("div", { className: `flex gap-3 ${isReply ? 'ml-12 mt-3' : ''}`, children: [_jsx("div", { className: "flex-shrink-0", children: _jsx("div", { className: "h-10 w-10 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center text-white font-semibold text-sm", children: comment.user_name.charAt(0).toUpperCase() }) }), _jsx("div", { className: "flex-1 min-w-0", children: _jsx(Card, { className: "hover:shadow-md transition-shadow", children: _jsxs(CardContent, { className: "p-3", children: [_jsxs("div", { className: "flex items-center justify-between mb-2", children: [_jsxs("div", { children: [_jsx("span", { className: "font-semibold text-gray-900 text-sm", children: comment.user_name }), _jsx("span", { className: "text-xs text-gray-500 ml-2", children: formatDistanceToNow(new Date(comment.created_at), { addSuffix: true }) }), comment.is_edited && (_jsx("span", { className: "text-xs text-gray-400 ml-2", children: "(edited)" }))] }), isAuthor && !isEditing && (_jsxs("div", { className: "relative", children: [_jsx("button", { className: "p-1 text-gray-400 hover:text-gray-600 rounded", children: _jsx(MoreVertical, { className: "h-4 w-4" }) }), _jsxs("div", { className: "absolute right-0 mt-1 w-32 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-10 hidden group-hover:block", children: [_jsxs("button", { onClick: () => {
                                                                setEditingComment(comment.id);
                                                                setEditContent(comment.content);
                                                            }, className: "w-full px-3 py-1.5 text-left text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2", children: [_jsx(Edit, { className: "h-3 w-3" }), "Edit"] }), _jsxs("button", { onClick: () => handleDelete(comment.id), className: "w-full px-3 py-1.5 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-2", children: [_jsx(Trash2, { className: "h-3 w-3" }), "Delete"] })] })] }))] }), isEditing ? (_jsxs("div", { className: "space-y-2", children: [_jsx("textarea", { value: editContent, onChange: (e) => setEditContent(e.target.value), className: "w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500", rows: 3 }), _jsxs("div", { className: "flex items-center gap-2", children: [_jsx(Button, { size: "sm", variant: "primary", onClick: () => handleEdit(comment.id), children: "Save" }), _jsx(Button, { size: "sm", variant: "secondary", onClick: () => {
                                                        setEditingComment(null);
                                                        setEditContent('');
                                                    }, children: "Cancel" })] })] })) : (_jsxs(_Fragment, { children: [_jsx("p", { className: "text-sm text-gray-700 whitespace-pre-wrap", children: comment.content }), comment.attachments && comment.attachments.length > 0 && (_jsx("div", { className: "mt-2 space-y-1", children: comment.attachments.map((attachment) => (_jsxs("a", { href: attachment.url, className: "flex items-center gap-2 text-xs text-blue-600 hover:text-blue-700", children: [_jsx(Paperclip, { className: "h-3 w-3" }), attachment.name] }, attachment.id))) })), comment.reactions && Object.keys(comment.reactions).length > 0 && (_jsx("div", { className: "flex items-center gap-2 mt-2", children: Object.entries(comment.reactions).map(([type, count]) => {
                                                const reaction = REACTIONS.find(r => r.type === type);
                                                const Icon = reaction?.icon || ThumbsUp;
                                                return (_jsxs("button", { onClick: () => handleReact(comment.id, type), className: "px-2 py-0.5 bg-gray-100 hover:bg-gray-200 rounded-full text-xs font-medium flex items-center gap-1", children: [_jsx(Icon, { className: "h-3 w-3" }), count] }, type));
                                            }) })), _jsxs("div", { className: "flex items-center gap-3 mt-3 text-xs", children: [_jsxs("button", { onClick: () => setReplyingTo(comment.id), className: "text-gray-600 hover:text-gray-900 font-medium flex items-center gap-1", children: [_jsx(Reply, { className: "h-3 w-3" }), "Reply"] }), _jsxs("div", { className: "relative", children: [_jsxs("button", { onClick: () => setShowReactions(showReactions === comment.id ? null : comment.id), className: "text-gray-600 hover:text-gray-900 font-medium flex items-center gap-1", children: [_jsx(Smile, { className: "h-3 w-3" }), "React"] }), showReactions === comment.id && (_jsx("div", { className: "absolute left-0 mt-1 bg-white rounded-lg shadow-lg border border-gray-200 p-2 flex gap-2 z-10", children: REACTIONS.map((reaction) => {
                                                                const Icon = reaction.icon;
                                                                return (_jsx("button", { onClick: () => handleReact(comment.id, reaction.type), className: "p-2 hover:bg-gray-100 rounded transition-colors", title: reaction.label, children: _jsx(Icon, { className: "h-5 w-5" }) }, reaction.type));
                                                            }) }))] })] })] }))] }) }) })] }, comment.id));
    };
    return (_jsxs("div", { className: "space-y-6", children: [_jsxs("div", { className: "flex items-center gap-2", children: [_jsx(MessageSquare, { className: "h-5 w-5 text-gray-600" }), _jsxs("h3", { className: "text-lg font-semibold text-gray-900", children: ["Comments (", comments.length, ")"] })] }), _jsx(Card, { children: _jsxs(CardContent, { className: "p-4", children: [replyingTo && (_jsxs("div", { className: "mb-2 p-2 bg-blue-50 rounded-lg flex items-center justify-between", children: [_jsxs("span", { className: "text-sm text-blue-700", children: ["Replying to ", comments.find(c => c.id === replyingTo)?.user_name] }), _jsx("button", { onClick: () => setReplyingTo(null), className: "text-blue-600 hover:text-blue-700", children: "\u2715" })] })), _jsxs("div", { className: "relative", children: [_jsx("textarea", { ref: textareaRef, value: newComment, onChange: (e) => handleTextChange(e.target.value), placeholder: "Add a comment... (Use @ to mention someone)", className: "w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 resize-none", rows: 3 }), showMentions && filteredUsers.length > 0 && (_jsx("div", { className: "absolute bottom-full left-0 mb-2 w-full bg-white rounded-lg shadow-lg border border-gray-200 max-h-40 overflow-y-auto z-10", children: filteredUsers.map((user) => (_jsxs("button", { onClick: () => handleSelectMention(user), className: "w-full px-3 py-2 text-left text-sm hover:bg-gray-100 flex items-center gap-2", children: [_jsx("div", { className: "h-6 w-6 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center text-white text-xs", children: user.name.charAt(0).toUpperCase() }), user.name] }, user.id))) }))] }), _jsxs("div", { className: "flex items-center justify-between mt-3", children: [_jsxs("div", { className: "flex items-center gap-2", children: [_jsx("button", { className: "p-2 text-gray-400 hover:text-gray-600 rounded transition-colors", children: _jsx(Paperclip, { className: "h-4 w-4" }) }), _jsx("button", { className: "p-2 text-gray-400 hover:text-gray-600 rounded transition-colors", children: _jsx(AtSign, { className: "h-4 w-4" }) }), _jsx("button", { className: "p-2 text-gray-400 hover:text-gray-600 rounded transition-colors", children: _jsx(Smile, { className: "h-4 w-4" }) })] }), _jsxs(Button, { size: "sm", variant: "primary", onClick: handleSubmit, disabled: !newComment.trim(), children: [_jsx(Send, { className: "h-4 w-4 mr-2" }), replyingTo ? 'Reply' : 'Comment'] })] })] }) }), _jsx("div", { className: "space-y-4", children: commentThreads.length === 0 ? (_jsxs("div", { className: "text-center py-12 text-gray-500", children: [_jsx(MessageSquare, { className: "h-16 w-16 mx-auto mb-4 text-gray-300" }), _jsx("p", { children: "No comments yet" }), _jsx("p", { className: "text-sm mt-1", children: "Be the first to comment!" })] })) : (commentThreads.map(({ comment, replies }) => (_jsxs("div", { children: [renderComment(comment), replies.map(reply => renderComment(reply, true))] }, comment.id)))) })] }));
};
