<?php

/**
 * Tour of PrettyPhp\System\Posix: every one of the 41 functions of ext-posix is used below
 * (the wrapped function is named in the comment of each call).
 *
 * Calls that change the process state are made harmless: signal 0, setting the IDs and limits the
 * process already has, setsid() in a forked child only.
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PrettyPhp\System\Posix;
use PrettyPhp\System\PosixFile;
use PrettyPhp\System\ResourceLimit;

/**
 * Run an example that may fail on this system (no terminal, not root, ...) and print why it failed
 */
function attempt(string $label, callable $example): void
{
    try {
        echo $label . ': ' . $example() . "\n";
    } catch (RuntimeException $exception) {
        echo $label . ': not available (' . $exception->getMessage() . ")\n";
    }
}

echo "=== POSIX Module Examples ===\n\n";

// ============================================================
// Process Information
// ============================================================
echo "--- Process Information ---\n";
echo "Process ID: " . Posix::pid() . "\n";                                  // posix_getpid
echo "Parent Process ID: " . Posix::process()->parentId() . "\n";           // posix_getppid
echo "Process Group (getpgrp): " . Posix::process()->pgrp() . "\n";        // posix_getpgrp
echo "Process Group (getpgid): " . Posix::process()->groupId() . "\n";     // posix_getpgid
echo "Session ID: " . Posix::process()->sessionId() . "\n";                 // posix_getsid

$times = Posix::times();                                                    // posix_times
echo "User Time: " . round($times->getUserTime(), 4) . " seconds\n";
echo "System Time: " . round($times->getSystemTime(), 4) . " seconds\n";
echo "Total Time: " . round($times->getTotalTime(), 4) . " seconds\n";
echo "\n";

// ============================================================
// Process Control
// ============================================================
echo "--- Process Control ---\n";
// Signal 0 sends nothing: it only checks that the process exists and may be signalled
echo "Process is alive: " . (Posix::kill(Posix::pid(), 0) ? 'Yes' : 'No') . "\n"; // posix_kill

// Moving the process into the group it is already in changes nothing
attempt('Set process group', fn (): string => Posix::process()->setGroupId(0, Posix::process()->pgrp()) // posix_setpgid
    ? 'kept group ' . Posix::process()->pgrp()
    : 'failed');

// setsid() detaches from the terminal, so it runs in a child process
if (function_exists('pcntl_fork') && function_exists('pcntl_waitpid')) {
    $child = pcntl_fork();
    if ($child === 0) {
        attempt('New session in child', fn (): string => 'session ' . Posix::process()->setSid()); // posix_setsid
        // Skip PHP shutdown in the child: some extensions (e.g. grpc) hang in it after fork()
        Posix::kill(Posix::pid(), SIGKILL);
    }

    if ($child > 0) {
        pcntl_waitpid($child, $status);
    }
} else {
    echo "New session in child: skipped (ext-pcntl is required to fork)\n";
}
echo "\n";

// ============================================================
// User and Group Information
// ============================================================
echo "--- User Information ---\n";
echo "User ID: " . Posix::uid() . "\n";                                     // posix_getuid
echo "Effective User ID: " . Posix::user()->euid() . "\n";                  // posix_geteuid
echo "Group ID: " . Posix::gid() . "\n";                                    // posix_getgid
echo "Effective Group ID: " . Posix::user()->egid() . "\n";                 // posix_getegid
echo "Running as root: " . (Posix::isRoot() ? 'Yes' : 'No') . "\n";
attempt('Login Name', fn (): string => Posix::getLogin());                 // posix_getlogin

$user = Posix::getCurrentUser();                                            // posix_getpwuid
echo "User Name: " . $user->name . "\n";
echo "Home Directory: " . $user->dir . "\n";
echo "Shell: " . $user->shell . "\n";

