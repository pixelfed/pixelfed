<?php

namespace App\Services;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use GuzzleHttp\Exception\ConnectException;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToListContents;
use League\Flysystem\FileAttributes;
use League\Flysystem\UnableToWriteFile;

class FilesystemService
{
    const VERIFY_FILE_NAME = 'cfstest.txt';

    public static function getVerifyCredentials($key, $secret, $region, $bucket, $endpoint)
    {
        $client = new S3Client([
            'version' => 'latest',
            'region' => $region,
            'endpoint' => $endpoint,
            'credentials' => [
                'key' => $key,
                'secret' => $secret,
            ]
        ]);

        $adapter = new AwsS3V3Adapter(
            $client,
            $bucket,
        );

        $throw = false;
        $filesystem = new Filesystem($adapter);

        $writable = false;
        try {
            $filesystem->write(self::VERIFY_FILE_NAME, 'ok', []);
            $writable = true;
        } catch (FilesystemException | UnableToWriteFile $exception) {
            $writable = false;
        }

        if(!$writable) {
            return false;
        }

        try {
            $response = $filesystem->read(self::VERIFY_FILE_NAME);
            if($response === 'ok') {
                $writable = true;
                $res[] = self::VERIFY_FILE_NAME;
            } else {
                $writable = false;
            }
        } catch (FilesystemException | UnableToReadFile $exception) {
            $writable = false;
        }

        if(in_array(self::VERIFY_FILE_NAME, $res)) {
            try {
                $filesystem->delete(self::VERIFY_FILE_NAME);
            } catch (FilesystemException | UnableToDeleteFile $exception) {
                $writable = false;
            }
        }

        if(!$writable) {
            return false;
        }

        if(in_array(self::VERIFY_FILE_NAME, $res)) {
            return true;
        }

        return false;
    }
}
