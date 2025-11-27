# Agent C Tasks - Database Schema & Models

**Agent**: Agent C (Database/Infrastructure Specialist)  
**Sprint**: E04 Discovery Engine  
**Duration**: Week 1 (Days 1-2) + Week 2 (Optimization)

---

## 🎯 Your Mission

Build the database foundation for FunLynk's Posts vs Events architecture: Posts table, reactions, conversions, and models with relationships.

**Key Principle**: Posts are ephemeral (24-48h) and can evolve into Events. The database must track this lifecycle and conversion.

---

## 📋 Task List

### **Task 1: Verify/Update Posts Migration** (Priority: P0)

**File**: `database/migrations/2024_XX_XX_create_posts_table.php` (already exists from E01)

**Verify Schema**:
```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('title');
    $table->text('description')->nullable();
    $table->geography('location_coordinates', 'point', 4326); // PostGIS
    $table->string('location_name');
    $table->string('time_hint')->nullable(); // e.g., "Tonight around 8pm"
    $table->timestamp('expires_at'); // 24-48h from creation
    $table->enum('status', ['active', 'expired', 'converted'])->default('active');
    $table->integer('reaction_count')->default(0);
    $table->timestamp('conversion_suggested_at')->nullable();
    $table->foreignId('converted_to_activity_id')->nullable()->constrained('activities')->onDelete('set null');
    $table->timestamps();
    
    // Indexes
    $table->spatialIndex('location_coordinates'); // PostGIS GIST index
    $table->index('expires_at');
    $table->index('user_id');
    $table->index('status');
});
```

**If Missing**: Create the migration  
**If Exists**: Verify all columns and indexes are present

---

### **Task 2: Verify/Update Post Reactions Migration** (Priority: P0)

**File**: `database/migrations/2024_XX_XX_create_post_reactions_table.php` (already exists from E01)

**Verify Schema**:
```php
Schema::create('post_reactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('post_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->enum('reaction_type', ['im_down', 'join_me']);
    $table->timestamp('created_at');
    
    // Unique constraint: one reaction per user per post
    $table->unique(['post_id', 'user_id']);
    
    // Indexes
    $table->index('post_id');
    $table->index('user_id');
});
```

**If Missing**: Create the migration  
**If Exists**: Verify all columns and constraints are present

---

### **Task 3: Verify/Update Post Conversions Migration** (Priority: P0)

**File**: `database/migrations/2024_XX_XX_create_post_conversions_table.php` (already exists from E01)

**Verify Schema**:
```php
Schema::create('post_conversions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('post_id')->constrained()->onDelete('cascade');
    $table->foreignId('activity_id')->constrained()->onDelete('cascade');
    $table->timestamp('converted_at');
    $table->enum('conversion_type', ['manual', 'auto']); // manual = host-initiated, auto = 10+ reactions
    $table->integer('reaction_count_at_conversion');
    
    // Indexes
    $table->index('post_id');
    $table->index('activity_id');
});
```

**If Missing**: Create the migration  
**If Exists**: Verify all columns are present

---

### **Task 4: Create Post Model** (Priority: P0)

**File**: `app/Models/Post.php`

