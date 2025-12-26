#!/usr/bin/env python3
"""
Convert SQL INSERT statements to CSV format for locations data.
This script reads all locations*.sql files and extracts the data into a single CSV file.
"""

import re
import csv
import os
from pathlib import Path

def extract_values_from_sql(sql_content):
    """Extract INSERT VALUES from SQL content."""
    # Pattern to match INSERT INTO statements with values
    # Handles both `cities` and `locations` table names
    pattern = r"INSERT INTO `(?:cities|locations)` \([^)]+\) VALUES\s*(.*?)(?:;|\n\n)"
    
    matches = re.findall(pattern, sql_content, re.DOTALL | re.IGNORECASE)
    
    all_rows = []
    for match in matches:
        # Split by "),(" to get individual rows
        # Clean up the match first
        match = match.strip()
        if match.endswith(';'):
            match = match[:-1]
        
        # Split into individual value tuples
        rows = re.findall(r'\(([^)]+)\)', match)
        
        for row in rows:
            # Parse the values
            values = []
            # Split by comma, but respect quoted strings
            parts = re.findall(r"'(?:[^'\\]|\\.)*'|[^,]+", row)
            
            for part in parts:
                part = part.strip()
                # Remove quotes from strings
                if part.startswith("'") and part.endswith("'"):
                    part = part[1:-1]
                    # Unescape single quotes
                    part = part.replace("\\'", "'")
                    part = part.replace("''", "'")
                values.append(part)
            
            if len(values) == 8:  # id, city, state_code, state_name, zip, lat, lng, county
                all_rows.append(values)
    
    return all_rows

def main():
    # Paths
    project_root = Path(__file__).parent.parent
    sql_dir = project_root / 'public' / 'sql'
    output_file = project_root / 'database' / 'seeders' / 'data' / 'locations.csv'
    
    # Create output directory if it doesn't exist
    output_file.parent.mkdir(parents=True, exist_ok=True)
    
    # SQL files to process
    sql_files = [
        'locations.sql',
        'locations1.sql',
        'locations2.sql',
        'locations3.sql',
        'locations4.sql',
        'locations5.sql',
        'locations6.sql'
    ]
    
    all_data = []
    
    print("Converting SQL files to CSV...")
    
    for sql_file in sql_files:
        file_path = sql_dir / sql_file
        
        if not file_path.exists():
            print(f"⚠️  Warning: {sql_file} not found, skipping...")
            continue
        
        print(f"Processing {sql_file}...")
        
        with open(file_path, 'r', encoding='utf-8') as f:
            sql_content = f.read()
        
        rows = extract_values_from_sql(sql_content)
        all_data.extend(rows)
        print(f"  ✓ Extracted {len(rows)} rows")
    
    # Write to CSV
    print(f"\nWriting {len(all_data)} total rows to CSV...")
    
    with open(output_file, 'w', newline='', encoding='utf-8') as f:
        writer = csv.writer(f)
        # Write header
        writer.writerow(['id', 'city', 'state_code', 'state', 'zip', 'latitude', 'longitude', 'county'])
        # Write data
        writer.writerows(all_data)
    
    print(f"✓ Successfully created {output_file}")
    print(f"✓ Total locations: {len(all_data)}")

if __name__ == '__main__':
    main()

