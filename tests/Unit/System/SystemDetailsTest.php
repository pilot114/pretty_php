<?php

declare(strict_types=1);

use PrettyPhp\System\Posix;
use PrettyPhp\System\PosixFile;
use PrettyPhp\System\PosixProcess;
use PrettyPhp\System\PosixUser;
use PrettyPhp\System\ProcessTimes;
use PrettyPhp\System\ResourceLimit;
use PrettyPhp\System\SystemInfo;

mutates(Posix::class, PosixFile::class, PosixProcess::class, PosixUser::class, ProcessTimes::class, ResourceLimit::class, SystemInfo::class);

/**
 * Start a child process that sleeps and return its handle and pid
 *
 * @return array{0: resource, 1: int}
 */
function sleepingChild(): array
{
    $process = proc_open(['sleep', '30'], [], $pipes);
    return [$process, proc_get_status($process)['pid']];
}

/**
 * @param resource $process
 */
function terminationSignal($process): int
{
    for ($i = 0; $i < 200; $i++) {
        $status = proc_get_status($process);
        if (!$status['running']) {
            return $status['termsig'];
        }

        usleep(10_000);
    }

    return -1;
}

describe('System value objects', function (): void {
    it('converts clock ticks to seconds', function (): void {
        $times = new ProcessTimes(ticks: 100, utime: 250, stime: 50, cutime: 20, cstime: 10);
        expect($times->getUserTime())->toBe(2.5);
        expect($times->getSystemTime())->toBe(0.5);
        expect($times->getChildrenUserTime())->toBe(0.2);
        expect($times->getChildrenSystemTime())->toBe(0.1);
        expect($times->getTotalTime())->toBe(3.0);
    });

    it('detects operating system families case-insensitively', function (string $sysname, bool $linux, bool $bsd, bool $mac): void {
        $info = new SystemInfo($sysname, 'host', '1.0', 'v', 'x86_64');
        expect([$info->isLinux(), $info->isBSD(), $info->isMacOS()])->toBe([$linux, $bsd, $mac]);
    })->with([
        ['Linux', true, false, false],
        ['FreeBSD', false, true, false],
        ['Darwin', false, false, true],
    ]);

    it('reports unlimited resource limits', function (): void {
        $limit = new ResourceLimit(ResourceLimit::UNLIMITED, 10);
        expect($limit->isSoftUnlimited())->toBeTrue();
        expect($limit->isHardUnlimited())->toBeFalse();
        expect(new ResourceLimit(5, ResourceLimit::UNLIMITED)->isHardUnlimited())->toBeTrue();
        expect(ResourceLimit::get(POSIX_RLIMIT_NOFILE)->isSoftUnlimited())->toBeFalse();
    });
});

describe('System calls', function (): void {
    it('terminates processes with SIGTERM by default', function (Closure $kill): void {
        [$process, $pid] = sleepingChild();
        expect($kill($pid))->toBeTrue();
        expect(terminationSignal($process))->toBe(15);
        proc_close($process);
    })->with([
        'PosixProcess' => [fn (int $pid): bool => PosixProcess::kill($pid)],
        'Posix' => [fn (int $pid): bool => Posix::kill($pid)],
    ]);

    it('returns the login name or fails consistently', function (): void {
        $expected = posix_getlogin();
        if ($expected === false) {
            expect(fn (): string => PosixUser::getLogin())->toThrow(\RuntimeException::class, 'Failed to get login name');
        } else {
            expect(PosixUser::getLogin())->toBe($expected);
        }
    });

    it('names paths in file system errors', function (): void {
        $dir = sys_get_temp_dir() . '/pretty_php_posix_details_' . uniqid();
        mkdir($dir);
        try {
            expect(fn (): int|false => @PosixFile::pathconf($dir . '/missing', POSIX_PC_NAME_MAX))
                ->toThrow(\RuntimeException::class, 'Failed to get path configuration for ' . $dir . '/missing');
            PosixFile::mkfifo($dir . '/fifo');
            expect(fn (): bool => @PosixFile::mkfifo($dir . '/fifo'))
                ->toThrow(\RuntimeException::class, 'Failed to create FIFO at ' . $dir . '/fifo');
            expect(fn (): bool => @PosixFile::mknod($dir . '/fifo', POSIX_S_IFREG | 0644))
                ->toThrow(\RuntimeException::class, 'Failed to create node at ' . $dir . '/fifo');
        } finally {
            removeDirectory($dir);
        }
    });

    it('reports the resource in limit errors', function (): void {
        $limit = ResourceLimit::get(POSIX_RLIMIT_NOFILE);
        $hard = (int) $limit->hard;
        expect(fn (): bool => @ResourceLimit::set(POSIX_RLIMIT_NOFILE, $hard + 1, $hard + 1))
            ->toThrow(\RuntimeException::class, 'Failed to set resource limit for resource ' . POSIX_RLIMIT_NOFILE);
    })->skip(fn (): bool => posix_geteuid() === 0, 'root can raise hard limits');
});
