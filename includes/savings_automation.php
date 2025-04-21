<?php
require_once 'db.php';

// Enhanced savings automation with proper error handling
function run_savings_automation(int $userId): void {
    global $conn;
    
    // Get all active savings accounts with automation
    $query = $conn->prepare("
        SELECT id, name, monthly_amount, last_auto_transfer 
        FROM savings_accounts
        WHERE user_id = ? AND monthly_amount > 0 AND duration_months > 0
    ");
    $query->bind_param("i", $userId);
    $query->execute();
    $result = $query->get_result();
    
    $automationResults = [];
    $totalSuccess = 0;
    $totalFailed = 0;
    
    while ($row = $result->fetch_assoc()) {
        $accountId = $row['id'];
        $accountName = $row['name'];
        $monthly = $row['monthly_amount'];
        $lastRun = $row['last_auto_transfer'];
        
        // Check if automation should run
        $shouldRun = false;
        if (!$lastRun) {
            $shouldRun = true;
        } else {
            $lastDate = new DateTime($lastRun);
            $now = new DateTime();
            $diff = $lastDate->diff($now);
            if ($diff->days >= 30) {
                $shouldRun = true;
            }
        }
        
        if ($shouldRun) {
            // Check main account balance
            $balQ = $conn->prepare("SELECT main_balance FROM users WHERE id = ?");
            $balQ->bind_param("i", $userId);
            $balQ->execute();
            $balQ->bind_result($mainBal);
            $balQ->fetch();
            $balQ->close();
            
            if ($mainBal >= $monthly) {
                try {
                    $conn->begin_transaction();
                    
                    // Update main balance
                    $updateMain = $conn->prepare("UPDATE users SET main_balance = main_balance - ? WHERE id = ?");
                    $updateMain->bind_param("di", $monthly, $userId);
                    $updateMain->execute();
                    
                    // Update savings account
                    $updateSavings = $conn->prepare("UPDATE savings_accounts SET balance = balance + ?, last_auto_transfer = NOW() WHERE id = ?");
                    $updateSavings->bind_param("di", $monthly, $accountId);
                    $updateSavings->execute();
                    
                    // Record transaction
                    $type = 'prenos';
                    $description = "Avtomatski prenos v cilj: " . $accountName;
                    $category = "Varčevanje";
                    
                    $logTrans = $conn->prepare("INSERT INTO transactions (user_id, type, category, amount, description, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    $logTrans->bind_param("issds", $userId, $type, $category, $monthly, $description);
                    $logTrans->execute();
                    
                    $conn->commit();
                    $totalSuccess++;
                    $automationResults[] = "✅ Avtomatski prenos za {$accountName}: {$monthly}€";
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    $totalFailed++;
                    $automationResults[] = "❌ Napaka pri prenosu za {$accountName}: " . $e->getMessage();
                }
            } else {
                $totalFailed++;
                $automationResults[] = "⚠️ Nezadostna sredstva za avtomatski prenos v {$accountName} ({$monthly}€)";
            }
        }
    }
    
    // Store results in session for user feedback
    if (!empty($automationResults)) {
        $_SESSION['automation_results'] = $automationResults;
        $_SESSION['automation_summary'] = "Avtomatski prenosi: {$totalSuccess} uspešnih, {$totalFailed} neuspešnih";
    }
}