$group = Posix::getCurrentGroup();                                          // posix_getgrgid
echo "Group Name: " . $group->name . "\n";
echo "Group Members: " . implode(', ', $group->members) . "\n";

$sameGroup = Posix::user()->getGroupByName($group->name);                  // posix_getgrnam
echo "Group '{$group->name}' has GID: " . $sameGroup->gid . "\n";

echo "Supplementary Groups: " . implode(', ', Posix::getGroups()->get()) . "\n"; // posix_getgroups
echo "\n";

// ============================================================
// Changing Identity
// ============================================================
echo "--- Changing Identity ---\n";
// A process may always switch to the IDs it already has, so these calls change nothing
// posix_setuid
attempt('Set UID', fn (): string => Posix::user()->setUid(Posix::uid()) ? 'kept ' . Posix::uid() : 'failed');
// posix_seteuid
attempt('Set effective UID', fn (): string => Posix::user()->setEuid(Posix::user()->euid()) ? 'kept' : 'failed');
// posix_setgid
attempt('Set GID', fn (): string => Posix::user()->setGid(Posix::gid()) ? 'kept ' . Posix::gid() : 'failed');
// posix_setegid
attempt('Set effective GID', fn (): string => Posix::user()->setEgid(Posix::user()->egid()) ? 'kept' : 'failed');
// Rebuilding the supplementary group list needs root
// posix_initgroups
attempt('Init groups', fn (): string => Posix::user()->initGroups($user->name, $user->gid) ? 'done' : 'failed');
echo "\n";

// ============================================================
// System Information
// ============================================================
echo "--- System Information ---\n";
$sysinfo = Posix::uname();                                                  // posix_uname
echo "System: " . $sysinfo->sysname . "\n";
echo "Hostname: " . $sysinfo->nodename . "\n";
echo "Release: " . $sysinfo->release . "\n";
echo "Version: " . $sysinfo->version . "\n";
echo "Machine: " . $sysinfo->machine . "\n";

if ($sysinfo->domainname !== null) {
    echo "Domain: " . $sysinfo->domainname . "\n";
}

echo "Is Linux: " . ($sysinfo->isLinux() ? 'Yes' : 'No') . "\n";
echo "Is macOS: " . ($sysinfo->isMacOS() ? 'Yes' : 'No') . "\n";
echo "Is BSD: " . ($sysinfo->isBSD() ? 'Yes' : 'No') . "\n";
echo "Page Size: " . Posix::system()->sysconf(POSIX_SC_PAGESIZE) . " bytes\n";        // posix_sysconf
echo "Online CPUs: " . Posix::system()->sysconf(POSIX_SC_NPROCESSORS_ONLN) . "\n";
echo "\n";

// ============================================================
// Working Directory
// ============================================================
echo "--- Working Directory ---\n";
echo "Current Directory: " . Posix::getcwd() . "\n";                        // posix_getcwd
echo "\n";

// ============================================================
// File Access
// ============================================================
echo "--- File Access ---\n";
$testFile = '/etc/passwd';

if (Posix::access($testFile)) {                                             // posix_access
    echo "File exists: {$testFile}\n";
    echo "Is readable: " . (Posix::file()->isReadable($testFile) ? 'Yes' : 'No') . "\n";
    echo "Is writable: " . (Posix::file()->isWritable($testFile) ? 'Yes' : 'No') . "\n";
    echo "Is executable: " . (Posix::file()->isExecutable($testFile) ? 'Yes' : 'No') . "\n";
    // eaccess() checks with the effective IDs instead of the real ones
    attempt(
        'Readable with effective IDs',
        fn (): string => Posix::file()->eaccess($testFile, PosixFile::R_OK) ? 'Yes' : 'No' // posix_eaccess
    );
}

// posix_pathconf
attempt('Max file name length in /', fn (): string => (string) Posix::file()->pathconf('/', POSIX_PC_NAME_MAX));

