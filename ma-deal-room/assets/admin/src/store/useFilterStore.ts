import { create } from 'zustand';

interface TransactionFilters {
  status?: string;
  propertyType?: string;
  search?: string;
  sortBy?: string;
  sortOrder?: 'asc' | 'desc';
}

interface TaskFilters {
  status?: string;
  assigneeRole?: string;
  overdue?: boolean;
  transactionId?: number;
}

interface FilterState {
  transactionFilters: TransactionFilters;
  taskFilters: TaskFilters;
  setTransactionFilters: (filters: Partial<TransactionFilters>) => void;
  setTaskFilters: (filters: Partial<TaskFilters>) => void;
  resetTransactionFilters: () => void;
  resetTaskFilters: () => void;
}

const defaultTransactionFilters: TransactionFilters = {
  sortBy: 'closing_date',
  sortOrder: 'asc',
};

const defaultTaskFilters: TaskFilters = {};

export const useFilterStore = create<FilterState>((set) => ({
  transactionFilters: defaultTransactionFilters,
  taskFilters: defaultTaskFilters,

  setTransactionFilters: (filters) =>
    set((state) => ({
      transactionFilters: { ...state.transactionFilters, ...filters },
    })),

  setTaskFilters: (filters) =>
    set((state) => ({
      taskFilters: { ...state.taskFilters, ...filters },
    })),

  resetTransactionFilters: () =>
    set({ transactionFilters: defaultTransactionFilters }),

  resetTaskFilters: () =>
    set({ taskFilters: defaultTaskFilters }),
}));
