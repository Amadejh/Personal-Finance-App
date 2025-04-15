<?php
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php-error.log');

require_once __DIR__ . '/../includes/db.php';


if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user'])) return;

$userId = $_SESSION['user']['id'];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['new_transaction'])) {
    $type = $_POST['type'] ?? '';
    $category = $_POST['category'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($amount > 0 && in_array($type, ['nakazilo', 'dvig', 'prenos'])) {
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, category, amount, description, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issds", $userId, $type, $category, $amount, $description);
        $stmt->execute();
        $stmt->close();

        $_SESSION['popup'] = "✅ Transakcija uspešno dodana.";
    } else {
        $_SESSION['popup'] = "⚠️ Neveljavni podatki za transakcijo.";
    }

    header("Location: dashboard.php");
    exit;
}


// ➕ Create new savings account
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['create_savings_account'])) {
    $name = trim($_POST['goal_name']);
    $goal = floatval($_POST['goal_amount']);

    if ($name && $goal > 0) {
        $stmt = $conn->prepare("INSERT INTO savings_accounts (user_id, name, goal_amount, balance, created_at) VALUES (?, ?, ?, 0, NOW())");
        $userId = $_SESSION['user']['id'] ?? null;
        $stmt->bind_param("isd", $userId, $name, $goal);
        $stmt->execute();
        $stmt->close();

        $_SESSION['popup'] = "✅ Nov cilj varčavanja uspešno ustvarjen.";
    } else {
        $_SESSION['popup'] = "⚠️ Vnesi veljavno ime in cilj.";
    }
    header("Location: dashboard.php");
    exit;
}

// 💸 Manual transfer to savings
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['transfer_to_savings'])) {
    $amount = floatval($_POST['transfer_amount']);
    $savingsId = intval($_POST['savings_account']);

    $conn->begin_transaction();

    $check = $conn->prepare("SELECT main_balance FROM users WHERE id = ?");
    $check->bind_param("i", $userId);
    $check->execute();
    $check->bind_result($mainBal);
    $check->fetch();
    $check->close();

    if ($mainBal >= $amount && $amount > 0) {
        $stmt1 = $conn->prepare("UPDATE users SET main_balance = main_balance - ? WHERE id = ?");
        $stmt1->bind_param("di", $amount, $userId);
        $stmt1->execute();
        $stmt1->close();

        $stmt2 = $conn->prepare("UPDATE savings_accounts SET balance = balance + ? WHERE id = ? AND user_id = ?");
        $stmt2->bind_param("dii", $amount, $savingsId, $userId);
        $stmt2->execute();
        $stmt2->close();

        // Record the transfer as a transaction
        $description = "Prenos v cilj";
        $stmt3 = $conn->prepare("INSERT INTO transactions (user_id, type, amount, description, category, created_at) VALUES (?, 'prenos', ?, ?, 'Drugo', NOW())");
        $stmt3->bind_param("ids", $userId, $amount, $description);
        $stmt3->execute();
        $stmt3->close();

        $conn->commit();
        $_SESSION['popup'] = "✅ Prenos uspešno opravljen.";
    } else {
        $conn->rollback();
        $_SESSION['popup'] = "❌ Napaka: Nezadostna sredstva ali napačen znesek.";
    }
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

// ⚙️ Setup or Stop auto-save goal for a specific savings account
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accountId = intval($_POST['savings_account_id'] ?? 0);

    // Stop automation if requested
    if (isset($_POST['stop_automation']) && $accountId > 0) {
        $stmt = $conn->prepare("UPDATE savings_accounts SET monthly_amount = 0, duration_months = 0 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $accountId, $userId);
        if ($stmt->execute()) {
            $_SESSION['popup'] = "⏹️ Avtomatsko varčevanje izklopljeno.";
        } else {
            $_SESSION['popup'] = "❌ Napaka pri izklopu varčevanja.";
        }
        $stmt->close();
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // Proceed with setting up auto-save
    if (isset($_POST['setup_automation']) && $accountId > 0) {
        $months = intval($_POST['duration_months'] ?? 0);

        $stmt = $conn->prepare("SELECT goal_amount, balance FROM savings_accounts WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $accountId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $goalData = $result->fetch_assoc();
        $stmt->close();

        if ($goalData) {
            $goal = $goalData['goal_amount'];
            $current = $goalData['balance'];

            $remaining = $goal - $current;
            $monthly = $months > 0 ? round($remaining / $months, 2) : 0;

            if ($monthly > 0 && $months > 0) {
                $update = $conn->prepare("UPDATE savings_accounts SET monthly_amount = ?, duration_months = ? WHERE id = ?");
                $update->bind_param("dii", $monthly, $months, $accountId);
                $update->execute();
                $update->close();

                $_SESSION['popup'] = "✅ Avtomatsko varčevanje nastavljeno.";
            } else {
                $_SESSION['popup'] = "⚠️ Mesečni znesek ni veljaven.";
            }
        } else {
            $_SESSION['popup'] = "❌ Račun ni najden.";
        }

        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }
}



// ✅ Claim (delete) savings goal and return funds to main wallet
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['claim_goal_id'])) {
    $goalId = (int)$_POST['claim_goal_id'];

    // Fetch the goal balance and name safely
    $goalQuery = $conn->prepare("SELECT name, balance FROM savings_accounts WHERE id = ? AND user_id = ?");
    $goalQuery->bind_param("ii", $goalId, $userId);
    $goalQuery->execute();
    $goalResult = $goalQuery->get_result();
    $goal = $goalResult->fetch_assoc();
    $goalQuery->close();

    if ($goal) {
        $balanceToReturn = (float)$goal['balance'];
        $goalName = $goal['name'];

        // ✅ Return funds to main wallet
        $update = $conn->prepare("UPDATE users SET main_balance = main_balance + ? WHERE id = ?");
        $update->bind_param("di", $balanceToReturn, $userId);
        if (!$update->execute()) {
            $_SESSION['popup'] = "❌ Napaka pri prenosu sredstev: " . $update->error;
            header("Location: dashboard.php");
            exit;
        }
        $update->close();

        // ✅ Log this return as a transaction
        $desc = "Zaključen cilj";
        $type = 'nakazilo';
        $category = 'Drugo';

        $log = $conn->prepare("INSERT INTO transactions (user_id, type, category, amount, description, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $log->bind_param("issds", $userId, $type, $category, $balanceToReturn, $desc);
        if (!$log->execute()) {
            $_SESSION['popup'] = "❌ Napaka pri logiranju transakcije: " . $log->error;
            header("Location: dashboard.php");
            exit;
        }
        $log->close();

        // ✅ Delete the goal
        $delete = $conn->prepare("DELETE FROM savings_accounts WHERE id = ? AND user_id = ?");
        $delete->bind_param("ii", $goalId, $userId);
        if (!$delete->execute()) {
            $_SESSION['popup'] = "❌ Napaka pri brisanju cilja: " . $delete->error;
            header("Location: dashboard.php");
            exit;
        }
        $delete->close();

        $_SESSION['popup'] = "✅ Cilj uspešno zaključen in sredstva so bila prenesena v tvoj glavni račun.";
    } else {
        $_SESSION['popup'] = "⚠️ Napaka pri iskanju cilja ali ni najden.";
    }

    header("Location: dashboard.php");
    exit;
}




