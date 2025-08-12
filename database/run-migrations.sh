#!/bin/bash
# Buffalo Marathon 2025 - Migration Runner
# Safely run database migrations
# Created: 2025-01-09

echo "Buffalo Marathon 2025 - Database Migration Runner"
echo "================================================="

# Set database connection variables
DB_HOST="localhost"
DB_NAME="buffalo_marathon"
DB_USER="your_username"
DB_PASS="your_password"

# Check if mysql command is available
if ! command -v mysql &> /dev/null; then
    echo "Error: mysql command not found. Please install MySQL client."
    exit 1
fi

# Function to run a migration
run_migration() {
    local migration_file=$1
    local migration_name=$(basename "$migration_file" .sql)
    
    echo "Running migration: $migration_name"
    
    # Check if migration already applied
    result=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -se "SELECT COUNT(*) FROM activity_logs WHERE description LIKE '%migration%' AND description LIKE '%$migration_name%'" 2>/dev/null)
    
    if [ "$result" -gt 0 ]; then
        echo "  -> Migration $migration_name already applied, skipping."
        return 0
    fi
    
    # Run the migration
    if mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$migration_file"; then
        echo "  -> Migration $migration_name completed successfully."
        return 0
    else
        echo "  -> Error: Migration $migration_name failed!"
        return 1
    fi
}

# Check if database exists
echo "Checking database connection..."
if ! mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME;" 2>/dev/null; then
    echo "Error: Cannot connect to database '$DB_NAME'. Please check your credentials and ensure the database exists."
    echo "You can create the database by running: CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    exit 1
fi

echo "Database connection successful."
echo ""

# Get migration directory
MIGRATION_DIR="$(dirname "$0")/migrations"

if [ ! -d "$MIGRATION_DIR" ]; then
    echo "Error: Migrations directory not found at $MIGRATION_DIR"
    exit 1
fi

# Run migrations in order
echo "Running migrations..."
migration_count=0
failed_count=0

for migration_file in "$MIGRATION_DIR"/*.sql; do
    if [ -f "$migration_file" ]; then
        if run_migration "$migration_file"; then
            ((migration_count++))
        else
            ((failed_count++))
        fi
        echo ""
    fi
done

echo "Migration Summary:"
echo "=================="
echo "Total migrations: $migration_count"
echo "Failed migrations: $failed_count"

if [ $failed_count -eq 0 ]; then
    echo "All migrations completed successfully!"
    exit 0
else
    echo "Some migrations failed. Please check the error messages above."
    exit 1
fi