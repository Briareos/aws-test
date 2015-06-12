<?php
require 'vendor/autoload.php';

use Aws\Sdk;
use GuzzleHttp\Promise;

$sdk      = new Sdk([
    'region' => 'us-west-2',
    'version' => 'latest',
]);
$s3Client = $sdk->createS3();
$bucket   = 'mwp-test';

@mkdir(__DIR__.'/out');

$promiseGenerator = function () use ($s3Client, $bucket) {
    $mb1   = '1mb.test';
    $mb10  = '10mb.test';
    $mb100 = '100mb.test';

    $download = [$mb100, $mb10, $mb1, $mb100, $mb10, $mb1, $mb100, $mb10, $mb10, $mb1, $mb10];

    foreach ($download as $i => $name) {
        yield $s3Client->getObjectAsync([
            'Key'    => $name,
            'Bucket' => $bucket,
            '@http'  => [
                'sink' => sprintf('%s/out/%s.test', __DIR__, $i),
            ],
        ]);
    }
};

$fulfilled = function ($result) {
    echo 'Got result: '.var_export($result->toArray(), true)."\n\n";
};

$rejected = function ($reason) {
    echo 'Rejected: '.$reason."\n\n";
};

$promises = $promiseGenerator();
$each = Promise\each_limit($promises, 2, $fulfilled, $rejected);
$each->wait();
