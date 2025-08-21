<?php
// Simple test file to check if API is working
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple API Test</title>
</head>
<body>
    <h1>Simple API Test</h1>
    
    <h2>Test 1: Direct API call</h2>
    <button onclick="testDirect()">Test Direct API</button>
    <div id="result1"></div>
    
    <h2>Test 2: Test endpoint</h2>
    <button onclick="testTestEndpoint()">Test Test Endpoint</button>
    <div id="result2"></div>
    
    <h2>Test 3: Check file exists</h2>
    <div id="result3">
        <?php
        $apiFile = __DIR__ . '/api.php';
        if (file_exists($apiFile)) {
            echo "<p style='color: green;'>✅ api.php fayli mavjud</p>";
            echo "<p>Fayl hajmi: " . filesize($apiFile) . " bayt</p>";
        } else {
            echo "<p style='color: red;'>❌ api.php fayli topilmadi</p>";
        }
        
        $htaccessFile = __DIR__ . '/.htaccess';
        if (file_exists($htaccessFile)) {
            echo "<p style='color: green;'>✅ .htaccess fayli mavjud</p>";
        } else {
            echo "<p style='color: red;'>❌ .htaccess fayli topilmadi</p>";
        }
        ?>
    </div>

    <h2>Test 4: Server Info</h2>
    <div id="result4">
        <?php
        echo "<p><strong>Current Directory:</strong> " . __DIR__ . "</p>";
        echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
        echo "<p><strong>Request URI:</strong> " . $_SERVER['REQUEST_URI'] . "</p>";
        echo "<p><strong>Script Name:</strong> " . $_SERVER['SCRIPT_NAME'] . "</p>";
        ?>
    </div>

    <script>
        async function testDirect() {
            const resultDiv = document.getElementById('result1');
            resultDiv.innerHTML = 'Testing...';
            
            try {
                const response = await fetch('api.php?action=test');
                const text = await response.text();
                
                console.log('Direct API response:', text);
                
                try {
                    const data = JSON.parse(text);
                    resultDiv.innerHTML = '<pre style="color: green;">Success: ' + JSON.stringify(data, null, 2) + '</pre>';
                } catch (parseError) {
                    resultDiv.innerHTML = '<p style="color: red;">Parse error: ' + parseError.message + '</p><pre>' + text + '</pre>';
                }
            } catch (error) {
                resultDiv.innerHTML = '<p style="color: red;">Network error: ' + error.message + '</p>';
            }
        }

        async function testTestEndpoint() {
            const resultDiv = document.getElementById('result2');
            resultDiv.innerHTML = 'Testing test endpoint...';
            
            try {
                const response = await fetch('api.php?action=test');
                const text = await response.text();
                
                console.log('Test endpoint response:', text);
                
                if (response.ok) {
                    try {
                        const data = JSON.parse(text);
                        resultDiv.innerHTML = '<pre style="color: green;">Success: ' + JSON.stringify(data, null, 2) + '</pre>';
                    } catch (parseError) {
                        resultDiv.innerHTML = '<p style="color: red;">Parse error: ' + parseError.message + '</p><pre>' + text + '</pre>';
                    }
                } else {
                    resultDiv.innerHTML = '<p style="color: red;">HTTP Error: ' + response.status + '</p><pre>' + text + '</pre>';
                }
            } catch (error) {
                resultDiv.innerHTML = '<p style="color: red;">Network error: ' + error.message + '</p>';
            }
        }
    </script>
</body>
</html>
