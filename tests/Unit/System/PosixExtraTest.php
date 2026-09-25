<?php

declare(strict_types=1);

use PrettyPhp\System\GroupInfo;
use PrettyPhp\System\Posix;
use PrettyPhp\System\PosixFile;
use PrettyPhp\System\PosixProcess;
use PrettyPhp\System\PosixSystem;
use PrettyPhp\System\PosixUser;
use PrettyPhp\System\ProcessTimes;
use PrettyPhp\System\ResourceLimit;
use PrettyPhp\System\SystemInfo;
use PrettyPhp\System\UserInfo;

describe('Posix facade', function (): void {
    it('exposes module instances', function (): void {
        expect(Posix::process())->toBeInstanceOf(PosixProcess::class);
        expect(Posix::user())->toBeInstanceOf(PosixUser::class);
        expect(Posix::file())->toBeInstanceOf(PosixFile::class);
        expect(Posix::system())->toBeInstanceOf(PosixSystem::class);
    });

    it('delegates to underlying modules', function (): void {
        expect(Posix::getCurrentGroup()->gid)->toBe(posix_getgid());
        expect(Posix::kill(posix_getpid(), 0))->toBeTrue();
        expect(Posix::access(__FILE__))->toBeTrue();
        expect(Posix::getLastErrorMessage()->get())->toBeString();
        expect(Posix::times())->toBeInstanceOf(ProcessTimes::class);

        $limit = Posix::getResourceLimit(POSIX_RLIMIT_NOFILE);
        expect(Posix::setResourceLimit(POSIX_RLIMIT_NOFILE, (int) $limit->soft, (int) $limit->hard))->toBeTrue();
    });

    it('returns login name when available', function (): void {
        try {
            expect(Posix::getLogin())->toBeString();
        } catch (\RuntimeException $runtimeException) {
            expect($runtimeException->getMessage())->toBe('Failed to get login name');
        }
    });
});

describe('Posix users and groups', function (): void {
    it('looks up groups by name and gid', function (): void {
        $current = GroupInfo::current();
        expect(GroupInfo::fromName($current->name)->gid)->toBe($current->gid);
        expect(PosixUser::getGroupByName($current->name)->getName()->get())->toBe($current->name);
        expect(PosixUser::effectiveGroup()->gid)->toBe(posix_getegid());

        expect(fn (): GroupInfo => GroupInfo::fromName('no_such_group_xyz'))
            ->toThrow(\RuntimeException::class, 'Group not found: no_such_group_xyz');
        expect(fn (): GroupInfo => GroupInfo::fromGid(987_654_321))
            ->toThrow(\RuntimeException::class, 'Group with GID 987654321 not found');
    });

    it('looks up users by name and uid', function (): void {
        $current = UserInfo::current();
        $byName = PosixUser::getByName($current->name);
        expect($byName->uid)->toBe($current->uid);
        expect($byName->getGecos())->toBeInstanceOf(\PrettyPhp\Base\Str::class);

        expect(fn (): UserInfo => UserInfo::fromName('no_such_user_xyz'))
            ->toThrow(\RuntimeException::class, 'User not found: no_such_user_xyz');
        expect(fn (): UserInfo => UserInfo::fromUid(987_654_321))
            ->toThrow(\RuntimeException::class, 'User with UID 987654321 not found');
    });

    it('sets ids to current values', function (): void {
        expect(PosixUser::setUid(posix_getuid()))->toBeTrue();
        expect(PosixUser::setEuid(posix_geteuid()))->toBeTrue();
        expect(PosixUser::setGid(posix_getgid()))->toBeTrue();
        expect(PosixUser::setEgid(posix_getegid()))->toBeTrue();
    });

    it('refuses privilege changes for regular users', function (): void {
        expect(fn (): bool => @PosixUser::setUid(0))->toThrow(\RuntimeException::class, 'Failed to set UID to 0');
        expect(fn (): bool => @PosixUser::setEuid(0))->toThrow(\RuntimeException::class, 'Failed to set effective UID to 0');
        expect(fn (): bool => @PosixUser::setGid(0))->toThrow(\RuntimeException::class, 'Failed to set GID to 0');
        expect(fn (): bool => @PosixUser::setEgid(0))->toThrow(\RuntimeException::class, 'Failed to set effective GID to 0');
        expect(fn (): bool => @PosixUser::initGroups('root', 0))
            ->toThrow(\RuntimeException::class, 'Failed to initialize groups for user root');
    })->skip(fn (): bool => posix_geteuid() === 0, 'root can change ids');
});

