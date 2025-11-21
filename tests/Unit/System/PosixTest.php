<?php

use PrettyPhp\System\Posix;
use PrettyPhp\System\PosixFile;
use PrettyPhp\System\PosixProcess;
use PrettyPhp\System\PosixSystem;
use PrettyPhp\System\PosixUser;
use PrettyPhp\System\GroupInfo;
use PrettyPhp\System\UserInfo;
use PrettyPhp\System\SystemInfo;
use PrettyPhp\System\ResourceLimit;
use PrettyPhp\System\ProcessTimes;

describe('Posix', function (): void {
    it('can get current process id', function (): void {
        $pid = Posix::pid();
        expect($pid)->toBeInt();
        expect($pid)->toBeGreaterThan(0);
        expect($pid)->toBe(posix_getpid());
    });

    it('can get current user id', function (): void {
        $uid = Posix::uid();
        expect($uid)->toBeInt();
        expect($uid)->toBeGreaterThanOrEqual(0);
        expect($uid)->toBe(posix_getuid());
    });

    it('can get current group id', function (): void {
        $gid = Posix::gid();
        expect($gid)->toBeInt();
        expect($gid)->toBeGreaterThanOrEqual(0);
        expect($gid)->toBe(posix_getgid());
    });

    it('can check if running as root', function (): void {
        $isRoot = Posix::isRoot();
        expect($isRoot)->toBeBool();
        expect($isRoot)->toBe(posix_getuid() === 0);
    });

    it('can check if running in a terminal', function (): void {
        $isTTY = Posix::isTTY();
        expect($isTTY)->toBeBool();
    });

    it('can get current working directory', function (): void {
        $cwd = Posix::getcwd();
        expect($cwd->get())->toBe(posix_getcwd());
    });

    it('can get system information', function (): void {
        $sysinfo = Posix::uname();
        expect($sysinfo)->toBeInstanceOf(SystemInfo::class);
        expect($sysinfo->sysname)->toBeString();
        expect($sysinfo->nodename)->toBeString();
        expect($sysinfo->release)->toBeString();
        expect($sysinfo->version)->toBeString();
        expect($sysinfo->machine)->toBeString();
    });

    it('can get current user information', function (): void {
        $user = Posix::getCurrentUser();
        expect($user)->toBeInstanceOf(UserInfo::class);
        expect($user->uid)->toBe(posix_getuid());
        expect($user->name)->toBeString();
    });

    it('can get supplementary groups', function (): void {
        $groups = Posix::getGroups();
        expect($groups->get())->toBeArray();
    });

    it('can get last error', function (): void {
        $errno = Posix::getLastError();
        expect($errno)->toBeInt();
    });
});

describe('PosixProcess', function (): void {
    it('can get process id', function (): void {
        $pid = PosixProcess::id();
        expect($pid)->toBeInt();
        expect($pid)->toBeGreaterThan(0);
    });

    it('can get parent process id', function (): void {
        $ppid = PosixProcess::parentId();
        expect($ppid)->toBeInt();
        expect($ppid)->toBeGreaterThan(0);
    });

    it('can get process group id', function (): void {
        $pgid = PosixProcess::groupId();
        expect($pgid)->toBeInt();
        expect($pgid)->toBeGreaterThanOrEqual(0);
    });

    it('can get session id', function (): void {
        $sid = PosixProcess::sessionId();
        expect($sid)->toBeInt();
        expect($sid)->toBeGreaterThanOrEqual(0);
    });

    it('can get process times', function (): void {
        $times = PosixProcess::times();
        expect($times)->toBeInstanceOf(ProcessTimes::class);
        expect($times->ticks)->toBeInt();
        expect($times->utime)->toBeInt();
        expect($times->stime)->toBeInt();
    });
});

