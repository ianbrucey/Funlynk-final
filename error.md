
7s
Run vendor/bin/phpstan analyse --no-progress
Note: Using configuration file /home/runner/work/Funlynk-final/Funlynk-final/phpstan.neon.
Error: Access to an undefined property App\Models\User::$location_coordinates.
Error: Access to an undefined property App\Models\User::$location_coordinates.
Error: Access to an undefined property App\Models\User::$location_coordinates.
Error: Variable $result might not be defined.
Error: Method App\Console\Commands\FeedBenchmarkCommand::explainDWithin() should return list<string></string> but returns array.
Error: Method App\Console\Commands\FeedBenchmarkCommand::explainNearbyPosts() should return list<string></string> but returns array.
Error: Method App\Livewire\Discovery\NearbyFeed::placeholder() has no return type specified.
Error: Method App\Services\FeedService::basePostQuery() return type with generic class Illuminate\Database\Eloquent\Builder does not specify its types: TModel
Error: Method App\Services\FeedService::baseEventQuery() return type with generic class Illuminate\Database\Eloquent\Builder does not specify its types: TModel
Error: Method App\Services\FeedService::applySpatialFilter() has parameter $query with generic class Illuminate\Database\Eloquent\Builder but does not specify its types: TModel
Error: Method App\Services\FeedService::applySpatialFilter() return type with generic class Illuminate\Database\Eloquent\Builder does not specify its types: TModel
Error: Method App\Services\FeedService::paginateMerged() has parameter $cursorData with no value type specified in iterable type array.
Error: Method App\Services\FeedService::paginateMerged() has parameter $eventQuery with generic class Illuminate\Database\Eloquent\Builder but does not specify its types: TModel
Error: Method App\Services\FeedService::paginateMerged() has parameter $postQuery with generic class Illuminate\Database\Eloquent\Builder but does not specify its types: TModel
Error: Method App\Services\FeedService::paginateMerged() should return array{items: list<array<string, mixed>>, nextCursor: string|null, hasMore: bool} but returns array{items: array<int, array<string, mixed>>, nextCursor: string|null, hasMore: bool}.
Error: Method App\Services\FeedService::applyCursor() has parameter $cursorData with no value type specified in iterable type array.
Error: Method App\Services\FeedService::applyCursor() has parameter $query with generic class Illuminate\Database\Eloquent\Builder but does not specify its types: TModel
Error: Method App\Services\FeedService::applyCursor() return type with generic class Illuminate\Database\Eloquent\Builder does not specify its types: TModel
Error: Method App\Services\FeedService::encodeCursor() has parameter $item with no value type specified in iterable type array.
Error: Method App\Services\FeedService::mapPostToFeedItem() has parameter $viewerReactedIds with generic class Illuminate\Support\Collection but does not specify its types: TKey, TValue
Error: Access to an undefined property Illuminate\Database\Eloquent\Model::$display_name.
Error: Access to an undefined property Illuminate\Database\Eloquent\Model::$name.
Error: Access to an undefined property Illuminate\Database\Eloquent\Model::$username.
Error: Using nullsafe property access "?->display_name" on left side of ?? is unnecessary. Use -> instead.
Error: Using nullsafe property access "?->username" on left side of ?? is unnecessary. Use -> instead.
Error: Access to an undefined property Illuminate\Database\Eloquent\Model::$avatar_url.
Error: Access to an undefined property Illuminate\Database\Eloquent\Model::$profile_image_url.
Error: Access to an undefined property Illuminate\Database\Eloquent\Model::$username.
Error: Parameter #2 $syntax of method Carbon\Carbon::diffForHumans() expects array|int|null, true given.
Error: Cannot call method diffForHumans() on string.
Error: Access to an undefined property Illuminate\Database\Eloquent\Model::$display_name.
Error: Access to an undefined property Illuminate\Database\Eloquent\Model::$username.
Error: Using nullsafe property access "?->display_name" on left side of ?? is unnecessary. Use -> instead.
Error: Using nullsafe property access "?->username" on left side of ?? is unnecessary. Use -> instead.
Error: Access to an undefined property Illuminate\Database\Eloquent\Model::$profile_image_url.
Error: Parameter #2 $syntax of method Carbon\Carbon::diffForHumans() expects array|int|null, true given.

