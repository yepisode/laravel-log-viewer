# Laravel Log Viewer

Laravel 애플리케이션의 로그 파일을 웹 브라우저에서 쉽게 검색하고 분석할 수 있는 도구입니다.

## 주요 기능

- **로그 파일 검색**: 날짜별 Laravel 로그 파일 조회
- **레벨별 필터링**: Emergency, Alert, Critical, Error, Warning, Notice, Info, Debug 레벨별 필터링
- **키워드 검색**: 로그 메시지에서 특정 키워드 검색
- **실시간 모니터링**: 로그 파일 변경 시 자동 갱신
- **페이지네이션**: 대용량 로그 파일 효율적 탐색
- **복사 기능**: 로그 메시지 원클릭 복사
- **반응형 UI**: 모바일 및 데스크톱 환경 지원

## 파일 구조

```
laravel-logger/
├── index.php              # 메인 웹 인터페이스
├── LaravelLogReader.php    # 로그 파일 파싱 클래스
├── api.php                # REST API 엔드포인트
├── sse.php                # 실시간 갱신용 Server-Sent Events
├── example.php            # 사용 예제
├── test.php               # 테스트 파일
└── test_logs/             # 테스트용 로그 파일
    ├── laravel-2025-07-29.log
    └── laravel-2025-07-30.log
```

## 설치 및 설정

1. 프로젝트 파일을 웹 서버의 적절한 디렉터리에 복사
2. PHP 7.4 이상 환경에서 실행
3. 웹 브라우저에서 `index.php` 접속

## 사용 방법

### 기본 설정

1. 웹 인터페이스에 접속
2. "로그 디렉터리 경로" 필드에 Laravel 로그 디렉터리의 절대 경로 입력
   - 예: `/absolute/path/to/laravel/storage/logs`
3. "경로 설정" 버튼 클릭

### 로그 검색

1. 날짜 선택 (직접 입력 또는 빠른 날짜 버튼 사용)
2. 필요시 로그 레벨 선택
3. 필요시 키워드 입력
4. "검색" 버튼 클릭

### 실시간 모니터링

1. 로그를 한 번 검색한 후
2. "실시간 갱신" 체크박스 활성화
3. 로그 파일이 변경되면 자동으로 화면 갱신

## API 사용법

### 로그 검색
```
GET api.php?date=2025-07-30&level=error&keyword=exception&path=/path/to/logs
```

### 경로 유효성 검증
```
GET api.php?action=validate_path&path=/path/to/logs
```

### 파일 상태 확인
```
GET api.php?action=check_file_status&path=/path/to/logs&date=2025-07-30
```

## LaravelLogReader 클래스 사용법

```php
<?php
require_once 'LaravelLogReader.php';

// 인스턴스 생성 및 로그 디렉터리 설정
$reader = new LaravelLogReader();
$reader->setLogDirectory('/path/to/laravel/storage/logs');

// 특정 날짜의 로그 조회
$logs = $reader->getLogFile('2025-07-30');

// 레벨별 로그 조회
$errorLogs = $reader->getLogsByLevel('2025-07-30', 'error');

// 키워드 검색
$searchResults = $reader->searchLogs('2025-07-30', 'exception');

// 사용 가능한 날짜 목록
$availableDates = $reader->getAvailableDates();
?>
```

## 응답 형식

모든 API 응답은 다음 형식을 따릅니다:

```json
{
    "success": true,
    "data": {
        "file_path": "/path/to/log/file",
        "file_size": 1024,
        "last_modified": 1722322800,
        "log_count": 100,
        "logs": [
            {
                "timestamp": "2025-07-30 10:30:45",
                "level": "error",
                "message": "로그 메시지 내용",
                "raw_line": "전체 로그 라인"
            }
        ]
    },
    "message": "로그 조회가 완료되었습니다."
}
```

## 보안 고려사항

- 절대 경로만 허용
- 시스템 디렉터리 접근 차단
- Path traversal 공격 방지
- 입력값 sanitization 적용

## 요구사항

- PHP 7.4+
- 웹 서버 (Apache, Nginx 등)
- Laravel 로그 파일 읽기 권한

## 브라우저 지원

- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+

## 라이선스

MIT License