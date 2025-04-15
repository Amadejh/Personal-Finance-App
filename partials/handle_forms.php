<?php
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user'])) return;

$userId = $_SESSION['user']['id'];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['claim_goal_id'])) {
    $goalId = (int) $_POST['claim_goal_id'];
    $userId = $_SESSION['user']['id'];

    // Transfer goal balance to main account
    $stmt = $conn->prepare("SELECT balance FROM savings_accounts WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $goalId, $userId);
    $stmt->execute();
    $stmt->bind_result($balance);
    $stmt->fetch();
    $stmt->close();

    if ($balance > 0) {
        $conn->begin_transaction();

        try {
            $stmt1 = $conn->prepare("UPDATE users SET main_balance = main_balance + ? WHERE id = ?");
            $stmt1->bind_param("di", $balance, $userId);
            $stmt1->execute();

            $stmt2 = $conn->prepare("DELETE FROM savings_accounts WHERE id = ? AND user_id = ?");
            $stmt2->bind_param("ii", $goalId, $userId);
            $stmt2->execute();

            $conn->commit();
            $_SESSION['popup'] = "✅ Prihranki iz cilja so bili uspešno izplačani!";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['popup'] = "❌ Napaka pri izplačilu cilja.";
        }
    }

    header("Location: dashboard.php");
    exit;
}


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

        $_SESSION['popup'] = "✅ Nov cilj varčevanja uspešno ustvarjen.";
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
    $accountId = intval($_POST['savings_account_id']);

    // Stop automation if requested
    if (isset($_POST['stop_automation'])) {
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
    if (isset($_POST['setup_automation'])) {
        $months = intval($_POST['duration_months']);

        $stmt = $conn->prepare("SELECT goal_amount, balance FROM savings_accounts WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $accountId, $userId);
        $stmt->execute();
        $stmt->bind_result($goal, $current);

        if ($stmt->fetch()) {
            $stmt->close();

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



// 🗑️ Delete savings account
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_savings_account_id'])) {
    $accountId = intval($_POST['delete_savings_account_id']);

    $stmt = $conn->prepare("DELETE FROM savings_accounts WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $accountId, $userId);

    if ($stmt->execute()) {
        $_SESSION['popup'] = "🗑️ Cilj uspešno izbrisan.";
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "❌ Napaka pri brisanju.";
    }
}

if (isset($_POST['delete_savings_account_id'])) {
    $goalId = (int)$_POST['delete_savings_account_id'];

    // Fetch the goal first
    $goalQuery = $conn->prepare("SELECT balance FROM savings_accounts WHERE id = ? AND user_id = ?");
    $goalQuery->bind_param("ii", $goalId, $userId);
    $goalQuery->execute();
    $goalResult = $goalQuery->get_result();
    $goal = $goalResult->fetch_assoc();
    $goalQuery->close();

    if ($goal) {
        $balanceToReturn = $goal['balance'];

        // Return funds to main balance
        $updateWallet = $conn->prepare("UPDATE users SET main_balance = main_balance + ? WHERE id = ?");
        $updateWallet->bind_param("di", $balanceToReturn, $userId);
        $updateWallet->execute();
        $updateWallet->close();

        // Delete the goal
        $deleteStmt = $conn->prepare("DELETE FROM savings_accounts WHERE id = ? AND user_id = ?");
        $deleteStmt->bind_param("ii", $goalId, $userId);
        $deleteStmt->execute();
        $deleteStmt->close();

        $_SESSION['popup'] = "✅ Sredstva so bila vrnjena v tvoj račun.";
    } else {
        $_SESSION['popup'] = "⚠️ Napaka pri iskanju cilja.";
    }

    header("Location: dashboard.php");
    exit;
}


?>
