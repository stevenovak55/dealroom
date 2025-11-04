#!/usr/bin/env python3
"""
Build plugin ZIP file for WordPress deployment
"""
import zipfile
import os
from pathlib import Path

def should_exclude(file_path):
    """Check if file should be excluded from ZIP"""
    exclude_patterns = [
        'node_modules/',
        '.git/',
        '.gitignore',
        '.gitattributes',
        'tmp/',
        'data/',
        '.DS_Store',
        'Thumbs.db',
        '.env',
        'composer.lock',
        'package-lock.json',
        '.editorconfig',
        '.phpcs.xml'
    ]

    for pattern in exclude_patterns:
        if pattern in file_path:
            return True

    # Exclude hidden files
    parts = Path(file_path).parts
    for part in parts:
        if part.startswith('.') and part not in ['.htaccess', '.editorconfig']:
            return True

    return False

def create_plugin_zip(source_dir, output_file):
    """Create a ZIP file of the plugin directory"""
    source_path = Path(source_dir)
    file_count = 0
    total_size = 0

    print(f"Creating ZIP: {output_file}")
    print(f"Source directory: {source_dir}")
    print()

    with zipfile.ZipFile(output_file, 'w', zipfile.ZIP_DEFLATED) as zipf:
        for root, dirs, files in os.walk(source_dir):
            # Remove excluded directories from dirs list to prevent walking into them
            dirs[:] = [d for d in dirs if not should_exclude(os.path.join(root, d))]

            for file in files:
                file_path = os.path.join(root, file)

                # Skip excluded files
                if should_exclude(file_path):
                    continue

                # Calculate relative path for ZIP
                arcname = os.path.relpath(file_path, os.path.dirname(source_dir))

                # Add to ZIP
                zipf.write(file_path, arcname)
                file_count += 1
                total_size += os.path.getsize(file_path)

                if file_count % 100 == 0:
                    print(f"  Processed {file_count} files...")

    zip_size = os.path.getsize(output_file)

    print()
    print("✓ ZIP created successfully")
    print(f"  Files included: {file_count}")
    print(f"  Total source size: {total_size / (1024*1024):.2f} MB")
    print(f"  ZIP file size: {zip_size / (1024*1024):.2f} MB")
    print(f"  Compression ratio: {(1 - zip_size/total_size)*100:.1f}%")

if __name__ == '__main__':
    source_dir = '/home/snova/projects/dealroom/ma-deal-room'
    output_file = '/home/snova/projects/dealroom/ma-deal-room-v1.0.1.zip'

    # Remove old ZIP if it exists
    if os.path.exists(output_file):
        os.remove(output_file)
        print(f"Removed old ZIP: {output_file}\n")

    create_plugin_zip(source_dir, output_file)
