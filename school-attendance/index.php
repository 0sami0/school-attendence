<?php
session_start();

// Redirige si l'utilisateur est déjà connecté
if (isset($_SESSION['teacher_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Gère les messages d'erreur venant de login_process.php
$error_message = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] == 'invalid_credentials') {
        $error_message = 'Email ou mot de passe incorrect.';
    } else {
        $error_message = 'Une erreur est survenue.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - EMG</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        /* --- CHARTE GRAPHIQUE EMG --- */
        :root {
            --emg-blue: #00529b;
            --emg-blue-light: #0061b5; /* Nouveau bleu pour le dégradé */
            --emg-yellow: #ffd100;
        }

        /* --- Réinitialisation et Police --- */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #1f2937;
            
            /* NOUVEAU FOND EN DÉGRADÉ */
            background-color: var(--emg-blue); /* Couleur de secours */
            background-image: linear-gradient(120deg, var(--emg-blue-light), var(--emg-blue));
        }

        /* --- Conteneur Principal --- */
        .login-container {
            background-color: #ffffff;
            padding: 48px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;

            /* NOUVEL ACCENT JAUNE */
            border-top: 5px solid var(--emg-yellow);
        }

        /* --- Logo --- */
        .logo { height: 50px; margin-bottom: 32px; }
        h1 { font-size: 28px; font-weight: 700; margin-bottom: 24px; }

        /* --- Formulaire --- */
        .form-group { margin-bottom: 16px; position: relative; }
        .form-control {
            width: 100%; padding: 12px 16px;
            border: 1px solid #d1d5db; border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control::placeholder { color: #9ca3af; }
        .form-control:focus {
            outline: none;
            /* NOUVEL ACCENT JAUNE AU FOCUS */
            border-color: var(--emg-yellow);
            box-shadow: 0 0 0 3px rgba(255, 209, 0, 0.25);
        }
        #togglePassword {
            position: absolute; top: 50%; right: 16px;
            transform: translateY(-50%); cursor: pointer; color: #9ca3af;
        }

        /* --- Boutons --- */
        .btn {
            display: inline-block; width: 100%; padding: 12px;
            border-radius: 8px; font-size: 16px; font-weight: 600;
            text-align: center; text-decoration: none; cursor: pointer;
            transition: background-color 0.2s; margin-top: 8px;
        }
        .btn-primary {
            background-color: var(--emg-blue);
            color: white; border: none;
        }
        .btn-primary:hover { background-color: #00417a; }
        
        /* --- Message d'erreur --- */
        .error-message {
            background-color: #fee2e2; color: #b91c1c;
            padding: 12px; border-radius: 8px;
            margin-bottom: 16px; text-align: center; font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="assets/logo-emg.png" alt="Logo EMG" class="logo">
        <h1>Connexion</h1>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="login_process.php" method="post">
            <div class="form-group">
                <input type="email" id="email" name="email" class="form-control" placeholder="adresse@email.com" required>
            </div>
            <div class="form-group">
                <input type="password" id="password" name="password" class="form-control" placeholder="Mot de passe" required>
                <i class="fas fa-eye-slash" id="togglePassword"></i>
            </div>
            <button type="submit" class="btn btn-primary">Se connecter</button>
        </form>
    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');

        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>