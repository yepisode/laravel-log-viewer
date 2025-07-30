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

try {
    // GET 파라미터 받기
    $date = isset($_GET['date']) ? sanitizeInput($_GET['date']) : '';
    $level = isset($_GET['level']) ? sanitizeInput($_GET['level']) : '';
    $keyword = isset($_GET['keyword']) ? sanitizeInput($_GET['keyword']) : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

    // 필수 파라미터 검증
    if (empty($date)) {
        sendResponse(false, null, '날짜를 선택해주세요.');
    }

    // 날짜 형식 검증
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        sendResponse(false, null, '올바른 날짜 형식이 아닙니다. (YYYY-MM-DD)');
    }

    // LaravelLogReader 인스턴스 생성 및 설정
    $logReader = new LaravelLogReader();
    
    // 테스트 로그 디렉토리 설정 (실제 환경에서는 적절한 경로로 변경)
    $logDirectory = __DIR__ . '/test_logs';
    $logReader->setLogDirectory($logDirectory);

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