# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Laravel Backend
- `php artisan serve` - Start Laravel development server
- `php artisan migrate` - Run database migrations
- `php artisan test` - Run PHP tests with PHPUnit
- `composer dev` - Start full development environment (server, queue, logs, and Vite)
- `composer test` - Clear config and run tests

### Vue.js Frontend
- `npm run dev` - Start Vite development server
- `npm run build` - Build for production (includes type checking)
- `npm run preview` - Preview production build
- `npm run type-check` - Run TypeScript type checking
- `npm run build-only` - Build without type checking

### Testing
- PHPUnit tests are located in `tests/` directory with Feature and Unit test suites
- Run `composer test` or `php artisan test` for backend tests
- Frontend testing setup not configured

## Architecture Overview

This is a **Laravel + Vue.js SPA** for a real estate application called "Lahomes" with the following architecture:

### Backend (Laravel 12)
- **MVC Structure**: Standard Laravel structure with Models, Controllers, and Views
- **Database**: SQLite database (`database/database.sqlite`) 
- **API**: Laravel serves as API backend for the Vue frontend
- **Authentication**: Laravel authentication system
- **Key directories**:
  - `app/` - Application logic (Models, Controllers, Providers)
  - `routes/` - Route definitions
  - `database/` - Migrations, factories, seeders
  - `config/` - Configuration files

### Frontend (Vue 3 + TypeScript)
- **SPA Architecture**: Single Page Application using Vue Router
- **State Management**: Pinia stores for layout and authentication
- **UI Framework**: Bootstrap 5 + Bootstrap Vue Next
- **Build Tool**: Vite with Laravel Vite plugin integration
- **Key directories**:
  - `resources/js/` - Vue application source
  - `resources/js/views/` - Page components organized by feature
  - `resources/js/components/` - Reusable Vue components  
  - `resources/js/stores/` - Pinia state management
  - `resources/js/layouts/` - Layout components (Auth, Vertical)

### Feature Modules
The application includes comprehensive modules for:
- **Dashboards**: Analytics, Agent, Customer dashboards
- **Property Management**: Grid/list views, property details, creation
- **Agent Management**: Agent profiles, grids, creation
- **Customer Management**: Customer profiles, transactions
- **Content**: Blog posts, reviews, messaging
- **UI Components**: Charts, forms, tables, maps

### Styling & Assets
- **SCSS**: Custom SCSS in `resources/js/assets/scss/` with component-based organization
- **Icons**: Boxicons and Remix icons
- **Images**: Comprehensive asset library in `public/assets/images/`
- **Fonts**: Custom font files included

### Development Integration
- **Laravel Vite Plugin**: Seamless integration between Laravel and Vite
- **Hot Module Replacement**: Available during development
- **TypeScript**: Full TypeScript support with type definitions
- **Component Auto-import**: Bootstrap Vue Next components auto-imported

### Mock Data & Development
- Includes fake backend helper for development (`resources/js/helpers/fake-backend.ts`)
- Extensive mock data files throughout view components
- No environment file present - configure `.env` as needed for database and app settings