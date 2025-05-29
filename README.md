# Survey Management System

A Laravel-based survey management system with AI-powered survey summary generation and event-driven notifications. This system allows you to create surveys, collect responses, and automatically generate intelligent summaries using OpenAI, with notifications sent to relevant users.

## Features

- **Survey Management**: Create and manage surveys with responses
- **AI-Powered Summaries**: Automatic survey summary generation using OpenAI GPT-4
- **Event-Driven Notifications**: Real-time notifications when summaries are created
- **Sentiment Analysis**: Automatic sentiment detection (positive, negative, neutral)
- **Topic Extraction**: AI-powered topic identification from survey responses
- **Rich Email Templates**: Beautiful email notifications with summary insights
- **Queue Support**: Background processing for better performance
- **Comprehensive Testing**: Full test suite with Pest PHP

## Tech Stack

- **Backend**: Laravel 12.x
- **PHP**: 8.2+
- **AI Integration**: OpenAI PHP SDK
- **Testing**: Pest PHP
- **Queue System**: Laravel Horizon
- **Architecture**: Domain-Driven Design (DDD)

## Requirements

- PHP 8.2 or higher
- Composer
- MySQL database
- OpenAI API key (for AI features)

## Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd survey
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Configure Environment Variables

Edit `.env` file and update the following:

```bash
# Application
APP_NAME="Survey Management System"
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=survey_db
DB_USERNAME=your_username
DB_PASSWORD=your_password

# OpenAI Configuration (Required for AI features)
OPENAI_API_KEY=your_openai_api_key
OPENAI_ORGANIZATION=your_openai_organization_id

# Queue Configuration
QUEUE_CONNECTION=database
```

### 5. Database Setup

```bash
# Create database tables
php artisan migrate

# (Optional) Seed with sample data
php artisan db:seed
```

## Running the Application

### Development Server

```bash
# Start Laravel development server
php artisan serve

# In another terminal, start Vite for asset compilation
npm run dev
```

Your application will be available at `http://localhost:8000`

### Queue Workers (Required for Notifications)

The notification system uses Laravel queues. Start the queue worker:

```bash
# Basic queue worker
php artisan queue:work

# Or use Horizon for advanced queue management
php artisan horizon
```

## OpenAI Configuration

Add OpenAi configs to the .env file

```bash
OPENAI_API_KEY=sk-your-api-key-here
OPENAI_ORGANIZATION=org-your-organization-id
```

## Usage

### Creating Survey Summaries

You can create survey summaries in several ways:

#### 1. Using the Demo Command

This command will:
- Create demo data if none exists
- Generate AI-powered survey summaries
- Fire events and send notifications
- Show the complete workflow

#### 2. Programmatically

```php
use Domain\Surveys\Actions\CreateSurveySummaryAction;

$action = new CreateSurveySummaryAction();
$summary = $action->execute($survey);
// This automatically fires SurveySummaryCreated event
// Which triggers notifications to relevant users
```

### Event-Driven Notifications

The system uses Laravel events for notifications:

1. **Event**: `SurveySummaryCreated` - Fired when a summary is created
2. **Listener**: `NotifyUsersAboutTheNewSurveySummaries` - Handles the event
3. **Notification**: `SurveySummariesNotification` - Sends rich email content

## Testing

The project uses Pest PHP for testing:

```bash
# Run all tests
php artisan test

# Run tests with coverage
./vendor/bin/pest --coverage
```

## API Documentation

The Survey Management System provides a RESTful API for managing surveys and responses. The API uses Laravel Sanctum for authentication and includes rate limiting for public endpoints.

### Base URL

```
http://localhost:8000/api
```

### Authentication

The API uses Laravel Sanctum token-based authentication. First, obtain a token by authenticating with your credentials.

#### Create Authentication Token

**POST** `/tokens/create`

Create a new API token for authentication.

**Request Body:**
```json
{
    "email": "user@example.com",
    "password": "your_password"
}
```

**Response (Success):**
```json
{
    "token": "1|abc123def456ghi789..."
}
```

**Response (Error):**
```json
{
    "message": "Unauthenticated",
    "errors": {
        "email": ["The provided credentials are incorrect."]
    }
}
```

**Usage:**
Include the token in subsequent requests using the Authorization header:
```
Authorization: Bearer 1|abc123def456ghi789...
```

### Survey Endpoints

#### Get All Surveys

**GET** `/surveys`

Retrieve a list of all surveys. This endpoint is public and doesn't require authentication.

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "title": "Customer Satisfaction Survey",
            "description": "A survey about our service quality",
            "status": "active",
            "company_id": 1,
            "created_at": "2025-05-28T10:00:00.000000Z",
            "updated_at": "2025-05-28T10:00:00.000000Z"
        }
    ]
}
```

#### Create Survey

**POST** `/surveys`

Create a new survey. Requires authentication.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "title": "New Survey Title",
    "description": "Survey description",
    "company_id": 1
}
```

**Response (Success):**
```json
{
    "data": {
        "id": 2,
        "title": "New Survey Title",
        "description": "Survey description",
        "status": "active",
        "company_id": 1,
        "created_at": "2025-05-28T10:00:00.000000Z",
        "updated_at": "2025-05-28T10:00:00.000000Z"
    }
}
```

