<?php
$licenseTypes = ['建造執照', '雜項執照', '使用執照', '拆除執照'];
$year = 102;
$currentYear = date('Y') - 1911;
$month = 1;
$currentMonth = date('m');

for ($year; $year <= $currentYear; $year++) {
    for ($month = 1; $month <= 12; $month++) {
        $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
        if ((int)$year > (int)$currentYear || ((int)$year == (int)$currentYear && (int)$month > (int)$currentMonth)) {
            break 2;
        }
        $rawDir = __DIR__ . "/../raw/{$year}/{$monthStr}";
        if (!is_dir($rawDir)) {
            mkdir($rawDir, 0777, true);
        }
        foreach($licenseTypes as $licenseType) {
            $page = 1;
            while (true) {
                $start = ($page - 1) * 100 + 1;
                $encodedLicenseType = urlencode($licenseType);
                $encodedDate = urlencode("{$year}年{$monthStr}月");
                $url = "http://210.69.40.24/opendata/OpenDataSearchUrl.do?d=OPENDATA&c=BUILDLIC&Start={$start}&執照類別={$encodedLicenseType}&發照日期={$encodedDate}";
                $fileName = $rawDir . "/{$licenseType}_{$page}.json";
                // Skip if file exists and not in the most recent 3 months
                $isRecent = ((int)$year == (int)$currentYear && (int)$month >= (int)$currentMonth - 2);
                if (file_exists($fileName) && !$isRecent) {
                    echo "Skipping existing file: {$fileName}\n";
                    $page++;
                    continue;
                }
                // Use cURL to fetch the remote file with browser headers
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                    'Accept: application/json, text/javascript, */*; q=0.01',
                    'Accept-Language: zh-TW,zh;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Connection: keep-alive',
                ]);
                $json = curl_exec($ch);
                curl_close($ch);
                if ($json === false || trim($json) === '' || $json === '{}') {
                    break;
                }
                file_put_contents($fileName, $json);
                echo "Saved: {$fileName}\n";
                // Check if there is more data (pagination)
                $data = json_decode($json, true);
                if (empty($data) || count($data['data']) < 100) { // 100 per page
                    break;
                }
                $page++;
            }
        }
    }
}