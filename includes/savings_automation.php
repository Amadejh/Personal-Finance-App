<?php
require_once 'db.php';

function run_savings_automation(int $userId): void
{
    global $conn;

    $query = $conn->prepare("
        SELECT id, monthly_amount, last_auto_transfer 
        FROM savings_accounts
        WHERE user_id = ? AND monthly_amount > 0 AND duration_months > 0
    ");
    $query->bind_param("i", $userId);
    $query->execute();
    $result = $query->get_result();

    while ($row = $result->fetch_assoc()) {
        $accountId = $row['id'];
        $monthly = $row['monthly_amount'];
        $lastRun = $row['last_auto_transfer'];

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
            // preveri glavni račun
            $balQ = $conn->prepare("SELECT main_balance FROM users WHERE id = ?");
            $balQ->bind_param("i", $userId);
            $balQ->execute();
            $balQ->bind_result($mainBal);
            $balQ->fetch();
            $balQ->close();

            if ($mainBal >= $monthly) {
                $conn->begin_transaction();

                // 1. odštej od glavnega računa
                $conn->query("UPDATE users SET main_balance = main_balance - $monthly WHERE id = $userId");

                // 2. dodaj v varčevalni račun/cilj
                $conn->query("UPDATE savings_accounts SET balance = balance + $monthly, last_auto_transfer = NOW() WHERE id = $accountId");

                $conn->commit();
            }
        }
    }
}
