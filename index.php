<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel 로그 뷰어</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .search-panel {
            background: white;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        .search-panel-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 25px;
            cursor: pointer;
            user-select: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .search-panel-header:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
        }
        
        .search-panel-header h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .accordion-icon {
            transition: transform 0.3s ease;
            font-size: 1.2rem;
        }
        
        .accordion-icon.collapsed {
            transform: rotate(-90deg);
        }
        
        .search-panel-content {
            padding: 25px;
            transition: all 0.3s ease;
            max-height: 1000px;
            overflow: hidden;
        }
        
        .search-panel-content.collapsed {
            max-height: 0;
            padding-top: 0;
            padding-bottom: 0;
        }

        .search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }

        .form-group input,
        .form-group select {
            padding: 10px;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .quick-dates {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .quick-date-btn {
            padding: 8px 16px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 20px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }

        .quick-date-btn:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .search-btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.2s;
        }

        .search-btn:hover {
            transform: translateY(-2px);
        }

        .results-panel {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .results-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .results-count {
            font-weight: 600;
            color: #555;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .log-table {
            width: 100%;
            border-collapse: collapse;
        }

        .log-table th,
        .log-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .log-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
        }

        .log-table tr:hover {
            background: #f8f9fa;
        }
        
        .log-row {
            position: relative;
        }
        
        .log-row:hover .copy-btn-inline {
            opacity: 1;
        }

        .log-level {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .log-level.error { background: #fee; color: #c53030; }
        .log-level.warning { background: #fff3cd; color: #856404; }
        .log-level.info { background: #e7f3ff; color: #0056b3; }
        .log-level.debug { background: #f8f9fa; color: #6c757d; }
        .log-level.critical { background: #f5c6cb; color: #721c24; }

        .log-message {
            max-width: 800px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .timestamp {
            font-family: 'Courier New', monospace;
            font-size: 13px;
            min-width: 150px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            gap: 10px;
        }

        .pagination button {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .pagination button:hover:not(:disabled) {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination .current-page {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .modal-header h3 {
            margin: 0;
            color: #333;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }

        .close:hover {
            color: #000;
        }

        .copy-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
            transition: all 0.3s;
        }

        .copy-btn:hover {
            background: #5a67d8;
            transform: translateY(-1px);
        }

        .copy-btn:active {
            transform: translateY(0);
        }
        
        .copy-btn-inline {
            background: #667eea;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            opacity: 0.7;
            transition: all 0.2s ease;
            white-space: nowrap;
            min-width: 60px;
        }
        
        .copy-btn-inline:hover {
            background: #5a67d8;
            transform: translateY(-1px);
        }
        
        .copy-btn-inline:active {
            transform: translateY(0);
        }
        
        .copy-btn-inline.copied {
            background: #28a745;
        }

        .copy-success {
            color: #28a745;
            font-size: 14px;
            margin-left: 10px;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .copy-success.show {
            opacity: 1;
        }

        .log-detail {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            white-space: pre-wrap;
            word-break: break-all;
            font-size: 14px;
        }

        .log-meta {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 10px 20px;
            margin-bottom: 20px;
        }

        .log-meta dt {
            font-weight: 600;
            color: #555;
        }

        .log-meta dd {
            margin: 0;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        @keyframes pulse {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
            }
            
            70% {
                transform: scale(1);
                box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
            }
            
            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
            }
        }
        
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .search-form {
                grid-template-columns: 1fr;
            }
            
            .log-table th,
            .log-table td {
                padding: 8px 5px;
                font-size: 12px;
            }
            
            .log-message {
                max-width: 150px;
            }
            
            .copy-btn-inline {
                padding: 4px 8px;
                font-size: 10px;
                min-width: 45px;
            }
            
            .timestamp {
                font-size: 11px;
                min-width: 120px;
            }
            
            .modal-content {
                margin: 10% auto;
                padding: 20px;
                width: 95%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Laravel 로그 뷰어</h1>
            <p>Laravel 애플리케이션의 로그를 쉽게 검색하고 분석하세요</p>
        </div>

        <div class="search-panel">
            <div class="search-panel-header" onclick="toggleSearchPanel()">
                <h3>검색 설정</h3>
                <span class="accordion-icon">▼</span>
            </div>
            
            <div class="search-panel-content" id="search-panel-content">
                <div class="path-setting" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #667eea;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="log_directory">로그 디렉토리 경로</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" id="log_directory" name="log_directory" 
                                   placeholder="/absolute/path/to/laravel/storage/logs" 
                                   style="flex: 1;"
                                   value="/Users/mellow/Projects/laravel-logger/test_logs">
                            <button type="button" onclick="setLogDirectory()" class="search-btn" style="padding: 10px 20px; font-size: 14px;">경로 설정</button>
                        </div>
                        <small style="color: #6c757d; margin-top: 5px; display: block;">Laravel 로그 파일들이 저장된 절대 경로를 입력하세요</small>
                    </div>
                </div>
                
                <div class="realtime-controls" style="margin-bottom: 15px; padding: 10px; background: #e7f3ff; border-radius: 8px; display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <label style="display: flex; align-items: center; cursor: pointer;">
                            <input type="checkbox" id="realtime-toggle" style="margin-right: 5px;">
                            <span>실시간 갱신</span>
                        </label>
                        <span id="realtime-status" style="font-size: 12px; color: #6c757d;">비활성화</span>
                    </div>
                    <div id="realtime-indicator" style="display: none; align-items: center; gap: 5px;">
                        <div class="pulse-dot" style="width: 8px; height: 8px; background: #28a745; border-radius: 50%; animation: pulse 2s infinite;"></div>
                        <span style="font-size: 12px; color: #28a745;">실시간 모니터링 중</span>
                    </div>
                </div>

                <div class="quick-dates">
                    <button type="button" class="quick-date-btn" onclick="setQuickDate('today')">오늘</button>
                    <button type="button" class="quick-date-btn" onclick="setQuickDate('yesterday')">어제</button>
                    <button type="button" class="quick-date-btn" onclick="setQuickDate('week')">일주일 전</button>
                    <button type="button" class="quick-date-btn" onclick="setQuickDate('month')">한달 전</button>
                </div>
                
                <form class="search-form" onsubmit="searchLogs(event)">
                    <div class="form-group">
                        <label for="search_date">날짜</label>
                        <input type="date" id="search_date" name="search_date" value="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="log_level">로그 레벨</label>
                        <select id="log_level" name="log_level">
                            <option value="">전체</option>
                            <option value="emergency">Emergency</option>
                            <option value="alert">Alert</option>
                            <option value="critical">Critical</option>
                            <option value="error">Error</option>
                            <option value="warning">Warning</option>
                            <option value="notice">Notice</option>
                            <option value="info">Info</option>
                            <option value="debug">Debug</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="keyword">키워드 검색</label>
                        <input type="text" id="keyword" name="keyword" placeholder="검색할 키워드를 입력하세요">
                    </div>
                    
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="search-btn">검색</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="results-panel">
            <div class="results-header">
                <div class="results-count" id="results-count">검색 결과를 표시할 준비가 되었습니다</div>
                <div id="loading" class="loading" style="display: none;">로딩 중...</div>
            </div>
            
            <div id="results-container">
                <div class="no-results">
                    날짜를 선택하고 검색버튼을 클릭하여 로그를 조회하세요.
                </div>
            </div>
        </div>
    </div>

    <!-- 상세 보기 모달 -->
    <div id="logModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>로그 상세 정보</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div id="modal-body"></div>
        </div>
    </div>

    <script>
        let currentPage = 1;
        let totalPages = 1;
        let currentSearchData = null;
        const logsPerPage = 20;
        let currentLogDirectory = '';
        let eventSource = null;
        let realtimeEnabled = false;
        let lastModifiedTime = 0;
        let currentSearchParams = null;

        // 로컬 스토리지에서 저장된 경로 불러오기
        function loadSavedPath() {
            const savedPath = localStorage.getItem('laravel_log_directory');
            if (savedPath) {
                document.getElementById('log_directory').value = savedPath;
                currentLogDirectory = savedPath;
                showPathStatus('저장된 경로가 로드되었습니다: ' + savedPath, 'success');
            }
        }

        // 로그 디렉토리 경로 설정
        async function setLogDirectory() {
            const pathInput = document.getElementById('log_directory');
            const path = pathInput.value.trim();
            
            if (!path) {
                showPathStatus('경로를 입력해주세요.', 'error');
                return;
            }

            // 경로 유효성 검증을 위해 API 호출
            try {
                const response = await fetch(`api.php?action=validate_path&path=${encodeURIComponent(path)}`);
                const data = await response.json();
                
                if (data.success) {
                    currentLogDirectory = path;
                    localStorage.setItem('laravel_log_directory', path);
                    showPathStatus(`경로가 설정되었습니다: ${path}`, 'success');
                    
                    // 사용 가능한 날짜 목록 업데이트
                    if (data.available_dates && data.available_dates.length > 0) {
                        updateAvailableDates(data.available_dates);
                    }
                } else {
                    showPathStatus(`경로 오류: ${data.message}`, 'error');
                }
            } catch (error) {
                console.error('Path validation error:', error);
                showPathStatus('경로 검증 중 오류가 발생했습니다.', 'error');
            }
        }

        // 경로 상태 메시지 표시
        function showPathStatus(message, type) {
            const pathSetting = document.querySelector('.path-setting');
            let statusDiv = pathSetting.querySelector('.path-status');
            
            if (!statusDiv) {
                statusDiv = document.createElement('div');
                statusDiv.className = 'path-status';
                statusDiv.style.marginTop = '10px';
                statusDiv.style.padding = '8px 12px';
                statusDiv.style.borderRadius = '4px';
                statusDiv.style.fontSize = '14px';
                pathSetting.appendChild(statusDiv);
            }
            
            statusDiv.textContent = message;
            
            if (type === 'success') {
                statusDiv.style.backgroundColor = '#d4edda';
                statusDiv.style.color = '#155724';
                statusDiv.style.border = '1px solid #c3e6cb';
            } else if (type === 'error') {
                statusDiv.style.backgroundColor = '#f8d7da';
                statusDiv.style.color = '#721c24';
                statusDiv.style.border = '1px solid #f5c6cb';
            }
            
            // 3초 후 메시지 제거
            setTimeout(() => {
                if (statusDiv.parentNode) {
                    statusDiv.parentNode.removeChild(statusDiv);
                }
            }, 3000);
        }

        // 사용 가능한 날짜 목록 업데이트
        function updateAvailableDates(dates) {
            const resultsCount = document.getElementById('results-count');
            if (dates.length > 0) {
                resultsCount.textContent = `사용 가능한 로그 파일: ${dates.length}개 (${dates[0]} ~ ${dates[dates.length-1]})`;
            } else {
                resultsCount.textContent = '로그 파일이 없습니다.';
            }
        }

        function setQuickDate(type) {
            const dateInput = document.getElementById('search_date');
            const today = new Date();
            let targetDate;

            switch(type) {
                case 'today':
                    targetDate = today;
                    break;
                case 'yesterday':
                    targetDate = new Date(today.getTime() - 24 * 60 * 60 * 1000);
                    break;
                case 'week':
                    targetDate = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
                    break;
                case 'month':
                    targetDate = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);
                    break;
            }

            dateInput.value = targetDate.toISOString().split('T')[0];
        }

        async function searchLogs(event) {
            event.preventDefault();
            
            // 로그 디렉토리가 설정되지 않은 경우 확인
            if (!currentLogDirectory) {
                showPathStatus('먼저 로그 디렉토리 경로를 설정해주세요.', 'error');
                return;
            }
            
            const formData = new FormData(event.target);
            const searchParams = {
                date: formData.get('search_date'),
                level: formData.get('log_level'),
                keyword: formData.get('keyword'),
                path: currentLogDirectory,
                page: 1
            };

            currentPage = 1;
            await performSearch(searchParams);
        }

        async function performSearch(params) {
            const loading = document.getElementById('loading');
            const resultsContainer = document.getElementById('results-container');
            const resultsCount = document.getElementById('results-count');

            loading.style.display = 'block';
            resultsContainer.innerHTML = '';
            
            // 현재 검색 파라미터 저장 (실시간 갱신용)
            currentSearchParams = params;

            try {
                const queryString = new URLSearchParams(params).toString();
                const response = await fetch(`api.php?${queryString}`);
                const data = await response.json();

                currentSearchData = data;
                
                if (data.success) {
                    displayResults(data.data);
                    updateResultsCount(data.data);
                    
                    // 파일 수정 시간 저장
                    if (data.data && data.data.last_modified) {
                        lastModifiedTime = data.data.last_modified;
                    }
                    
                    // 파일 상태 확인을 위한 추가 요청
                    checkFileStatus(params.path, params.date);
                } else {
                    resultsContainer.innerHTML = `<div class="no-results">오류: ${data.message}</div>`;
                    resultsCount.textContent = '오류가 발생했습니다';
                }
            } catch (error) {
                console.error('Search error:', error);
                resultsContainer.innerHTML = '<div class="no-results">검색 중 오류가 발생했습니다.</div>';
                resultsCount.textContent = '오류가 발생했습니다';
            } finally {
                loading.style.display = 'none';
            }
        }
        
        // 파일 상태 확인
        async function checkFileStatus(path, date) {
            try {
                const response = await fetch(`api.php?action=check_file_status&path=${encodeURIComponent(path)}&date=${date}`);
                const data = await response.json();
                
                if (data.success && data.data.exists) {
                    lastModifiedTime = data.data.modified_time;
                }
            } catch (error) {
                console.error('File status check error:', error);
            }
        }

        function displayResults(data) {
            const resultsContainer = document.getElementById('results-container');
            
            if (!data.logs || data.logs.length === 0) {
                resultsContainer.innerHTML = '<div class="no-results">검색 결과가 없습니다.</div>';
                return;
            }

            // 페이지네이션 계산
            const totalLogs = data.logs.length;
            totalPages = Math.ceil(totalLogs / logsPerPage);
            const startIndex = (currentPage - 1) * logsPerPage;
            const endIndex = Math.min(startIndex + logsPerPage, totalLogs);
            const currentLogs = data.logs.slice(startIndex, endIndex);

            let tableHTML = `
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>시간</th>
                            <th>레벨</th>
                            <th>메시지</th>
                            <th style="width: 80px;">동작</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            currentLogs.forEach((log, index) => {
                const globalIndex = startIndex + index;
                tableHTML += `
                    <tr class="log-row">
                        <td class="timestamp" onclick="showLogDetail(${globalIndex})">${log.timestamp}</td>
                        <td onclick="showLogDetail(${globalIndex})"><span class="log-level ${log.level}">${log.level}</span></td>
                        <td class="log-message" onclick="showLogDetail(${globalIndex})">${escapeHtml(log.message)}</td>
                        <td style="text-align: center;">
                            <button class="copy-btn-inline" onclick="copyLogInline(${globalIndex}, this)" title="메시지 복사">복사</button>
                        </td>
                    </tr>
                `;
            });

            tableHTML += '</tbody></table>';
            
            if (totalPages > 1) {
                tableHTML += generatePagination();
            }

            resultsContainer.innerHTML = tableHTML;
        }

        function generatePagination() {
            let paginationHTML = '<div class="pagination">';
            
            // 이전 페이지 버튼
            paginationHTML += `<button onclick="goToPage(${currentPage - 1})" ${currentPage <= 1 ? 'disabled' : ''}>이전</button>`;
            
            // 페이지 번호들
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);
            
            if (startPage > 1) {
                paginationHTML += `<button onclick="goToPage(1)">1</button>`;
                if (startPage > 2) {
                    paginationHTML += '<span>...</span>';
                }
            }
            
            for (let i = startPage; i <= endPage; i++) {
                paginationHTML += `<button onclick="goToPage(${i})" ${i === currentPage ? 'class="current-page"' : ''}>${i}</button>`;
            }
            
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    paginationHTML += '<span>...</span>';
                }
                paginationHTML += `<button onclick="goToPage(${totalPages})">${totalPages}</button>`;
            }
            
            // 다음 페이지 버튼
            paginationHTML += `<button onclick="goToPage(${currentPage + 1})" ${currentPage >= totalPages ? 'disabled' : ''}>다음</button>`;
            
            paginationHTML += '</div>';
            return paginationHTML;
        }

        function goToPage(page) {
            if (page < 1 || page > totalPages) return;
            
            currentPage = page;
            displayResults(currentSearchData.data);
            updateResultsCount(currentSearchData.data);
        }

        function updateResultsCount(data) {
            const resultsCount = document.getElementById('results-count');
            const totalLogs = data.logs ? data.logs.length : 0;
            
            if (totalLogs === 0) {
                resultsCount.textContent = '검색 결과가 없습니다';
            } else if (totalPages > 1) {
                const startIndex = (currentPage - 1) * logsPerPage + 1;
                const endIndex = Math.min(currentPage * logsPerPage, totalLogs);
                resultsCount.textContent = `총 ${totalLogs.toLocaleString()}개 중 ${startIndex}-${endIndex}개 표시 (${currentPage}/${totalPages} 페이지)`;
            } else {
                resultsCount.textContent = `총 ${totalLogs.toLocaleString()}개의 로그`;
            }
        }

        function showLogDetail(logIndex) {
            if (!currentSearchData || !currentSearchData.data.logs || !currentSearchData.data.logs[logIndex]) {
                return;
            }

            const log = currentSearchData.data.logs[logIndex];
            const modal = document.getElementById('logModal');
            const modalBody = document.getElementById('modal-body');

            const modalContent = `
                <dl class="log-meta">
                    <dt>시간:</dt>
                    <dd>${log.timestamp}</dd>
                    <dt>레벨:</dt>
                    <dd><span class="log-level ${log.level}">${log.level}</span></dd>
                    <dt>원본 라인:</dt>
                    <dd class="log-detail">${escapeHtml(log.raw_line)}</dd>
                </dl>
                <h4>메시지 내용:</h4>
                <div class="log-detail" id="log-message-content">${escapeHtml(log.message)}</div>
                <button class="copy-btn" onclick="copyLogMessage()">메시지 복사</button>
                <span class="copy-success" id="copy-success">복사되었습니다!</span>
            `;

            modalBody.innerHTML = modalContent;
            modal.style.display = 'block';
        }

        function closeModal() {
            document.getElementById('logModal').style.display = 'none';
        }

        function copyLogMessage() {
            const messageContent = document.getElementById('log-message-content');
            const successMsg = document.getElementById('copy-success');
            
            if (messageContent) {
                const textToCopy = messageContent.textContent;
                
                // 클립보드에 복사
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(textToCopy).then(function() {
                        // 성공 메시지 표시
                        successMsg.classList.add('show');
                        setTimeout(function() {
                            successMsg.classList.remove('show');
                        }, 2000);
                    }).catch(function(err) {
                        // 폴백: execCommand 사용
                        fallbackCopyTextToClipboard(textToCopy, successMsg);
                    });
                } else {
                    // 폴백: execCommand 사용
                    fallbackCopyTextToClipboard(textToCopy, successMsg);
                }
            }
        }

        function fallbackCopyTextToClipboard(text, successMsg) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.top = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    successMsg.classList.add('show');
                    setTimeout(function() {
                        successMsg.classList.remove('show');
                    }, 2000);
                }
            } catch (err) {
                console.error('복사 실패:', err);
            }

            document.body.removeChild(textArea);
        }
        
        // 인라인 복사 기능
        function copyLogInline(logIndex, buttonElement) {
            if (!currentSearchData || !currentSearchData.data.logs || !currentSearchData.data.logs[logIndex]) {
                return;
            }
            
            const log = currentSearchData.data.logs[logIndex];
            const textToCopy = log.message;
            
            // 클립보드에 복사
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(textToCopy).then(function() {
                    showCopySuccess(buttonElement);
                }).catch(function(err) {
                    // 폴백: execCommand 사용
                    fallbackCopyInline(textToCopy, buttonElement);
                });
            } else {
                // 폴백: execCommand 사용
                fallbackCopyInline(textToCopy, buttonElement);
            }
        }
        
        // 폴백 복사 함수
        function fallbackCopyInline(text, buttonElement) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.top = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    showCopySuccess(buttonElement);
                }
            } catch (err) {
                console.error('복사 실패:', err);
            }

            document.body.removeChild(textArea);
        }
        
        // 복사 성공 피드백
        function showCopySuccess(buttonElement) {
            const originalText = buttonElement.textContent;
            const originalClass = buttonElement.className;
            
            buttonElement.textContent = '복사됨!';
            buttonElement.classList.add('copied');
            
            setTimeout(function() {
                buttonElement.textContent = originalText;
                buttonElement.className = originalClass;
            }, 1500);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // 모달 외부 클릭 시 닫기
        window.onclick = function(event) {
            const modal = document.getElementById('logModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // ESC 키로 모달 닫기
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' || event.keyCode === 27) {
                const modal = document.getElementById('logModal');
                if (modal.style.display === 'block') {
                    closeModal();
                }
            }
        });

        // 실시간 갱신 시작
        function startRealtimeMonitoring() {
            if (!currentLogDirectory || !currentSearchParams || !currentSearchParams.date) {
                showNotification('먼저 로그를 검색해주세요.', 'error');
                document.getElementById('realtime-toggle').checked = false;
                return;
            }
            
            // 기존 연결이 있으면 종료
            if (eventSource) {
                eventSource.close();
            }
            
            const url = `sse.php?path=${encodeURIComponent(currentLogDirectory)}&date=${currentSearchParams.date}`;
            eventSource = new EventSource(url);
            
            eventSource.addEventListener('connected', function(e) {
                const data = JSON.parse(e.data);
                updateRealtimeStatus(true);
                showNotification('실시간 모니터링이 시작되었습니다.', 'success');
            });
            
            eventSource.addEventListener('file_changed', function(e) {
                const data = JSON.parse(e.data);
                
                // 마지막으로 확인한 시간보다 새로운 변경이면 자동 갱신
                if (data.modified_time > lastModifiedTime) {
                    lastModifiedTime = data.modified_time;
                    showNotification(`로그 파일이 업데이트되었습니다. (${data.modified_time_formatted})`, 'info');
                    
                    // 현재 검색 조건으로 다시 검색 (약간의 지연을 두어 파일 쓰기 완료 보장)
                    setTimeout(() => {
                        performSearch(currentSearchParams);
                    }, 500);
                }
            });
            
            eventSource.addEventListener('file_missing', function(e) {
                const data = JSON.parse(e.data);
                showNotification(data.message, 'warning');
            });
            
            eventSource.addEventListener('error', function(e) {
                if (eventSource.readyState === EventSource.CLOSED) {
                    updateRealtimeStatus(false);
                    
                    // 실시간 갱신이 활성화된 상태면 재연결 시도
                    if (realtimeEnabled) {
                        setTimeout(() => {
                            if (realtimeEnabled) {
                                startRealtimeMonitoring();
                            }
                        }, 5000);
                    }
                }
            });
            
            eventSource.onerror = function(e) {
                if (eventSource.readyState === EventSource.CLOSED) {
                    updateRealtimeStatus(false);
                    showNotification('실시간 연결이 끊어졌습니다.', 'warning');
                }
            };
        }
        
        // 실시간 갱신 중지
        function stopRealtimeMonitoring() {
            if (eventSource) {
                eventSource.close();
                eventSource = null;
            }
            updateRealtimeStatus(false);
        }
        
        // 실시간 상태 업데이트
        function updateRealtimeStatus(connected) {
            const status = document.getElementById('realtime-status');
            const indicator = document.getElementById('realtime-indicator');
            
            if (connected) {
                status.textContent = '연결됨';
                status.style.color = '#28a745';
                indicator.style.display = 'flex';
            } else {
                status.textContent = '비활성화';
                status.style.color = '#6c757d';
                indicator.style.display = 'none';
            }
        }
        
        // 알림 표시
        function showNotification(message, type = 'info') {
            const resultsHeader = document.querySelector('.results-header');
            let notification = document.getElementById('notification');
            
            if (!notification) {
                notification = document.createElement('div');
                notification.id = 'notification';
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 15px 20px;
                    border-radius: 8px;
                    font-size: 14px;
                    z-index: 9999;
                    max-width: 350px;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                    transition: all 0.3s ease;
                `;
                document.body.appendChild(notification);
            }
            
            notification.textContent = message;
            notification.className = 'fade-in';
            
            // 타입별 스타일
            const styles = {
                info: { background: '#e7f3ff', color: '#0056b3', border: '1px solid #bee5eb' },
                success: { background: '#d4edda', color: '#155724', border: '1px solid #c3e6cb' },
                warning: { background: '#fff3cd', color: '#856404', border: '1px solid #ffeaa7' },
                error: { background: '#f8d7da', color: '#721c24', border: '1px solid #f5c6cb' }
            };
            
            Object.assign(notification.style, styles[type] || styles.info);
            
            // 3초 후 제거
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.style.opacity = '0';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }
            }, 3000);
        }

        // 검색 패널 토글 함수
        function toggleSearchPanel() {
            const content = document.getElementById('search-panel-content');
            const icon = document.querySelector('.accordion-icon');
            
            const isCollapsed = content.classList.contains('collapsed');
            
            if (isCollapsed) {
                // 펼치기
                content.classList.remove('collapsed');
                icon.classList.remove('collapsed');
                icon.textContent = '▼';
                localStorage.setItem('search_panel_collapsed', 'false');
            } else {
                // 접기
                content.classList.add('collapsed');
                icon.classList.add('collapsed');
                icon.textContent = '▶';
                localStorage.setItem('search_panel_collapsed', 'true');
            }
        }
        
        // 패널 상태 복원
        function restoreSearchPanelState() {
            const isCollapsed = localStorage.getItem('search_panel_collapsed') === 'true';
            
            if (isCollapsed) {
                const content = document.getElementById('search-panel-content');
                const icon = document.querySelector('.accordion-icon');
                
                content.classList.add('collapsed');
                icon.classList.add('collapsed');
                icon.textContent = '▶';
            }
        }

        // 페이지 로드 시 저장된 경로 불러오기
        document.addEventListener('DOMContentLoaded', function() {
            loadSavedPath();
            restoreSearchPanelState();
            
            // 저장된 경로가 없으면 기본 경로로 자동 설정
            if (!localStorage.getItem('laravel_log_directory')) {
                const defaultPath = document.getElementById('log_directory').value;
                if (defaultPath) {
                    setLogDirectory();
                }
            }
            
            // Enter 키로 경로 설정 가능하도록
            document.getElementById('log_directory').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    setLogDirectory();
                }
            });
            
            // 실시간 갱신 토글
            document.getElementById('realtime-toggle').addEventListener('change', function(e) {
                realtimeEnabled = e.target.checked;
                
                if (realtimeEnabled) {
                    startRealtimeMonitoring();
                } else {
                    stopRealtimeMonitoring();
                }
            });
        });
        
        // 페이지 종료 시 SSE 연결 정리
        window.addEventListener('beforeunload', function() {
            if (eventSource) {
                eventSource.close();
            }
        });
    </script>
</body>
</html>