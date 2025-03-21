<?php
/**
 * Teste da exibição da seção de Ganhadores da Landing Page
 */

// Incluir arquivos necessários
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Dados simulados para um vendedor e seus ganhadores
$winners = [
    [
        'id' => 1,
        'name' => 'João Silva',
        'vehicle_model' => 'Honda Civic LX',
        'credit_amount' => 60000.00,
        'contemplation_date' => '2025-02-15',
        'photo' => 'uploads/winners/1742485085_Design-sem-nome-26.jpg',
        'status' => 'active',
        'created_at' => '2025-03-18 14:30:00'
    ],
    [
        'id' => 2,
        'name' => 'Maria Souza',
        'vehicle_model' => 'Toyota Corolla',
        'credit_amount' => 75000.00,
        'contemplation_date' => '2025-03-01',
        'photo' => null,
        'status' => 'active',
        'created_at' => '2025-03-19 10:15:00'
    ],
    [
        'id' => 3,
        'name' => 'Carlos Pereira',
        'vehicle_model' => 'Fiat Strada',
        'credit_amount' => 48000.00,
        'contemplation_date' => '2025-03-10',
        'photo' => 'uploads/winners/1742581137_WhatsApp Image 2025-03-21 at 09.49.20.jpeg',
        'status' => 'active',
        'created_at' => '2025-03-20 16:45:00'
    ]
];

