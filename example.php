<?php

require_once 'LaravelLogReader.php';

try {
    $logReader = new LaravelLogReader();
    
    $logDirectory = '/path/to/your/laravel/storage/logs';
    $logReader->setLogDirectory($logDirectory);
    
    echo "=== Laravel 로그 리더 사용 예제 ===\n\n";
    
    echo "1. 사용 가능한 로그 날짜 목록:\n";
    $availableDates = $logReader->getAvailableDates();
    foreach ($availableDates as $date) {
        echo "   - {$date}\n";
    }
    echo "\n";
    
    if (!empty($availableDates)) {
        $latestDate = $availableDates[0];
        
        echo "2. 최신 로그 파일 ({$latestDate}) 읽기:\n";
        $latestLog = $logReader->getLogFile($latestDate);
        echo "   파일 경로: {$latestLog['file_path']}\n";
        echo "   파일 크기: " . number_format($latestLog['file_size']) . " bytes\n";
        echo "   마지막 수정: " . date('Y-m-d H:i:s', $latestLog['last_modified']) . "\n";
        echo "   총 로그 수: {$latestLog['log_count']}\n\n";
        
        if (!empty($latestLog['logs'])) {
            echo "3. 최근 5개 로그 엔트리:\n";
            $recentLogs = array_slice($latestLog['logs'], -5);
            foreach ($recentLogs as $log) {
                echo "   [{$log['timestamp']}] {$log['level']}: " . substr($log['message'], 0, 100) . "...\n";
            }
            echo "\n";
            
            echo "4. ERROR 레벨 로그만 필터링:\n";
            $errorLogs = $logReader->getLogsByLevel($latestDate, 'error');
            echo "   ERROR 로그 수: {$errorLogs['filtered_log_count']}\n";
            if (!empty($errorLogs['logs'])) {
                foreach (array_slice($errorLogs['logs'], 0, 3) as $log) {
                    echo "   [{$log['timestamp']}] " . substr($log['message'], 0, 80) . "...\n";
                }
            }
            echo "\n";
            
            echo "5. 특정 키워드로 로그 검색 (예: 'exception'):\n";
            $searchResults = $logReader->searchLogs($latestDate, 'exception');
            echo "   검색 결과 수: {$searchResults['filtered_log_count']}\n";
            if (!empty($searchResults['logs'])) {
                foreach (array_slice($searchResults['logs'], 0, 2) as $log) {
                    echo "   [{$log['timestamp']}] " . substr($log['message'], 0, 80) . "...\n";
                }
            }
            echo "\n";
        }
    }
    
    echo "6. 오늘 날짜의 로그 파일 확인:\n";
    $today = date('Y-m-d');
    if ($logReader->hasLogForDate($today)) {
        echo "   오늘({$today}) 로그 파일이 존재합니다.\n";
        $todayLog = $logReader->getTodayLog();
        echo "   오늘 로그 수: {$todayLog['log_count']}\n";
    } else {
        echo "   오늘({$today})의 로그 파일이 없습니다.\n";
    }
    
} catch (InvalidArgumentException $e) {
    echo "입력 오류: " . $e->getMessage() . "\n";
} catch (RuntimeException $e) {
    echo "실행 오류: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "예상치 못한 오류: " . $e->getMessage() . "\n";
}

echo "\n=== 체이닝 방식 사용 예제 ===\n";

try {
    $result = (new LaravelLogReader())
        ->setLogDirectory('/path/to/your/laravel/storage/logs')
        ->getLogsByLevel('2025-07-30', 'error');
    
    echo "체이닝으로 ERROR 로그 조회 완료\n";
    echo "ERROR 로그 수: {$result['filtered_log_count']}\n";
    
} catch (Exception $e) {
    echo "체이닝 예제 오류: " . $e->getMessage() . "\n";
}

echo "\n=== 실제 사용법 ===\n";
echo "// 기본 사용법\n";
echo "\$logReader = new LaravelLogReader();\n";
echo "\$logReader->setLogDirectory('/absolute/path/to/logs');\n";
echo "\$logs = \$logReader->getLogFile('2025-07-30');\n\n";

echo "// 체이닝 사용법\n";
echo "\$logs = (new LaravelLogReader())\n";
echo "    ->setLogDirectory('/absolute/path/to/logs')\n";
echo "    ->getLogFile('2025-07-30');\n\n";

echo "// 특정 레벨 로그 조회\n";
echo "\$errorLogs = \$logReader->getLogsByLevel('2025-07-30', 'error');\n\n";

echo "// 키워드 검색\n";
echo "\$searchResults = \$logReader->searchLogs('2025-07-30', 'database');\n\n";

echo "// 오늘 로그 조회\n";
echo "\$todayLogs = \$logReader->getTodayLog();\n\n";

echo "// 최신 로그 조회\n";
echo "\$latestLogs = \$logReader->getLatestLog();\n";