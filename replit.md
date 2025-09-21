# Funlynk - Mobile-First Social Network

## Project Overview
Funlynk is a mobile-first social network designed for activity-first discovery. Users can discover and participate in niche, spontaneous, or hyper-local activities like pickup basketball, music jam sessions, or workshop events.

## Technology Stack
- **Frontend (Mobile App)**: React Native with Expo
- **Frontend (Web)**: Next.js (future - marketing/web app)
- **Backend & Database**: Supabase (PostgreSQL, Auth, Realtime)
- **Payments**: Stripe Connect
- **Infrastructure**: Supabase + Vercel

## Current Implementation Status

### Epic Planning Status
- ✅ **E01 Core Infrastructure** - Complete (Database, Auth, Geolocation, Notifications)
- ✅ **E02 User & Profile Management** - Complete (Profiles, Social Graph, Followers)
- ✅ **E03 Activity Management** - Complete (CRUD, Tagging, RSVPs)
- ✅ **E04 Discovery Engine** - Complete (Search, Recommendations, Feed)
- ✅ **E05 Social Interaction** - Complete (Comments, Sharing, Communities)
- ✅ **E06 Payments & Monetization** - Complete (Stripe, Subscriptions, Revenue)
- ✅ **E07 Administration** - Complete (Analytics, Moderation, Monitoring)

### Implementation Status
**Current Phase**: E01 Core Infrastructure Implementation

#### F01 Database Foundation
- **T01 Supabase Setup**: ✅ Complete (100% complete)
- **T02 Database Schema**: ✅ Complete (100% complete)
- **T03 RLS Policies**: 🔄 In Progress (95% complete - ready for execution)
- **T04 Database Migrations**: ⏳ Pending (depends on T03)
- **T05 Performance Optimization**: ⏳ Pending (depends on T03)
- **T06 Backup Procedures**: ⏳ Pending (depends on T01)

## Development Environment Setup

### Replit Configuration
- **Framework**: React Native Expo with web support
- **Development Server**: Running on port 5000 with LAN host access
- **Workflow**: "Expo Web Server" - `cd funlynk-app && npx expo start --web --port 5000 --host lan`
- **Dependencies**: Node.js 20, npm with legacy peer deps support

### Key Files
- `funlynk-app/` - React Native Expo application
- `context-engine/` - Complete project planning and architecture documentation
- `funlynk-app/package.json` - Mobile app dependencies
- `funlynk-app/metro.config.js` - Metro bundler configuration
- `funlynk-app/app.json` - Expo configuration

### Environment Dependencies
- Node.js 20.x (installed)
- React Native web dependencies (react-dom, react-native-web)
- Expo CLI for development and deployment

## Architecture Decisions

### Mobile-First Strategy
Primary user experience is the mobile app for on-the-go, spontaneous activity discovery.

### Asymmetrical Follower Model
One-way "follower" system (like Instagram) to reduce social friction and empower hosts to build audiences.

### Centralized Payments via Stripe Connect
All payment complexities delegated to Stripe Connect (Express model) for compliance and security.

### Database Architecture
PostgreSQL with PostGIS via Supabase for ACID compliance, complex relationships, and geospatial queries.

## Next Steps

1. **Complete T01 Supabase Setup** - Create Supabase project and configure environment
2. **Implement T02 Database Schema** - Create all tables and relationships from epic planning
3. **Configure Security (T03)** - Implement Row Level Security policies
4. **Set up Development Workflow** - Configure migrations and version control

## User Preferences
- Follow existing project architecture and planning documents in `context-engine/`
- Adhere to coding standards defined in `context-engine/standards/`
- Use hierarchical Epic→Feature→Task development approach
- Maintain comprehensive documentation and progress tracking

## Recent Changes
- **Sept 20, 2025**: Project imported to Replit environment
- **Sept 20, 2025**: React Native Expo web development server configured
- **Sept 20, 2025**: Basic project structure setup completed
- **Sept 20, 2025**: T01 Supabase Setup completed with full database connection
- **Sept 20, 2025**: T02 Database Schema completed with comprehensive social network schema
- **Sept 20, 2025**: Authentication system integration completed with auto-provisioning
- **Sept 20, 2025**: T03 RLS Policies designed and ready for implementation
- **Sept 20, 2025**: Core infrastructure foundation complete - ready for feature development

## Development Notes
- All epic planning is complete with 35 comprehensive planning documents
- 25 major architectural decisions documented in planning tracker
- Project ready for implementation phase with complete context
- Follow `context-engine/global-context.md` for development guidelines