?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Exibição de Ganhadores</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            padding-top: 2rem;
            background-color: #f5f7fa;
        }
        
        h1, h2 {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .test-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .test-section {
            background-color: white;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 3rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .lp-winner-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            height: 100%;
        }
        
        .lp-winner-image {
            height: 200px;
            overflow: hidden;
        }
        
        .lp-winner-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .lp-winner-card:hover .lp-winner-image img {
            transform: scale(1.1);
        }
        
        .lp-winner-content {
            padding: 20px;
        }
        
        .lp-winner-title {
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .lp-winner-desc {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .lp-winner-date {
            color: #999;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
        }
        
        .lp-winner-date i {
            margin-right: 5px;
        }
        
        /* Estilos para a versão da página do vendedor */
        .preview-bg {
            background-color: #f5f7fa;
            padding: 20px;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>Teste de Exibição de Ganhadores</h1>
        
        <div class="test-section">
            <h2>1. Layout da Landing Page Pública</h2>
            <p class="text-center mb-4">Este é o layout como aparece na landing page pública (referência: <code>landing-page.php</code>)</p>
            
            <div class="row">
                <?php foreach ($winners as $winner): ?>
                <div class="col-md-4">
                    <div class="lp-winner-card">
                        <div class="lp-winner-image">
                            <?php if (!empty($winner['photo']) && file_exists(__DIR__ . '/../' . $winner['photo'])): ?>
                            <img src="<?php echo url($winner['photo']); ?>" alt="<?php echo htmlspecialchars($winner['name']); ?>">
                            <?php else: ?>
                            <div style="width: 100%; height: 100%; background-color: #f5f5f5; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                                <div style="font-size: 2rem; margin-bottom: 10px; color: #aaa;">🚗</div>
                                <div style="color: #666; text-align: center; padding: 0 20px;">Veículo Contemplado</div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="lp-winner-content">
                            <h4 class="lp-winner-title"><?php echo htmlspecialchars($winner['name']); ?></h4>
                            <p class="lp-winner-desc"><?php echo htmlspecialchars($winner['vehicle_model']); ?> - R$ <?php echo number_format($winner['credit_amount'], 2, ',', '.'); ?></p>
                            <div class="lp-winner-date">
                                <i class="far fa-calendar-alt"></i>
                                <?php echo date('d/m/Y', strtotime($winner['contemplation_date'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="test-section">
            <h2>2. Preview na Página do Vendedor</h2>
            <p class="text-center mb-4">Este é o layout de prévia como aparece na página de gerenciamento do vendedor (referência: <code>seller/landing-page.php</code>)</p>
            
            <div class="row mb-4" style="background-color: #f5f7fa; padding: 20px; border-radius: 8px;">
                <?php foreach ($winners as $index => $winner): ?>
                    <?php if ($index < 3): // Mostrar apenas os 3 primeiros na prévia ?>
                    <div class="col-md-4 mb-3">
                        <div class="lp-winner-card" style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); height: 100%;">
                            <div class="lp-winner-image" style="height: 200px; overflow: hidden;">
                                <?php if (!empty($winner['photo']) && file_exists(__DIR__ . '/../' . $winner['photo'])): ?>
                                <img src="<?php echo url($winner['photo']); ?>" alt="<?php echo htmlspecialchars($winner['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                <div style="width: 100%; height: 100%; background-color: #f5f5f5; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                                    <div style="font-size: 2rem; margin-bottom: 10px; color: #aaa;">🚗</div>
                                    <div style="color: #666; text-align: center; padding: 0 20px;">Veículo Contemplado</div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="lp-winner-content" style="padding: 20px;">
                                <h4 class="lp-winner-title" style="font-weight: 600; margin-bottom: 10px;"><?php echo htmlspecialchars($winner['name']); ?></h4>
                                <p class="lp-winner-desc" style="color: #666; font-size: 0.9rem; margin-bottom: 15px;"><?php echo htmlspecialchars($winner['vehicle_model']); ?> - R$ <?php echo number_format($winner['credit_amount'], 2, ',', '.'); ?></p>
                                <div class="lp-winner-date" style="color: #999; font-size: 0.85rem; display: flex; align-items: center;">
                                    <i class="far fa-calendar-alt" style="margin-right: 5px;"></i>
                                    <?php echo date('d/m/Y', strtotime($winner['contemplation_date'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <h3 class="mb-3">Tabela de Gestão</h3>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Veículo</th>
                            <th>Valor do Crédito</th>
                            <th>Data da Contemplação</th>
                            <th>Foto</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($winners as $winner): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($winner['name']); ?></td>
                            <td><?php echo htmlspecialchars($winner['vehicle_model']); ?></td>
                            <td>R$ <?php echo number_format($winner['credit_amount'], 2, ',', '.'); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($winner['contemplation_date'])); ?></td>
                            <td>
                                <?php if (!empty($winner['photo'])): ?>
                                <img src="<?php echo url($winner['photo']); ?>" alt="Foto" width="70" height="50" style="object-fit: cover;" class="rounded">
                                <?php else: ?>
                                <span class="badge bg-secondary">Sem foto</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="test-section">
            <h2>3. Visualização em diferentes tamanhos de tela</h2>
            <p class="text-center mb-4">Verifique como os elementos se comportam em diferentes dispositivos</p>
            
            <div class="row">
                <div class="col-12 mb-4">
                    <button id="desktop-view" class="btn btn-primary me-2">Desktop</button>
                    <button id="tablet-view" class="btn btn-primary me-2">Tablet</button>
                    <button id="mobile-view" class="btn btn-primary">Mobile</button>
                </div>
            </div>
            
            <div id="responsive-preview" class="desktop">
                <div class="row">
                    <?php foreach ($winners as $index => $winner): ?>
                    <?php if ($index < 3): // Mostrar apenas os 3 primeiros ?>
                    <div class="col-md-4 mb-3">
                        <div class="lp-winner-card">
                            <div class="lp-winner-image">
                                <?php if (!empty($winner['photo']) && file_exists(__DIR__ . '/../' . $winner['photo'])): ?>
                                <img src="<?php echo url($winner['photo']); ?>" alt="<?php echo htmlspecialchars($winner['name']); ?>">
                                <?php else: ?>
                                <div style="width: 100%; height: 100%; background-color: #f5f5f5; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                                    <div style="font-size: 2rem; margin-bottom: 10px; color: #aaa;">🚗</div>
                                    <div style="color: #666; text-align: center; padding: 0 20px;">Veículo Contemplado</div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="lp-winner-content">
                                <h4 class="lp-winner-title"><?php echo htmlspecialchars($winner['name']); ?></h4>
                                <p class="lp-winner-desc"><?php echo htmlspecialchars($winner['vehicle_model']); ?> - R$ <?php echo number_format($winner['credit_amount'], 2, ',', '.'); ?></p>
                                <div class="lp-winner-date">
                                    <i class="far fa-calendar-alt"></i>
                                    <?php echo date('d/m/Y', strtotime($winner['contemplation_date'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Elementos para visualização responsiva
            const responsivePreview = document.getElementById('responsive-preview');
            const desktopBtn = document.getElementById('desktop-view');
            const tabletBtn = document.getElementById('tablet-view');
            const mobileBtn = document.getElementById('mobile-view');
            
            // Eventos para botões de visualização
            desktopBtn.addEventListener('click', function() {
                responsivePreview.className = 'desktop';
                responsivePreview.style.maxWidth = '100%';
            });
            
            tabletBtn.addEventListener('click', function() {
                responsivePreview.className = 'tablet';
                responsivePreview.style.maxWidth = '768px';
                responsivePreview.style.margin = '0 auto';
            });
            
            mobileBtn.addEventListener('click', function() {
                responsivePreview.className = 'mobile';
                responsivePreview.style.maxWidth = '375px';
                responsivePreview.style.margin = '0 auto';
            });
        });
    </script>
</body>
</html>