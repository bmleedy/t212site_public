# Development Environment Setup - Quick Reference

## For New Developers

### First Time Setup
```bash
# 1. Clone the repository
git clone git@github.com:bmleedy/t212site.git
cd t212site

# 2. Run the automated setup
chmod +x setup.sh
./setup.sh

# 3. Configure credentials
# Edit public_html/CREDENTIALS.json with your database password

# 4. Verify installation
php tests/unit/SetupScriptTest.php
php tests/test_runner.php

# 5. Visit the site
# http://localhost
```

## Setup Script Features

### ✅ Automatic Installation
- Git
- Apache web server
- PHP 7+ with required extensions
- MariaDB database
- Proper directory structure

### ✅ Configuration
- Apache DocumentRoot → `/var/www/t212site/public_html`
- Database user and database creation
- File permissions
- SSH keys for GitHub

### ✅ Idempotent
- Safe to run multiple times
- Detects existing installations
- Only fixes what's broken
- Can recover from partial installations

### ✅ Verification
- Runs comprehensive checks after installation
- Tests database connection
- Verifies web server response
- Checks all services are running

## Quick Commands

```bash
# Re-run setup (safe to run anytime)
./setup.sh

# Test your environment
php tests/unit/SetupScriptTest.php

# Run all tests
php tests/test_runner.php

# Check Apache status
sudo systemctl status apache2

# Check MySQL status
sudo systemctl status mysql

# View Apache error log
sudo tail -f /var/log/apache2/error.log

# Restart Apache
sudo systemctl restart apache2

# Restart MySQL
sudo systemctl restart mysql
```

## What the Script Does

### Phase 1: Pre-flight Checks
- ✓ Verifies Debian/Ubuntu system
- ✓ Checks sudo access
- ✓ Tests internet connectivity
- ✓ Validates repository structure

### Phase 2: Git Setup
- ✓ Installs git if needed
- ✓ Configures user name and email
- ✓ Ready for GitHub operations

### Phase 3: SSH Keys
- ✓ Creates ~/.ssh directory
- ✓ Generates SSH key pair
- ✓ Displays public key for GitHub
- ✓ Tests GitHub connection

### Phase 4: Apache Setup
- ✓ Installs Apache2
- ✓ Creates /var/www directory
- ✓ Sets proper ownership
- ✓ Creates symlink to repository
- ✓ Configures DocumentRoot
- ✓ Enables mod_rewrite
- ✓ Starts and enables service

### Phase 5: PHP Setup
- ✓ Installs PHP
- ✓ Installs required extensions:
  - libapache2-mod-php
  - php-mysql
  - php-mbstring
  - php-json
- ✓ Reloads Apache

### Phase 6: Database Setup
- ✓ Installs MariaDB
- ✓ Starts and enables service
- ✓ Creates database user
- ✓ Creates database
- ✓ Imports schema (optional)
- ✓ Tests connection

### Phase 7: Verification
- ✓ Checks all services running
- ✓ Validates configuration files
- ✓ Tests database connection
- ✓ Tests web server response
- ✓ Reports any issues

## Troubleshooting

### Script fails with "Permission denied"
```bash
chmod +x setup.sh
```

### "sudo: command not found"
You need to be root or install sudo:
```bash
su -
apt-get install sudo
usermod -aG sudo YOUR_USERNAME
```

### "Apache DocumentRoot wrong"
```bash
./setup.sh  # Re-run setup to fix
```

### "Database connection failed"
1. Check CREDENTIALS.json has correct password
2. Run `./setup.sh` to recreate database user
3. Check if MySQL is running: `sudo systemctl status mysql`

### "Cannot connect to http://localhost"
1. Check Apache is running: `sudo systemctl status apache2`
2. Check for errors: `sudo tail /var/log/apache2/error.log`
3. Try server IP instead of localhost

### "SSH key not accepted by GitHub"
1. Copy your public key: `cat ~/.ssh/id_rsa.pub`
2. Add to GitHub: Settings → SSH and GPG keys
3. Test: `ssh -T git@github.com`

## File Locations

| Component | Location |
|-----------|----------|
| Setup script | `./setup.sh` |
| Web root | `/var/www/t212site/public_html` |
| Apache config | `/etc/apache2/sites-available/000-default.conf` |
| Credentials | `public_html/CREDENTIALS.json` |
| Database schema | `db_copy/u104214272_t212.sql` |
| Tests | `tests/` |
| Setup test | `tests/unit/SetupScriptTest.php` |
| Apache logs | `/var/log/apache2/` |
| MySQL logs | `/var/log/mysql/` |

## Next Steps After Setup

1. **Add SSH key to GitHub**
   - Settings → SSH and GPG keys → New SSH key

2. **Configure CREDENTIALS.json**
   - Get database password from another developer
   - Or use the one from production server

3. **Import database**
   - Already done by setup.sh if you chose "yes"
   - Or manually: `mysql -u u321706752_t212db -p u321706752_t212 < db_copy/u104214272_t212.sql`

4. **Run tests**
   - `php tests/test_runner.php`
   - Should see all tests passing

5. **Start coding!**
   - Create feature branch
   - Make changes
   - Run tests
   - Commit and push

## Support

- Run setup script again: `./setup.sh`
- Check setup test: `php tests/unit/SetupScriptTest.php`
- View README: `cat README.md`
- Ask other developers for help
