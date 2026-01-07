#!/bin/bash

################################################################################
# Troop 212 Website Development Environment Setup Script
#
# This script automates the setup of a local development environment for the
# BSA Troop 212 website on Ubuntu/Debian-based systems (including Raspberry Pi).
#
# Features:
# - Idempotent: Safe to run multiple times
# - Verbose output with colored status messages
# - Comprehensive error handling
# - User-friendly prompts
# - Validates existing configurations
# - Can fix misconfigured environments
#
# Usage: ./setup.sh
################################################################################

set -e  # Exit on error
set -u  # Exit on undefined variable

# Color codes for output
readonly RED='\033[0;31m'
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[1;33m'
readonly BLUE='\033[0;34m'
readonly NC='\033[0m' # No Color

# Script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Configuration
readonly APACHE_SITE_CONFIG="/etc/apache2/sites-available/000-default.conf"
readonly APACHE_DOC_ROOT="/var/www/t212site/public_html"
readonly DB_NAME="u321706752_t212"
readonly DB_USER="u321706752_t212db"
readonly CREDENTIALS_FILE="$SCRIPT_DIR/public_html/CREDENTIALS.json"

################################################################################
# Utility Functions
################################################################################

# Print colored status messages
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Print section headers
print_section() {
    echo ""
    echo "================================================================================================"
    echo -e "${GREEN}$1${NC}"
    echo "================================================================================================"
    echo ""
}

# Check if a command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Check if a package is installed
package_installed() {
    dpkg -l "$1" 2>/dev/null | grep -q "^ii"
}

# Check if a systemd service is running
service_running() {
    systemctl is-active --quiet "$1"
}

# Check if a systemd service is enabled
service_enabled() {
    systemctl is-enabled --quiet "$1"
}

# Prompt user for yes/no with default
prompt_yes_no() {
    local prompt="$1"
    local default="${2:-y}"
    local response

    if [[ "$default" == "y" ]]; then
        prompt="$prompt [Y/n]: "
    else
        prompt="$prompt [y/N]: "
    fi

    read -p "$prompt" response
    response="${response:-$default}"

    [[ "$response" =~ ^[Yy]$ ]]
}

# Run command with sudo if not root
run_sudo() {
    if [[ $EUID -eq 0 ]]; then
        "$@"
    else
        sudo "$@"
    fi
}

################################################################################
# Pre-flight Checks
################################################################################

preflight_checks() {
    print_section "Pre-flight Checks"

    # Check if running on supported OS
    if [[ ! -f /etc/debian_version ]]; then
        print_error "This script requires a Debian-based system (Ubuntu, Debian, Raspberry Pi OS)"
        exit 1
    fi
    print_success "Running on Debian-based system"

    # Check if we have sudo access
    if [[ $EUID -ne 0 ]]; then
        if ! sudo -v; then
            print_error "This script requires sudo access"
            exit 1
        fi
        print_success "Sudo access confirmed"
    fi

    # Check internet connectivity
    if ! ping -c 1 8.8.8.8 >/dev/null 2>&1; then
        print_warning "No internet connectivity detected - some steps may fail"
    else
        print_success "Internet connectivity confirmed"
    fi

    # Check if we're in the right directory
    if [[ ! -d "$SCRIPT_DIR/public_html" ]]; then
        print_error "Cannot find public_html directory. Are you in the t212site repository?"
        exit 1
    fi
    print_success "Repository structure validated"
}

################################################################################
# Git Setup
################################################################################

setup_git() {
    print_section "Git Setup"

    if command_exists git; then
        local git_version=$(git --version | awk '{print $3}')
        print_success "Git already installed (version $git_version)"
    else
        print_status "Installing git..."
        run_sudo apt-get update
        run_sudo apt-get install -y git
        print_success "Git installed successfully"
    fi

    # Check git configuration
    if ! git config user.name >/dev/null 2>&1; then
        print_warning "Git user name not configured"
        read -p "Enter your name for git commits: " git_name
        git config --global user.name "$git_name"
        print_success "Git user name configured"
    else
        print_success "Git user name: $(git config user.name)"
    fi

    if ! git config user.email >/dev/null 2>&1; then
        print_warning "Git user email not configured"
        read -p "Enter your email for git commits: " git_email
        git config --global user.email "$git_email"
        print_success "Git user email configured"
    else
        print_success "Git user email: $(git config user.email)"
    fi
}

################################################################################
# SSH Key Setup for GitHub
################################################################################

setup_ssh_keys() {
    print_section "SSH Key Setup for GitHub"

    local ssh_dir="$HOME/.ssh"
    local default_key="$ssh_dir/id_rsa"

    # Create .ssh directory if it doesn't exist
    if [[ ! -d "$ssh_dir" ]]; then
        mkdir -p "$ssh_dir"
        chmod 700 "$ssh_dir"
        print_success "Created $ssh_dir directory"
    fi

    # Check if SSH key exists
    if [[ -f "$default_key" ]]; then
        print_success "SSH key already exists at $default_key"

        if prompt_yes_no "Do you want to view your public key to add to GitHub?"; then
            echo ""
            echo "Copy this public key and add it to GitHub (Settings > SSH and GPG keys):"
            echo "---"
            cat "${default_key}.pub"
            echo "---"
            echo ""
            read -p "Press Enter when you've added the key to GitHub..."
        fi
    else
        if prompt_yes_no "No SSH key found. Generate a new one?"; then
            print_status "Generating SSH key..."
            read -p "Enter your email for the SSH key: " ssh_email
            ssh-keygen -t rsa -b 4096 -C "$ssh_email" -f "$default_key"

            print_success "SSH key generated"
            echo ""
            echo "Add this public key to GitHub (Settings > SSH and GPG keys):"
            echo "---"
            cat "${default_key}.pub"
            echo "---"
            echo ""
            read -p "Press Enter when you've added the key to GitHub..."
        fi
    fi

    # Test GitHub connection
    print_status "Testing GitHub SSH connection..."
    if ssh -T git@github.com 2>&1 | grep -q "successfully authenticated"; then
        print_success "GitHub SSH connection verified"
    else
        print_warning "Could not verify GitHub connection. You may need to add your SSH key to GitHub."
    fi
}

################################################################################
# Apache Setup
################################################################################

setup_apache() {
    print_section "Apache Web Server Setup"

    # Install Apache if not present
    if package_installed apache2; then
        print_success "Apache2 already installed"
    else
        print_status "Installing Apache2..."
        run_sudo apt-get update
        run_sudo apt-get install -y apache2
        print_success "Apache2 installed"
    fi

    # Create /var/www if it doesn't exist
    if [[ ! -d "/var/www" ]]; then
        print_status "Creating /var/www directory..."
        run_sudo mkdir -p /var/www
        print_success "/var/www created"
    fi

    # Set ownership to current user
    local current_user=$(whoami)
    print_status "Setting ownership of /var/www to $current_user..."
    run_sudo chown -R "$current_user:$current_user" /var/www
    run_sudo chmod 755 /var
    run_sudo chmod 755 /var/www
    print_success "Permissions configured"

    # Create symlink if repository is not in /var/www/t212site
    if [[ "$SCRIPT_DIR" != "/var/www/t212site" ]]; then
        if [[ -L "/var/www/t212site" ]]; then
            local current_target=$(readlink -f /var/www/t212site)
            if [[ "$current_target" != "$SCRIPT_DIR" ]]; then
                print_warning "Symlink exists but points to wrong location: $current_target"
                if prompt_yes_no "Update symlink to point to $SCRIPT_DIR?"; then
                    run_sudo rm /var/www/t212site
                    run_sudo ln -s "$SCRIPT_DIR" /var/www/t212site
                    print_success "Symlink updated"
                fi
            else
                print_success "Symlink already correctly configured"
            fi
        elif [[ -d "/var/www/t212site" ]]; then
            print_warning "/var/www/t212site exists as a directory"
            if prompt_yes_no "Replace with symlink to $SCRIPT_DIR?"; then
                run_sudo mv /var/www/t212site "/var/www/t212site.backup.$(date +%s)"
                run_sudo ln -s "$SCRIPT_DIR" /var/www/t212site
                print_success "Created symlink (old directory backed up)"
            fi
        else
            print_status "Creating symlink /var/www/t212site -> $SCRIPT_DIR"
            run_sudo ln -s "$SCRIPT_DIR" /var/www/t212site
            print_success "Symlink created"
        fi
    else
        print_success "Repository is already in /var/www/t212site"
    fi

    # Configure Apache site
    print_status "Configuring Apache site..."

    if [[ -f "$APACHE_SITE_CONFIG" ]]; then
        # Check if DocumentRoot is already set correctly
        if grep -q "DocumentRoot $APACHE_DOC_ROOT" "$APACHE_SITE_CONFIG"; then
            print_success "Apache DocumentRoot already configured correctly"
        else
            print_status "Updating Apache DocumentRoot..."

            # Backup original config
            run_sudo cp "$APACHE_SITE_CONFIG" "${APACHE_SITE_CONFIG}.backup.$(date +%s)"

            # Update DocumentRoot
            run_sudo sed -i "s|DocumentRoot.*|DocumentRoot $APACHE_DOC_ROOT|" "$APACHE_SITE_CONFIG"

            # Add Directory configuration if not present
            if ! grep -q "<Directory $APACHE_DOC_ROOT>" "$APACHE_SITE_CONFIG"; then
                run_sudo sed -i "/<\/VirtualHost>/i\\
\\        <Directory $APACHE_DOC_ROOT>\\
\\                Options Indexes FollowSymLinks\\
\\                AllowOverride All\\
\\                Require all granted\\
\\        </Directory>" "$APACHE_SITE_CONFIG"
            fi

            print_success "Apache configuration updated"
        fi
    else
        print_error "Apache site configuration not found at $APACHE_SITE_CONFIG"
        exit 1
    fi

    # Enable mod_rewrite if not already enabled
    if ! run_sudo apache2ctl -M 2>/dev/null | grep -q rewrite_module; then
        print_status "Enabling mod_rewrite..."
        run_sudo a2enmod rewrite
        print_success "mod_rewrite enabled"
    else
        print_success "mod_rewrite already enabled"
    fi

    # Start and enable Apache
    if service_running apache2; then
        print_success "Apache2 is running"
        print_status "Reloading Apache2 configuration..."
        run_sudo systemctl reload apache2
        print_success "Apache2 reloaded"
    else
        print_status "Starting Apache2..."
        run_sudo systemctl start apache2
        print_success "Apache2 started"
    fi

    if service_enabled apache2; then
        print_success "Apache2 is enabled on boot"
    else
        print_status "Enabling Apache2 on boot..."
        run_sudo systemctl enable apache2
        print_success "Apache2 enabled on boot"
    fi
}

################################################################################
# PHP Setup
################################################################################

setup_php() {
    print_section "PHP Setup"

    # Required PHP packages
    local php_packages=(
        "php"
        "libapache2-mod-php"
        "php-mysql"
        "php-mbstring"
        "php-json"
    )

    local packages_to_install=()

    # Check which packages need to be installed
    for pkg in "${php_packages[@]}"; do
        if package_installed "$pkg"; then
            print_success "$pkg already installed"
        else
            packages_to_install+=("$pkg")
        fi
    done

    # Install missing packages
    if [[ ${#packages_to_install[@]} -gt 0 ]]; then
        print_status "Installing PHP packages: ${packages_to_install[*]}"
        run_sudo apt-get update
        run_sudo apt-get install -y "${packages_to_install[@]}"
        print_success "PHP packages installed"
    else
        print_success "All required PHP packages already installed"
    fi

    # Get PHP version
    if command_exists php; then
        local php_version=$(php -v | head -n 1 | awk '{print $2}')
        print_success "PHP version: $php_version"
    fi

    # Reload Apache to load PHP module
    print_status "Reloading Apache to load PHP..."
    run_sudo systemctl reload apache2
    print_success "Apache reloaded"
}

################################################################################
# MariaDB/MySQL Setup
################################################################################

setup_database() {
    print_section "Database Setup (MariaDB)"

    # Install MariaDB if not present
    if package_installed mariadb-server; then
        print_success "MariaDB already installed"
    else
        print_status "Installing MariaDB..."
        run_sudo apt-get update
        run_sudo apt-get install -y mariadb-server
        print_success "MariaDB installed"
    fi

    # Start and enable MariaDB
    if service_running mysql; then
        print_success "MariaDB is running"
    else
        print_status "Starting MariaDB..."
        run_sudo systemctl start mysql
        print_success "MariaDB started"
    fi

    if service_enabled mysql; then
        print_success "MariaDB is enabled on boot"
    else
        print_status "Enabling MariaDB on boot..."
        run_sudo systemctl enable mysql
        print_success "MariaDB enabled on boot"
    fi

    # Check if CREDENTIALS.json exists
    if [[ ! -f "$CREDENTIALS_FILE" ]]; then
        print_error "CREDENTIALS.json not found at $CREDENTIALS_FILE"
        print_error "This file is required for database setup but is not in git (it contains secrets)"
        print_error "Please obtain CREDENTIALS.json from another developer or the production server"

        if prompt_yes_no "Do you want to create a template CREDENTIALS.json file?"; then
            create_credentials_template
            print_warning "Please edit $CREDENTIALS_FILE with the correct credentials before continuing"
            return
        else
            return
        fi
    fi

    # Read database password from CREDENTIALS.json
    local db_password
    if command_exists jq; then
        db_password=$(jq -r '.db.password' "$CREDENTIALS_FILE")
    else
        print_warning "jq not installed, using grep to parse JSON (less reliable)"
        db_password=$(grep -oP '"password"\s*:\s*"\K[^"]+' "$CREDENTIALS_FILE" | head -1)
    fi

    if [[ -z "$db_password" ]]; then
        print_error "Could not read database password from $CREDENTIALS_FILE"
        return
    fi

    print_success "Database credentials loaded from CREDENTIALS.json"

    # Check if database user exists
    print_status "Checking if database user exists..."

    if run_sudo mysql -e "SELECT User FROM mysql.user WHERE User='$DB_USER';" | grep -q "$DB_USER"; then
        print_success "Database user '$DB_USER' already exists"
    else
        print_status "Creating database user '$DB_USER'..."

        run_sudo mysql <<EOF
CREATE USER '$DB_USER'@'localhost' IDENTIFIED BY '$db_password';
GRANT ALL PRIVILEGES ON *.* TO '$DB_USER'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;
EOF
        print_success "Database user created"
    fi

    # Check if database exists
    if run_sudo mysql -e "SHOW DATABASES LIKE '$DB_NAME';" | grep -q "$DB_NAME"; then
        print_success "Database '$DB_NAME' already exists"

        # Check if database has tables
        local table_count=$(run_sudo mysql -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME';" | tail -1)

        if [[ "$table_count" -gt 0 ]]; then
            print_success "Database has $table_count tables"
        else
            print_warning "Database exists but is empty"

            if [[ -f "$SCRIPT_DIR/db_copy/u104214272_t212.sql" ]]; then
                if prompt_yes_no "Do you want to import the database schema?"; then
                    import_database "$db_password"
                fi
            fi
        fi
    else
        print_status "Creating database '$DB_NAME'..."
        run_sudo mysql -e "CREATE DATABASE $DB_NAME;"
        print_success "Database created"

        # Import schema if available
        if [[ -f "$SCRIPT_DIR/db_copy/u104214272_t212.sql" ]]; then
            if prompt_yes_no "Do you want to import the database schema?"; then
                import_database "$db_password"
            fi
        fi
    fi

    # Test database connection
    print_status "Testing database connection..."
    if mysql -u "$DB_USER" -p"$db_password" -e "USE $DB_NAME; SELECT 1;" >/dev/null 2>&1; then
        print_success "Database connection successful"
    else
        print_error "Could not connect to database with provided credentials"
    fi
}

import_database() {
    local db_password="$1"
    local sql_file="$SCRIPT_DIR/db_copy/u104214272_t212.sql"

    print_status "Importing database from $sql_file..."
    print_warning "This may take a few minutes..."

    if mysql -u "$DB_USER" -p"$db_password" "$DB_NAME" < "$sql_file"; then
        print_success "Database imported successfully"
    else
        print_error "Database import failed"
    fi
}

create_credentials_template() {
    cat > "$CREDENTIALS_FILE" <<'EOF'
{
  "db": {
    "host": "localhost",
    "username": "u321706752_t212db",
    "password": "YOUR_DATABASE_PASSWORD_HERE",
    "database": "u321706752_t212"
  },
  "smtp": {
    "host": "smtp.example.com",
    "port": 587,
    "username": "your-email@example.com",
    "password": "YOUR_SMTP_PASSWORD_HERE"
  }
}
EOF
    print_success "Created template CREDENTIALS.json file"
}

################################################################################
# Verification Tests
################################################################################

run_verification() {
    print_section "Environment Verification"

    local issues=0

    # Check Apache
    print_status "Checking Apache..."
    if service_running apache2; then
        print_success "✓ Apache is running"
    else
        print_error "✗ Apache is not running"
        ((issues++))
    fi

    # Check Apache config
    if grep -q "DocumentRoot $APACHE_DOC_ROOT" "$APACHE_SITE_CONFIG"; then
        print_success "✓ Apache DocumentRoot configured correctly"
    else
        print_error "✗ Apache DocumentRoot not configured correctly"
        ((issues++))
    fi

    # Check PHP
    print_status "Checking PHP..."
    if command_exists php; then
        print_success "✓ PHP is installed"
    else
        print_error "✗ PHP is not installed"
        ((issues++))
    fi

    # Check MariaDB
    print_status "Checking MariaDB..."
    if service_running mysql; then
        print_success "✓ MariaDB is running"
    else
        print_error "✗ MariaDB is not running"
        ((issues++))
    fi

    # Check web directory
    if [[ -d "$APACHE_DOC_ROOT" ]]; then
        print_success "✓ Web directory exists: $APACHE_DOC_ROOT"
    else
        print_error "✗ Web directory not found: $APACHE_DOC_ROOT"
        ((issues++))
    fi

    # Check CREDENTIALS.json
    if [[ -f "$CREDENTIALS_FILE" ]]; then
        print_success "✓ CREDENTIALS.json exists"
    else
        print_error "✗ CREDENTIALS.json not found"
        ((issues++))
    fi

    # Test web server response
    print_status "Testing web server response..."
    if curl -s http://localhost >/dev/null 2>&1; then
        print_success "✓ Web server responding on http://localhost"
    else
        print_warning "⚠ Could not connect to web server on http://localhost"
        print_warning "  (This may be normal if you're running this remotely)"
    fi

    echo ""
    echo "================================================================================================"

    if [[ $issues -eq 0 ]]; then
        print_success "All verification checks passed! ✓"
        echo ""
        print_status "Your development environment is ready!"
        print_status "Access the website at: http://localhost (or your server's IP address)"
    else
        print_error "Found $issues issue(s) during verification"
        print_error "Please review the errors above and run this script again"
        return 1
    fi

    echo "================================================================================================"
    echo ""
}

################################################################################
# Main Execution
################################################################################

main() {
    clear
    echo "================================================================================================"
    echo -e "${GREEN}Troop 212 Website - Development Environment Setup${NC}"
    echo "================================================================================================"
    echo ""
    echo "This script will set up a complete local development environment for the Troop 212 website."
    echo ""
    echo "The following components will be installed and configured:"
    echo "  • Git"
    echo "  • SSH keys for GitHub"
    echo "  • Apache web server"
    echo "  • PHP"
    echo "  • MariaDB database"
    echo ""
    echo "This script is idempotent - it's safe to run multiple times."
    echo ""

    if ! prompt_yes_no "Do you want to continue with the setup?"; then
        print_status "Setup cancelled by user"
        exit 0
    fi

    # Run setup steps
    preflight_checks
    setup_git
    setup_ssh_keys
    setup_apache
    setup_php
    setup_database
    run_verification

    print_section "Setup Complete!"

    echo "Next steps:"
    echo "  1. If you haven't already, add your SSH key to GitHub"
    echo "  2. Make sure CREDENTIALS.json has the correct database password"
    echo "  3. Visit http://localhost in your browser to test the site"
    echo "  4. Run the test suite: php tests/test_runner.php"
    echo ""

    print_success "Happy coding! 🎉"
}

# Run main function
main "$@"
