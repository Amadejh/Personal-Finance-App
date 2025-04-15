<?php
$plain = 'admin123';
$stored = '$2y$10$vIj1nkkGR4PtEObgcZYMBu7zyIhyxYj6MaKjz8OKr75DdSGjYsi9K'; // this must match DB

echo "<h2>🔍 Password Test</h2>";
echo "<p>Input password: <strong>$plain</strong></p>";
echo "<p>Stored hash: <code>$stored</code></p>";

if (password_verify($plain, $stored)) {
    echo "<p style='color:green;'>✅ MATCH</p>";
} else {
    echo "<p style='color:red;'>❌ NO MATCH</p>";
}
?>
