<?php
$page_title = "Login - ConCamp";
$body_class = "login-page";

// Processar formulário de login
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Validar campos
    if (empty($email) || empty($password)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        // Tentar fazer login
        $success = login($email, $password);
        
        if ($success) {
            // Redirecionar para o dashboard
            redirect('index.php?route=dashboard');
        } else {
            $error = 'E-mail ou senha incorretos.';
        }
    }
}
?>

<div class="container py-5">
    <div class="auth-container">
        <div class="auth-header">
            <h3>Login</h3>
            <p class="text-muted">Entre com suas credenciais</p>
        </div>
        
        <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="post" action="" class="auth-form needs-validation" novalidate>
            <div class="form-group mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" class="form-control" id="email" name="email" required>
                <div class="invalid-feedback">
                    Por favor, insira um e-mail válido.
                </div>
            </div>
            
            <div class="form-group mb-4">
                <label for="password" class="form-label">Senha</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <div class="invalid-feedback">
                    Por favor, insira sua senha.
                </div>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">Entrar</button>
            </div>
            
            <div class="auth-links mt-4">
                <a href="index.php?route=forgot-password" class="text-decoration-none">Esqueceu sua senha?</a>
            </div>
        </form>
    </div>
</div>
