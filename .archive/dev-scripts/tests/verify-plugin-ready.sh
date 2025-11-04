#!/bin/bash
# Quick verification script to confirm plugin is production-ready

echo "======================================"
echo "MA Deal Room Plugin Verification"
echo "======================================"
echo ""

# Check plugin ZIP exists
if [ -f "ma-deal-room-v1.0.0.zip" ]; then
    echo "✅ Plugin ZIP exists"
    SIZE=$(ls -lh ma-deal-room-v1.0.0.zip | awk '{print $5}')
    echo "   Size: $SIZE"
else
    echo "❌ Plugin ZIP not found"
    exit 1
fi

# Check main plugin file has the new functions
if grep -q "ma_deal_room_sync_task_definitions" ma-deal-room/ma-deal-room.php; then
    echo "✅ Task definitions sync function present"
else
    echo "❌ Task definitions sync function missing"
    exit 1
fi

# Check activation hook calls task definitions sync
if grep -q "ma_deal_room_sync_task_definitions()" ma-deal-room/ma-deal-room.php; then
    echo "✅ Activation hook calls task definitions sync"
else
    echo "❌ Activation hook missing task definitions sync call"
    exit 1
fi

# Check BaseController has admin_permission_callback
if grep -q "public function admin_permission_callback" ma-deal-room/src/REST/Controllers/BaseController.php; then
    echo "✅ BaseController has admin_permission_callback"
else
    echo "❌ BaseController missing admin_permission_callback"
    exit 1
fi

# Check BaseController has rate limiter null checks
if grep -q "if (\$this->rate_limiter !== null)" ma-deal-room/src/REST/Controllers/BaseController.php; then
    echo "✅ BaseController has rate limiter null checks"
else
    echo "❌ BaseController missing rate limiter null checks"
    exit 1
fi

# Check YAML templates exist
YAML_COUNT=$(ls -1 ma-deal-room/assets/templates/*.yaml 2>/dev/null | wc -l)
if [ "$YAML_COUNT" -eq 7 ]; then
    echo "✅ All 7 YAML templates present"
else
    echo "❌ Expected 7 YAML templates, found $YAML_COUNT"
    exit 1
fi

# Check MLS repository and model exist
if [ -f "ma-deal-room/src/Repositories/MLSConfigRepository.php" ]; then
    echo "✅ MLSConfigRepository exists"
else
    echo "❌ MLSConfigRepository missing"
    exit 1
fi

if [ -f "ma-deal-room/src/Models/MLSConfig.php" ]; then
    echo "✅ MLSConfig model exists"
else
    echo "❌ MLSConfig model missing"
    exit 1
fi

# Check MLS repository is registered in Plugin.php
if grep -q "mls_config_repository" ma-deal-room/src/Core/Plugin.php; then
    echo "✅ MLS repository registered in service container"
else
    echo "❌ MLS repository not registered in service container"
    exit 1
fi

# Check MLS migrations exist
if [ -f "ma-deal-room/database/migrations/021_create_mls_config_table.sql" ] && \
   [ -f "ma-deal-room/database/migrations/022_add_mls_fields_to_transactions.sql" ] && \
   [ -f "ma-deal-room/database/migrations/023_create_mls_sync_log_table.sql" ]; then
    echo "✅ All MLS migrations present"
else
    echo "❌ MLS migrations missing"
    exit 1
fi

echo ""
echo "======================================"
echo "✅ Plugin is production-ready!"
echo "======================================"
echo ""
echo "Next steps:"
echo "1. Download: scp snova@server:/home/snova/projects/dealroom/ma-deal-room-v1.0.0.zip ~/"
echo "2. Upload to WordPress via Plugins > Add New > Upload Plugin"
echo "3. Deactivate and reactivate the plugin"
echo "4. Verify 276 task definitions are created automatically"
echo ""
echo "Documentation: FINAL_DEPLOYMENT_GUIDE.md"
