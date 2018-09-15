<?php

namespace MobileApi\Util\Storage;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use MobileApi\Exception\Http\HttpResponseException;
use MobileApi\Util\Config;

/**
 * Manager.php
 *
 * @author: Anson
 * @date  : 2017-04-20 15:16
 */
class Manager
{
    /**
     * @param string $bucket
     * @param string $filePath
     * @param string $fileName
     *
     * @return array
     * @throws \Exception
     * @throws \MobileApi\Exception\Http\HttpResponseException
     */
    public static function upload($bucket, $filePath, $fileName = '')
    {
        if (!file_exists($filePath) || !is_file($filePath) || !is_readable($filePath)) {
            throw new \Exception('Wrong file path.');
        }

        //s3 docs: http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#putobject
        $s3  = new S3Client([
            'version'     => Config::get('storage.version', 'latest'),
            'region'      => Config::get('storage.region', 'cn-north-1'),
            'endpoint'    => Config::get('storage.endpoint', 'http://storage.lianjia.com'),
            'credentials' => [
                'key'    => Config::get('storage.credentials.key', ''),
                'secret' => Config::get('storage.credentials.secret', ''),
            ],
        ]);
        $key = self::keyGen($filePath, $fileName);

        try {
            $ret = $s3->putObject([
                'Bucket'     => $bucket,
                'Key'        => $key,
                'SourceFile' => $filePath,
                'ACL'        => 'public-read',
            ]);

            return [
                'key'  => $key,
                'path' => $bucket . '/' . $key,
                'url'  => $ret['ObjectURL'],
            ];
        } catch (S3Exception $e) {
            throw new HttpResponseException($e->getMessage(), $e->getCode());
        }
    }

    protected static function keyGen($filePath, $fileName = '')
    {
        $explodedArray = explode('.', (string)$fileName);

        $ext = count($explodedArray) !== 0 ? '.' . end($explodedArray) : '';

        return sha1(md5($filePath, true) . microtime(true)) . $ext;
    }
}