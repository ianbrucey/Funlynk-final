# F02 Recommendation Engine

## Feature Overview

Provide intelligent recommendations for posts and events using multi-factor scoring: recency, proximity, interest match, and social boost. Uses PostGIS distance, JSON interest overlap, and Redis caching. Builds on E01 `posts`, `activities`, `post_reactions`, `follows`.

## Feature Scope

### In Scope
- **Scoring service** combining multiple factors
- **Cold start** strategy for new users
- **Caching & freshness** management
- **Analytics** for score components and outcomes

### Out of Scope
- Feed rendering (F01)
- Conversion triggering (F03)

## Tasks Breakdown

### T01: RecommendationService & Scoring
**Estimated Time**: 6-7 hours
**Dependencies**: None
**Artisan Commands**:
```bash
php artisan make:class Services/RecommendationService --no-interaction
php artisan make:class Services/ScoringService --no-interaction
php artisan make:test --pest Unit/RecommendationScoringTest --no-interaction
```
**Description**: Implement scoring: `score = (recency*0.3)+(proximity*0.3)+(interest*0.2)+(social*0.2)`.
**Deliverables**:
- [ ] Deterministic scoring helpers + tests

---

### T02: Interest Matching
**Estimated Time**: 4-5 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:test --pest Unit/InterestScoreTest --no-interaction
```
**Description**: Jaccard similarity between user interests and item tags/keywords.
**Deliverables**:
- [ ] Interest score helper + tests

---

### T03: Social Boost
**Estimated Time**: 3-4 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:test --pest Unit/SocialBoostTest --no-interaction
```
**Description**: Boost if creator is followed or followed users reacted.
**Deliverables**:
- [ ] Social boost calculation + tests

---

### T04: Cold Start Handling
**Estimated Time**: 4-5 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:test --pest Feature/ColdStartTest --no-interaction
```
**Description**: Use nearby + trending tags for users without history.
**Deliverables**:
- [ ] Strategy and integration tests

---

### T05: Recommendation Caching
**Estimated Time**: 3-4 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:job UpdateRecommendations --no-interaction
php artisan make:test --pest Feature/RecommendationCacheTest --no-interaction
```
**Description**: Cache per-user recs for short TTL; invalidate on signal changes.
**Deliverables**:
- [ ] Job + cache keys and invalidation

---

### T06: Analytics & Explainability
**Estimated Time**: 4-5 hours
**Dependencies**: T01-T05
**Artisan Commands**:
```bash
php artisan make:test --pest Feature/RecommendationAnalyticsTest --no-interaction
```
**Description**: Log component scores and provide "Because..." explanations.
**Deliverables**:
- [ ] Metrics and basic dashboards

---

### T07: Integration into Feeds
**Estimated Time**: 4-5 hours
**Dependencies**: T01-T05
**Artisan Commands**:
```bash
php artisan make:test --pest Feature/FeedIntegrationTest --no-interaction
```
**Description**: Provide API to F01 feed services; ensure latency <100ms for score retrieval.
**Deliverables**:
- [ ] Stable API + performance tests

## Success Criteria

### Database & Models
- [ ] Queries leverage indexes; no N+1

### Filament Resources
- [ ] Admin widgets show rec quality metrics

### Business Logic & Services
- [ ] Deterministic scores; fresh recommendations

### User Experience
- [ ] Higher CTR vs baseline; explainable cards

### Integration
- [ ] Plugs into F01 feed generation

## Dependencies

### Blocks
- **F01 Discovery Feed**: Consumes recommendations

### External Dependencies
- **E01 Core**: Posts, Activities, Follows, PostReactions
- **Redis** for caching

## Technical Notes

### Laravel 12
- Optimize queries; configure `bootstrap/app.php`

### Filament v4
- Widgets for metrics

### Performance
- Cache hot users; batch recompute; prefetch for active sessions

### Testing
- Pest v4 + `RefreshDatabase`

---

**Feature Status**: Ready for Implementation
**Priority**: P1
**Epic**: E04 Discovery Engine
**Estimated Total Time**: 30-40 hours
**Dependencies**: E01/E02/E03 ready
