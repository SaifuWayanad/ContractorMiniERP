<?php

namespace App\Services;

use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Indian states and their districts, loaded from resources/data/india-states-districts.json.
 * Edit that file to add or rename districts.
 */
#[Singleton]
class LocationService
{
    /**
     * @var array<string, list<string>>
     */
    private array $districtsByState;

    public function __construct()
    {
        $path = resource_path('data/india-states-districts.json');
        $data = json_decode(File::get($path), true);

        if (! is_array($data)) {
            throw new RuntimeException("Could not read {$path}");
        }

        ksort($data, SORT_NATURAL | SORT_FLAG_CASE);
        $this->districtsByState = array_map(function (array $districts): array {
            sort($districts, SORT_NATURAL | SORT_FLAG_CASE);

            return $districts;
        }, $data);
    }

    /**
     * @return list<string>
     */
    public function states(): array
    {
        return array_keys($this->districtsByState);
    }

    /**
     * @return list<string>
     */
    public function districts(?string $state): array
    {
        return $this->districtsByState[$state] ?? [];
    }

    /**
     * @return array<string, list<string>>
     */
    public function districtsByState(): array
    {
        return $this->districtsByState;
    }

    public function isValidDistrict(string $state, string $district): bool
    {
        return in_array($district, $this->districts($state), true);
    }
}
