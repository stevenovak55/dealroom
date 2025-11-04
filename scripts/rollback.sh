#!/bin/bash

#############################################################################
# MA Deal Room - Production Rollback Script
#
# This script performs automated rollback of the MA Deal Room plugin to
# a previous state by restoring database and files from backup.
#
# Usage:
#   ./scripts/rollback.sh [options]
#
# Options:
#   -d, --database <file>    Database backup file to restore
#   -f, --files <file>       Files backup tar.gz to restore
#   -e, --env <file>         Environment file backup to restore
#   -y, --yes                Skip confirmation prompts
#   -h, --help               Show this help message
#
# Examples:
#   # Interactive rollback (will prompt for backup files)
#   ./scripts/rollback.sh
#
#   # Automated rollback with specific files
#   ./scripts/rollback.sh -d backup-20251101.sql.gz -f files-20251101.tar.gz -y
#
# IMPORTANT: Always test rollback in staging first!
#
#############################################################################

set -e  # Exit on error
set -u  # Exit on undefined variable

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
BACKUP_DIR="/var/www/html/wp-content/backups"
PLUGIN_DIR="/var/www/html/wp-content/plugins/ma-deal-room"
WP_ROOT="/var/www/html"

# Default values
DB_BACKUP=""
FILES_BACKUP=""
ENV_BACKUP=""
SKIP_CONFIRMATION=false
DRY_RUN=false

# Logging
LOG_FILE="/var/log/ma-deal-rollback-$(date +%Y%m%d-%H%M%S).log"

#############################################################################
# Functions
#############################################################################

log() {
    local level=$1
    shift
    local message="$@"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo -e "${timestamp} [${level}] ${message}" | tee -a "$LOG_FILE"
}

log_info() {
    echo -e "${BLUE}[INFO]${NC} $@" | tee -a "$LOG_FILE"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $@" | tee -a "$LOG_FILE"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $@" | tee -a "$LOG_FILE"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $@" | tee -a "$LOG_FILE"
}

print_header() {
    echo -e "${BLUE}"
    echo "============================================================================="
    echo "  MA Deal Room - Production Rollback"
    echo "============================================================================="
    echo -e "${NC}"
    log_info "Rollback started at $(date)"
}

print_usage() {
    cat << EOF
Usage: $0 [options]

Options:
  -d, --database <file>    Database backup file to restore
  -f, --files <file>       Files backup tar.gz to restore
  -e, --env <file>         Environment file backup to restore
  -y, --yes                Skip confirmation prompts
  --dry-run                Show what would be done without making changes
  -h, --help               Show this help message

Examples:
  # Interactive rollback
  $0

  # Automated rollback
  $0 -d backup-20251101-030000.sql.gz -f files-20251101.tar.gz -y

  # Dry run to see what would happen
  $0 -d backup-20251101-030000.sql.gz --dry-run

For more information, see: docs/deployment/PRODUCTION_DEPLOYMENT.md
EOF
}

check_requirements() {
    log_info "Checking requirements..."

    # Check if running as root or with sudo
    if [ "$EUID" -ne 0 ] && ! groups | grep -q sudo; then
        log_error "This script must be run as root or with sudo privileges"
        exit 1
    fi

    # Check WP-CLI is installed
    if ! command -v wp &> /dev/null; then
        log_error "WP-CLI is not installed. Please install it first."
        exit 1
    fi

    # Check if WordPress is installed
    if [ ! -f "$WP_ROOT/wp-config.php" ]; then
        log_error "WordPress not found at $WP_ROOT"
        exit 1
    fi

    # Check if plugin directory exists
    if [ ! -d "$PLUGIN_DIR" ]; then
        log_warning "Plugin directory not found at $PLUGIN_DIR"
    fi

    log_success "All requirements met"
}

