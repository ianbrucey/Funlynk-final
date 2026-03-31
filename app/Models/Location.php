<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Location extends Model
{

    use Searchable;

    public $timestamps = false;

    protected $fillable = [
        'city',
        'state_code',
        'state',
        'county',
        'latitude',
        'longitude',
        'zip',
        'timezone',
    ];

    public static function defaultLocationId () {
            return 30301;
    }

    public function searchableChunkSize(): int
    {
        return 100;
    }


    /**
     * Get the indexable data array for the model.
     * .
     * @return array
     */
    public function toSearchableArray()
    {
        $array = $this->toArray();

        $array['_geo'] = [
            'lat'=> floatval($this->latitude) ?? 0.0,
            'lng'=> floatval($this->longitude) ?? 0.0
        ];

        return $array;
    }

    public static function findLocation(array $record) {
        $zip = $record['zip'];
        $city = $record['city'];
        $state = $record['state'];

        $location = self::where("zip", $zip)->first();

        if(!$location) {
            $location = self::where("city", $city)->where("state_code", $state)->first();
        }

        return $location;
    }

    /**
     * Specify the index name used for the model.
     * Add prefix for non-production environments.
     * NOTE: I think this is handled in the config/scout.php file
     */
//    public function searchableAs(): string
//    {
//        $prefix = config('app.index_prefix');
//        return $prefix . 'cities';
//    }

}