describe('PosixUser', function (): void {
    it('can get user id', function (): void {
        $uid = PosixUser::uid();
        expect($uid)->toBeInt();
        expect($uid)->toBeGreaterThanOrEqual(0);
    });

    it('can get effective user id', function (): void {
        $euid = PosixUser::euid();
        expect($euid)->toBeInt();
        expect($euid)->toBeGreaterThanOrEqual(0);
    });

    it('can get group id', function (): void {
        $gid = PosixUser::gid();
        expect($gid)->toBeInt();
        expect($gid)->toBeGreaterThanOrEqual(0);
    });

    it('can get effective group id', function (): void {
        $egid = PosixUser::egid();
        expect($egid)->toBeInt();
        expect($egid)->toBeGreaterThanOrEqual(0);
    });

    it('can get current user info', function (): void {
        $user = PosixUser::current();
        expect($user)->toBeInstanceOf(UserInfo::class);
        expect($user->uid)->toBe(posix_getuid());
        expect($user->name)->toBeString();
        expect($user->dir)->toBeString();
        expect($user->shell)->toBeString();
    });

    it('can get effective user info', function (): void {
        $user = PosixUser::effective();
        expect($user)->toBeInstanceOf(UserInfo::class);
        expect($user->uid)->toBe(posix_geteuid());
    });

    it('can get user by uid', function (): void {
        $uid = posix_getuid();
        $user = PosixUser::getByUid($uid);
        expect($user)->toBeInstanceOf(UserInfo::class);
        expect($user->uid)->toBe($uid);
    });

    it('can get current group info', function (): void {
        $group = PosixUser::currentGroup();
        expect($group)->toBeInstanceOf(GroupInfo::class);
        expect($group->gid)->toBe(posix_getgid());
        expect($group->name)->toBeString();
    });

    it('can get group by gid', function (): void {
        $gid = posix_getgid();
        $group = PosixUser::getGroupByGid($gid);
        expect($group)->toBeInstanceOf(GroupInfo::class);
        expect($group->gid)->toBe($gid);
    });

    it('can get login name', function (): void {
        try {
            $login = PosixUser::getLogin();
            expect($login)->toBeString();
        } catch (\RuntimeException $e) {
            // posix_getlogin may fail in some environments (e.g., Docker)
            expect($e->getMessage())->toContain('Failed to get login name');
        }
    })->skip(posix_getlogin() === false, 'posix_getlogin not available in this environment');
});

