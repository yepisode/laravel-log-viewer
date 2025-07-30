<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // nginx 버퍼링 비활성화

// 무한 실행 시간 설정
set_time_limit(0);
ob_implicit_flush(true);
ob_end_clean();

require_once 'LaravelLogReader.php';

// 클라이언트에 이벤트 전송
function sendEvent($eventType, $data) {
    echo "event: $eventType\n";
    echo "data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
    flush();
}

// 하트비트 전송 (연결 유지)
function sendHeartbeat() {
    echo ": heartbeat\n\n";
    flush();
}

// GET 파라미터 받기
$path = isset($_GET['path']) ? htmlspecialchars(trim($_GET['path']), ENT_QUOTES, 'UTF-8') : '';
$date = isset($_GET['date']) ? htmlspecialchars(trim($_GET['date']), ENT_QUOTES, 'UTF-8') : date('Y-m-d');

if (empty($path)) {
    sendEvent('error', ['message' => '로그 디렉토리 경로가 설정되지 않았습니다.']);
    exit;
}

try {
    $logReader = new LaravelLogReader();
    $logReader->setLogDirectory($path);
    
    // 초기 연결 성공 메시지
    sendEvent('connected', ['message' => '실시간 모니터링이 시작되었습니다.']);
    
    // 로그 파일 경로
    $logFileName = "laravel-{$date}.log";
    $logFilePath = $logReader->getLogDirectory() . DIRECTORY_SEPARATOR . $logFileName;
    
    // 마지막 확인한 파일 수정 시간과 크기
    $lastModifiedTime = 0;
    $lastFileSize = 0;
    $heartbeatCounter = 0;
    
    // 초기 파일 상태 설정 (첫 실행 시 현재 상태를 기준으로 함)
    if (file_exists($logFilePath)) {
        $lastModifiedTime = filemtime($logFilePath);
        $lastFileSize = filesize($logFilePath);
        error_log("SSE 시작: 초기 파일 상태 - 수정시간: $lastModifiedTime, 크기: $lastFileSize");
    }
    
    while (true) {
        // 파일이 존재하는지 확인
        if (file_exists($logFilePath)) {
            clearstatcache(true, $logFilePath); // 파일 상태 캐시 클리어
            $currentModifiedTime = filemtime($logFilePath);
            $currentFileSize = filesize($logFilePath);
            
            // 파일이 수정되었는지 확인 (수정시간 또는 파일크기 변경)
            if ($currentModifiedTime > $lastModifiedTime || $currentFileSize != $lastFileSize) {
                error_log("파일 변경 감지: 이전({$lastModifiedTime}, {$lastFileSize}) -> 현재({$currentModifiedTime}, {$currentFileSize})");
                
                sendEvent('file_changed', [
                    'date' => $date,
                    'modified_time' => $currentModifiedTime,
                    'modified_time_formatted' => date('Y-m-d H:i:s', $currentModifiedTime),
                    'file_size' => $currentFileSize,
                    'file_size_formatted' => formatBytes($currentFileSize),
                    'previous_modified_time' => $lastModifiedTime,
                    'previous_file_size' => $lastFileSize
                ]);
                
                $lastModifiedTime = $currentModifiedTime;
                $lastFileSize = $currentFileSize;
            }
        } else {
            // 파일이 없는 경우
            if ($lastModifiedTime > 0) {
                error_log("파일 없음 감지: $logFilePath");
                sendEvent('file_missing', [
                    'date' => $date,
                    'message' => '로그 파일이 삭제되었거나 이동되었습니다.'
                ]);
                $lastModifiedTime = 0;
                $lastFileSize = 0;
            }
        }
        
        // 10초마다 하트비트 전송
        $heartbeatCounter++;
        if ($heartbeatCounter >= 10) {
            sendHeartbeat();
            $heartbeatCounter = 0;
        }
        
        // 1초 대기
        sleep(1);
        
        // 연결이 끊어졌는지 확인
        if (connection_aborted()) {
            error_log("SSE 연결 종료됨");
            break;
        }
    }
    
} catch (Exception $e) {
    sendEvent('error', ['message' => $e->getMessage()]);
}

// 파일 크기 포맷 함수
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>