
**  ⇂ public/js/filament/tables/components/columns/select.js  ⇂ public/js/filament/tables/components/columns/text-input.js  ⇂ public/js/filament/tables/components/columns/toggle.js  ⇂ public/js/filament/widgets/components/chart.js  ⇂ public/js/filament/widgets/components/stats-overview/stat/chart.js  ⇂ public/fonts/filament/filament/inter/index.css  ⇂ public/fonts/filament/filament/inter/inter-cyrillic-ext-wght-normal-ASVAGXXE.woff2  ⇂ public/fonts/filament/filament/inter/inter-cyrillic-ext-wght-normal-IYF56FF6.woff2  ⇂ public/fonts/filament/filament/inter/inter-cyrillic-ext-wght-normal-XKHXBTUO.woff2  ⇂ public/fonts/filament/filament/inter/inter-cyrillic-wght-normal-EWLSKVKN.woff2  ⇂ public/fonts/filament/filament/inter/inter-cyrillic-wght-normal-JEOLYBOO.woff2  ⇂ public/fonts/filament/filament/inter/inter-cyrillic-wght-normal-R5CMSONN.woff2  ⇂ public/fonts/filament/filament/inter/inter-greek-ext-wght-normal-7GGTF7EK.woff2  ⇂ public/fonts/filament/filament/inter/inter-greek-ext-wght-normal-EOVOK2B5.woff2  ⇂ public/fonts/filament/filament/inter/inter-greek-ext-wght-normal-ZEVLMORV.woff2  ⇂ public/fonts/filament/filament/inter/inter-greek-wght-normal-AXVTPQD5.woff2  ⇂ public/fonts/filament/filament/inter/inter-greek-wght-normal-IRE366VL.woff2  ⇂ public/fonts/filament/filament/inter/inter-greek-wght-normal-N43DBLU2.woff2  ⇂ public/fonts/filament/filament/inter/inter-latin-ext-wght-normal-5SRY4DMZ.woff2  ⇂ public/fonts/filament/filament/inter/inter-latin-ext-wght-normal-GZCIV3NH.woff2  ⇂ public/fonts/filament/filament/inter/inter-latin-ext-wght-normal-HA22NDSG.woff2  ⇂ public/fonts/filament/filament/inter/inter-latin-wght-normal-NRMW37G5.woff2  ⇂ public/fonts/filament/filament/inter/inter-latin-wght-normal-O25CN4JL.woff2  ⇂ public/fonts/filament/filament/inter/inter-latin-wght-normal-OPIJAQLS.woff2  ⇂ public/fonts/filament/filament/inter/inter-vietnamese-wght-normal-CE5GGD3W.woff2  ⇂ public/fonts/filament/filament/inter/inter-vietnamese-wght-normal-TWG5UU7E.woff2  ⇂ public/js/filament/actions/actions.js  ⇂ public/js/filament/filament/app.js  ⇂ public/js/filament/filament/echo.js  ⇂ public/js/filament/notifications/notifications.js  ⇂ public/js/filament/schemas/schemas.js  ⇂ public/js/filament/support/support.js  ⇂ public/js/filament/tables/tables.js  ⇂ public/css/filament/filament/app.css   INFO  Successfully published assets!   INFO  Configuration cache cleared successfully.   INFO  Route cache cleared successfully.   INFO  Compiled views cleared successfully.   INFO  Successfully upgraded!100 packages you are using are looking for funding.
Use the `composer fund` command to find out more!
📦 Installing Node dependencies...
added 185 packages in 2s
32 packages are looking for funding
  run `npm fund` for details
🏗️  Building frontend assets...

