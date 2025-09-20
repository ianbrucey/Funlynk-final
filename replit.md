# Funlynk - React Native Expo App

## Overview
Funlynk is a React Native mobile application built with Expo that helps users discover and connect through shared experiences. Users can join activities, meet like-minded people, and build meaningful connections in their community.

## Project Structure
This repository contains the frontend mobile application built with:
- React Native 0.81.4
- Expo SDK 54.0.9  
- TypeScript 5.9.2
- React 19.1.0

The main application code is located in the `funlynk-app/` directory.

## Development Setup
The project is configured to run in the Replit environment:
- Development server runs on port 5000
- Web preview available through Replit's proxy system
- Hot reloading enabled for development

## Recent Changes (2025-09-20)
- Configured Node.js 20 environment
- Installed all project dependencies with legacy peer deps to resolve React version conflicts
- Added required web dependencies: react-dom and react-native-web
- Configured TypeScript with JSX support
- Set up Expo development server with proper host configuration for Replit
- Created Metro bundler configuration
- Configured deployment settings for production builds
- Added comprehensive .gitignore file

## Architecture
This is a frontend-only repository. The project documentation references a Laravel backend with PostgreSQL database, but the backend code is located in a separate repository.

## Deployment
The app is configured for autoscale deployment with:
- Build command: `npx expo export -p web`
- Run command: `npx serve dist -s -l`

## Key Features (from app.json)
- Location services integration
- Camera and photo library access
- Contact access capabilities
- Expo Router for navigation
- Cross-platform support (iOS, Android, Web)

## Development Workflow
1. The Expo development server automatically starts when the workspace is opened
2. Web preview is available through the Replit interface
3. Changes are automatically reflected with hot reloading
4. Console logs are available in the browser dev tools