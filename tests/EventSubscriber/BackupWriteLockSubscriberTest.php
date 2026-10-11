<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\BackupWriteLockSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class BackupWriteLockSubscriberTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/dogs-diary-lock-'.bin2hex(random_bytes(8));
        mkdir($this->directory, 0700);
    }

    protected function tearDown(): void
    {
        if (is_file($this->directory.'/writes.lock')) {
            unlink($this->directory.'/writes.lock');
        }
        rmdir($this->directory);
    }

    public function testBackupBlocksMutationsWithRetryableApiErrorButAllowsReads(): void
    {
        $backup = fopen($this->directory.'/writes.lock', 'c');
        self::assertTrue(flock($backup, LOCK_EX | LOCK_NB));
        $subscriber = new BackupWriteLockSubscriber($this->directory);
        $write = $this->event('/api/dogs', 'POST');
        $subscriber->onRequest($write);
        self::assertSame(503, $write->getResponse()?->getStatusCode());
        self::assertSame('30', $write->getResponse()?->headers->get('Retry-After'));
        self::assertSame('temporarily_unavailable', json_decode($write->getResponse()->getContent(), true)['error']['code']);

        foreach (['/api/dogs', '/healthz'] as $path) {
            $read = $this->event($path, 'GET');
            $subscriber->onRequest($read);
            self::assertFalse($read->hasResponse());
        }
        fclose($backup);

        $retry = $this->event('/api/dogs', 'POST');
        $subscriber->onRequest($retry);
        self::assertFalse($retry->hasResponse());
        unset($subscriber);
    }

    public function testActiveWritesPreventBackupUntilMainRequestFinishes(): void
    {
        $subscriber = new BackupWriteLockSubscriber($this->directory);
        $request = $this->event('/api/dogs/1', 'DELETE');
        $subscriber->onRequest($request);
        self::assertFalse($request->hasResponse());
        $backup = fopen($this->directory.'/writes.lock', 'c');
        self::assertFalse(flock($backup, LOCK_EX | LOCK_NB));

        $subscriber->onFinishRequest(new FinishRequestEvent(
            $request->getKernel(), $request->getRequest(), HttpKernelInterface::SUB_REQUEST,
        ));
        self::assertFalse(flock($backup, LOCK_EX | LOCK_NB));

        $subscriber->onFinishRequest(new FinishRequestEvent(
            $request->getKernel(), $request->getRequest(), HttpKernelInterface::MAIN_REQUEST,
        ));
        self::assertTrue(flock($backup, LOCK_EX | LOCK_NB));
        fclose($backup);
    }

    public function testConcurrentWritesShareLockAndReleaseEvenIfSubscriberIsDestroyed(): void
    {
        $first = new BackupWriteLockSubscriber($this->directory);
        $second = new BackupWriteLockSubscriber($this->directory);
        foreach ([$first, $second] as $subscriber) {
            $event = $this->event('/api/dogs', 'POST');
            $subscriber->onRequest($event);
            self::assertFalse($event->hasResponse());
        }
        unset($subscriber);
        $backup = fopen($this->directory.'/writes.lock', 'c');
        unset($first);
        self::assertFalse(flock($backup, LOCK_EX | LOCK_NB));
        unset($second);
        self::assertTrue(flock($backup, LOCK_EX | LOCK_NB));
        fclose($backup);
    }

    private function event(string $path, string $method): RequestEvent
    {
        return new RequestEvent(
            $this->createStub(HttpKernelInterface::class), Request::create($path, $method), HttpKernelInterface::MAIN_REQUEST,
        );
    }
}
