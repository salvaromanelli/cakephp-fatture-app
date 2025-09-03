<?php
// Test básico de CakePHP
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Test CakePHP</h1>";

echo "1. Autoloader test:<br>";
if (file_exists('vendor/autoload.php')) {
    try {
        require_once 'vendor/autoload.php';
        echo "✅ Autoloader OK<br>";
    } catch (Exception $e) {
        echo "❌ Autoloader error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ vendor/autoload.php not found<br>";
}

echo "<br>2. CakePHP classes test:<br>";
try {
    if (class_exists('Cake\Core\Configure')) {
        echo "✅ CakePHP classes available<br>";
    } else {
        echo "❌ CakePHP classes not found<br>";
    }
} catch (Exception $e) {
    echo "❌ Class error: " . $e->getMessage() . "<br>";
}

echo "<br>3. App webroot test:<br>";
if (file_exists('app/webroot/index.php')) {
    echo "✅ app/webroot/index.php exists<br>";
    try {
        // Capturar cualquier error del webroot
        ob_start();
        $old_cwd = getcwd();
        chdir('app/webroot');
        include 'index.php';
        chdir($old_cwd);
        $output = ob_get_contents();
        ob_end_clean();
        
        if (!empty($output)) {
            echo "✅ Webroot produces output (" . strlen($output) . " chars)<br>";
        } else {
            echo "❌ Webroot produces no output<br>";
        }
    } catch (Exception $e) {
        echo "❌ Webroot error: " . $e->getMessage() . "<br>";
        echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
    } catch (Error $e) {
        echo "❌ PHP Fatal Error: " . $e->getMessage() . "<br>";
        echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
    }
} else {
    echo "❌ app/webroot/index.php not found<br>";
}
?>
