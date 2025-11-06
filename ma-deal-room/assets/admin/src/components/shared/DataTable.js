import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from './Table';
export function DataTable({ data, columns, className }) {
    return (_jsxs(Table, { className: className, children: [_jsx(TableHeader, { children: _jsx(TableRow, { children: columns.map((column, index) => (_jsx(TableHead, { children: column.header }, index))) }) }), _jsx(TableBody, { children: data.map((item, rowIndex) => (_jsx(TableRow, { children: columns.map((column, colIndex) => (_jsx(TableCell, { children: column.cell ? column.cell(item) : String(item[column.accessor] ?? '') }, colIndex))) }, rowIndex))) })] }));
}
