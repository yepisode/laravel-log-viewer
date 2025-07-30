<?php

require_once 'LaravelLogReader.php';

echo "=== Laravel 로그 리더 테스트 ===\n\n";

try {
    $logReader = new LaravelLogReader();
    
    $testLogDirectory = __DIR__ . '/test_logs';
    echo "테스트 로그 디렉토리: {$testLogDirectory}\n";
    
    $logReader->setLogDirectory($testLogDirectory);
    echo "✓ 로그 디렉토리 설정 완료\n\n";
    
    echo "1. 사용 가능한 로그 날짜:\n";
    $availableDates = $logReader->getAvailableDates();
    foreach ($availableDates as $date) {
        echo "   - {$date}\n";
    }
    echo "\n";
    
    if (!empty($availableDates)) {
        $testDate = '2025-07-30';
        
        echo "2. {$testDate} 로그 파일 읽기:\n";
        $logData = $logReader->getLogFile($testDate);
        echo "   파일 경로: {$logData['file_path']}\n";
        echo "   파일 크기: " . number_format($logData['file_size']) . " bytes\n";
        echo "   로그 수: {$logData['log_count']}\n\n";
        
        echo "3. 모든 로그 엔트리:\n";
        foreach ($logData['logs'] as $index => $log) {
            echo "   " . ($index + 1) . ". [{$log['timestamp']}] {$log['level']}: " . substr($log['message'], 0, 60) . "...\n";
        }
        echo "\n";
        
        echo "4. ERROR 레벨 로그 필터링:\n";
        $errorLogs = $logReader->getLogsByLevel($testDate, 'error');
        echo "   전체 로그: {$errorLogs['total_log_count']}, ERROR 로그: {$errorLogs['filtered_log_count']}\n";
        foreach ($errorLogs['logs'] as $index => $log) {
            echo "   " . ($index + 1) . ". [{$log['timestamp']}] " . substr($log['message'], 0, 80) . "...\n";
        }
        echo "\n";
        
        echo "5. 'connection' 키워드 검색:\n";
        $searchResults = $logReader->searchLogs($testDate, 'connection');
        echo "   검색 결과: {$searchResults['filtered_log_count']}개\n";
        foreach ($searchResults['logs'] as $index => $log) {
            echo "   " . ($index + 1) . ". [{$log['timestamp']}] " . substr($log['message'], 0, 80) . "...\n";
        }
        echo "\n";
        
        echo "6. 최신 로그 파일 조회:\n";
        $latestLog = $logReader->getLatestLog();
        echo "   최신 파일: " . basename($latestLog['file_path']) . "\n";
        echo "   로그 수: {$latestLog['log_count']}\n\n";
        
        echo "7. 특정 날짜 로그 존재 확인:\n";
        echo "   2025-07-30: " . ($logReader->hasLogForDate('2025-07-30') ? '존재' : '없음') . "\n";
        echo "   2025-07-29: " . ($logReader->hasLogForDate('2025-07-29') ? '존재' : '없음') . "\n";
        echo "   2025-07-28: " . ($logReader->hasLogForDate('2025-07-28') ? '존재' : '없음') . "\n\n";
    }
    
    echo "8. 체이닝 방식 테스트:\n";
    $chainResult = (new LaravelLogReader())
        ->setLogDirectory($testLogDirectory)
        ->getLogsByLevel('2025-07-29', 'info');
    echo "   INFO 로그 수: {$chainResult['filtered_log_count']}\n";
    
    echo "\n✅ 모든 테스트 완료!\n";
    
} catch (InvalidArgumentException $e) {
    echo "❌ 입력 오류: " . $e->getMessage() . "\n";
} catch (RuntimeException $e) {
    echo "❌ 실행 오류: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ 예상치 못한 오류: " . $e->getMessage() . "\n";
}