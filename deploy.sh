#!/bin/bash

# Production build script for the CINQ Central WordPress plugin.
# Installs dependencies, builds the admin app, and removes files not needed in production.
# The release workflow runs it on a clean checkout, then zips what is left.

set -e  # Exit on error

# Log file configuration
LOG_FILE="${1:-deploy.log}"

# Write a timestamped message to the log
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Log and print errors to stderr
error() {
    local message="❌ Error: $1"
    log "$message"
    echo "$message" >&2
    exit 1
}

# Log success messages
success() {
    log "✅ $1"
}

# Log informational messages
info() {
    log "ℹ️  $1"
}

# Initialize log file
log "🚀 Starting production build..."

# Ensure the script runs from the plugin root
if [ ! -f "cinq-wp-central.php" ] || [ ! -f "package.json" ]; then
    error "This script must be run from the plugin root directory"
fi

# 1. Install front-end dependencies
info "Installing front-end dependencies..."
if command -v npm &> /dev/null; then
    npm ci --production=false >> "$LOG_FILE" 2>&1 || error "npm install failed"
    success "npm dependencies installed"
else
    error "npm must be installed"
fi

# 2. Build the admin app (build/)
info "Building assets..."
npm run build >> "$LOG_FILE" 2>&1 || error "Build failed"
[ -f "build/index.js" ] || error "build/index.js is missing after the build"
success "Build completed successfully"

# 3. Remove files and directories not needed in production
info "Cleaning up development files..."

# Remove dependency directories: the plugin has its own autoloader and no
# runtime Composer dependency, vendor/ only holds the coding standards.
for dir in node_modules vendor; do
    if [ -d "$dir" ]; then
        rm -rf "$dir"
        success "$dir/ removed"
    fi
done

# Remove development configuration files
FILES_TO_REMOVE=(
    ".git"
    ".github"
    ".gitignore"
    ".gitattributes"
    ".editorconfig"
    ".eslintrc.json"
    ".stylelintrc.json"
    ".wp-env.json"
    "package.json"
    "package-lock.json"
    "composer.json"
    "composer.lock"
    "phpcs.xml"
    "deploy.sh"
    ".DS_Store"
)

for file in "${FILES_TO_REMOVE[@]}"; do
    if [ -e "$file" ]; then
        rm -rf "$file"
        success "$file removed"
    fi
done

# Remove sources and tests (not needed in production)
for dir in src tests; do
    if [ -d "$dir" ]; then
        rm -rf "$dir"
        success "$dir/ removed"
    fi
done

# Remove macOS system files
find . -name ".DS_Store" -type f -delete 2>/dev/null || true
find . -name "._*" -type f -delete 2>/dev/null || true

success "Cleanup completed"

log ""
log "✨ Production build completed successfully!"
log ""
info "Files kept for production:"
log "  - build/ (compiled admin app)"
log "  - includes/ (plugin PHP code)"
log "  - cinq-wp-central.php, uninstall.php, README.md, LICENSE"