describe('PosixFile', function (): void {
    beforeEach(function (): void {
        $this->testDir = sys_get_temp_dir() . '/posix_tests';
        if (!is_dir($this->testDir)) {
            mkdir($this->testDir, 0755, true);
        }

        $this->testFile = $this->testDir . '/test.txt';
        file_put_contents($this->testFile, 'test content');
        chmod($this->testFile, 0644);
    });

    afterEach(function (): void {
        $files = glob($this->testDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        if (is_dir($this->testDir)) {
            rmdir($this->testDir);
        }
    });

    it('can check file access', function (): void {
        expect(PosixFile::access($this->testFile, PosixFile::F_OK))->toBeTrue();
        expect(PosixFile::access($this->testFile, PosixFile::R_OK))->toBeTrue();
    });

    it('can check if file exists', function (): void {
        expect(PosixFile::exists($this->testFile))->toBeTrue();
        expect(PosixFile::exists($this->testDir . '/nonexistent.txt'))->toBeFalse();
    });

    it('can check if file is readable', function (): void {
        expect(PosixFile::isReadable($this->testFile))->toBeTrue();
    });

    it('can check if file is writable', function (): void {
        expect(PosixFile::isWritable($this->testFile))->toBeTrue();
    });

    it('can check if fd is a tty', function (): void {
        $result = PosixFile::isatty(\STDOUT);
        expect($result)->toBeBool();
    });

    it('can create a fifo', function (): void {
        $fifoPath = $this->testDir . '/test.fifo';
        expect(PosixFile::mkfifo($fifoPath, 0666))->toBeTrue();
        expect(file_exists($fifoPath))->toBeTrue();
        unlink($fifoPath);
    });
});

describe('PosixSystem', function (): void {
    it('can get system info', function (): void {
        $info = PosixSystem::uname();
        expect($info)->toBeInstanceOf(SystemInfo::class);
        expect($info->sysname)->toBeString();
    });

    it('can get current working directory', function (): void {
        $cwd = PosixSystem::getcwd();
        expect($cwd->get())->toBe(posix_getcwd());
    });

    it('can get last error', function (): void {
        $errno = PosixSystem::getLastError();
        expect($errno)->toBeInt();
    });

    it('can get error message', function (): void {
        $message = PosixSystem::strerror(0);
        expect($message->get())->toBeString();
    });
});

describe('UserInfo', function (): void {
    it('can get current user', function (): void {
        $user = UserInfo::current();
        expect($user->uid)->toBe(posix_getuid());
        expect($user->getName()->get())->toBeString();
        expect($user->getHomeDir()->get())->toBeString();
        expect($user->getShell()->get())->toBeString();
    });

    it('can get effective user', function (): void {
        $user = UserInfo::effective();
        expect($user->uid)->toBe(posix_geteuid());
    });

    it('can get user by uid', function (): void {
        $uid = posix_getuid();
        $user = UserInfo::fromUid($uid);
        expect($user->uid)->toBe($uid);
    });
});

describe('GroupInfo', function (): void {
    it('can get current group', function (): void {
        $group = GroupInfo::current();
        expect($group->gid)->toBe(posix_getgid());
        expect($group->getName()->get())->toBeString();
        expect($group->getMembers())->toBeInstanceOf(\PrettyPhp\Base\Arr::class);
    });

    it('can get effective group', function (): void {
        $group = GroupInfo::effective();
        expect($group->gid)->toBe(posix_getegid());
    });

    it('can get group by gid', function (): void {
        $gid = posix_getgid();
        $group = GroupInfo::fromGid($gid);
        expect($group->gid)->toBe($gid);
    });
});

describe('SystemInfo', function (): void {
    it('can get system info', function (): void {
        $info = SystemInfo::get();
        expect($info->sysname)->toBeString();
        expect($info->nodename)->toBeString();
        expect($info->release)->toBeString();
        expect($info->version)->toBeString();
        expect($info->machine)->toBeString();

        expect($info->getSysname()->get())->toBe($info->sysname);
        expect($info->getNodename()->get())->toBe($info->nodename);
    });

    it('can detect operating system', function (): void {
        $info = SystemInfo::get();
        $sysname = strtolower($info->sysname);

        if ($sysname === 'linux') {
            expect($info->isLinux())->toBeTrue();
            expect($info->isBSD())->toBeFalse();
            expect($info->isMacOS())->toBeFalse();
        } elseif (str_contains($sysname, 'bsd')) {
            expect($info->isBSD())->toBeTrue();
            expect($info->isLinux())->toBeFalse();
        } elseif ($sysname === 'darwin') {
            expect($info->isMacOS())->toBeTrue();
            expect($info->isLinux())->toBeFalse();
        }
    });
});

describe('ResourceLimit', function (): void {
    it('can get resource limit', function (): void {
        $limit = ResourceLimit::get(ResourceLimit::NOFILE);
        expect($limit)->toBeInstanceOf(ResourceLimit::class);
        expect($limit->getSoftLimit())->not->toBeNull();
        expect($limit->getHardLimit())->not->toBeNull();
    });

    it('can check if unlimited', function (): void {
        $limit = ResourceLimit::get(ResourceLimit::CORE);
        expect($limit->isSoftUnlimited())->toBeBool();
        expect($limit->isHardUnlimited())->toBeBool();
    });
});

describe('ProcessTimes', function (): void {
    it('can get process times', function (): void {
        $times = ProcessTimes::get();
        expect($times->ticks)->toBeInt();
        expect($times->utime)->toBeInt();
        expect($times->stime)->toBeInt();
        expect($times->cutime)->toBeInt();
        expect($times->cstime)->toBeInt();
    });

    it('can calculate time in seconds', function (): void {
        $times = ProcessTimes::get();
        expect($times->getUserTime())->toBeFloat();
        expect($times->getSystemTime())->toBeFloat();
        expect($times->getTotalTime())->toBeFloat();
        expect($times->getTotalTime())->toBeGreaterThanOrEqual(0);
    });
});
