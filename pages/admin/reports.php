<?php
/**
 * Relatórios do sistema
 */

// Título da página
$page_title = 'Relatórios';

// Verificar permissão
if (!isAdmin()) {
    include_once __DIR__ . '/../access-denied.php';
    exit;
}

// Determinar o tipo de relatório a ser exibido
$report_type = isset($_GET['type']) ? sanitize($_GET['type']) : 'performance';

// Filtros de data
$date_from = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : date('Y-m-d');

// Carregar dados com base no tipo de relatório
$conn = getConnection();

// Funções para gerar relatórios (implementadas no arquivo functions.php)
switch ($report_type) {
    case 'performance':
        $report_data = getSellerPerformanceReport($date_from, $date_to);
        break;
    
    case 'plans':
        $report_data = getPopularPlansReport($date_from, $date_to);
        break;
    
    case 'conversion':
        // Obter dados de conversão por dia
        $sql = "SELECT DATE(created_at) as date, status, COUNT(*) as count
                FROM leads
                WHERE DATE(created_at) BETWEEN :date_from AND :date_to
                GROUP BY DATE(created_at), status
                ORDER BY DATE(created_at) ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'date_from' => $date_from,
            'date_to' => $date_to
        ]);
        
        $conversion_data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!isset($conversion_data[$row['date']])) {
                $conversion_data[$row['date']] = [
                    'date' => $row['date'],
                    'total' => 0,
                    'new' => 0,
                    'contacted' => 0,
                    'negotiating' => 0,
                    'converted' => 0,
                    'lost' => 0
                ];
            }
            
            $conversion_data[$row['date']][$row['status']] = $row['count'];
            $conversion_data[$row['date']]['total'] += $row['count'];
        }
        
        $report_data = array_values($conversion_data);
        break;
    
    case 'sources':
        // Obter dados de fontes de leads
        $sql = "SELECT source, COUNT(*) as count
                FROM leads
                WHERE DATE(created_at) BETWEEN :date_from AND :date_to
                GROUP BY source
                ORDER BY count DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'date_from' => $date_from,
            'date_to' => $date_to
        ]);
        
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
    
    default:
        $report_data = [];
}