**Implementation**:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class Post extends Model
{
    use HasSpatial;
    
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'location_coordinates',
        'location_name',
        'time_hint',
        'expires_at',
        'status',
        'reaction_count',
        'conversion_suggested_at',
        'converted_to_activity_id',
    ];
    
    protected function casts(): array
    {
        return [
            'location_coordinates' => Point::class,
            'expires_at' => 'datetime',
            'conversion_suggested_at' => 'datetime',
        ];
    }
    
    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function reactions(): HasMany
    {
        return $this->hasMany(PostReaction::class);
    }
    
    public function convertedActivity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'converted_to_activity_id');
    }
    
    public function conversion(): HasOne
    {
        return $this->hasOne(PostConversion::class);
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where('expires_at', '>', now());
    }
    
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now())
                     ->orWhere('status', 'expired');
    }
    
    public function scopeNearUser($query, float $lat, float $lng, int $radiusMeters = 10000)
    {
        $point = new Point($lat, $lng, 4326);
        return $query->whereDistance('location_coordinates', $point, '<=', $radiusMeters);
    }
    
    // Helper Methods
    public function isExpired(): bool
    {
        return $this->expires_at->isPast() || $this->status === 'expired';
    }
    
    public function canConvert(): bool
    {
        return $this->reaction_count >= 5 && $this->status === 'active';
    }
    
    public function shouldAutoConvert(): bool
    {
        return $this->reaction_count >= 10 && $this->status === 'active';
    }
    
    public function timeUntilExpiration(): string
    {
        if ($this->isExpired()) {
            return 'Expired';
        }
        
        return $this->expires_at->diffForHumans();
    }
    
    public function imDownCount(): int
    {
        return $this->reactions()->where('reaction_type', 'im_down')->count();
    }
    
    public function joinMeCount(): int
    {
        return $this->reactions()->where('reaction_type', 'join_me')->count();
    }
}
```

---

### **Task 5: Create PostReaction Model** (Priority: P0)

**File**: `app/Models/PostReaction.php`

**Implementation**:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostReaction extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'post_id',
        'user_id',
        'reaction_type',
        'created_at',
    ];
    
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
    
    // Relationships
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    // Validation
    public static function validReactionTypes(): array
    {
        return ['im_down', 'join_me'];
    }
}
```

---

### **Task 6: Create PostConversion Model** (Priority: P0)

**File**: `app/Models/PostConversion.php`

**Implementation**:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostConversion extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'post_id',
        'activity_id',
        'converted_at',
        'conversion_type',
        'reaction_count_at_conversion',
    ];
    
    protected function casts(): array
    {
        return [
            'converted_at' => 'datetime',
        ];
    }
    
    // Relationships
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
    
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
```

---

### **Task 7: Update Activity Model** (Priority: P0)

**File**: `app/Models/Activity.php`

**Add to existing model**:
```php
// Add to fillable array
'originated_from_post_id',

// Add relationship
public function originatedFromPost(): BelongsTo
{
    return $this->belongsTo(Post::class, 'originated_from_post_id');
}

// Add scope
public function scopeConvertedFromPost($query)
{
    return $query->whereNotNull('originated_from_post_id');
}
```

---

### **Task 8: Create Factories** (Priority: P1)

**Files to Create**:
- `database/factories/PostFactory.php`
- `database/factories/PostReactionFactory.php`

**PostFactory**:
```php
public function definition(): array
{
    return [
        'user_id' => User::factory(),
        'title' => fake()->sentence(),
        'description' => fake()->paragraph(),
        'location_coordinates' => new Point(fake()->latitude(), fake()->longitude(), 4326),
        'location_name' => fake()->address(),
        'time_hint' => fake()->randomElement(['Tonight around 8pm', 'Tomorrow afternoon', 'This weekend']),
        'expires_at' => now()->addHours(rand(24, 48)),
        'status' => 'active',
        'reaction_count' => 0,
    ];
}
```

---

### **Task 9: Create Seeders** (Priority: P1)

**File**: `database/seeders/PostSeeder.php`

**Create 50 test posts**:
- 30 active posts (not expired)
- 10 posts with 3-7 reactions (eligible for conversion suggestion)
- 5 posts with 10+ reactions (eligible for auto-conversion)
- 5 expired posts

---

### **Task 10: Run Migrations & Seed** (Priority: P0)

```bash
php artisan migrate:fresh --seed
```

Verify:
- Posts table exists with PostGIS column
- Post reactions table exists
- Post conversions table exists
- Test data seeded correctly

---

## ✅ Definition of Done

- [ ] Posts migration verified/created with PostGIS column
- [ ] Post reactions migration verified/created
- [ ] Post conversions migration verified/created
- [ ] Post model created with relationships and scopes
- [ ] PostReaction model created
- [ ] PostConversion model created
- [ ] Activity model updated with post relationship
- [ ] Factories created for testing
- [ ] Seeders created with test data
- [ ] Migrations run successfully
- [ ] Test data seeded (50 posts with various states)

---

## 🚀 Start Date

**Day 1** (Start immediately - you're the foundation!)

Good luck! 🗄️

