import { useState, useCallback, useMemo } from 'react';
export const useBulkSelection = ({ items, idField = 'id' }) => {
    const [selectedIds, setSelectedIds] = useState([]);
    // Get all item IDs
    const allIds = useMemo(() => {
        return items.map(item => item[idField]);
    }, [items, idField]);
    // Check if an item is selected
    const isSelected = useCallback((id) => {
        return selectedIds.includes(id);
    }, [selectedIds]);
    // Check if all items are selected
    const isAllSelected = useMemo(() => {
        return allIds.length > 0 && selectedIds.length === allIds.length;
    }, [allIds, selectedIds]);
    // Check if some (but not all) items are selected
    const isIndeterminate = useMemo(() => {
        return selectedIds.length > 0 && selectedIds.length < allIds.length;
    }, [allIds, selectedIds]);
    // Toggle a single item selection
    const toggleItem = useCallback((id) => {
        setSelectedIds(prev => {
            if (prev.includes(id)) {
                return prev.filter(itemId => itemId !== id);
            }
            else {
                return [...prev, id];
            }
        });
    }, []);
    // Toggle all items selection
    const toggleAll = useCallback(() => {
        if (isAllSelected) {
            setSelectedIds([]);
        }
        else {
            setSelectedIds(allIds);
        }
    }, [isAllSelected, allIds]);
    // Select multiple items
    const selectItems = useCallback((ids) => {
        setSelectedIds(prev => {
            const newIds = ids.filter(id => !prev.includes(id));
            return [...prev, ...newIds];
        });
    }, []);
    // Deselect multiple items
    const deselectItems = useCallback((ids) => {
        setSelectedIds(prev => prev.filter(id => !ids.includes(id)));
    }, []);
    // Clear all selections
    const clearSelection = useCallback(() => {
        setSelectedIds([]);
    }, []);
    // Select range (for shift-click functionality)
    const selectRange = useCallback((startId, endId) => {
        const startIndex = allIds.indexOf(startId);
        const endIndex = allIds.indexOf(endId);
        if (startIndex === -1 || endIndex === -1)
            return;
        const [minIndex, maxIndex] = [Math.min(startIndex, endIndex), Math.max(startIndex, endIndex)];
        const rangeIds = allIds.slice(minIndex, maxIndex + 1);
        selectItems(rangeIds);
    }, [allIds, selectItems]);
    // Get selected items (full objects)
    const selectedItems = useMemo(() => {
        return items.filter(item => selectedIds.includes(item[idField]));
    }, [items, selectedIds, idField]);
    return {
        selectedIds,
        selectedItems,
        isSelected,
        isAllSelected,
        isIndeterminate,
        toggleItem,
        toggleAll,
        selectItems,
        deselectItems,
        clearSelection,
        selectRange,
        selectedCount: selectedIds.length,
        totalCount: allIds.length,
    };
};
