<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

try {
    $factory = new App\Infrastructure\Media\MediaStorageFactory(
        new App\Infrastructure\Media\LocalMediaStorage(sys_get_temp_dir()),
        getenv('MEDIA_STORAGE') ?: '', getenv('MEDIA_R2_ENDPOINT') ?: '',
        getenv('MEDIA_R2_REGION') ?: 'auto', getenv('MEDIA_R2_BUCKET') ?: '',
        getenv('MEDIA_R2_ACCESS_KEY_ID') ?: '', getenv('MEDIA_R2_SECRET_ACCESS_KEY') ?: '',
    );
    $check = new App\Infrastructure\Media\R2StorageCheck(
        $factory->createS3Client(),
        new GuzzleHttp\Client(['connect_timeout' => 10, 'timeout' => 30]),
        getenv('MEDIA_R2_BUCKET') ?: '',
    );
    echo json_encode(['r2Check' => 'passed', 'checks' => $check->run()], JSON_THROW_ON_ERROR).PHP_EOL;
} catch (Throwable $exception) {
    $result = ['r2Check' => 'failed', 'message' => 'R2 synthetic verification failed; no credentials or signed URLs are displayed.'];
    if ($exception instanceof Aws\Exception\AwsException) {
        $result['httpStatus'] = $exception->getStatusCode();
    }
    fwrite(STDERR, json_encode($result, JSON_THROW_ON_ERROR).PHP_EOL);
    exit(1);
}