> build
> vite build
> vite v7.2.2 building client environment for production...
> transforming...
> /*! 🌼 daisyUI 5.4.7 */
> Found 1 warning while optimizing generated CSS:
> **│ }
> │ @layer base {
> │   @property --radialprogress {
> ┆^-- Unknown at rule: @property
> ┆
> │     syntax: "`<percentage>`";
> │     inherits: true;
> ✓ 62 modules transformed.
> rendering chunks...
> computing gzip size...
> public/build/manifest.json              0.33 kB │ gzip:  0.17 kB
> public/build/assets/app-CnoGZv5Z.css  250.19 kB │ gzip: 34.82 kB
> public/build/assets/app-A0zrrP85.js   121.39 kB │ gzip: 40.05 kB
> ✓ built in 1.27s
> 🗄️  Running database migrations...
> INFO  Nothing to migrate.
> 🌍 Seeding location data...
> INFO  Seeding database.
> ✓ Locations already seeded (41755 records), skipping...
> 🔧 Caching configuration...
> INFO  Configuration cached successfully.
> INFO  Routes cached successfully.
> INFO  Blade templates cached successfully.
> 🔍 Creating Meilisearch indexes...
> Idle timeout reached for "http://178.156.239.246:7700/indexes/posts_index".
> Idle timeout reached for "http://178.156.239.246:7700/indexes/activities_index".
> Idle timeout reached for "http://178.156.239.246:7700/indexes/users_index".
> Idle timeout reached for "http://178.156.239.246:7700/indexes/locations_index".
> ⚙️  Syncing Meilisearch index settings...
> Idle timeout reached for "http://178.156.239.246:7700/indexes/posts_index/settings".
> 📇 Indexing search data...
> All [App\Models\Post] records have been imported.
> All [App\Models\Activity] records have been imported.
> All [App\Models\User] records have been imported.
> {"message":"Idle timeout reached for \"http://178.156.239.246:7700/indexes/locations/documents?primaryKey=id\".","context":{"exception":{"class":"Meilisearch\\Exceptions\\CommunicationException","message":"Idle timeout reached for \"http://178.156.239.246:7700/indexes/locations/documents?primaryKey=id\".","code":0,"file":"/var/www/html/vendor/meilisearch/meilisearch-php/src/Http/Client.php:175","trace":["/var/www/html/vendor/meilisearch/meilisearch-php/src/Http/Client.php:99","/var/www/html/vendor/meilisearch/meilisearch-php/src/Endpoints/Delegates/HandlesDocuments.php:43","/var/www/html/vendor/laravel/scout/src/Engines/MeilisearchEngine.php:81","/var/www/html/vendor/laravel/scout/src/Searchable.php:91","/var/www/html/vendor/laravel/scout/src/Searchable.php:71","/var/www/html/vendor/laravel/scout/src/Searchable.php:42","/var/www/html/vendor/laravel/framework/src/Illuminate/Macroable/Traits/Macroable.php:126","/var/www/html/vendor/laravel/scout/src/SearchableScope.php:38","/var/www/html/vendor/laravel/framework/src/Illuminate/Database/Concerns/BuildsQueries.php:207","/var/www/html/vendor/laravel/framework/src/Illuminate/Database/Concerns/BuildsQueries.php:133","/var/www/html/vendor/laravel/scout/src/SearchableScope.php:37","/var/www/html/vendor/laravel/framework/src/Illuminate/Database/Eloquent/Builder.php:2217","/var/www/html/vendor/laravel/scout/src/Searchable.php:175","/var/www/html/vendor/laravel/scout/src/Console/ImportCommand.php:59","/var/www/html/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php:36","/var/www/html/vendor/laravel/framework/src/Illuminate/Container/Util.php:43","/var/www/html/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php:96","/var/www/html/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php:35","/var/www/html/vendor/laravel/framework/src/Illuminate/Container/Container.php:836","/var/www/html/vendor/laravel/framework/src/Illuminate/Console/Command.php:211","/var/www/html/vendor/symfony/console/Command/Command.php:341","/var/www/html/vendor/laravel/framework/src/Illuminate/Console/Command.php:180","/var/www/html/vendor/symfony/console/Application.php:1102","/var/www/html/vendor/symfony/console/Application.php:356","/var/www/html/vendor/symfony/console/Application.php:195","/var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Console/Kernel.php:197","/var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Application.php:1235","/var/www/html/artisan:16"],"previous":{"class":"Symfony\\Component\\HttpClient\\Psr18NetworkException","message":"Idle timeout reached for \"http://178.156.239.246:7700/indexes/locations/documents?primaryKey=id\".","code":0,"file":"/var/www/html/vendor/symfony/http-client/Psr18Client.php:148","trace":["/var/www/html/vendor/meilisearch/meilisearch-php/src/Http/Client.php:173","/var/www/html/vendor/meilisearch/meilisearch-php/src/Http/Client.php:99","/var/www/html/vendor/meilisearch/meilisearch-php/src/Endpoints/Delegates/HandlesDocuments.php:43","/var/www/html/vendor/laravel/scout/src/Engines/MeilisearchEngine.php:81","/var/www/html/vendor/laravel/scout/src/Searchable.php:91","/var/www/html/vendor/laravel/scout/src/Searchable.php:71","/var/www/html/vendor/laravel/scout/src/Searchable.php:42","/var/www/html/vendor/laravel/framework/src/Illuminate/Macroable/Traits/Macroable.php:126","/var/www/html/vendor/laravel/scout/src/SearchableScope.php:38","/var/www/html/vendor/laravel/framework/src/Illuminate/Database/Concerns/BuildsQueries.php:207","/var/www/html/vendor/laravel/framework/src/Illuminate/Database/Concerns/BuildsQueries.php:133","/var/www/html/vendor/laravel/scout/src/SearchableScope.php:37","/var/www/html/vendor/laravel/framework/src/Illuminate/Database/Eloquent/Builder.php:2217","/var/www/html/vendor/laravel/scout/src/Searchable.php:175","/var/www/html/vendor/laravel/scout/src/Console/ImportCommand.php:59","/var/www/html/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php:36","/var/www/html/vendor/laravel/framework/src/Illuminate/Container/Util.php:43","/var/www/html/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php:96","/var/www/html/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php:35","/var/www/html/vendor/laravel/framework/src/Illuminate/Container/Container.php:836","/var/www/html/vendor/laravel/framework/src/Illuminate/Console/Command.php:211","/var/www/html/vendor/symfony/console/Command/Command.php:341","/var/www/html/vendor/laravel/framework/src/Illuminate/Console/Command.php:180","/var/www/html/vendor/symfony/console/Application.php:1102","/var/www/html/vendor/symfony/console/Application.php:356","/var/www/html/vendor/symfony/console/Application.php:195","/var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Console/Kernel.php:197","/var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Application.php:1235","/var/www/html/artisan:16"],"previous":{"class":"Symfony\\Component\\HttpClient\\Exception\\TimeoutException","message":"Idle timeout reached for \"http://178.156.239.246:7700/indexes/locations/documents?primaryKey=id\".","code":0,"file":"/var/www/html/vendor/symfony/http-client/Chunk/ErrorChunk.php:55","trace":["/var/www/html/vendor/symfony/http-client/Response/CommonResponseTrait.php:146","/var/www/html/vendor/symfony/http-client/Response/TransportResponseTrait.php:53","/var/www/html/vendor/symfony/http-client/Internal/HttplugWaitLoop.php:114","/var/www/html/vendor/symfony/http-client/Psr18Client.php:142","/var/www/html/vendor/meilisearch/meilisearch-php/src/Http/Client.php:173","/var/www/html/vendor/meilisearch/meilisearch-php/src/Http/Client.php:99","/var/www/html/vendor/meilisearch/meilisearch-php/src/Endpoints/Delegates/HandlesDocuments.php:43","/var/www/html/vendor/laravel/scout/src/Engines/MeilisearchEngine.php:81","/var/www/html/vendor/laravel/scout/src/Searchable.php:91","/var/www/html/vendor/laravel/scout/src/Searchable.php:71","/var/www/html/vendor/laravel/scout/src/Searchable.php:42","/var/www/html/vendor/laravel/framework/src/Illuminate/Macroable/Traits/Macroable.php:126","/var/www/html/vendor/laravel/scout/src/SearchableScope.php:38","/var/www/html/vendor/laravel/framework/src/Illuminate/Database/Concerns/BuildsQueries.php:207","/var/www/html/vendor/laravel/framework/src/Illuminate/Database/Concerns/BuildsQueries.php:133","/var/www/html/vendor/laravel/scout/src/SearchableScope.php:37","/var/www/html/vendor/laravel/framework/src/Illuminate/Database/Eloquent/Builder.php:2217","/var/www/html/vendor/laravel/scout/src/Searchable.php:175","/var/www/html/vendor/laravel/scout/src/Console/ImportCommand.php:59","/var/www/html/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php:36","/var/www/html/vendor/laravel/framework/src/Illuminate/Container/Util.php:43","/var/www/html/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php:96","/var/www/html/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php:35","/var/www/html/vendor/laravel/framework/src/Illuminate/Container/Container.php:836","/var/www/html/vendor/laravel/framework/src/Illuminate/Console/Command.php:211","/var/www/html/vendor/symfony/console/Command/Command.php:341","/var/www/html/vendor/laravel/framework/src/Illuminate/Console/Command.php:180","/var/www/html/vendor/symfony/console/Application.php:1102","/var/www/html/vendor/symfony/console/Application.php:356","/var/www/html/vendor/symfony/console/Application.php:195","/var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Console/Kernel.php:197","/var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Application.php:1235","/var/www/html/artisan:16"]}}}},"level":400,"level_name":"ERROR","channel":"production","datetime":"2026-03-29T01:59:37.606295+00:00","extra":{}}
> In Client.php line 175:

  Idle timeout reached for "http://178.156.239.246:7700/indexes/locations/doc
  uments?primaryKey=id".

In Psr18Client.php line 148:

  Idle timeout reached for "http://178.156.239.246:7700/indexes/locations/doc
  uments?primaryKey=id".

In ErrorChunk.php line 55:

  Idle timeout reached for "http://178.156.239.246:7700/indexes/locations/doc
  uments?primaryKey=id".
