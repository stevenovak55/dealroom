#!/usr/bin/env python3
"""
Create WordPress plugin ZIP file for ma-deal-room
Excludes development files and includes only production-ready code
"""

import os
import zipfile
from pathlib import Path

def should_exclude(path, excludes):
    """Check if path matches any exclusion pattern"""
    path_str = str(path)
    for exclude in excludes:
        if exclude in path_str:
            return True
    return False

def create_plugin_zip():
    plugin_dir = Path('/home/snova/projects/dealroom/ma-deal-room')
    output_zip = Path('/home/snova/projects/dealroom/ma-deal-room-v1.0.0.zip')

    # Patterns to exclude
    excludes = [
        'node_modules',
        '.git',
        '.gitignore',
        '.env',
        'tmp/',
        'data/',
        '.log',
        '.next',
        '/dist/',
        'test-',
        'setup-',
        'process-queue.php',
        'run-migrations.php',
        '.DS_Store',
        '__pycache__',
        '.pyc',
        'CLAUDE.md',
        'AI_MASTER.md',
        'SESSION_HANDOFF',
        'IMPLEMENTATION_SUMMARY',
        'DEPLOYMENT_GUIDE',
        '_REPORT.md',
        '_GUIDE.md',
        '_SUMMARY.md',
        '_INDEX.md',
        '_STRUCTURE.md',
        '_FLOW.md',
        '_EXPLORATION',
        '_COMPLETE.md',
        'VENDORREQUEST',
        'VENDOR_',
        'DOCUMENT_UPLOAD',
        'EXCEL_EXPORT',
        'GIT_COMMIT',
        'QUICK_START',
        'SECURITY_AUDIT',
        'T2.',
        '.backup',
        '.edited',
        '.example.tsx',
        '.quickref.md',
        '.README.md',
        '.VISUAL.md',
        '.FILES.txt',
        'docs/api/',
    ]

    print(f"Creating plugin ZIP: {output_zip}")
    print(f"Source directory: {plugin_dir}")

    file_count = 0

    with zipfile.ZipFile(output_zip, 'w', zipfile.ZIP_DEFLATED) as zipf:
        for root, dirs, files in os.walk(plugin_dir):
            root_path = Path(root)

            # Skip excluded directories
            dirs[:] = [d for d in dirs if not should_exclude(root_path / d, excludes)]

            for file in files:
                file_path = root_path / file

                # Skip excluded files
                if should_exclude(file_path, excludes):
                    continue

                # Calculate relative path
                rel_path = file_path.relative_to(plugin_dir.parent)

                # Add to ZIP
                zipf.write(file_path, rel_path)
                file_count += 1

                if file_count % 100 == 0:
                    print(f"  Added {file_count} files...")

    file_size = output_zip.stat().st_size
    file_size_mb = file_size / (1024 * 1024)

    print(f"\nPlugin ZIP created successfully!")
    print(f"  Location: {output_zip}")
    print(f"  Total files: {file_count}")
    print(f"  Size: {file_size_mb:.2f} MB")
    print(f"\nReady for WordPress upload!")

if __name__ == '__main__':
    create_plugin_zip()
