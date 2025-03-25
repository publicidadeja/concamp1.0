# ConCamp Development Guidelines

## Build & Run Commands
- Local development: `npm start` (runs node server.js)
- XAMPP: Start Apache and MySQL services
- PHP debugging: Check logs in `/logs` directory
- Database setup: Run SQL files in root directory

## Code Organization
- `/includes`: Core functions and utilities
- `/api`: API endpoints
- `/pages`: View templates
- `/actions`: Form processing
- `/assets`: Static resources (JS, CSS, images)

## Code Style
- Naming: camelCase for functions/variables, snake_case for DB tables/columns
- PHP: Use PHPDoc comments for functions with @param and @return tags
- Indentation: 4 spaces
- Database: Always use prepared statements with named parameters
- Validation: Sanitize inputs with `sanitize($input, $context)` function
- Forms: Validate CSRF tokens for all submissions

## Security & Error Handling
- Never echo unsanitized user input
- Use context-aware sanitization
- Validate file uploads using `validateUpload()` function
- Store sensitive data in config files, not in code
- Wrap API endpoints in try/catch blocks
- Log errors to appropriate log files
- Return standardized JSON responses for API endpoints