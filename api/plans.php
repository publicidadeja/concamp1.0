<?php
/**
 * API para obter planos disponíveis (temporário - dados estáticos)
 */

// Resposta padrão
$response = [
    'success' => true,
    'plans' => [],
    'error' => null
];

// Dados estáticos para teste
if ($_GET['type'] == 'car' && $_GET['term'] == '60') {
    $response['plans'] = [
        ['id' => 1, 'credit' => 10000, 'model' => 'Modelo A', 'first_installment' => 200, 'other_installments' => 180],
        ['id' => 2, 'credit' => 15000, 'model' => 'Modelo B', 'first_installment' => 300, 'other_installments' => 270],
    ];
} elseif ($_GET['type'] == 'motorcycle' && $_GET['term'] == '72') {
    $response['plans'] = [
        ['id' => 3, 'credit' => 5000, 'model' => 'Modelo C', 'first_installment' => 100, 'other_installments' => 90],
        ['id' => 4, 'credit' => 7500, 'model' => 'Modelo D', 'first_installment' => 150, 'other_installments' => 140],
    ];
}

// Enviar resposta
header('Content-Type: application/json');
echo json_encode($response);
exit;
