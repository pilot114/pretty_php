<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PrettyPhp\System\Posix;
use PrettyPhp\System\ResourceLimit;

echo "=== POSIX Module Examples ===\n\n";

// ============================================================
// Process Information
// ============================================================
echo "--- Process Information ---\n";
echo "Process ID: " . Posix::pid() . "\n";
echo "Parent Process ID: " . Posix::process()->parentId() . "\n";
echo "Process Group ID: " . Posix::process()->groupId() . "\n";
echo "Session ID: " . Posix::process()->sessionId() . "\n";

// Get process times
$times = Posix::times();
echo "User Time: " . round($times->getUserTime(), 4) . " seconds\n";
echo "System Time: " . round($times->getSystemTime(), 4) . " seconds\n";
echo "Total Time: " . round($times->getTotalTime(), 4) . " seconds\n";
echo "\n";

// ============================================================
// User and Group Information
// ============================================================
echo "--- User Information ---\n";
echo "User ID: " . Posix::uid() . "\n";
echo "Effective User ID: " . Posix::user()->euid() . "\n";
echo "Group ID: " . Posix::gid() . "\n";
echo "Effective Group ID: " . Posix::user()->egid() . "\n";
echo "Running as root: " . (Posix::isRoot() ? 'Yes' : 'No') . "\n";

// Get current user information
$user = Posix::getCurrentUser();
echo "Login Name: " . $user->name . "\n";
echo "Home Directory: " . $user->dir . "\n";
echo "Shell: " . $user->shell . "\n";

// Get current group information
$group = Posix::getCurrentGroup();
echo "Group Name: " . $group->name . "\n";
echo "Group Members: " . implode(', ', $group->members) . "\n";

// Get supplementary groups
echo "Supplementary Groups: " . implode(', ', Posix::getGroups()->get()) . "\n";
echo "\n";

// ============================================================
// System Information
// ============================================================
echo "--- System Information ---\n";
$sysinfo = Posix::uname();
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
echo "\n";

// ============================================================
// Working Directory
// ============================================================
echo "--- Working Directory ---\n";
echo "Current Directory: " . Posix::getcwd() . "\n";
echo "\n";

// ============================================================
// File Access
// ============================================================
echo "--- File Access ---\n";
$testFile = '/etc/passwd';

if (Posix::file()->exists($testFile)) {
    echo "File exists: {$testFile}\n";
    echo "Is readable: " . (Posix::file()->isReadable($testFile) ? 'Yes' : 'No') . "\n";
    echo "Is writable: " . (Posix::file()->isWritable($testFile) ? 'Yes' : 'No') . "\n";
    echo "Is executable: " . (Posix::file()->isExecutable($testFile) ? 'Yes' : 'No') . "\n";
}
echo "\n";

// ============================================================
// Terminal
// ============================================================
echo "--- Terminal Information ---\n";
echo "Running in TTY: " . (Posix::isTTY() ? 'Yes' : 'No') . "\n";
echo "STDOUT is TTY: " . (Posix::isatty(\STDOUT) ? 'Yes' : 'No') . "\n";

try {
    $ctermid = Posix::system()->ctermid();
    echo "Controlling Terminal: " . $ctermid . "\n";
} catch (Exception $e) {
    echo "No controlling terminal\n";
}
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
    try {
        $limit = Posix::getResourceLimit($resource);
        echo "{$name}:\n";
        echo "  Soft: " . (is_int($limit->soft) ? $limit->soft : $limit->soft) . "\n";
        echo "  Hard: " . (is_int($limit->hard) ? $limit->hard : $limit->hard) . "\n";
    } catch (Exception $e) {
        echo "{$name}: Not available\n";
    }
}
echo "\n";

// ============================================================
// Error Handling
// ============================================================
echo "--- Error Handling ---\n";
echo "Last Error Code: " . Posix::getLastError() . "\n";
echo "Last Error Message: " . Posix::getLastErrorMessage() . "\n";
echo "\n";

// ============================================================
// Creating a FIFO (Named Pipe)
// ============================================================
echo "--- FIFO Example ---\n";
$fifoPath = sys_get_temp_dir() . '/test_fifo_' . getmypid();

try {
    Posix::file()->mkfifo($fifoPath, 0666);
    echo "Created FIFO at: {$fifoPath}\n";

    if (file_exists($fifoPath)) {
        echo "FIFO exists and is ready for use\n";
        unlink($fifoPath);
        echo "FIFO cleaned up\n";
    }
} catch (Exception $e) {
    echo "Failed to create FIFO: " . $e->getMessage() . "\n";
}
echo "\n";

// ============================================================
// User Lookup Examples
// ============================================================
echo "--- User Lookup ---\n";

// Get user by name (common users)
$possibleUsers = ['root', 'nobody', 'daemon', getenv('USER') ?: 'root'];

foreach ($possibleUsers as $username) {
    try {
        $user = Posix::user()->getByName($username);
        echo "User '{$username}':\n";
        echo "  UID: {$user->uid}\n";
        echo "  GID: {$user->gid}\n";
        echo "  Home: {$user->dir}\n";
        echo "  Shell: {$user->shell}\n";
        break; // Just show one example
    } catch (Exception $e) {
        continue;
    }
}
echo "\n";

echo "=== All examples completed ===\n";
