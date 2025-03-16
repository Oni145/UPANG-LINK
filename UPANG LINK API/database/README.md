# Database Initialization

This directory contains scripts for initializing and updating the UPANG LINK database.

## Scripts

- `init_all.bat`: Main initialization script that drops the existing database, creates a new one, and applies all necessary updates.
- `schema.sql`: Contains the database schema definition.
- `fix_constraints.sql`: Applies constraints to the database.
- `create_user.sql`: Creates the admin user.
- `update_schema.sql`: Updates the database schema.
- `update_requirements.sql`: Updates the requirements for all request types.
- `update_requirements.bat`: Standalone script to update requirements without reinitializing the database.
- `test_requirements.bat`: Test script to verify that requirements are properly set.

## Requirements Structure

Each request type has a standardized requirements structure in JSON format:

```json
{
  "fields": [
    {
      "name": "field_name",
      "label": "Field Label",
      "type": "text|file|select",
      "required": true|false,
      "description": "Field description",
      "allowed_types": "pdf,jpg,png" // for file fields
    }
  ],
  "required_docs": ["Document 1", "Document 2"],
  "instructions": "Instructions for the request type"
}
```

## Usage

To initialize the database:

```
.\init_all.bat
```

To update requirements only:

```
.\update_requirements.bat
```

To test requirements:

```
.\test_requirements.bat
``` 