import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { cn } from '@/utils/cn';
export const Skeleton = ({ className }) => {
    return (_jsx("div", { className: cn('animate-pulse rounded-md bg-gray-200', className) }));
};
export const TaskCardSkeleton = () => {
    return (_jsxs("div", { className: "bg-white rounded-lg border border-gray-200 p-4 space-y-3", children: [_jsx("div", { className: "flex items-start justify-between", children: _jsxs("div", { className: "flex-1 space-y-2", children: [_jsx(Skeleton, { className: "h-4 w-3/4" }), _jsxs("div", { className: "flex gap-2", children: [_jsx(Skeleton, { className: "h-5 w-16" }), _jsx(Skeleton, { className: "h-5 w-16" })] })] }) }), _jsx(Skeleton, { className: "h-3 w-full" }), _jsx(Skeleton, { className: "h-3 w-5/6" }), _jsxs("div", { className: "space-y-2 pt-2", children: [_jsx(Skeleton, { className: "h-3 w-2/3" }), _jsx(Skeleton, { className: "h-3 w-1/2" })] })] }));
};
export const TaskLibrarySkeleton = () => {
    return (_jsx("div", { className: "space-y-8", children: [1, 2, 3].map((categoryIndex) => (_jsxs("div", { children: [_jsxs("div", { className: "flex items-center gap-3 mb-4", children: [_jsx(Skeleton, { className: "w-1 h-6 rounded-full" }), _jsx(Skeleton, { className: "h-6 w-48" }), _jsx(Skeleton, { className: "ml-auto h-4 w-16" })] }), _jsx("div", { className: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4", children: [1, 2, 3].map((taskIndex) => (_jsx(TaskCardSkeleton, {}, taskIndex))) })] }, categoryIndex))) }));
};
