<?php
include '../database/koneksi.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

header('Content-Type: application/json');
$response = array();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_SESSION['id'])) {
        $userId = $_SESSION['id'];  // Mengambil ID pengguna dari sesi

        // Debugging: Log the incoming POST data
        error_log(print_r($_POST, true));

        if (isset($_POST["answer"]) && isset($_POST["start_time"]) && isset($_POST["level_id"])) {
            $userAnswer = $_POST['answer'];
            $startTime = $_POST['start_time'];
            $endTime = time();
            $timeTaken = $endTime - $startTime;
            $levelId = $_POST['level_id'];

            $correctAnswers = [
                "I am reading a book in the library",
                "i am reading a book in the library"
            ];

            if (in_array($userAnswer, $correctAnswers)) {
                $score = ($timeTaken <= 60) ? 100 : 85;
            } else {
                $score = 0;
            }

            $sqlUpdateScore = "UPDATE user SET score = score + ? WHERE id = ?";
            $stmt = $conn->prepare($sqlUpdateScore);
            $stmt->bind_param("ii", $score, $userId);

            if ($stmt->execute()) {
                // Save the game state to autosave table
                $sqlAutosave = "INSERT INTO autosave (user_id, level_id) VALUES (?, ?)
                                ON DUPLICATE KEY UPDATE level_id = ?";
                $stmtAutosave = $conn->prepare($sqlAutosave);
                $stmtAutosave->bind_param("iii", $userId, $levelId, $levelId);

                if ($stmtAutosave->execute()) {
                    $response["success"] = true;
                    $response["message"] = "Score updated and game state saved.";
                    // Redirecting to the main menu
                    echo json_encode($response);
                    $stmtAutosave->close();
                    $stmt->close();
                    $conn->close();
                    header("Location: ../tampilanUtama/mainMenu.php");
                    exit();
                } else {
                    $response["success"] = false;
                    $response["message"] = "Error: " . $stmtAutosave->error;
                }
                $stmtAutosave->close();
            } else {
                $response["success"] = false;
                $response["message"] = "Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $response["success"] = false;
            $response["message"] = "Some fields are missing.";
        }
    } else {
        $response["success"] = false;
        $response["message"] = "User is not logged in.";
    }
} else {
    $response["success"] = false;
    $response["message"] = "HTTP method not supported.";
}

echo json_encode($response);
$conn->close();
?>