describe('Posix processes', function (): void {
    it('handles process groups and signals', function (): void {
        expect(PosixProcess::pgrp())->toBe(posix_getpgrp());
        expect(PosixProcess::setGroupId(posix_getpid(), posix_getpgrp()))->toBeTrue();
        expect(PosixProcess::kill(posix_getpid(), 0))->toBeTrue();

        expect(fn (): int => @PosixProcess::groupId(987_654_321))
            ->toThrow(\RuntimeException::class, 'Failed to get process group ID');
        expect(fn (): int => @PosixProcess::sessionId(987_654_321))
            ->toThrow(\RuntimeException::class, 'Failed to get session ID');
        expect(fn (): bool => @PosixProcess::setGroupId(1, 1))
            ->toThrow(\RuntimeException::class, 'Failed to set process group ID');
        expect(fn (): bool => @PosixProcess::kill(987_654_321, 0))
            ->toThrow(\RuntimeException::class, 'Failed to send signal 0 to process 987654321');
    });

    it('reports process times of children', function (): void {
        $times = ProcessTimes::get();
        expect($times->getChildrenUserTime())->toBeFloat();
        expect($times->getChildrenSystemTime())->toBeFloat();
    });
});

describe('Posix files', function (): void {
    beforeEach(function (): void {
        $this->dir = sys_get_temp_dir() . '/pretty_php_posix_' . uniqid();
        mkdir($this->dir);
    });

    afterEach(function (): void {
        removeDirectory($this->dir);
    });

    it('checks effective access and executability', function (): void {
        expect(PosixFile::eaccess(__FILE__, PosixFile::R_OK))->toBeTrue();
        expect(PosixFile::isExecutable('/bin/sh'))->toBeTrue();
    });

    it('reads path configuration', function (): void {
        expect(PosixFile::pathconf($this->dir, POSIX_PC_NAME_MAX))->toBeGreaterThan(0);
        expect(PosixFile::fpathconf(STDIN, POSIX_PC_PIPE_BUF))->toBeInt();

        expect(fn (): int|false => @PosixFile::pathconf($this->dir . '/missing', POSIX_PC_NAME_MAX))
            ->toThrow(\RuntimeException::class, 'Failed to get path configuration');
        expect(fn (): int|false => @PosixFile::fpathconf(STDIN, 99999))
            ->toThrow(\RuntimeException::class, 'Failed to get file descriptor configuration');
    });

    it('creates FIFOs and nodes', function (): void {
        expect(PosixFile::mkfifo($this->dir . '/fifo'))->toBeTrue();
        expect(filetype($this->dir . '/fifo'))->toBe('fifo');
        expect(fn (): bool => @PosixFile::mkfifo($this->dir . '/fifo'))
            ->toThrow(\RuntimeException::class, 'Failed to create FIFO');

        expect(PosixFile::mknod($this->dir . '/node', POSIX_S_IFREG | 0644))->toBeTrue();
        expect(fn (): bool => @PosixFile::mknod($this->dir . '/node', POSIX_S_IFREG | 0644))
            ->toThrow(\RuntimeException::class, 'Failed to create node');
    });

    it('resolves terminal names', function (): void {
        $pty = fopen('/dev/ptmx', 'r+');
        expect(PosixFile::ttyname($pty)->get())->toBe('/dev/ptmx');
        fclose($pty);

        $file = fopen(__FILE__, 'r');
        expect(fn (): \PrettyPhp\Base\Str => @PosixFile::ttyname($file))
            ->toThrow(\RuntimeException::class, 'Failed to get terminal name');
        fclose($file);
    })->skip(fn (): bool => !is_writable('/dev/ptmx'), 'pseudo terminals are not available');
});

describe('Posix system', function (): void {
    it('reads system configuration and terminal', function (): void {
        expect(PosixSystem::sysconf(POSIX_SC_PAGESIZE))->toBeGreaterThan(0);
        expect(PosixSystem::ctermid()->get())->toBe('/dev/tty');
        expect(PosixSystem::errno())->toBeInt();
    });

    it('throws when working directory was removed', function (): void {
        $cwd = getcwd();
        $gone = sys_get_temp_dir() . '/pretty_php_gone_' . uniqid();
        mkdir($gone);
        chdir($gone);
        rmdir($gone);

        try {
            expect(fn (): \PrettyPhp\Base\Str => @PosixSystem::getcwd())
                ->toThrow(\RuntimeException::class, 'Failed to get current working directory');
        } finally {
            chdir($cwd);
        }
    });

    it('manages resource limits', function (): void {
        $limit = PosixSystem::getResourceLimit(POSIX_RLIMIT_NOFILE);
        expect(PosixSystem::setResourceLimit(POSIX_RLIMIT_NOFILE, (int) $limit->soft, (int) $limit->hard))->toBeTrue();

        expect(fn (): ResourceLimit => @ResourceLimit::get(9999))
            ->toThrow(\RuntimeException::class, 'Failed to get resource limit for resource 9999');
        expect(fn (): bool => @ResourceLimit::set(POSIX_RLIMIT_NOFILE, (int) $limit->hard + 1, (int) $limit->hard + 1))
            ->toThrow(\RuntimeException::class, 'Failed to set resource limit');
    })->skip(fn (): bool => posix_geteuid() === 0, 'root can raise hard limits');

    it('exposes uname fields', function (): void {
        $info = SystemInfo::get();
        expect($info->getRelease()->get())->toBe(php_uname('r'));
        expect($info->getVersion()->get())->toBe(php_uname('v'));
        expect($info->getMachine()->get())->toBe(php_uname('m'));
        expect($info->getDomainname())->toBeInstanceOf(\PrettyPhp\Base\Str::class);
    });
});
