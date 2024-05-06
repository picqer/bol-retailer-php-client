<?php

namespace Jobjen\BolRetailerV10\OpenApi;

class SpecsDownloader
{
    private const SPECS = [
        [
            'source' => 'https://api.bol.com/retailer/public/apispec/Retailer%20API%20-%20v10',
            'target' => 'retailer.json',
        ],
        [
            'source' => 'https://api.bol.com/retailer/public/apispec/Shared%20API%20-%20v10',
            'target' => 'shared.json',
        ],
    ];

    public static function run(): void
    {
        foreach (static::SPECS as $spec) {
            $sourceFile = file_get_contents($spec['source']);

            // Tidy JSON formatting
            $sourceTidied = json_encode(json_decode($sourceFile), JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);

            file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . $spec['target'], $sourceTidied);
        }
    }
}
