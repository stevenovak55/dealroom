import { create } from 'zustand';
const defaultTransactionFilters = {
    sortBy: 'closing_date',
    sortOrder: 'asc',
};
const defaultTaskFilters = {};
export const useFilterStore = create((set) => ({
    transactionFilters: defaultTransactionFilters,
    taskFilters: defaultTaskFilters,
    setTransactionFilters: (filters) => set((state) => ({
        transactionFilters: { ...state.transactionFilters, ...filters },
    })),
    setTaskFilters: (filters) => set((state) => ({
        taskFilters: { ...state.taskFilters, ...filters },
    })),
    resetTransactionFilters: () => set({ transactionFilters: defaultTransactionFilters }),
    resetTaskFilters: () => set({ taskFilters: defaultTaskFilters }),
}));