list_available_backups() {
    log_info "Available database backups:"
    if [ -d "$BACKUP_DIR" ]; then
        ls -lh "$BACKUP_DIR"/*.sql.gz 2>/dev/null | awk '{print $9, "(" $5 ")"}'  || log_warning "No database backups found"
    else
        log_warning "Backup directory not found: $BACKUP_DIR"
    fi
}

confirm_action() {
    if [ "$SKIP_CONFIRMATION" = true ]; then
        return 0
    fi

    local message="$1"
    echo -e "${YELLOW}${message}${NC}"
    read -p "Continue? (yes/no): " -r
    if [[ ! $REPLY =~ ^[Yy]([Ee][Ss])?$ ]]; then
        log_info "Operation cancelled by user"
        exit 0
    fi
}

create_pre_rollback_backup() {
    log_info "Creating pre-rollback backup (safety measure)..."

    if [ "$DRY_RUN" = true ]; then
        log_info "[DRY RUN] Would create backup"
        return 0
    fi

    local backup_name="pre-rollback-$(date +%Y%m%d-%H%M%S).sql.gz"

    # Create database backup
    cd "$WP_ROOT"
    if sudo -u www-data wp db export - | gzip > "/tmp/$backup_name"; then
        log_success "Pre-rollback backup created: /tmp/$backup_name"
    else
        log_error "Failed to create pre-rollback backup"
        exit 1
    fi
}

restore_database() {
    local backup_file="$1"

    if [ ! -f "$backup_file" ]; then
        log_error "Database backup file not found: $backup_file"
        exit 1
    fi

    log_info "Restoring database from: $backup_file"

    if [ "$DRY_RUN" = true ]; then
        log_info "[DRY RUN] Would restore database from $backup_file"
        return 0
    fi

    # Deactivate plugin first
    log_info "Deactivating plugin..."
    cd "$WP_ROOT"
    sudo -u www-data wp plugin deactivate ma-deal-room 2>/dev/null || true

    # Import database
    log_info "Importing database..."
    if gunzip -c "$backup_file" | sudo -u www-data wp db import -; then
        log_success "Database restored successfully"
    else
        log_error "Failed to restore database"
        exit 1
    fi

    # Flush cache
    log_info "Flushing cache..."
    sudo -u www-data wp cache flush || true
}

restore_files() {
    local backup_file="$1"

    if [ ! -f "$backup_file" ]; then
        log_error "Files backup not found: $backup_file"
        exit 1
    fi

    log_info "Restoring files from: $backup_file"

    if [ "$DRY_RUN" = true ]; then
        log_info "[DRY RUN] Would restore files from $backup_file"
        return 0
    fi

    # Create backup of current files
    if [ -d "$PLUGIN_DIR" ]; then
        log_info "Backing up current plugin files..."
        local current_backup="/tmp/ma-deal-room-current-$(date +%Y%m%d-%H%M%S).tar.gz"
        tar -czf "$current_backup" -C "$(dirname "$PLUGIN_DIR")" "$(basename "$PLUGIN_DIR")"
        log_info "Current files backed up to: $current_backup"
    fi

    # Remove current plugin directory
    if [ -d "$PLUGIN_DIR" ]; then
        log_info "Removing current plugin directory..."
        rm -rf "$PLUGIN_DIR"
    fi

    # Extract backup
    log_info "Extracting backup..."
    mkdir -p "$PLUGIN_DIR"
    if tar -xzf "$backup_file" -C "$(dirname "$PLUGIN_DIR")"; then
        log_success "Files restored successfully"
    else
        log_error "Failed to restore files"
        exit 1
    fi

    # Fix permissions
    log_info "Setting correct permissions..."
    chown -R www-data:www-data "$PLUGIN_DIR"
    find "$PLUGIN_DIR" -type d -exec chmod 755 {} \;
    find "$PLUGIN_DIR" -type f -exec chmod 644 {} \;
}

restore_env() {
    local backup_file="$1"

    if [ ! -f "$backup_file" ]; then
        log_error ".env backup not found: $backup_file"
        exit 1
    fi

    log_info "Restoring .env file from: $backup_file"

    if [ "$DRY_RUN" = true ]; then
        log_info "[DRY RUN] Would restore .env from $backup_file"
        return 0
    fi

    local env_path="$PLUGIN_DIR/.env"

    # Backup current .env
    if [ -f "$env_path" ]; then
        cp "$env_path" "$env_path.rollback-backup-$(date +%Y%m%d-%H%M%S)"
    fi

    # Restore .env
    cp "$backup_file" "$env_path"
    chown www-data:www-data "$env_path"
    chmod 440 "$env_path"

    log_success ".env file restored"
}

clear_cache() {
    log_info "Clearing all caches..."

    if [ "$DRY_RUN" = true ]; then
        log_info "[DRY RUN] Would clear caches"
        return 0
    fi

    # WordPress object cache
    cd "$WP_ROOT"
    sudo -u www-data wp cache flush 2>/dev/null || true

    # WordPress transients
    sudo -u www-data wp transient delete --all 2>/dev/null || true

    # Redis cache
    if command -v redis-cli &> /dev/null; then
        redis-cli FLUSHDB &> /dev/null || true
        log_info "Redis cache flushed"
    fi

    # OpCache
    if systemctl is-active --quiet php8.1-fpm; then
        systemctl reload php8.1-fpm
        log_info "PHP OpCache cleared"
    elif systemctl is-active --quiet php8.0-fpm; then
        systemctl reload php8.0-fpm
        log_info "PHP OpCache cleared"
    fi

    log_success "Caches cleared"
}

verify_rollback() {
    log_info "Verifying rollback..."

    if [ "$DRY_RUN" = true ]; then
        log_info "[DRY RUN] Would verify rollback"
        return 0
    fi

    # Check database connection
    cd "$WP_ROOT"
    if sudo -u www-data wp db check; then
        log_success "Database connection OK"
    else
        log_error "Database connection failed"
        return 1
    fi

    # Check if plugin files exist
    if [ -d "$PLUGIN_DIR" ]; then
        log_success "Plugin files present"
    else
        log_warning "Plugin files not found"
    fi

    # Check if .env file exists
    if [ -f "$PLUGIN_DIR/.env" ]; then
        log_success ".env file present"
    else
        log_warning ".env file not found"
    fi

    # Try to activate plugin
    if sudo -u www-data wp plugin activate ma-deal-room 2>/dev/null; then
        log_success "Plugin activated successfully"
    else
        log_warning "Failed to activate plugin"
    fi

    log_success "Rollback verification complete"
}

print_summary() {
    echo -e "${GREEN}"
    echo "============================================================================="
    echo "  Rollback Summary"
    echo "============================================================================="
    echo -e "${NC}"
    echo "Database restored:  ${DB_BACKUP:-N/A}"
    echo "Files restored:     ${FILES_BACKUP:-N/A}"
    echo ".env restored:      ${ENV_BACKUP:-N/A}"
    echo "Log file:           $LOG_FILE"
    echo ""
    log_info "Rollback completed at $(date)"
}

#############################################################################
# Main Script
#############################################################################

main() {
    print_header

    # Parse command line arguments
    while [[ $# -gt 0 ]]; do
        case $1 in
            -d|--database)
                DB_BACKUP="$2"
                shift 2
                ;;
            -f|--files)
                FILES_BACKUP="$2"
                shift 2
                ;;
            -e|--env)
                ENV_BACKUP="$2"
                shift 2
                ;;
            -y|--yes)
                SKIP_CONFIRMATION=true
                shift
                ;;
            --dry-run)
                DRY_RUN=true
                shift
                ;;
            -h|--help)
                print_usage
                exit 0
                ;;
            *)
                log_error "Unknown option: $1"
                print_usage
                exit 1
                ;;
        esac
    done

    # Check requirements
    check_requirements

    # List available backups if none specified
    if [ -z "$DB_BACKUP" ] && [ -z "$FILES_BACKUP" ]; then
        list_available_backups
        echo ""
        log_error "No backup files specified. Use -d and/or -f options."
        echo ""
        print_usage
        exit 1
    fi

    # Show rollback plan
    echo ""
    log_info "Rollback Plan:"
    [ -n "$DB_BACKUP" ] && echo "  - Database: $DB_BACKUP"
    [ -n "$FILES_BACKUP" ] && echo "  - Files: $FILES_BACKUP"
    [ -n "$ENV_BACKUP" ] && echo "  - .env: $ENV_BACKUP"
    echo ""

    if [ "$DRY_RUN" = true ]; then
        log_warning "DRY RUN MODE - No changes will be made"
        echo ""
    fi

    # Confirm rollback
    confirm_action "This will replace the current installation with the backup. This action cannot be easily undone."

    # Create pre-rollback backup
    create_pre_rollback_backup

    # Restore database
    if [ -n "$DB_BACKUP" ]; then
        restore_database "$DB_BACKUP"
    fi

    # Restore files
    if [ -n "$FILES_BACKUP" ]; then
        restore_files "$FILES_BACKUP"
    fi

    # Restore .env
    if [ -n "$ENV_BACKUP" ]; then
        restore_env "$ENV_BACKUP"
    fi

    # Clear caches
    clear_cache

    # Verify rollback
    verify_rollback

    # Print summary
    print_summary

    if [ "$DRY_RUN" = true ]; then
        log_info "Dry run complete. No changes were made."
    else
        log_success "Rollback completed successfully!"
        echo ""
        log_warning "IMPORTANT: Please verify the site is working correctly:"
        echo "  1. Check homepage: https://yourdomain.com"
        echo "  2. Check WordPress admin: https://yourdomain.com/wp-admin"
        echo "  3. Test login functionality"
        echo "  4. Check error logs: tail -f /var/log/nginx/error.log"
        echo "  5. Monitor Sentry for errors"
    fi
}

# Run main function
main "$@"
