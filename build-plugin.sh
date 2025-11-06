#!/bin/bash

# MA Deal Room Plugin Build Script
# Creates a production-ready WordPress plugin zip file

set -e

echo "================================================"
echo "MA Deal Room WordPress Plugin Build Script"
echo "================================================"
echo ""

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
PLUGIN_SLUG="ma-deal-room"
PLUGIN_DIR="/home/user/dealroom/${PLUGIN_SLUG}"
BUILD_DIR="/home/user/dealroom/build"
DIST_DIR="/home/user/dealroom/releases"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
VERSION=$(grep "Version:" "${PLUGIN_DIR}/ma-deal-room.php" | head -1 | awk '{print $3}')

echo -e "${BLUE}Plugin Version:${NC} ${VERSION}"
echo -e "${BLUE}Build Timestamp:${NC} ${TIMESTAMP}"
echo ""

# Step 1: Clean previous builds
echo -e "${YELLOW}Step 1/6: Cleaning previous builds...${NC}"
rm -rf "${BUILD_DIR}"
mkdir -p "${BUILD_DIR}"
mkdir -p "${DIST_DIR}"

# Step 2: Build React admin assets
echo -e "${YELLOW}Step 2/6: Building React admin assets...${NC}"
cd "${PLUGIN_DIR}/assets/admin"
if [ ! -d "node_modules" ]; then
    echo "Installing npm dependencies..."
    npm install --silent
fi
npm run build --silent
cd -

# Step 3: Copy plugin files to build directory
echo -e "${YELLOW}Step 3/6: Copying plugin files...${NC}"
mkdir -p "${BUILD_DIR}/${PLUGIN_SLUG}"

# Copy main plugin files
cp -r "${PLUGIN_DIR}/src" "${BUILD_DIR}/${PLUGIN_SLUG}/"
cp -r "${PLUGIN_DIR}/database" "${BUILD_DIR}/${PLUGIN_SLUG}/"
cp -r "${PLUGIN_DIR}/templates" "${BUILD_DIR}/${PLUGIN_SLUG}/"
cp -r "${PLUGIN_DIR}/assets/templates" "${BUILD_DIR}/${PLUGIN_SLUG}/assets/"
cp "${PLUGIN_DIR}/ma-deal-room.php" "${BUILD_DIR}/${PLUGIN_SLUG}/"
cp "${PLUGIN_DIR}/readme.txt" "${BUILD_DIR}/${PLUGIN_SLUG}/" 2>/dev/null || true
cp "${PLUGIN_DIR}/LICENSE" "${BUILD_DIR}/${PLUGIN_SLUG}/" 2>/dev/null || true

# Copy built admin assets (dist folder only, not source)
mkdir -p "${BUILD_DIR}/${PLUGIN_SLUG}/assets/admin"
cp -r "${PLUGIN_DIR}/assets/admin/dist" "${BUILD_DIR}/${PLUGIN_SLUG}/assets/admin/"

# Copy public assets if they exist
if [ -d "${PLUGIN_DIR}/assets/public" ]; then
    cp -r "${PLUGIN_DIR}/assets/public" "${BUILD_DIR}/${PLUGIN_SLUG}/assets/"
fi

# Copy vendor dependencies if composer was used
if [ -d "${PLUGIN_DIR}/vendor" ]; then
    echo "Copying vendor dependencies..."
    cp -r "${PLUGIN_DIR}/vendor" "${BUILD_DIR}/${PLUGIN_SLUG}/"
fi

# Step 4: Remove development files from build
echo -e "${YELLOW}Step 4/6: Removing development files...${NC}"
cd "${BUILD_DIR}/${PLUGIN_SLUG}"

# Remove development files and directories
find . -type d -name ".git" -exec rm -rf {} + 2>/dev/null || true
find . -type f -name ".gitignore" -delete 2>/dev/null || true
find . -type f -name ".DS_Store" -delete 2>/dev/null || true
find . -type f -name "*.log" -delete 2>/dev/null || true
find . -type d -name "node_modules" -exec rm -rf {} + 2>/dev/null || true
find . -type d -name "tests" -exec rm -rf {} + 2>/dev/null || true
find . -type d -name "docs" -exec rm -rf {} + 2>/dev/null || true
find . -type f -name "*.md" -not -name "readme.txt" -delete 2>/dev/null || true
find . -type f -name "composer.json" -delete 2>/dev/null || true
find . -type f -name "composer.lock" -delete 2>/dev/null || true
find . -type f -name "package.json" -delete 2>/dev/null || true
find . -type f -name "package-lock.json" -delete 2>/dev/null || true
find . -type f -name "phpunit.xml" -delete 2>/dev/null || true
find . -type f -name ".eslintrc.*" -delete 2>/dev/null || true
find . -type f -name ".prettierrc" -delete 2>/dev/null || true
find . -type f -name "tsconfig*.json" -delete 2>/dev/null || true
find . -type f -name "vite.config.*" -delete 2>/dev/null || true
find . -type f -name "tailwind.config.*" -delete 2>/dev/null || true
find . -type f -name "postcss.config.*" -delete 2>/dev/null || true

cd -

# Step 5: Create zip file
echo -e "${YELLOW}Step 5/6: Creating zip file...${NC}"
cd "${BUILD_DIR}"
ZIP_NAME="${PLUGIN_SLUG}-v${VERSION}-${TIMESTAMP}.zip"
ZIP_LATEST="${PLUGIN_SLUG}-latest.zip"

zip -r "${ZIP_NAME}" "${PLUGIN_SLUG}" -q
cp "${ZIP_NAME}" "${ZIP_LATEST}"

# Move to releases directory
mv "${ZIP_NAME}" "${DIST_DIR}/"
mv "${ZIP_LATEST}" "${DIST_DIR}/"

cd -

# Step 6: Verify and report
echo -e "${YELLOW}Step 6/6: Verifying build...${NC}"
ZIP_SIZE=$(du -h "${DIST_DIR}/${ZIP_NAME}" | cut -f1)
ZIP_FILE_COUNT=$(unzip -l "${DIST_DIR}/${ZIP_NAME}" | tail -1 | awk '{print $2}')

echo ""
echo -e "${GREEN}================================================${NC}"
echo -e "${GREEN}Build completed successfully!${NC}"
echo -e "${GREEN}================================================${NC}"
echo ""
echo -e "${BLUE}Plugin:${NC} ${PLUGIN_SLUG}"
echo -e "${BLUE}Version:${NC} ${VERSION}"
echo -e "${BLUE}Zip File:${NC} ${ZIP_NAME}"
echo -e "${BLUE}Size:${NC} ${ZIP_SIZE}"
echo -e "${BLUE}Files:${NC} ${ZIP_FILE_COUNT}"
echo ""
echo -e "${BLUE}Location:${NC} ${DIST_DIR}/${ZIP_NAME}"
echo -e "${BLUE}Latest:${NC} ${DIST_DIR}/${ZIP_LATEST}"
echo ""
echo -e "${GREEN}Ready to install on WordPress!${NC}"
echo ""

# List contents for verification
echo "Zip contents preview:"
unzip -l "${DIST_DIR}/${ZIP_NAME}" | head -30
echo ""
echo "(Showing first 30 files)"
echo ""
