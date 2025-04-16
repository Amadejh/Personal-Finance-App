<?php
//  Poskrbi da je session pričet
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../includes/db.php';

//  Ustvari novega uporabnika
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['create_user'])) {
    $newName = trim($_POST['new_name']);
    $newLastname = trim($_POST['new_lastname']);
    $newEmail = trim($_POST['new_email']);
    $newGender = $_POST['new_gender'];
    $newPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $newRole = $_POST['new_role'];

    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $newEmail);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $_SESSION['popup'] = "⚠️ Uporabnik s tem emailom že obstaja.";
        $_SESSION['popup_type'] = "error";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, lastname, email, gender, password, role) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $newName, $newLastname, $newEmail, $newGender, $newPassword, $newRole);
        if ($stmt->execute()) {
            $_SESSION['popup'] = "✅ Novi uporabnik uspešno ustvarjen.";
            $_SESSION['popup_type'] = "success";
        } else {
            $_SESSION['popup'] = "❌ Napaka pri ustvarjanju uporabnika.";
            $_SESSION['popup_type'] = "error";
        }
    }

    header("Location: ../admin/dashboard.php");
    exit();
}

//  Uredi obstoječega uporabnika
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_user'])) {
    $editId = $_POST['edit_user_id'];
    $editName = trim($_POST['edit_name']);
    $editLastname = trim($_POST['edit_lastname']);
    $editEmail = trim($_POST['edit_email']);
    $editRole = $_POST['edit_role'];

    // Preveri geslo
    $passwordUpdate = "";
    if (!empty($_POST['edit_password'])) {
        $newPassword = password_hash($_POST['edit_password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET name = ?, lastname = ?, email = ?, password = ?, role = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $editName, $editLastname, $editEmail, $newPassword, $editRole, $editId);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ?, lastname = ?, email = ?, role = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $editName, $editLastname, $editEmail, $editRole, $editId);
    }

    if ($stmt->execute()) {
        $_SESSION['popup'] = "✅ Uporabnik uspešno posodobljen.";
        $_SESSION['popup_type'] = "success";
    } else {
        $_SESSION['popup'] = "❌ Napaka pri posodabljanju uporabnika.";
        $_SESSION['popup_type'] = "error";
    }

    header("Location: ../admin/dashboard.php");
    exit();
}

//  Simulacija transakcije
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_transaction'])) {
    $userId = $_POST['user_id'];
    $type = $_POST['type'];
    $category = $_POST['category'];
    $amount = round(floatval($_POST['amount']), 2);
    $desc = $_POST['description'];

    if ($amount > 0 && in_array($type, ['nakazilo', 'dvig', 'prenos'])) {
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, category, amount, description, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issds", $userId, $type, $category, $amount, $desc);

        if ($stmt->execute()) {
            $_SESSION['popup'] = "✅ Transakcija uspešno dodana.";
            $_SESSION['popup_type'] = "success";
        } else {
            $_SESSION['popup'] = "❌ Napaka pri vnosu v bazo.";
            $_SESSION['popup_type'] = "error";
        }

        $stmt->close();
    } else {
        $_SESSION['popup'] = "⚠️ Neveljavni podatki.";
        $_SESSION['popup_type'] = "error";
    }

    header("Location: ../admin/dashboard.php");
    exit();
}

// Brisanje uporabnika
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_user'])) {
    $deleteId = $_POST['edit_user_id'];

    $conn->begin_transaction();
    try {
        $stmt1 = $conn->prepare("DELETE FROM transactions WHERE user_id = ?");
        $stmt1->bind_param("i", $deleteId);
        $stmt1->execute();

        $stmt2 = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt2->bind_param("i", $deleteId);
        $stmt2->execute();

        $conn->commit();
        $_SESSION['popup'] = "🗑️ Uporabnik uspešno izbrisan.";
        $_SESSION['popup_type'] = "success";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['popup'] = "❌ Napaka pri brisanju uporabnika.";
        $_SESSION['popup_type'] = "error";
    }

    header("Location: ../admin/dashboard.php");
    exit();
}