# t212site
BSA Troop 212 website

## Quick Start - Automated Setup (Not tested yet)

The easiest way to set up your development environment is to use the automated setup script:

### Prerequisites
- Ubuntu/Debian-based system (including Raspberry Pi OS)
- Sudo access
- Internet connection

### Run the Setup Script
1. Clone this repository:
   ```bash
   git clone git@github.com:bmleedy/t212site.git
   cd t212site
   ```

2. Run the automated setup script:
   ```bash
   chmod +x setup.sh
   ./setup.sh
   ```

The script will:
- ✅ Install and configure Git
- ✅ Set up SSH keys for GitHub
- ✅ Install and configure Apache web server
- ✅ Install PHP and required extensions
- ✅ Install and configure MariaDB database
- ✅ Create proper directory structure and permissions
- ✅ Verify the entire installation

The script is **idempotent** - you can run it multiple times safely. It will detect what's already configured and only make necessary changes.

### After Running setup.sh

1. **Add your SSH key to GitHub** (if you haven't already):
   - The script will display your public key
   - Copy it and add to GitHub: Settings → SSH and GPG keys → New SSH key

2. **Configure CREDENTIALS.json**:
   - Edit `public_html/CREDENTIALS.json` with your database password
   - If the file doesn't exist, the setup script can create a template

3. **Test your installation**:
   ```bash
   # Run the test suite to verify everything works
   php tests/test_runner.php

   # Visit the website in your browser
   http://localhost
   # Or use your server's IP address if accessing remotely
   ```

---

## Manual Setup (Alternative Method)

If you prefer to set up manually or need to troubleshoot, follow these steps:

## Install software on your base system. (Raspberry Pi or vanilla Ubuntu distribution)
1. Install git ```suto apt-get install -y git```
1. [if you're installing on an rPi] configure and update a raspberry pi, enabling SSH
1. ```sudo apt-get update```
1. ```sudo apt-get upgrade -y```
2. from your home directory ```cd .ssh```
1. use ssh-keygen to create a new ssh key (make sure to use a passphrase)
1. modify ~/.ssh/config to configure that key for github.com:
> Host github.com
>   User evan
>   IdentityFile ~/.ssh/evan_rpi
1. upload the new ssh key to github so you can clone the repo
   1. Log into your github account on github.com
   1. navigate to your profile (not the troop website)
   1. Click the tab on the left under "Access" that says "SSH and GPG keys"
   1. Click the green "add ssh key" button in the top right corner
   1. Paste in your key (from the .pub file), give it a title, and click "add SSH key"
1. Create a directory for your apache website
   1. ```sudo mkdir /var/www```
   2. ```sudo chown bmleedy:bmleedy /var/www``` (replace "bmleedy" with your username)
   3. ```cd /var/www```
1. Download the code for the site: ```git clone git@github.com:bmleedy/t212site.git```
1. Install apache: ```sudo apt install -y apache2```
   1. ```sudo systemctl start apache2```
   1. ```sudo systemctl reload apache2```
   1. ```sudo systemctl enable apache2```
1. Modify directory in sites config file ```sudo vi /etc/apache2/sites-available/000-default.conf```
   1. ```DocumentRoot /var/www/t212site/public_html```
1. ```sudo systemctl reload apache2```
1. Now install php for the site ```sudo apt install php libapache2-mod-php php-mysql -y```
1. ```sudo systemctl reload apache2```
1. And set the permissions ```chmod 755 /var``` and ```chmod 755 /var/www```

At this point, the website should load if you open your host in the browser (localhost, or your webserver ip address if opening from another machine)

## Setting up a local user DB for testing
*NOTE*: Database credentials are stored securely in `public_html/CREDENTIALS.json` (not in git). See CREDENTIALS.json for the database username and password.
1. ```sudo apt-get install mariadb-server -y```
1. ```mariadb-secure-installation``` and fill in the fields as-needed
1. Log into the db initially ```sudo mariadb -u root -p```  and run:
> CREATE USER 'u321706752_t212db'@'localhost' IDENTIFIED BY 'YOUR_PASSWORD_FROM_CREDENTIALS_JSON';
> GRANT ALL PRIVILEGES ON \*.\* to 'u321706752_t212db'@'localhost' WITH GRANT OPTION;
> FLUSH PRIVILEGES;
> CREATE DATABASE u321706752_t212;
> exit;
1. Now, you can logout and manage the db as the user.  Also, you can start/stop the db with these commands:
   1. ```sudo systemctl start mysql```    # To start MySQL service
   1. ```sudo systemctl stop mysql```     # To stop MySQL service
   1. ```sudo systemctl enable mysql```   # To enable MySQL on boot
   1. ```sudo systemctl disable mysql```  # To disable MySQL on boot





# On the website: setting up database backups

On hostinger, we have a cron job every Monday to backup the database:

> mysqldump --all-databases --single-transaction --user=u321706752_t212db --password=YOUR_PASSWORD_FROM_CREDENTIALS_JSON > /home/u321706752/db_backup$(date +%Y.%m.%d).sql

**Note:** Replace `YOUR_PASSWORD_FROM_CREDENTIALS_JSON` with the actual database password from `public_html/CREDENTIALS.json`

To restore this in the future you just need to run (from the SSH panel)
1. Do a backup manually using a mysqldump command as above
1. Login via SSH (you may have to enable SSH to do this)
1. Run ```mysql <name_of_backup.sql```
1. Don't forget to re-disable your SSH access

NOTE: there's probably a way to do this via phpMyAdmin directly in hostinger for the website too.

NOTE2: Theoretically, we'll need to clean up the database backups eventually, but as of this writing the database backup is less than 2MB and the hosting plan includes 100GB of storage.  Even if the database were to 10x in size, weekly backups will take about 104 years to fill up our hosting quota. (If you are reading this 200 years in the future, Awesome! Go clean up those files!)



---

## Troubleshooting the Setup

### Run the Setup Test Suite

To check if your environment is properly configured:

```bash
php tests/unit/SetupScriptTest.php
```

This will verify:
- All required software is installed
- Services are running and enabled
- Configuration files are correct
- Database connection works
- Web server is responding

### Common Issues

#### "Apache is not running"
```bash
sudo systemctl start apache2
sudo systemctl enable apache2
```

#### "Database connection failed"
1. Check if MySQL/MariaDB is running:
   ```bash
   sudo systemctl status mysql
   ```
2. Verify credentials in `public_html/CREDENTIALS.json`
3. Make sure the database user exists (run `./setup.sh` again)

#### "Permission denied" errors
```bash
# Fix web directory permissions
sudo chown -R $(whoami):$(whoami) /var/www
chmod 755 /var
chmod 755 /var/www
```

#### "CREDENTIALS.json not found"
This file contains secrets and is not in git. Either:
1. Run `./setup.sh` to create a template
2. Copy from another developer
3. Get from the production server

#### "Web server shows wrong site"
Check Apache configuration:
```bash
cat /etc/apache2/sites-available/000-default.conf
# DocumentRoot should be: /var/www/t212site/public_html
```

If wrong, run `./setup.sh` to fix it.

#### Re-run Setup Script

The setup script is idempotent - you can run it as many times as needed:
```bash
./setup.sh
```

It will detect existing configurations and only fix what's broken.

---

## Development Workflow

### Running Tests
```bash
# Run all tests
php tests/test_runner.php

# Run specific test
php tests/unit/SetupScriptTest.php
php tests/unit/NotificationPreferencesTest.php
```

### Accessing the Website
- **Local machine**: http://localhost
- **Remote server**: http://YOUR_SERVER_IP
- **Production**: http://www.t212.org

### Making Changes
1. Create a feature branch: `git checkout -b feature-name`
2. Make your changes
3. Run tests: `php tests/test_runner.php`
4. Commit: `git commit -am "Description of changes"`
5. Push: `git push origin feature-name`
6. Create a pull request on GitHub

### Database Management
```bash
# Backup database
mysqldump -u u321706752_t212db -p u321706752_t212 > backup.sql

# Restore database
mysql -u u321706752_t212db -p u321706752_t212 < backup.sql

# Import fresh schema
mysql -u u321706752_t212db -p u321706752_t212 < db_copy/u104214272_t212.sql
```

---

## Project Structure

```
t212site/
├── setup.sh                      # Automated setup script
├── public_html/                  # Web root
│   ├── index.php                # Homepage
│   ├── api/                     # Backend API endpoints
│   ├── templates/               # HTML templates
│   ├── includes/                # Shared PHP includes
│   ├── css/                     # Stylesheets
│   ├── js/                      # JavaScript files
│   └── CREDENTIALS.json         # Database/SMTP credentials (not in git)
├── tests/                       # Test suite
│   ├── test_runner.php         # Main test runner
│   ├── bootstrap.php           # Test bootstrap
│   ├── unit/                   # Unit tests
│   └── integration/            # Integration tests
├── db_copy/                    # Database backups and schema
└── ai_spec_files/              # Documentation and specifications
```

---

## Getting Help

- **Issues with setup**: Run `./setup.sh` again or check the troubleshooting section above
- **Test failures**: Run `php tests/test_runner.php` to see which tests are failing
- **Questions**: Ask other developers or check the documentation in `ai_spec_files/`
