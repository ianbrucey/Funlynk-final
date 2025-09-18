
### **Tier 1: Foundational Services & Core Data Models**

*(This layer is the bedrock. Everything else depends on it.)*

**Module 1: Core Infrastructure**

* **Submodule 1.1: Database Schema & Models**
  * **Purpose:** Defines the entire data structure of the application.
  * **Components:** Tables/models for `Users`, `Activities`, `Tags`, `Follows`, ` RSVPs`, `Comments`, `Payments`, etc.
  * **Depends On:** Nothing. This is the foundation.
* **Submodule 1.2: Authentication Service**
  * **Purpose:** Manages user identity, registration, login, and session management.
  * **Components:** Secure password handling, social logins (Google/Apple), token generation (JWT).
  * **Depends On:** `Database Schema (1.1)`
* **Submodule 1.3: Geolocation Service**
  * **Purpose:** Handles all location-based logic.
  * **Components:** Functions to process coordinates, calculate distances, and perform efficient spatial queries (e.g., "find all activities within a 5-mile radius").
  * **Depends On:** `Database Schema (1.1)`
* **Submodule 1.4: Notification Service**
  * **Purpose:** A centralized service for sending all communications.
  * **Components:** Push notification handlers (APNS/FCM), email sending logic. Other modules will call this service.
  * **Depends On:** `Database Schema (1.1)`

---

### **Tier 2: Core Application Logic & User Management**

*(With the foundation in place, we can now build the core features users will interact with.)*

**Module 2: User & Profile Management**

* **Submodule 2.1: Profile Service**
  * **Purpose:** Manages all user profile data beyond authentication.
  * **Components:** CRUD (Create, Read, Update, Delete) for bios, profile pictures, user interests (tags).
  * **Depends On:** `Authentication Service (1.2)`
* **Submodule 2.2: Social Graph Service (Followers)**
  * **Purpose:** Manages the connections between users.
  * **Components:** Logic for `follow`, `unfollow`, `list followers`, `list following`.
  * **Depends On:** `Profile Service (2.1)`

**Module 3: Activity Management**

* **Submodule 3.1: Activity CRUD Service**
  * **Purpose:** The core logic for creating, reading, updating, and deleting activities.
  * **Components:** Handles all data related to an activity: title, description, time, location, host, associated tags.
  * **Depends On:** `Profile Service (2.1)`, `Geolocation Service (1.3)`
* **Submodule 3.2: Tagging & Category System**
  * **Purpose:** Manages the creation and association of tags with activities and users.
  * **Components:** Logic for tag searching and suggestions.
  * **Depends On:** `Activity CRUD Service (3.1)`, `Profile Service (2.1)`
* **Submodule 3.3: RSVP / Attendance Service**
  * **Purpose:** Manages which users are attending which activities.
  * **Components:** Logic for `join`, `leave`, `list attendees`.
  * **Depends On:** `Profile Service (2.1)`, `Activity CRUD Service (3.1)`

---

### **Tier 3: Discovery & Engagement Features**

*(Now that users can create content, we need to build the systems for others to find and engage with it.)*

**Module 4: Discovery Engine**

* **Submodule 4.1: Feed Generation Service**
  * **Purpose:** Generates the dynamic home feeds for users.
  * **Components:** Algorithms for the "For You" (interest-based), "Nearby" (geo-based), and "Following" (social graph-based) feeds.
  * **Depends On:** `Social Graph Service (2.2)`, `Activity CRUD Service (3.1)`, `Geolocation Service (1.3)`
* **Submodule 4.2: Search Service**
  * **Purpose:** Powers all search and filtering functionality.
  * **Components:** Keyword search, tag filtering, date filtering, location filtering.
  * **Depends On:** `Activity CRUD Service (3.1)`, `Tagging System (3.2)`

**Module 5: Social Interaction**

* **Submodule 5.1: Commenting Service**
  * **Purpose:** Manages discussions on activity pages.
  * **Components:** Logic for creating, viewing, and deleting comments.
  * **Depends On:** `Profile Service (2.1)`, `Activity CRUD Service (3.1)`
* **Submodule 5.2: "Flare" (Inquiry) System**
  * **Purpose:** Manages the full lifecycle of a user inquiry.
  * **Components:** Logic to create a Flare, notify relevant users, track interest, and convert a Flare into a concrete activity.
  * **Depends On:** `Profile Service (2.1)`, `Tagging System (3.2)`, `Notification Service (1.4)`, and triggers `Activity CRUD Service (3.1)` upon conversion.

---

### **Tier 4: Monetization & Administration**

*(This final layer rests on top of the entire functioning application.)*

**Module 6: Payments & Monetization**

* **Submodule 6.1: Stripe Connect Integration Service**
  * **Purpose:** A dedicated service to encapsulate all interactions with the Stripe API.
  * **Components:** Onboarding hosts (Express accounts), creating Payment Intents, handling webhooks for successful payments and account updates.
  * **Depends On:** `Profile Service (2.1)`
* **Submodule 6.2: Ticketing Service**
  * **Purpose:** Connects a successful payment to a user's attendance.
  * **Components:** Logic that, upon a successful payment webhook from Stripe, formally registers the user for the event.
  * **Depends On:** `Stripe Integration (6.1)`, `RSVP / Attendance Service (3.3)`

**Module 7: Administration**

* **Submodule 7.1: Content Moderation Service**
  * **Purpose:** Manages the reporting and review of user-generated content.
  * **Components:** User-facing "report" feature, backend queue for admin review, tools for admins to take action (hide, delete, ban).
  * **Depends On:** `Profile Service (2.1)`, `Activity CRUD Service (3.1)`, `Commenting Service (5.1)`
* **Submodule 7.2: Admin Dashboard**
  * **Purpose:** A web-based interface for platform administrators.
  * **Components:** Analytics view, user management, content oversight.
  * **Depends On:** Nearly all other modules.