---

  Line   app/Console/Commands/FeedBenchmarkCommand.php

---

  120    Access to an undefined property
         App\Models\User::$location_coordinates.
         🪪  property.notFound
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-und
         efined-property
  121    Access to an undefined property
         App\Models\User::$location_coordinates.
         🪪  property.notFound
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-und
         efined-property
  122    Access to an undefined property
         App\Models\User::$location_coordinates.
         🪪  property.notFound
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-und
         efined-property
  126    Variable $result might not be defined.
         🪪  variable.undefined
  154    Method App\Console\Commands\FeedBenchmarkCommand::explainDWithin()
         should return list<string></string> but returns array.
         🪪  return.type
         💡  array might not be a list.
  386    Method
         App\Console\Commands\FeedBenchmarkCommand::explainNearbyPosts()
         should return list<string></string> but returns array.
         🪪  return.type
         💡  array might not be a list.

---

---

  Line   app/Livewire/Discovery/NearbyFeed.php

---

  48     Method App\Livewire\Discovery\NearbyFeed::placeholder() has no return
         type specified.
         🪪  missingType.return

---

---

  Line   app/Services/FeedService.php

---

  320    Method App\Services\FeedService::basePostQuery() return type with
         generic class Illuminate\Database\Eloquent\Builder does not specify
         its types: TModel
         🪪  missingType.generics
  352    Method App\Services\FeedService::baseEventQuery() return type with
         generic class Illuminate\Database\Eloquent\Builder does not specify
         its types: TModel
         🪪  missingType.generics
  387    Method App\Services\FeedService::applySpatialFilter() has parameter
         $query with generic class Illuminate\Database\Eloquent\Builder but
         does not specify its types: TModel
         🪪  missingType.generics
  387    Method App\Services\FeedService::applySpatialFilter() return type
         with generic class Illuminate\Database\Eloquent\Builder does not
         specify its types: TModel
         🪪  missingType.generics
  404    Method App\Services\FeedService::paginateMerged() has parameter
         $cursorData with no value type specified in iterable type array.
         🪪  missingType.iterableValue
         💡  See:
         https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-i
         terable-type
  404    Method App\Services\FeedService::paginateMerged() has parameter
         $eventQuery with generic class Illuminate\Database\Eloquent\Builder
         but does not specify its types: TModel
         🪪  missingType.generics
  404    Method App\Services\FeedService::paginateMerged() has parameter
         $postQuery with generic class Illuminate\Database\Eloquent\Builder
         but does not specify its types: TModel
         🪪  missingType.generics
  426    Method App\Services\FeedService::paginateMerged() should return
         array{items: list<array<string, mixed>>, nextCursor: string|null, has
         More: bool} but returns array{items: array<int, array<string, mixed>>
         , nextCursor: string|null, hasMore: bool}.
         🪪  return.type
         💡  Offset 'items' (list<array<string, mixed>>) does not accept type a
         rray<int, array<string, mixed>>: array<int, array<string, mixed>> mig
         ht not be a list.
  433    Method App\Services\FeedService::applyCursor() has parameter
         $cursorData with no value type specified in iterable type array.
         🪪  missingType.iterableValue
         💡  See:
         https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-i
         terable-type
  433    Method App\Services\FeedService::applyCursor() has parameter $query
         with generic class Illuminate\Database\Eloquent\Builder but does not
         specify its types: TModel
         🪪  missingType.generics
  433    Method App\Services\FeedService::applyCursor() return type with
         generic class Illuminate\Database\Eloquent\Builder does not specify
         its types: TModel
         🪪  missingType.generics
  449    Method App\Services\FeedService::encodeCursor() has parameter $item
         with no value type specified in iterable type array.
         🪪  missingType.iterableValue
         💡  See:
