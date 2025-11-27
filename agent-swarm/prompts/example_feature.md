# Task: Implement User Profile Feature

## Overview
Create a complete user profile feature with frontend, backend, and tests.

## Requirements

### Frontend (delegate to frontend agent)
- Create `src/components/Profile.jsx` - React component displaying user info
- Create `src/components/Profile.css` - Styling for the profile component
- Fields: name, email, avatar, bio

### Backend (delegate to backend agent)  
- Create `src/api/profile.py` - REST API endpoints
- GET /api/profile/{user_id} - Fetch profile
- PUT /api/profile/{user_id} - Update profile
- Include input validation

### Testing (delegate to qa agent)
- Create `tests/test_profile.py` - Unit tests for API
- Create `tests/test_profile_ui.py` - Component tests

## Execution Strategy
1. Run frontend and backend agents IN PARALLEL (no dependencies)
2. Run qa agent AFTER both complete (needs the code to test)

## Success Criteria
- All files created in correct locations
- Code follows project conventions
- Tests are runnable

