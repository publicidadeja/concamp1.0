const express = require('express');
const path = require('path');
const fs = require('fs');
const app = express();
const port = 3000;

// Servir arquivos estáticos
app.use(express.static(path.join(__dirname)));

// Rota para a página inicial
app.get('/', (req, res) => {
  // Ler o conteúdo do arquivo index.php
  fs.readFile(path.join(__dirname, 'index.php'), 'utf8', (err, data) => {
    if (err) {
      return res.status(500).send('Erro ao ler o arquivo index.php');
    }
    
    // Como não podemos processar PHP, vamos mostrar uma página HTML simples
    res.send(`
      <!DOCTYPE html>
      <html lang="pt-BR">
      <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ConCamp - Visualização</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="/assets/css/style.css" rel="stylesheet">
      </head>
      <body>
        <div class="container mt-5">
          <div class="alert alert-warning">
            <h4>Ambiente de Visualização</h4>
            <p>Este é um ambiente de visualização limitado. O sistema ConCamp requer um servidor PHP para funcionar completamente.</p>
            <p>Você está vendo uma versão estática dos arquivos.</p>
          </div>
          
          <div class="card">
            <div class="card-header">
              <h2>ConCamp - Sistema de Gestão de Contratos Premiados</h2>
            </div>
            <div class="card-body">
              <h5>Estrutura do Projeto:</h5>
              <ul>
                <li><strong>pages/</strong> - Páginas do sistema</li>
                <li><strong>includes/</strong> - Funções e componentes PHP</li>
                <li><strong>assets/</strong> - Arquivos CSS, JS e imagens</li>
                <li><strong>api/</strong> - Endpoints da API</li>
                <li><strong>actions/</strong> - Ações do sistema</li>
                <li><strong>install/</strong> - Script de instalação</li>
              </ul>
              
              <h5 class="mt-4">Para instalar o sistema completo:</h5>
              <p>Acesse a pasta <code>/install</code> em um ambiente com PHP e MySQL.</p>
              
              <div class="mt-4">
                <a href="/pages/home.php" class="btn btn-primary">Ver Página Inicial (Estática)</a>
                <a href="/pages/simulador.php" class="btn btn-secondary">Ver Simulador (Estático)</a>
              </div>
            </div>
          </div>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
      </body>
      </html>
    `);
  });
});

// Rota para servir outros arquivos PHP como HTML estático
app.get('*.php', (req, res) => {
  const filePath = path.join(__dirname, req.path);
  
  fs.readFile(filePath, 'utf8', (err, data) => {
    if (err) {
      return res.status(404).send('Arquivo não encontrado');
    }
    
    // Enviar o conteúdo do arquivo PHP como HTML
    res.send(`
      <!DOCTYPE html>
      <html lang="pt-BR">
      <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ConCamp - Visualização</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="/assets/css/style.css" rel="stylesheet">
      </head>
      <body>
        <div class="container mt-3">
          <div class="alert alert-warning">
            <p>Este é um ambiente de visualização limitado. O arquivo PHP não pode ser processado.</p>
          </div>
          
          <div class="card">
            <div class="card-header">
              <h4>Conteúdo do arquivo: ${req.path}</h4>
            </div>
            <div class="card-body">
              <pre class="bg-light p-3"><code>${data.replace(/</g, '<').replace(/>/g, '>')}</code></pre>
            </div>
          </div>
          
          <a href="/" class="btn btn-primary mt-3">Voltar para a página inicial</a>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
      </body>
      </html>
    `);
  });
});

app.listen(port, () => {
  console.log(`Servidor rodando em http://localhost:${port}`);
});