#### Update Survey

**PUT** `/surveys/{survey}`

Update an existing survey. Requires authentication.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "title": "Updated Survey Title",
    "description": "Updated description"
}
```

**Response:**
```json
{
    "data": {
        "id": 1,
        "title": "Updated Survey Title",
        "description": "Updated description",
        "status": "active",
        "company_id": 1,
        "created_at": "2025-05-28T10:00:00.000000Z",
        "updated_at": "2025-05-28T10:30:00.000000Z"
    }
}
```

#### Delete Survey

**DELETE** `/surveys/{survey}`

Delete a survey. Requires authentication.

**Headers:**
```
Authorization: Bearer {token}
```

**Response Code:**
```json
204
```

### Survey Response Endpoints

#### Submit Survey Response

**POST** `/surveys/{survey}/responses`

Submit a response to a specific survey. This endpoint is public but rate-limited to 10 requests per minute.

**Request Body:**
```json
{
    "response_text": "This is my response to the survey. I found the service excellent and would recommend it to others."
}
```

**Response (Success):**
```json
{
    "data": {
        "id": 1,
        "survey_id": 1,
        "response_text": "This is my response to the survey. I found the service excellent and would recommend it to others.",
        "created_at": "2025-05-28T10:00:00.000000Z",
        "updated_at": "2025-05-28T10:00:00.000000Z"
    }
}
```

**Rate Limiting:**
- **Limit:** 10 requests per minute
- **Headers in Response:**
  - `X-RateLimit-Limit: 10`
  - `X-RateLimit-Remaining: 9`

### HTTP Status Codes

The API uses standard HTTP status codes:

- **200 OK** - Request successful
- **201 Created** - Resource created successfully
- **400 Bad Request** - Invalid request data
- **401 Unauthorized** - Authentication required or invalid
- **403 Forbidden** - Access denied
- **404 Not Found** - Resource not found
- **422 Unprocessable Entity** - Validation errors
- **429 Too Many Requests** - Rate limit exceeded
- **500 Internal Server Error** - Server error

### Error Responses

All error responses follow a consistent format:

```json
{
    "message": "Error description",
    "errors": {
        "field_name": [
            "Specific validation error message"
        ]
    }
}
```

### Example Usage

#### Complete Workflow Example

1. **Create a token:**
```bash
curl -X POST http://localhost:8000/api/tokens/create \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@example.com", "password": "password"}'
```

2. **Create a survey:**
```bash
curl -X POST http://localhost:8000/api/surveys \
  -H "Authorization: Bearer 1|abc123..." \
  -H "Content-Type: application/json" \
  -d '{"title": "Product Feedback", "description": "Tell us about your experience", "company_id": 1}'
```

3. **Submit a response:**
```bash
curl -X POST http://localhost:8000/api/surveys/1/responses \
  -H "Content-Type: application/json" \
  -d '{"response_text": "Great product, very satisfied!"}'
```

4. **Get all surveys:**
```bash
curl -X GET http://localhost:8000/api/surveys
```

### Rate Limiting

- **Survey Responses:** 10 requests per minute per IP address
- **Authenticated Endpoints:** No specific rate limiting (uses Laravel's default)

### Security Features

- **Sanctum Authentication:** Token-based authentication
- **Rate Limiting:** Applied to public endpoints
- **Input Validation:** All inputs are validated before processing
- **SQL Injection Protection:** Laravel's Eloquent ORM provides protection

## Opensource packages used

1. Spatie Laravel Permissions
2. Enum Permissions - created by myself - https://github.com/Althinect/enum-permission
3. Open AI PHP Laravel plugin - https://github.com/openai-php/laravel

## Potential Improvements

1. **Service Layer Implementation** - Separate business logic from actions into dedicated service classes with interfaces for better dependency injection and testability

2. **AI Service Abstraction** - Abstract OpenAI API calls behind interfaces to enable easy mocking for tests and potential provider switching

3. **Data Transfer Objects (DTOs)** - Replace array data passing with type-safe DTOs for better code clarity and compile-time error checking

4. **Enhanced Domain Events** - Enrich events with more contextual data (user, timestamps) and implement additional domain events for complete workflow coverage

5. **Query Objects** - Encapsulate complex database queries in dedicated query classes to improve maintainability and reusability

## Time Tracking

| Task Area | Hours | Description |
|-----------|-------|-------------|
| **Architecture/Setup** | 1.0 | Initial Laravel setup, Domain-driven design structure, environment configuration |
| **Models/Database** | 1.5 | Eloquent models, migrations, relationships, database schema design |
| **Authentication/Authorization** | 0.5 | Laravel Sanctum setup, permissions with Spatie package, user authentication |
| **API Development** | 2.0 | REST endpoints, controllers, request validation, resource transformers |
| **Event System** | 1.5 | Domain events, listeners, notification system implementation |
| **AI Integration** | 1.5 | OpenAI API integration, survey summary generation, sentiment analysis |
| **Testing** | 1.0 | Unit tests, feature tests, Pest PHP test framework setup |
| **Documentation** | 0.5 | README creation, API documentation, code comments |
| **Queue/Background Jobs** | 0.5 | Queue configuration, background processing setup |
| **Total** | **9.0** | **Complete survey management system with AI-powered features** |