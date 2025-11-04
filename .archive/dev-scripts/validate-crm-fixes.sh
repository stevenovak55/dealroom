#!/bin/bash
# Simple validation script for CRM deployment fixes

echo "========================================="
echo "CRM Deployment Fixes Validation"
echo "========================================="
echo ""

PASS=0
FAIL=0

# Test 1: Check Migration 024 uses correct table names
echo "✓ Test 1: Migration 024 table names..."
if grep -q "ma_deal_parties" ma-deal-room/database/migrations/024_add_crm_sync_fields.sql && \
   grep -q "ma_deal_custom_users" ma-deal-room/database/migrations/024_add_crm_sync_fields.sql && \
   ! grep -q "ma_contacts" ma-deal-room/database/migrations/024_add_crm_sync_fields.sql && \
   ! grep -q "\`{prefix}ma_users\`" ma-deal-room/database/migrations/024_add_crm_sync_fields.sql; then
    echo "  ✅ PASS - Migration 024 uses correct table names"
    ((PASS++))
else
    echo "  ❌ FAIL - Migration 024 has incorrect table names"
    ((FAIL++))
fi

# Test 2: Check ContactSyncService uses correct table
echo "✓ Test 2: ContactSyncService table reference..."
if grep -q "ma_deal_parties" ma-deal-room/src/Services/Integration/CRM/ContactSyncService.php && \
   ! grep -q "'ma_contacts'" ma-deal-room/src/Services/Integration/CRM/ContactSyncService.php; then
    echo "  ✅ PASS - ContactSyncService uses ma_deal_parties"
    ((PASS++))
else
    echo "  ❌ FAIL - ContactSyncService has incorrect table reference"
    ((FAIL++))
fi

# Test 3: Check CRMSyncController is registered
echo "✓ Test 3: CRMSyncController registration..."
if grep -q "use MADealRoom\\\\REST\\\\Controllers\\\\CRMSyncController;" ma-deal-room/src/Core/Plugin.php && \
   grep -q "crm_sync_controller" ma-deal-room/src/Core/Plugin.php; then
    echo "  ✅ PASS - CRMSyncController is registered"
    ((PASS++))
else
    echo "  ❌ FAIL - CRMSyncController not registered"
    ((FAIL++))
fi

# Test 4: Check uninstall.php includes CRM/MLS tables
echo "✓ Test 4: uninstall.php cleanup..."
if grep -q "ma_deal_crm_config" ma-deal-room/uninstall.php && \
   grep -q "ma_deal_mls_config" ma-deal-room/uninstall.php; then
    echo "  ✅ PASS - uninstall.php includes CRM/MLS tables"
    ((PASS++))
else
    echo "  ❌ FAIL - uninstall.php missing CRM/MLS tables"
    ((FAIL++))
fi

# Test 5: Check namespace consistency
echo "✓ Test 5: Namespace consistency..."
BAD_NAMESPACES=$(grep -r "namespace MA_Deal_Room" ma-deal-room/src/Services/Integration/CRM ma-deal-room/src/Repositories/CRMConfigRepository.php ma-deal-room/src/REST/Controllers/CRMSyncController.php 2>/dev/null | wc -l)
if [ "$BAD_NAMESPACES" -eq "0" ]; then
    echo "  ✅ PASS - All CRM files use MADealRoom namespace"
    ((PASS++))
else
    echo "  ❌ FAIL - Found $BAD_NAMESPACES files with incorrect namespace"
    ((FAIL++))
fi

# Summary
echo ""
echo "========================================="
echo "Test Results: $PASS passed, $FAIL failed"
echo "========================================="

if [ "$FAIL" -eq "0" ]; then
    echo "✅ ALL TESTS PASSED - Ready for deployment testing"
    exit 0
else
    echo "❌ SOME TESTS FAILED - Fix issues before deployment"
    exit 1
fi
