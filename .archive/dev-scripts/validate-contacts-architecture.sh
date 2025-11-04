#!/bin/bash
# Validation script for global contacts architecture

echo "========================================="
echo "Global Contacts Architecture Validation"
echo "========================================="
echo ""

PASS=0
FAIL=0

# Test 1: Migration 026 exists
echo "✓ Test 1: Migration 026 exists..."
if [ -f "ma-deal-room/database/migrations/026_create_contacts_table.sql" ]; then
    echo "  ✅ PASS - Migration 026 file exists"
    ((PASS++))
else
    echo "  ❌ FAIL - Migration 026 not found"
    ((FAIL++))
fi

# Test 2: Migration 026 creates ma_deal_contacts table
echo "✓ Test 2: Migration 026 creates contacts table..."
if grep -q "CREATE TABLE.*ma_deal_contacts" ma-deal-room/database/migrations/026_create_contacts_table.sql && \
   grep -q "ALTER TABLE.*ma_deal_parties" ma-deal-room/database/migrations/026_create_contacts_table.sql && \
   grep -q "contact_id" ma-deal-room/database/migrations/026_create_contacts_table.sql; then
    echo "  ✅ PASS - Migration creates contacts and links parties"
    ((PASS++))
else
    echo "  ❌ FAIL - Migration incomplete"
    ((FAIL++))
fi

# Test 3: Migration 024 updated to use custom_users only
echo "✓ Test 3: Migration 024 uses custom_users..."
if grep -q "ma_deal_custom_users" ma-deal-room/database/migrations/024_add_crm_sync_fields.sql && \
   ! grep -q "ma_deal_parties.*crm_id" ma-deal-room/database/migrations/024_add_crm_sync_fields.sql && \
   ! grep -q "\`{prefix}ma_contacts\`" ma-deal-room/database/migrations/024_add_crm_sync_fields.sql; then
    echo "  ✅ PASS - Migration 024 updated correctly"
    ((PASS++))
else
    echo "  ❌ FAIL - Migration 024 still references old tables"
    ((FAIL++))
fi

# Test 4: ContactSyncService uses ma_deal_contacts
echo "✓ Test 4: ContactSyncService uses ma_deal_contacts..."
if grep -q "ma_deal_contacts" ma-deal-room/src/Services/Integration/CRM/ContactSyncService.php && \
   ! grep -q "'ma_deal_parties'" ma-deal-room/src/Services/Integration/CRM/ContactSyncService.php; then
    echo "  ✅ PASS - ContactSyncService uses ma_deal_contacts"
    ((PASS++))
else
    echo "  ❌ FAIL - ContactSyncService still uses ma_deal_parties"
    ((FAIL++))
fi

# Test 5: ContactSyncService stores sync errors
echo "✓ Test 5: ContactSyncService error handling..."
if grep -q "crm_sync_error" ma-deal-room/src/Services/Integration/CRM/ContactSyncService.php; then
    echo "  ✅ PASS - Error field stored in sync errors"
    ((PASS++))
else
    echo "  ❌ FAIL - crm_sync_error not stored"
    ((FAIL++))
fi

# Test 6: Data migration script exists
echo "✓ Test 6: Data migration script exists..."
if [ -f "migrate-parties-to-contacts.php" ] && \
   grep -q "DRY RUN" migrate-parties-to-contacts.php; then
    echo "  ✅ PASS - Data migration script with dry-run support"
    ((PASS++))
else
    echo "  ❌ FAIL - Data migration script missing or incomplete"
    ((FAIL++))
fi

# Test 7: Documentation exists
echo "✓ Test 7: Documentation complete..."
if [ -f "CONTACTS_ARCHITECTURE_GUIDE.md" ] && \
   grep -q "ma_deal_contacts" CONTACTS_ARCHITECTURE_GUIDE.md && \
   grep -q "Migration Process" CONTACTS_ARCHITECTURE_GUIDE.md; then
    echo "  ✅ PASS - Complete architecture documentation"
    ((PASS++))
else
    echo "  ❌ FAIL - Documentation missing or incomplete"
    ((FAIL++))
fi

# Test 8: CRM fields in contacts table
echo "✓ Test 8: Contacts table has CRM fields..."
if grep -q "crm_id" ma-deal-room/database/migrations/026_create_contacts_table.sql && \
   grep -q "crm_provider" ma-deal-room/database/migrations/026_create_contacts_table.sql && \
   grep -q "crm_sync_status" ma-deal-room/database/migrations/026_create_contacts_table.sql && \
   grep -q "crm_sync_error" ma-deal-room/database/migrations/026_create_contacts_table.sql; then
    echo "  ✅ PASS - All CRM sync fields present"
    ((PASS++))
else
    echo "  ❌ FAIL - Missing CRM sync fields"
    ((FAIL++))
fi

# Test 9: Email uniqueness constraint
echo "✓ Test 9: Email uniqueness constraint..."
if grep -q "UNIQUE.*account_email" ma-deal-room/database/migrations/026_create_contacts_table.sql; then
    echo "  ✅ PASS - Email uniqueness per account enforced"
    ((PASS++))
else
    echo "  ❌ FAIL - Missing email uniqueness constraint"
    ((FAIL++))
fi

# Test 10: CRM ID uniqueness constraint
echo "✓ Test 10: CRM ID uniqueness constraint..."
if grep -q "UNIQUE.*crm_unique" ma-deal-room/database/migrations/026_create_contacts_table.sql; then
    echo "  ✅ PASS - CRM ID uniqueness enforced"
    ((PASS++))
else
    echo "  ❌ FAIL - Missing CRM ID uniqueness constraint"
    ((FAIL++))
fi

# Summary
echo ""
echo "========================================="
echo "Test Results: $PASS passed, $FAIL failed"
echo "========================================="

if [ "$FAIL" -eq "0" ]; then
    echo "✅ ALL TESTS PASSED - Contacts architecture ready"
    echo ""
    echo "Next steps:"
    echo "1. Run migration 026: wp ma-deal migrate"
    echo "2. Run data migration: php migrate-parties-to-contacts.php --dry-run"
    echo "3. If dry-run looks good: php migrate-parties-to-contacts.php"
    echo "4. Test CRM sync with new architecture"
    exit 0
else
    echo "❌ SOME TESTS FAILED - Review issues before deployment"
    exit 1
fi
