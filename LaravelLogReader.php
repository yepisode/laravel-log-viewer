<?php

class LaravelLogReader
{
    private string $logDirectory;
    private array $validLogLevels = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

    public function __construct(string $logDirectory = '')
    {
        if (!empty($logDirectory)) {
            $this->setLogDirectory($logDirectory);
        }
    }

    public function setLogDirectory(string $path): self
    {
        $realPath = realpath($path);
        
        if ($realPath === false) {
            throw new InvalidArgumentException("디렉토리를 찾을 수 없습니다: {$path}");
        }

        if (!is_dir($realPath)) {
            throw new InvalidArgumentException("유효한 디렉토리가 아닙니다: {$path}");
        }

        if (!is_readable($realPath)) {
            throw new InvalidArgumentException("디렉토리에 읽기 권한이 없습니다: {$path}");
        }

        $this->logDirectory = $realPath;
        return $this;
    }

    public function getLogDirectory(): string
    {
        if (!isset($this->logDirectory)) {
            throw new RuntimeException("로그 디렉토리가 설정되지 않았습니다. setLogDirectory()를 먼저 호출하세요.");
        }
        return $this->logDirectory;
    }

    public function getLogFile(string $date): array
    {
        $this->validateLogDirectory();
        
        if (!$this->isValidDate($date)) {
            throw new InvalidArgumentException("유효하지 않은 날짜 형식입니다. YYYY-MM-DD 형식을 사용하세요: {$date}");
        }

        $logFileName = "laravel-{$date}.log";
        $logFilePath = $this->logDirectory . DIRECTORY_SEPARATOR . $logFileName;

        if (!file_exists($logFilePath)) {
            throw new RuntimeException("로그 파일을 찾을 수 없습니다: {$logFilePath}");
        }

        if (!is_readable($logFilePath)) {
            throw new RuntimeException("로그 파일에 읽기 권한이 없습니다: {$logFilePath}");
        }

        return $this->parseLogFile($logFilePath);
    }

    public function getTodayLog(): array
    {
        $today = date('Y-m-d');
        return $this->getLogFile($today);
    }

    public function getLatestLog(): array
    {
        $this->validateLogDirectory();
        
        $logFiles = $this->findLogFiles();
        
        if (empty($logFiles)) {
            throw new RuntimeException("로그 파일을 찾을 수 없습니다: {$this->logDirectory}");
        }

        arsort($logFiles);
        $latestFile = array_key_first($logFiles);
        
        return $this->parseLogFile($logFiles[$latestFile]);
    }

    public function getAvailableDates(): array
    {
        $this->validateLogDirectory();
        
        $logFiles = $this->findLogFiles();
        $dates = array_keys($logFiles);
        rsort($dates);
        
        return $dates;
    }

    public function hasLogForDate(string $date): bool
    {
        try {
            $this->getLogFile($date);
            return true;
        } catch (RuntimeException $e) {
            return false;
        }
    }

    private function validateLogDirectory(): void
    {
        if (!isset($this->logDirectory)) {
            throw new RuntimeException("로그 디렉토리가 설정되지 않았습니다.");
        }
    }

    private function isValidDate(string $date): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    private function findLogFiles(): array
    {
        $logFiles = [];
        $pattern = $this->logDirectory . DIRECTORY_SEPARATOR . 'laravel-*.log';
        
        foreach (glob($pattern) as $file) {
            if (preg_match('/laravel-(\d{4}-\d{2}-\d{2})\.log$/', basename($file), $matches)) {
                $logFiles[$matches[1]] = $file;
            }
        }
        
        return $logFiles;
    }

    private function parseLogFile(string $filePath): array
    {
        $content = file_get_contents($filePath);
        
        if ($content === false) {
            throw new RuntimeException("로그 파일을 읽을 수 없습니다: {$filePath}");
        }

        $logs = [];
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $parsedLog = $this->parseLogLine($line);
            if ($parsedLog !== null) {
                $logs[] = $parsedLog;
            }
        }

        return [
            'file_path' => $filePath,
            'file_size' => filesize($filePath),
            'last_modified' => filemtime($filePath),
            'log_count' => count($logs),
            'logs' => $logs
        ];
    }

    private function parseLogLine(string $line): ?array
    {
        $pattern = '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \w+\.(\w+): (.+)$/';
        
        if (preg_match($pattern, $line, $matches)) {
            return [
                'timestamp' => $matches[1],
                'level' => strtolower($matches[2]),
                'message' => $matches[3],
                'raw_line' => $line
            ];
        }

        return null;
    }

    public function getLogsByLevel(string $date, string $level): array
    {
        $level = strtolower($level);
        
        if (!in_array($level, $this->validLogLevels)) {
            throw new InvalidArgumentException("유효하지 않은 로그 레벨입니다: {$level}");
        }

        $logData = $this->getLogFile($date);
        $filteredLogs = array_filter($logData['logs'], function($log) use ($level) {
            return $log['level'] === $level;
        });

        return [
            'file_path' => $logData['file_path'],
            'file_size' => $logData['file_size'],
            'last_modified' => $logData['last_modified'],
            'total_log_count' => $logData['log_count'],
            'filtered_log_count' => count($filteredLogs),
            'level' => $level,
            'logs' => array_values($filteredLogs)
        ];
    }

    public function searchLogs(string $date, string $searchTerm): array
    {
        $logData = $this->getLogFile($date);
        $filteredLogs = array_filter($logData['logs'], function($log) use ($searchTerm) {
            return stripos($log['message'], $searchTerm) !== false;
        });

        return [
            'file_path' => $logData['file_path'],
            'file_size' => $logData['file_size'],
            'last_modified' => $logData['last_modified'],
            'total_log_count' => $logData['log_count'],
            'filtered_log_count' => count($filteredLogs),
            'search_term' => $searchTerm,
            'logs' => array_values($filteredLogs)
        ];
    }
}