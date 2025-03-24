<?php
/**
 * Script para corrigir problemas de CSS
 * Este arquivo é incluído após o tema personalizado para garantir que certas regras sejam aplicadas
 */

// Definir cabeçalho como CSS
header('Content-Type: text/css');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Obtemos o timestamp atual para forçar recarregamento
$timestamp = time();
?>
/* CSS de correção carregado em <?php echo date('Y-m-d H:i:s', $timestamp); ?> */

/* 
 * Sobreposições para garantir que o tema personalizado seja aplicado corretamente
 * Este arquivo tem precedência sobre todos os outros
 */

/* Adicionar marcador especial ao body para aumentar a especificidade do seletor */
body.theme-applied {
}

/* Definir cores diretamente */
body .btn-primary,
html body .btn-primary,
.btn-primary {
    background-color: #00053c !important;
    border-color: #00053c !important;
    color: #ffffff !important;
}

/* Reforçar aplicação de cores secundárias */
body .btn-secondary,
html body .btn-secondary,
.btn-secondary {
    background-color: #4e5055 !important;
    border-color: #4e5055 !important;
    color: #ffffff !important;
}

/* Definir estilos para botões outline */
body .btn-outline-primary,
html body .btn-outline-primary,
.btn-outline-primary {
    color: #00053c !important;
    border-color: #00053c !important;
    background-color: transparent !important;
}

body .btn-outline-primary:hover,
html body .btn-outline-primary:hover,
.btn-outline-primary:hover,
body .btn-outline-primary:focus,
html body .btn-outline-primary:focus,
.btn-outline-primary:focus,
body .btn-outline-primary:active,
html body .btn-outline-primary:active,
.btn-outline-primary:active {
    background-color: #00053c !important;
    color: #ffffff !important;
    border-color: #00053c !important;
}

/* Reforçar aplicação de cor do cabeçalho */
body .navbar {
    background-color: var(--header-bg) !important;
    color: var(--header-text) !important;
}

body .navbar a,
body .navbar .nav-link {
    color: var(--header-text) !important;
}

/* Links com prioridade máxima - cor definida diretamente */
body a:not(.btn),
html body a:not(.btn) {
    color: #00053c !important;
}

body a:not(.btn):hover,
html body a:not(.btn):hover {
    color: #000963 !important; /* Versão ligeiramente mais clara */
}