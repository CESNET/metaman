<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class QueueService
{
    public static function jobExists(string $jobClass, $identifier): bool
    {
        $connection = config('queue.default');

        switch ($connection) {
            case 'database':
                return self::checkDatabaseQueue($jobClass, $identifier);
            case 'redis':
                return self::checkRedisQueue($jobClass, $identifier);
            default:
                return false;
        }
    }

    private static function checkDatabaseQueue(string $jobClass, $identifier): bool
    {
        return DB::table('jobs')
            ->where('payload', 'like', '%'.$jobClass.'%')
            ->where('payload', 'like', '%'.$identifier.'%')
            ->exists();
    }

    private static function checkRedisQueue(string $jobClass, $identifier): bool
    {
        $queue = config('queue.connections.redis.queue', 'default');
        $jobs = Redis::lrange('queues:'.$queue, 0, -1);

        foreach ($jobs as $job) {
            if (str_contains($job, $jobClass) && str_contains($job, (string) $identifier)) {
                return true;
            }
        }

        return false;
    }
}
