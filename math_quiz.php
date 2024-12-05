<?php
session_start();



// Game initialization
if (isset($_POST['start_game'])) {
    $level = $_POST['level'];
    $customMin = $level === 'custom' ? (int)$_POST['custom_min'] : null;
    $customMax = $level === 'custom' ? (int)$_POST['custom_max'] : null;

    $_SESSION['level'] = $level;
    $_SESSION['operator'] = $_POST['operator'];
    $_SESSION['total_questions'] = $_POST['num_questions'];
    $_SESSION['current_question'] = 1;
    $_SESSION['score'] = 0;
    $_SESSION['question'] = generateQuestion($level, $_SESSION['operator'], $customMin, $customMax);
    $_SESSION['remark'] = "";
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}


// Function to generate a random math question and multiple choices
function generateQuestion($level, $operator, $customMin = null, $customMax = null) {
    if ($level === 'custom' && isset($customMin, $customMax)) {
        $num1 = rand($customMin, $customMax);
        $num2 = rand($customMin, $customMax);
    } else {
        $max = $level == 1 ? 10 : 100;
        $num1 = rand(1, $max);
        $num2 = rand(1, $max);
    }

    // Prevent division by zero
    if ($operator == '/' && $num2 == 0) {
        $num2 = rand($customMin ?? 1, $customMax ?? $max);
    }

    // Calculate the answer
    $answer = eval("return $num1 $operator $num2;");
    $question = "$num1 $operator $num2";

    // Generate multiple choices
    $choices = [$answer];
    $maxChoiceRange = ($customMax ?? $max) * 2;
    while (count($choices) < 4) {
        $fakeAnswer = rand(1, $maxChoiceRange);
        if (!in_array($fakeAnswer, $choices)) {
            $choices[] = $fakeAnswer;
        }
    }
    shuffle($choices);

    $_SESSION['answer'] = $answer; // Store the correct answer
    $_SESSION['choices'] = $choices; // Store the choices
    return $question;
}


// Game in progress
if (isset($_POST['submit_answer'])) {
    $userAnswer = $_POST['answer'];
    if ($userAnswer == $_SESSION['answer']) {
        $_SESSION['score']++;
        $_SESSION['remark'] = "✅ Correct! Well done!";
    } else {
        $_SESSION['remark'] = "❌ Incorrect. The correct answer was " . $_SESSION['answer'];
    }

    $_SESSION['current_question']++;
    if ($_SESSION['current_question'] <= $_SESSION['total_questions']) {
        $_SESSION['question'] = generateQuestion(
            $_SESSION['level'],
            $_SESSION['operator'],
            $_POST['custom_min'] ?? null,
            $_POST['custom_max'] ?? null
        );
    } else {
        header('Location: ' . $_SERVER['PHP_SELF'] . '?game_over=1');
        exit();
    }
}

// Game over logic
if (isset($_GET['game_over'])) {
    $score = $_SESSION['score'];
    $total = $_SESSION['total_questions'];
    session_destroy();
    $resultMessage = "Game Over! 🎉 You scored $score out of $total.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="math_quiz.css">
    <title>Math Quiz Game</title>
</head>
<body>
    <div class="container">
        <h1>Math Quiz Game</h1>

        <?php if (!isset($_SESSION['current_question']) && !isset($_GET['game_over'])): ?>
        <!-- Game Setup -->
        <form method="post" action="">
            <label for="level">Select Difficulty Level:</label>
            <select name="level" id="level" required>
                <option value="1">Level 1 (1-10)</option>
                <option value="2">Level 2 (1-100)</option>
                <option value="custom">Custom Range</option>
            </select>

            <div id="custom-range-container" style="display: none;">
                <div>
                    <label for="custom_min">Custom Min:</label>
                    <input type="number" name="custom_min" id="custom_min" placeholder="Enter min value">
                </div>
                <div>
                    <label for="custom_max">Custom Max:</label>
                    <input type="number" name="custom_max" id="custom_max" placeholder="Enter max value">
                </div>
            </div>

            <label for="operator">Choose Operator:</label>
            <select name="operator" id="operator" required>
                <option value="+">Addition (+)</option>
                <option value="-">Subtraction (-)</option>
                <option value="*">Multiplication (*)</option>
                <option value="/">Division (/)</option>
            </select>

            <label for="num_questions">Number of Questions:</label>
            <input type="number" name="num_questions" id="num_questions" placeholder="Enter number of questions" required>

            <button type="submit" name="start_game">Start Game</button>
        </form>
        <?php elseif (!isset($_GET['game_over'])): ?>
        <!-- Game In Progress -->
        <p>Question <?= $_SESSION['current_question'] ?> of <?= $_SESSION['total_questions'] ?>:</p>
        <p><strong><?= $_SESSION['question'] ?></strong></p>
        <form method="post" action="">
            <?php foreach ($_SESSION['choices'] as $choice): ?>
            <div class="choice-container">
                <input type="radio" name="answer" value="<?= $choice ?>" id="choice<?= $choice ?>" required>
                <label for="choice<?= $choice ?>"><?= $choice ?></label>
            </div>
            <?php endforeach; ?>
            <button type="submit" name="submit_answer">Submit Answer</button>
        </form>
        <p class="remark"><?= $_SESSION['remark'] ?></p>
        <?php else: ?>
        <!-- Game Over -->
        <div class="game-over">
            <p><?= $resultMessage ?></p>
            <a href="<?= $_SERVER['PHP_SELF'] ?>">Play Again</a>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Show or hide custom range inputs based on level selection
        document.getElementById('level').addEventListener('change', function () {
    const customRangeContainer = document.getElementById('custom-range-container');
    if (this.value === 'custom') {
        customRangeContainer.style.display = 'block'; // Show custom range
    } else {
        customRangeContainer.style.display = 'none'; // Hide custom range
    }
});
    </script>
</body>
</html>
