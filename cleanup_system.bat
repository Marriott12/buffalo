@echo off
echo =====================================================
echo Buffalo Marathon 2025 - System Cleanup Script
echo Date: %date% %time%
echo Purpose: Remove all unused test, debug, and temporary files
echo =====================================================
echo.

cd /d "c:\wamp64\www\buffalo-marathon"

echo Cleaning test files...
if exist test_*.php del test_*.php /q
if exist debug_*.php del debug_*.php /q
if exist setup_*.php del setup_*.php /q
if exist quick_test.php del quick_test.php /q
if exist comprehensive_logout_test.php del comprehensive_logout_test.php /q

echo Cleaning utility and migration files...
if exist migrate_database.php del migrate_database.php /q
if exist manual_database_update.sql del manual_database_update.sql /q
if exist remote_database_update.sql del remote_database_update.sql /q
if exist verify_schema.php del verify_schema.php /q
if exist update_schema.php del update_schema.php /q
if exist update_contact_info.php del update_contact_info.php /q
if exist enhance_database.php del enhance_database.php /q
if exist error_diagnostic.php del error_diagnostic.php /q
if exist system_verification.php del system_verification.php /q
if exist navigation_status.php del navigation_status.php /q
if exist setup_database_complete.php del setup_database_complete.php /q

echo Cleaning documentation files...
if exist REMOTE_MIGRATION_GUIDE.md del REMOTE_MIGRATION_GUIDE.md /q
if exist SCHEDULE_UPDATE_SUMMARY.md del SCHEDULE_UPDATE_SUMMARY.md /q
if exist SYSTEM_ENHANCEMENT_COMPLETE.md del SYSTEM_ENHANCEMENT_COMPLETE.md /q
if exist SSL_SETUP_GUIDE.md del SSL_SETUP_GUIDE.md /q
if exist PRODUCTION_READY.md del PRODUCTION_READY.md /q

echo Cleaning database migration files...
if exist database\migration_2025_08_14.sql del database\migration_2025_08_14.sql /q
if exist database\update_schedule.sql del database\update_schedule.sql /q
if exist database\security_tables.sql del database\security_tables.sql /q
if exist database\optimize.sql del database\optimize.sql /q

echo Cleaning log files...
if exist logs\php_errors.log del logs\php_errors.log /q
if exist logs\*.log del logs\*.log /q

echo Cleaning any remaining temporary files...
if exist *.tmp del *.tmp /q
if exist *.bak del *.bak /q
if exist *_backup.* del *_backup.* /q
if exist *_temp.* del *_temp.* /q

echo.
echo =====================================================
echo Cleanup completed successfully!
echo =====================================================
echo.
echo Remaining production files:
dir *.php /b
echo.
pause
