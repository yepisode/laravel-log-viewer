<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'LaravelLogReader.php';

function sendResponse($success, $data = null, $message = '') {
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
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

function validatePath($path) {
    // 빈 경로 체크
    if (empty($path)) {
        return false;
    }
    
    // 절대 경로인지 확인 (Unix/Linux/macOS는 '/'로 시작, Windows는 드라이브 문자로 시작)
    if (!preg_match('/^(\/|[A-Za-z]:\\\\)/', $path)) {
        return false;
    }
    
    // 위험한 패턴들 체크 (보안상 중요한 시스템 디렉토리만 차단)
    $dangerousPatterns = [
        '../',      // 상위 디렉토리 접근
        '..\\',     // 상위 디렉토리 접근 (Windows)
        '/etc/',    // 시스템 설정 디렉토리
        '/root/',   // 루트 사용자 홈
        '/boot/',   // 부트 디렉토리
        '/sys/',    // 시스템 파일시스템
        '/proc/',   // 프로세스 정보
    ];
    
    foreach ($dangerousPatterns as $pattern) {
        if (strpos($path, $pattern) !== false) {
            return false;
        }
    }
    
    // 실제 경로 존재 여부 확인
    $realPath = realpath($path);
    if ($realPath === false || !is_dir($realPath) || !is_readable($realPath)) {
        return false;
    }
    
    return true;
}

try {
    // GET 파라미터 받기
    $action = isset($_GET['action']) ? sanitizeInput($_GET['action']) : 'search';
    $date = isset($_GET['date']) ? sanitizeInput($_GET['date']) : '';
    $level = isset($_GET['level']) ? sanitizeInput($_GET['level']) : '';
    $keyword = isset($_GET['keyword']) ? sanitizeInput($_GET['keyword']) : '';
    $path = isset($_GET['path']) ? sanitizeInput($_GET['path']) : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

    // 경로 유효성 검증 액션
    if ($action === 'validate_path') {
        if (empty($path)) {
            sendResponse(false, null, '경로를 입력해주세요.');
        }

        // 경로 보안 검증
        if (!validatePath($path)) {
            sendResponse(false, null, '유효하지 않은 경로입니다.');
        }

        try {
            $logReader = new LaravelLogReader();
            $logReader->setLogDirectory($path);
            
            // 사용 가능한 날짜 목록 가져오기
            $availableDates = $logReader->getAvailableDates();
            
            sendResponse(true, [
                'path' => $path,
                'available_dates' => $availableDates,
                'count' => count($availableDates)
            ], '경로가 유효합니다.');
            
        } catch (Exception $e) {
            sendResponse(false, null, $e->getMessage());
        }
    }
    
    // 파일 상태 확인 액션
    if ($action === 'check_file_status') {
        if (empty($path) || empty($date)) {
            sendResponse(false, null, '경로와 날짜가 필요합니다.');
        }
        
        try {
            $logReader = new LaravelLogReader();
            $logReader->setLogDirectory($path);
            
            $logFileName = "laravel-{$date}.log";
            $logFilePath = $logReader->getLogDirectory() . DIRECTORY_SEPARATOR . $logFileName;
            
            if (file_exists($logFilePath)) {
                $modifiedTime = filemtime($logFilePath);
                $fileSize = filesize($logFilePath);
                
                sendResponse(true, [
                    'exists' => true,
                    'modified_time' => $modifiedTime,
                    'modified_time_formatted' => date('Y-m-d H:i:s', $modifiedTime),
                    'file_size' => $fileSize,
                    'file_size_formatted' => formatBytes($fileSize)
                ], '파일 상태 확인 완료');
            } else {
                sendResponse(true, [
                    'exists' => false
                ], '파일이 존재하지 않습니다.');
            }
        } catch (Exception $e) {
            sendResponse(false, null, $e->getMessage());
        }
    }

    // 검색 액션 (기본)
    if (empty($path)) {
        sendResponse(false, null, '로그 디렉토리 경로가 설정되지 않았습니다.');
    }

    if (empty($date)) {
        sendResponse(false, null, '날짜를 선택해주세요.');
    }

    // 날짜 형식 검증
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        sendResponse(false, null, '올바른 날짜 형식이 아닙니다. (YYYY-MM-DD)');
    }

    // 경로 보안 검증
    if (!validatePath($path)) {
        sendResponse(false, null, '유효하지 않은 경로입니다.');
    }

    // LaravelLogReader 인스턴스 생성 및 설정
    $logReader = new LaravelLogReader();
    $logReader->setLogDirectory($path);

    // 해당 날짜의 로그 파일 존재 여부 확인
    if (!$logReader->hasLogForDate($date)) {
        sendResponse(false, null, "해당 날짜({$date})의 로그 파일이 존재하지 않습니다.");
    }

    // 로그 데이터 가져오기
    $logData = null;

    if (!empty($level) && !empty($keyword)) {
        // 레벨과 키워드 둘 다 있는 경우
        $logData = $logReader->getLogsByLevel($date, $level);
        $filteredLogs = array_filter($logData['logs'], function($log) use ($keyword) {
            return stripos($log['message'], $keyword) !== false;
        });
        $logData['logs'] = array_values($filteredLogs);
        $logData['filtered_by'] = "레벨: {$level}, 키워드: {$keyword}";
        $logData['filtered_log_count'] = count($filteredLogs);
    } elseif (!empty($level)) {
        // 레벨만 있는 경우
        $logData = $logReader->getLogsByLevel($date, $level);
        $logData['filtered_by'] = "레벨: {$level}";
    } elseif (!empty($keyword)) {
        // 키워드만 있는 경우
        $logData = $logReader->searchLogs($date, $keyword);
        $logData['filtered_by'] = "키워드: {$keyword}";
    } else {
        // 필터 없음 - 전체 로그
        $logData = $logReader->getLogFile($date);
        $logData['filtered_by'] = "전체";
        $logData['filtered_log_count'] = $logData['log_count'];
    }

    // 추가 메타 정보
    $logData['request_date'] = $date;
    $logData['request_level'] = $level;
    $logData['request_keyword'] = $keyword;
    $logData['request_page'] = $page;
    $logData['file_date'] = date('Y-m-d H:i:s', $logData['last_modified']);

    // 로그를 최신순으로 정렬 (최신 로그가 위로 오도록)
    if (isset($logData['logs']) && is_array($logData['logs'])) {
        usort($logData['logs'], function($a, $b) {
            return strcmp($b['timestamp'], $a['timestamp']);
        });
    }

    sendResponse(true, $logData, '로그 조회가 완료되었습니다.');

} catch (InvalidArgumentException $e) {
    sendResponse(false, null, '입력 오류: ' . $e->getMessage());
} catch (RuntimeException $e) {
    sendResponse(false, null, '실행 오류: ' . $e->getMessage());
} catch (Exception $e) {
    error_log('Laravel Log Viewer API Error: ' . $e->getMessage());
    sendResponse(false, null, '서버 오류가 발생했습니다. 관리자에게 문의하세요.');
}
?>