$handle = fopen(__FILE__, 'r');
if ($handle !== false) {
    attempt(
        'Max path length for an open file',
        fn (): string => (string) Posix::file()->fpathconf($handle, POSIX_PC_PATH_MAX) // posix_fpathconf
    );
    fclose($handle);
}
echo "\n";

// ============================================================
// Terminal
// ============================================================
echo "--- Terminal Information ---\n";
echo "Running in TTY: " . (Posix::isTTY() ? 'Yes' : 'No') . "\n";
echo "STDOUT is TTY: " . (Posix::isatty(\STDOUT) ? 'Yes' : 'No') . "\n";   // posix_isatty
attempt('Terminal of STDOUT', fn (): string => (string) Posix::file()->ttyname(\STDOUT)); // posix_ttyname
attempt('Controlling Terminal', fn (): string => (string) Posix::system()->ctermid());   // posix_ctermid
echo "\n";

// ============================================================
// Resource Limits
// ============================================================
echo "--- Resource Limits ---\n";

$limits = [
    'NOFILE' => ResourceLimit::NOFILE,
    'CORE' => ResourceLimit::CORE,
    'CPU' => ResourceLimit::CPU,
    'DATA' => ResourceLimit::DATA,
    'STACK' => ResourceLimit::STACK,
];

foreach ($limits as $name => $resource) {
    attempt($name, function () use ($resource): string {
        $limit = Posix::getResourceLimit($resource);                        // posix_getrlimit

        return "soft {$limit->soft}, hard {$limit->hard}";
    });
}

// Setting the limits the process already has changes nothing
$openFiles = Posix::getResourceLimit(ResourceLimit::NOFILE);
if (is_int($openFiles->soft) && is_int($openFiles->hard)) {
    attempt(
        'Set NOFILE',
        // posix_setrlimit
        fn (): string => Posix::setResourceLimit(ResourceLimit::NOFILE, $openFiles->soft, $openFiles->hard)
            ? 'kept'
            : 'failed'
    );
}
echo "\n";

// ============================================================
// Error Handling
// ============================================================
echo "--- Error Handling ---\n";
Posix::access('/nonexistent/pretty_php');                                   // fails with ENOENT
echo "Last Error Code: " . Posix::getLastError() . "\n";                    // posix_get_last_error
echo "Last Error Code (errno): " . Posix::system()->errno() . "\n";         // posix_errno
echo "Last Error Message: " . Posix::getLastErrorMessage() . "\n";
echo "Message for EACCES (13): " . Posix::system()->strerror(13) . "\n";   // posix_strerror
echo "\n";

// ============================================================
// Creating Special Files
// ============================================================
echo "--- FIFO Example ---\n";
$fifoPath = sys_get_temp_dir() . '/test_fifo_' . getmypid();

attempt('Create FIFO with mkfifo', function () use ($fifoPath): string {
    Posix::file()->mkfifo($fifoPath, 0666);                                 // posix_mkfifo
    $exists = file_exists($fifoPath);
    unlink($fifoPath);

    return $exists ? "created and removed {$fifoPath}" : 'not created';
});

// mknod() can create the same FIFO; device nodes need root
attempt('Create FIFO with mknod', function () use ($fifoPath): string {
    Posix::file()->mknod($fifoPath, PosixFile::S_IFIFO | 0666);             // posix_mknod
    $exists = file_exists($fifoPath);
    unlink($fifoPath);

    return $exists ? "created and removed {$fifoPath}" : 'not created';
});
echo "\n";

// ============================================================
// User Lookup Examples
// ============================================================
echo "--- User Lookup ---\n";

$username = getenv('USER') ?: $user->name;
attempt("User '{$username}'", function () use ($username): string {
    $found = Posix::user()->getByName($username);                           // posix_getpwnam

    return "UID {$found->uid}, GID {$found->gid}, home {$found->dir}, shell {$found->shell}";
});
echo "\n";

echo "=== All examples completed ===\n";
