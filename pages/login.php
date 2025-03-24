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

<div class="container"> <!-- Container Bootstrap para centralizar o conteúdo -->
    <div class="row justify-content-center align-items-center vh-100"> <!-- Linha que centraliza verticalmente e horizontalmente -->
        <div class="col-md-6 col-lg-5"> <!-- Coluna para o conteúdo de login, ajustada para telas médias e grandes -->
            <div class="auth-container bg-white rounded shadow-sm p-4 p-md-5"> <!-- Container de autenticação com fundo branco, borda arredondada, sombra e padding -->
                <div class="auth-header mb-3"> <!-- Header de autenticação com margem inferior -->
                    <h3 class="text-center mb-1">Login</h3> <!-- Título centralizado -->
                    <p class="text-muted text-center">Entre com suas credenciais</p> <!-- Subtítulo centralizado -->
                </div>

                <?php if (!empty($error)): ?>
                <div class="alert alert-danger rounded-sm"><?php echo $error; ?></div> <!-- Alerta de erro com borda arredondada -->
                <?php endif; ?>

                <form method="post" action="" class="auth-form needs-validation" novalidate> <!-- Formulário de autenticação -->
                    <div class="mb-3"> <!-- Grupo de formulário com margem inferior -->
                        <label for="email" class="form-label">E-mail</label> <!-- Label do campo de e-mail -->
                        <div class="input-group"> <!-- Input group para adicionar ícone -->
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span> <!-- Ícone de e-mail -->
                            <input type="email" class="form-control rounded-sm" id="email" name="email" placeholder="Seu e-mail" required> <!-- Input de e-mail com borda arredondada e placeholder -->
                            <div class="invalid-feedback">
                                Por favor, insira um e-mail válido.
                            </div>
                        </div>
                    </div>

                    <div class="mb-4"> <!-- Grupo de formulário com margem inferior maior -->
                        <label for="password" class="form-label">Senha</label> <!-- Label do campo de senha -->
                        <div class="input-group"> <!-- Input group para adicionar ícone -->
                            <span class="input-group-text"><i class="fas fa-lock"></i></span> <!-- Ícone de senha -->
                            <input type="password" class="form-control rounded-sm" id="password" name="password" placeholder="Sua senha" required> <!-- Input de senha com borda arredondada e placeholder -->
                            <div class="invalid-feedback">
                                Por favor, insira sua senha.
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mb-3"> <!-- Grid para o botão de login com gap e margem inferior -->
                        <button type="submit" class="btn btn-primary btn-lg rounded-sm">Entrar</button> <!-- Botão de login primário, grande e com borda arredondada -->
                    </div>

                    <div class="auth-links text-center mt-2"> <!-- Links de autenticação centralizados e com margem superior -->
                        <a href="index.php?route=forgot-password" class="text-decoration-none">Esqueceu sua senha?</a> <!-- Link para "Esqueceu sua senha?" sem sublinhado -->
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    /* Estilos Personalizados para a Página de Login */
    body.login-page {
        background-color: #f0f2f5; /* Cor de fundo geral da página de login */
    }

    .auth-container {
        border-radius: 10px; /* Borda arredondada para o container de autenticação */
    }

    .auth-header h3 {
        color: #343a40; /* Cor do título de login */
    }

    .auth-form .form-label {
        font-weight: 500; /* Peso da fonte dos labels do formulário */
        color: #495057;
    }

    .auth-form .form-control {
        border: 1px solid #ced4da; /* Borda dos inputs do formulário */
    }

    .auth-form .form-control:focus {
        border-color: #007bff; /* Cor da borda no foco dos inputs */
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25); /* Sombra no foco dos inputs */
    }

    .auth-form .input-group-text {
        background-color: #e9ecef; /* Cor de fundo dos ícones nos inputs */
        border: 1px solid #ced4da; /* Borda dos ícones nos inputs */
        color: #495057; /* Cor dos ícones nos inputs */
        border-right: none; /* Remover borda direita dos ícones para juntar com o input */
    }
     .auth-form .input-group > .form-control:not(:first-child),
     .auth-form .input-group > .form-select:not(:first-child) {
        border-left: none; /* Remover borda esquerda dos inputs para juntar com o ícone */
     }

    .auth-links a {
        color: #007bff; /* Cor dos links de autenticação */
    }

    .auth-links a:hover {
        color: #0056b3; /* Cor dos links de autenticação no hover */
    }

    /* Ajustes para telas menores (mobile) */
    @media (max-width: 768px) {
        .auth-container {
            padding: 2rem; /* Aumentar padding do container em mobile */
        }

        .auth-header h3 {
            font-size: 1.75rem; /* Reduzir tamanho do título em mobile */
        }

        .auth-header p {
            font-size: 1rem; /* Reduzir tamanho do subtítulo em mobile */
        }

        .auth-form .form-label {
            font-size: 1rem; /* Reduzir tamanho dos labels em mobile */
        }

        .auth-form .form-control {
            font-size: 1rem; /* Reduzir tamanho dos inputs em mobile */
            padding: 0.75rem 1rem; /* Ajustar padding dos inputs em mobile */
        }

        .auth-form .btn-lg {
            font-size: 1.1rem; /* Reduzir tamanho da fonte do botão em mobile */
        }
    }
</style>

<script>
    (function () {
        'use strict'

        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        var forms = document.querySelectorAll('.needs-validation')

        // Loop over them and prevent submission
        Array.prototype.slice.call(forms)
            .forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }

                    form.classList.add('was-validated')
                }, false)
            })
    })()
</script>