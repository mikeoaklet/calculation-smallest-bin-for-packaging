<?php

namespace App\Http\Controllers\V1;

use Illuminate\Support\Facades\Log;

class CalculationRequest
{
    public $items;

    protected const BINS = [
        // Small Cube.
        [
            'w' => 20, // cm
            'h' => 20, // cm
            'd' => 20, // cm
            'max_wg' => 5, // kg
            'id' => 'bin_s'
        ],

        // Tall and long.
        [
            'w' => 20,
            'h' => 40,
            'd' => 40,
            'max_wg' => 10,
            'id' => 'bin_m'
        ],

        // Flat and wide.
        [
            'w' => 40,
            'h' => 20,
            'd' => 40,
            'max_wg' => 10,
            'id' => 'bin_l'
        ]
    ];

    public function calculate() : string
    {
        $response = $this->runRequest();
        $smallestPossibleBin = collect($response['response']['bins_packed'])
            ->filter(function ($bin) {
                return !count($bin['not_packed_items']);
            })
            ->pluck('bin_data')
            ->sortByDesc('used_space') // = Effectiveness of bin. Higher is better.
            ->first();

        // There is no suitable bin for all items.
        if (!$smallestPossibleBin)
            return json_encode([
                'error' => 'No suitable bin found for this request.'
            ]);

        return json_encode([
            'bin_id' => $smallestPossibleBin['id'],
            'w' => $smallestPossibleBin['w'],
            'h' => $smallestPossibleBin['h'],
            'd' => $smallestPossibleBin['d'],
        ]);
    }

    /**
     * @see https://www.3dbinpacking.com/en/developer/sbp/api
     */
    protected function runRequest() : array
    {
        $query = json_encode([
            'bins' => self::BINS,
            'items' => $this->items,
            'username' => env('3DBIN_USERNAME'),
            'api_key' => env('3DBIN_KEY')
        ]);

        $url = "http://" . env('3DBIN_REGION') . ".api.3dbinpacking.com/packer/pack";
        $prepared_query = 'query='.$query;
        $ch = curl_init($url);
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $prepared_query );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        $resp = curl_exec($ch);
        if (curl_errno($ch)) {
            Log::error('Error #' . curl_errno($ch) . ': ' . curl_error($ch));
            return [
                'response' => [
                    'bins_packed' => []
                ]
            ];
        }

        curl_close($ch);

        return json_decode($resp,true);
    }
}
