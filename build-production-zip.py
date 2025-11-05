#!/usr/bin/env python3
"""
Build production-ready plugin ZIP file for WordPress deployment
Excludes all development files, tests, and documentation not needed for production
"""
import zipfile
import os
from pathlib import Path

def should_exclude(file_path, base_path):
    """Check if file should be excluded from production ZIP"""

    # Convert to relative path for easier pattern matching
    try:
        rel_path = os.path.relpath(file_path, base_path)
    except ValueError:
        return True

    # Exclude patterns - development files, tests, docs
    exclude_patterns = [
        # Development files
        'node_modules/',
        'assets/admin/src/',  # Source files (we have dist/)
        'assets/admin/node_modules/',
        '.git/',
        '.github/',
        'dev-tools/',
        'tests/',
        'test-',

        # Config files not needed in production
        '.gitignore',
        '.gitattributes',
        '.editorconfig',
        '.phpcs.xml',
        'phpunit.xml',
        'composer.lock',  # Will be regenerated on install
        'package-lock.json',
        'package.json',  # React is already built
        'tsconfig.json',
        'vite.config.ts',
        'tailwind.config.js',
        'postcss.config.js',
        'eslint',
        '.prettier',

        # Documentation (not needed in plugin)
        'KNOWN_TODOS.md',
        'PRODUCTION_READY_CHECKLIST.md',
        'PLUGIN_ACTIVATION_TEST_REPORT.md',
        'DEVELOPER_GUIDE.md',
        'DEPLOYMENT_CHECKLIST.md',
        'DEVELOPMENT_ROADMAP.md',
        'VERSION_HISTORY.md',
        'WP_PLUGIN_DEPLOYMENT_AGENT.md',
        'AI_MASTER.md',
        'CLAUDE.md',
        'GEMINI.md',
        'CODEX.md',

        # Build scripts
        'build-plugin-zip.py',
        'create-plugin-zip.py',
        'build-production-zip.py',

        # Docker files
        'docker-compose.yml',
        'Dockerfile',

        # Environment files
        '.env',
        '.env.example',  # Included separately with instructions

        # Temporary files
        'tmp/',
        'data/',
        '.DS_Store',
        'Thumbs.db',
        '.cache/',
        '*.log',
        '*.tmp',
        '*.swp',
        '*.swo',

        # Coverage and test results
        'coverage/',
        '.phpunit.result.cache',
    ]

    # Check patterns
    for pattern in exclude_patterns:
        if pattern in rel_path:
            return True

    # Exclude hidden files (except .htaccess)
    parts = Path(rel_path).parts
    for part in parts:
        if part.startswith('.') and part not in ['.htaccess']:
            return True

    # Exclude Python cache
    if '__pycache__' in rel_path or '.pyc' in rel_path:
        return True

    return False

def create_plugin_zip(source_dir, output_file, version):
    """Create a production ZIP file of the plugin directory"""
    source_path = Path(source_dir)
    file_count = 0
    total_size = 0

    print("=" * 60)
    print("MA DEAL ROOM - PRODUCTION ZIP BUILDER")
    print("=" * 60)
    print()
    print(f"Plugin Version: {version}")
    print(f"Source directory: {source_dir}")
    print(f"Output file: {output_file}")
    print()
    print("Packaging files...")
    print()

    with zipfile.ZipFile(output_file, 'w', zipfile.ZIP_DEFLATED) as zipf:
        for root, dirs, files in os.walk(source_dir):
            # Remove excluded directories from dirs list
            dirs[:] = [d for d in dirs if not should_exclude(os.path.join(root, d), source_dir)]

            for file in files:
                file_path = os.path.join(root, file)

                # Skip excluded files
                if should_exclude(file_path, source_dir):
                    continue

                # Calculate relative path for ZIP (keep ma-deal-room/ prefix)
                arcname = os.path.relpath(file_path, os.path.dirname(source_dir))

                # Add to ZIP
                zipf.write(file_path, arcname)
                file_count += 1
                file_size = os.path.getsize(file_path)
                total_size += file_size

                # Show progress for large files
                if file_size > 100000:  # > 100KB
                    print(f"  + {arcname} ({file_size / 1024:.1f} KB)")

                if file_count % 100 == 0:
                    print(f"  Processed {file_count} files...")

    zip_size = os.path.getsize(output_file)

    print()
    print("=" * 60)
    print("✓ PRODUCTION ZIP CREATED SUCCESSFULLY")
    print("=" * 60)
    print()
    print(f"Files included: {file_count}")
    print(f"Total source size: {total_size / (1024*1024):.2f} MB")
    print(f"ZIP file size: {zip_size / (1024*1024):.2f} MB")
    print(f"Compression ratio: {(1 - zip_size/total_size)*100:.1f}%")
    print()
    print("This ZIP file is ready to install on your WordPress site!")
    print()
    print("Installation instructions:")
    print("1. Log in to WordPress admin")
    print("2. Go to Plugins → Add New → Upload Plugin")
    print("3. Choose this ZIP file")
    print("4. Click 'Install Now'")
    print("5. Click 'Activate Plugin'")
    print()
    print("IMPORTANT: Before activating, ensure:")
    print("- PHP 8.0+ is installed")
    print("- WordPress 6.0+ is running")
    print("- MySQL 8.0+ or MariaDB 10.2+ is available")
    print("- You have database CREATE TABLE permissions")
    print()

if __name__ == '__main__':
    # Configuration
    VERSION = '2.0.0'
    source_dir = '/home/user/dealroom/ma-deal-room'
    output_file = f'/home/user/dealroom/ma-deal-room-v{VERSION}.zip'

    # Verify source directory exists
    if not os.path.exists(source_dir):
        print(f"ERROR: Source directory not found: {source_dir}")
        exit(1)

    # Verify required production files exist
    required_files = [
        os.path.join(source_dir, 'ma-deal-room.php'),
        os.path.join(source_dir, 'vendor/autoload.php'),
        os.path.join(source_dir, 'assets/admin/dist/assets/index.js'),
    ]

    missing_files = []
    for req_file in required_files:
        if not os.path.exists(req_file):
            missing_files.append(req_file)

    if missing_files:
        print("ERROR: Required files missing:")
        for mf in missing_files:
            print(f"  - {mf}")
        print()
        print("Please run:")
        print("  composer install --no-dev --optimize-autoloader")
        print("  cd assets/admin && npm install && npm run build")
        exit(1)

    # Remove old ZIP if it exists
    if os.path.exists(output_file):
        os.remove(output_file)
        print(f"Removed old ZIP: {output_file}\n")

    # Create the ZIP
    create_plugin_zip(source_dir, output_file, VERSION)