// Gerar token CSRF
$csrf_token = createCsrfToken();
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <ul class="nav nav-tabs card-header-tabs">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $report_type === 'performance' ? 'active' : ''; ?>" href="<?php echo url('index.php?route=admin-reports&type=performance'); ?>">
                            <i class="fas fa-chart-line me-2"></i>Desempenho dos Vendedores
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $report_type === 'plans' ? 'active' : ''; ?>" href="<?php echo url('index.php?route=admin-reports&type=plans'); ?>">
                            <i class="fas fa-list-alt me-2"></i>Planos Populares
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $report_type === 'conversion' ? 'active' : ''; ?>" href="<?php echo url('index.php?route=admin-reports&type=conversion'); ?>">
                            <i class="fas fa-funnel-dollar me-2"></i>Taxa de Conversão
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $report_type === 'sources' ? 'active' : ''; ?>" href="<?php echo url('index.php?route=admin-reports&type=sources'); ?>">
                            <i class="fas fa-project-diagram me-2"></i>Origem dos Leads
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <!-- Filtros -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <form method="get" action="<?php echo url('index.php'); ?>" class="form-inline">
                            <input type="hidden" name="route" value="admin-reports">
                            <input type="hidden" name="type" value="<?php echo $report_type; ?>">
                            
                            <div class="row g-2 align-items-center">
                                <div class="col-auto">
                                    <label for="date_from" class="form-label">Período:</label>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">De</span>
                                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Até</span>
                                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                                </div>
                                <div class="col-auto">
                                    <button type="button" class="btn btn-success btn-sm" id="exportCSV">
                                        <i class="fas fa-file-csv me-2"></i>Exportar CSV
                                    </button>
                                </div>
                                <div class="col-auto">
                                    <button type="button" class="btn btn-danger btn-sm" id="exportPDF">
                                        <i class="fas fa-file-pdf me-2"></i>Exportar PDF
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Conteúdo do Relatório -->
                <?php if ($report_type === 'performance'): ?>
                <!-- Relatório de Desempenho dos Vendedores -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="reportTable">
                        <thead>
                            <tr>
                                <th>Vendedor</th>
                                <th>Total de Leads</th>
                                <th>Em Negociação</th>
                                <th>Convertidos</th>
                                <th>Taxa de Conversão</th>
                                <th>Leads Perdidos</th>
                                <th>Novos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($report_data)): ?>
                            <tr>
                                <td colspan="7" class="text-center">Nenhum dado encontrado para o período selecionado.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($report_data as $row): ?>
                            <?php 
                                $conversion_rate = $row['total_leads'] > 0 ? round(($row['converted'] / $row['total_leads']) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><?php echo $row['name']; ?></td>
                                <td><?php echo $row['total_leads']; ?></td>
                                <td><?php echo $row['negotiating']; ?></td>
                                <td><?php echo $row['converted']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $conversion_rate; ?>%" aria-valuenow="<?php echo $conversion_rate; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <span><?php echo $conversion_rate; ?>%</span>
                                    </div>
                                </td>
                                <td><?php echo $row['lost']; ?></td>
                                <td><?php echo $row['new_leads']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php elseif ($report_type === 'plans'): ?>
                <!-- Relatório de Planos Populares -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="reportTable">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Modelo</th>
                                <th>Valor do Crédito</th>
                                <th>Prazo</th>
                                <th>Quantidade</th>
                                <th>Percentual</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($report_data)): ?>
                            <tr>
                                <td colspan="6" class="text-center">Nenhum dado encontrado para o período selecionado.</td>
                            </tr>
                            <?php else: ?>
                            <?php 
                                $total_count = array_sum(array_column($report_data, 'count'));
                                foreach ($report_data as $row): 
                                $percentage = $total_count > 0 ? round(($row['count'] / $total_count) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><?php echo $row['plan_type'] === 'car' ? 'Carro' : 'Moto'; ?></td>
                                <td><?php echo $row['plan_model'] ?: 'Não especificado'; ?></td>
                                <td>R$ <?php echo formatCurrency($row['plan_credit']); ?></td>
                                <td><?php echo $row['plan_term']; ?> meses</td>
                                <td><?php echo $row['count']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                            <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $percentage; ?>%" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <span><?php echo $percentage; ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php elseif ($report_type === 'conversion'): ?>
                <!-- Relatório de Taxa de Conversão -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="reportTable">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Total de Leads</th>
                                <th>Novos</th>
                                <th>Contactados</th>
                                <th>Em Negociação</th>
                                <th>Convertidos</th>
                                <th>Perdidos</th>
                                <th>Taxa de Conversão</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($report_data)): ?>
                            <tr>
                                <td colspan="8" class="text-center">Nenhum dado encontrado para o período selecionado.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($report_data as $row): ?>
                            <?php 
                                $conversion_rate = $row['total'] > 0 ? round(($row['converted'] / $row['total']) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><?php echo formatDate($row['date']); ?></td>
                                <td><?php echo $row['total']; ?></td>
                                <td><?php echo $row['new']; ?></td>
                                <td><?php echo $row['contacted']; ?></td>
                                <td><?php echo $row['negotiating']; ?></td>
                                <td><?php echo $row['converted']; ?></td>
                                <td><?php echo $row['lost']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $conversion_rate; ?>%" aria-valuenow="<?php echo $conversion_rate; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <span><?php echo $conversion_rate; ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php elseif ($report_type === 'sources'): ?>
                <!-- Relatório de Fontes de Leads -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="reportTable">
                        <thead>
                            <tr>
                                <th>Origem</th>
                                <th>Quantidade</th>
                                <th>Percentual</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($report_data)): ?>
                            <tr>
                                <td colspan="3" class="text-center">Nenhum dado encontrado para o período selecionado.</td>
                            </tr>
                            <?php else: ?>
                            <?php 
                                $total_count = array_sum(array_column($report_data, 'count'));
                                foreach ($report_data as $row): 
                                $percentage = $total_count > 0 ? round(($row['count'] / $total_count) * 100, 1) : 0;
                                $source_name = !empty($row['source']) ? $row['source'] : 'Não especificado';
                            ?>
                            <tr>
                                <td><?php echo $source_name; ?></td>
                                <td><?php echo $row['count']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $percentage; ?>%" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <span><?php echo $percentage; ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Gráficos -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Visualização Gráfica</h5>
            </div>
            <div class="card-body">
                <canvas id="mainChart" width="400" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Distribuição</h5>
            </div>
            <div class="card-body">
                <canvas id="secondaryChart" width="400" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Exportar para CSV
    document.getElementById('exportCSV').addEventListener('click', function() {
        const table = document.getElementById('reportTable');
        if (!table) return;
        
        let csv = [];
        const rows = table.querySelectorAll('tr');
        
        for (let i = 0; i < rows.length; i++) {
            const row = [], cols = rows[i].querySelectorAll('td, th');
            
            for (let j = 0; j < cols.length; j++) {
                // Remover tags HTML e obter apenas o texto
                let text = cols[j].innerText.replace(/"/g, '""');
                row.push('"' + text + '"');
            }
            
            csv.push(row.join(','));
        }
        
        const csvContent = csv.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.setAttribute('hidden', '');
        a.setAttribute('href', url);
        a.setAttribute('download', 'relatorio_<?php echo $report_type; ?>_<?php echo date('Y-m-d'); ?>.csv');
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    });
    
    // Exportar para PDF (simulação - na prática precisaria de uma biblioteca como jsPDF)
    document.getElementById('exportPDF').addEventListener('click', function() {
        alert('Para exportar para PDF, é necessário implementar uma biblioteca como jsPDF. Por favor, use a exportação CSV por enquanto.');
    });
    
    // Configuração dos gráficos com Chart.js
    <?php if ($report_type === 'performance' && !empty($report_data)): ?>
    // Gráfico de desempenho dos vendedores
    const mainCtx = document.getElementById('mainChart').getContext('2d');
    new Chart(mainCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($report_data, 'name')); ?>,
            datasets: [{
                label: 'Leads Convertidos',
                data: <?php echo json_encode(array_column($report_data, 'converted')); ?>,
                backgroundColor: 'rgba(40, 167, 69, 0.7)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 1
            }, {
                label: 'Em Negociação',
                data: <?php echo json_encode(array_column($report_data, 'negotiating')); ?>,
                backgroundColor: 'rgba(255, 193, 7, 0.7)',
                borderColor: 'rgba(255, 193, 7, 1)',
                borderWidth: 1
            }, {
                label: 'Perdidos',
                data: <?php echo json_encode(array_column($report_data, 'lost')); ?>,
                backgroundColor: 'rgba(220, 53, 69, 0.7)',
                borderColor: 'rgba(220, 53, 69, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Desempenho por Vendedor'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Gráfico de distribuição de status
    const secondaryCtx = document.getElementById('secondaryChart').getContext('2d');
    new Chart(secondaryCtx, {
        type: 'doughnut',
        data: {
            labels: ['Convertidos', 'Em Negociação', 'Contactados', 'Novos', 'Perdidos'],
            datasets: [{
                data: [
                    <?php echo array_sum(array_column($report_data, 'converted')); ?>,
                    <?php echo array_sum(array_column($report_data, 'negotiating')); ?>,
                    <?php echo array_sum(array_column($report_data, 'contacted')); ?>,
                    <?php echo array_sum(array_column($report_data, 'new_leads')); ?>,
                    <?php echo array_sum(array_column($report_data, 'lost')); ?>
                ],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.7)',
                    'rgba(255, 193, 7, 0.7)',
                    'rgba(0, 123, 255, 0.7)',
                    'rgba(108, 117, 125, 0.7)',
                    'rgba(220, 53, 69, 0.7)'
                ],
                borderColor: [
                    'rgba(40, 167, 69, 1)',
                    'rgba(255, 193, 7, 1)',
                    'rgba(0, 123, 255, 1)',
                    'rgba(108, 117, 125, 1)',
                    'rgba(220, 53, 69, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Distribuição de Status'
                }
            }
        }
    });
    
    <?php elseif ($report_type === 'plans' && !empty($report_data)): ?>
    // Gráfico de planos populares
    const mainCtx = document.getElementById('mainChart').getContext('2d');
    new Chart(mainCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_map(function($row) {
                return ($row['plan_type'] === 'car' ? 'Carro' : 'Moto') . ' - R$ ' . number_format($row['plan_credit'], 2, ',', '.');
            }, $report_data)); ?>,
            datasets: [{
                label: 'Quantidade',
                data: <?php echo json_encode(array_column($report_data, 'count')); ?>,
                backgroundColor: 'rgba(23, 162, 184, 0.7)',
                borderColor: 'rgba(23, 162, 184, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Planos Mais Populares'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Gráfico de distribuição por tipo
    const secondaryCtx = document.getElementById('secondaryChart').getContext('2d');
    new Chart(secondaryCtx, {
        type: 'pie',
        data: {
            labels: ['Carro', 'Moto'],
            datasets: [{
                data: [
                    <?php 
                    $car_count = array_sum(array_map(function($row) {
                        return $row['plan_type'] === 'car' ? $row['count'] : 0;
                    }, $report_data));
                    
                    $moto_count = array_sum(array_map(function($row) {
                        return $row['plan_type'] === 'motorcycle' ? $row['count'] : 0;
                    }, $report_data));
                    
                    echo $car_count . ', ' . $moto_count;
                    ?>
                ],
                backgroundColor: [
                    'rgba(0, 123, 255, 0.7)',
                    'rgba(255, 193, 7, 0.7)'
                ],
                borderColor: [
                    'rgba(0, 123, 255, 1)',
                    'rgba(255, 193, 7, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Distribuição por Tipo de Veículo'
                }
            }
        }
    });
    
    <?php elseif ($report_type === 'conversion' && !empty($report_data)): ?>
    // Gráfico de taxa de conversão
    const mainCtx = document.getElementById('mainChart').getContext('2d');
    new Chart(mainCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_map(function($row) {
                return date('d/m/Y', strtotime($row['date']));
            }, $report_data)); ?>,
            datasets: [{
                label: 'Total de Leads',
                data: <?php echo json_encode(array_column($report_data, 'total')); ?>,
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                borderColor: 'rgba(0, 123, 255, 1)',
                borderWidth: 2,
                fill: true
            }, {
                label: 'Convertidos',
                data: <?php echo json_encode(array_column($report_data, 'converted')); ?>,
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 2,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Evolução de Leads e Conversões'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Gráfico de distribuição de status
    const secondaryCtx = document.getElementById('secondaryChart').getContext('2d');
    new Chart(secondaryCtx, {
        type: 'doughnut',
        data: {
            labels: ['Novos', 'Contactados', 'Em Negociação', 'Convertidos', 'Perdidos'],
            datasets: [{
                data: [
                    <?php echo array_sum(array_column($report_data, 'new')); ?>,
                    <?php echo array_sum(array_column($report_data, 'contacted')); ?>,
                    <?php echo array_sum(array_column($report_data, 'negotiating')); ?>,
                    <?php echo array_sum(array_column($report_data, 'converted')); ?>,
                    <?php echo array_sum(array_column($report_data, 'lost')); ?>
                ],
                backgroundColor: [
                    'rgba(108, 117, 125, 0.7)',
                    'rgba(0, 123, 255, 0.7)',
                    'rgba(255, 193, 7, 0.7)',
                    'rgba(40, 167, 69, 0.7)',
                    'rgba(220, 53, 69, 0.7)'
                ],
                borderColor: [
                    'rgba(108, 117, 125, 1)',
                    'rgba(0, 123, 255, 1)',
                    'rgba(255, 193, 7, 1)',
                    'rgba(40, 167, 69, 1)',
                    'rgba(220, 53, 69, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Distribuição por Status'
                }
            }
        }
    });
    
    <?php elseif ($report_type === 'sources' && !empty($report_data)): ?>
    // Gráfico de fontes de leads
    const mainCtx = document.getElementById('mainChart').getContext('2d');
    new Chart(mainCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_map(function($row) {
                return !empty($row['source']) ? $row['source'] : 'Não especificado';
            }, $report_data)); ?>,
            datasets: [{
                label: 'Quantidade',
                data: <?php echo json_encode(array_column($report_data, 'count')); ?>,
                backgroundColor: 'rgba(0, 123, 255, 0.7)',
                borderColor: 'rgba(0, 123, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Leads por Origem'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Gráfico de distribuição por origem
    const secondaryCtx = document.getElementById('secondaryChart').getContext('2d');
    new Chart(secondaryCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode(array_map(function($row) {
                return !empty($row['source']) ? $row['source'] : 'Não especificado';
            }, $report_data)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($report_data, 'count')); ?>,
                backgroundColor: [
                    'rgba(0, 123, 255, 0.7)',
                    'rgba(40, 167, 69, 0.7)',
                    'rgba(255, 193, 7, 0.7)',
                    'rgba(220, 53, 69, 0.7)',
                    'rgba(23, 162, 184, 0.7)',
                    'rgba(108, 117, 125, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Distribuição por Origem'
                }
            }
        }
    });
    <?php endif; ?>
});
</script>
