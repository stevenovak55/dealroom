import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useMemo } from 'react';
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, Cell } from 'recharts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { formatDate, isOverdue } from '@/utils/formatDate';
export const TimelineView = ({ tasks }) => {
    // Prepare data for chart
    const chartData = useMemo(() => {
        return tasks
            .filter((task) => task.due_at)
            .map((task) => ({
            name: task.title,
            date: new Date(task.due_at).getTime(),
            status: task.status,
            isOverdue: isOverdue(task.due_at),
        }))
            .sort((a, b) => a.date - b.date);
    }, [tasks]);
    const getBarColor = (task) => {
        if (task.status === 'completed')
            return '#22c55e';
        if (task.isOverdue)
            return '#ef4444';
        return '#3b82f6';
    };
    return (_jsxs(Card, { children: [_jsx(CardHeader, { children: _jsx(CardTitle, { children: "Timeline" }) }), _jsx(CardContent, { children: chartData.length === 0 ? (_jsx("p", { className: "text-center text-gray-500 py-8", children: "No tasks with due dates" })) : (_jsx(ResponsiveContainer, { width: "100%", height: 400, children: _jsxs(BarChart, { data: chartData, layout: "vertical", children: [_jsx(XAxis, { type: "number", hide: true }), _jsx(YAxis, { dataKey: "name", type: "category", width: 200, tick: { fontSize: 12 } }), _jsx(Tooltip, { content: ({ payload }) => {
                                    if (!payload || !payload[0])
                                        return null;
                                    const data = payload[0].payload;
                                    return (_jsxs("div", { className: "bg-white p-3 border border-gray-200 rounded shadow-lg", children: [_jsx("p", { className: "font-medium text-gray-900", children: data.name }), _jsxs("p", { className: "text-sm text-gray-600", children: ["Due: ", formatDate(new Date(data.date).toISOString())] }), _jsxs("p", { className: "text-sm text-gray-600 capitalize", children: ["Status: ", data.status] })] }));
                                } }), _jsx(Bar, { dataKey: "date", radius: [0, 4, 4, 0], children: chartData.map((entry, index) => (_jsx(Cell, { fill: getBarColor(entry) }, `cell-${index}`))) })] }) })) })] }));
};
