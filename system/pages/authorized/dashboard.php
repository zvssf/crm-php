<?php

require_once SYSTEM . '/main-data.php';

$page_title = 'Статистика';
require_once SYSTEM . '/layouts/head.php';
?>

<?php
try {
    $pdo = db_connect();
    $data = []; // Массив для хранения всех статистических данных
    $user_group = (int) $user_data['user_group'];
    $user_id = (int) $user_data['user_id'];

    // Определяем временные рамки
    $today_start = date('Y-m-d 00:00:00');
    $month_start = date('Y-m-01 00:00:00');

    // --- ОБЩИЕ ЗАПРОСЫ ДЛЯ ДИРЕКТОРА И БУХГАЛТЕРА ---
    if ($user_group === 1 || $user_group === 2) {
        $data['total_cash_balance'] = $pdo->query("SELECT COALESCE(SUM(balance), 0) FROM `fin_cashes` WHERE `status` = 1")->fetchColumn();
        $data['total_agents_debt'] = $pdo->query("SELECT COALESCE(SUM(user_balance), 0) FROM `users` WHERE user_group = 4 AND user_balance < 0")->fetchColumn();
    }

    // --- ЗАПРОСЫ ДЛЯ ДИРЕКТОРА ---
    if ($user_group === 1) {
        $data['clients_on_review'] = $pdo->query("SELECT COALESCE(COUNT(client_id), 0) FROM `clients` WHERE client_status = 5")->fetchColumn();
        
        $stmt_sales = $pdo->prepare("SELECT COALESCE(SUM(sale_price), 0) FROM `clients` WHERE client_status = 2 AND updated_at >= :today_start");
        $stmt_sales->execute([':today_start' => $today_start]);
        $data['sales_today'] = $stmt_sales->fetchColumn();

        $data['suppliers_balance'] = $pdo->query("SELECT id, name, balance FROM `fin_suppliers` WHERE `status` = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // --- ЗАПРОСЫ ДЛЯ БУХГАЛТЕРА ---
    if ($user_group === 2) {
        $data['total_suppliers_balance'] = $pdo->query("SELECT COALESCE(SUM(balance), 0) FROM `fin_suppliers` WHERE `status` = 1")->fetchColumn();
        
        $stmt_trans = $pdo->prepare("SELECT COALESCE(COUNT(id), 0) FROM `fin_transactions` WHERE transaction_date >= :today_start");
        $stmt_trans->execute([':today_start' => $today_start]);
        $data['transactions_today'] = $stmt_trans->fetchColumn();
    }

    // --- ЗАПРОСЫ ДЛЯ МЕНЕДЖЕРА ---
    if ($user_group === 3) {
        $stmt_agents = $pdo->prepare("SELECT user_id FROM `users` WHERE user_group = 4 AND user_status = 1 AND user_supervisor = :user_id");
        $stmt_agents->execute([':user_id' => $user_id]);
        $team_agent_ids = $stmt_agents->fetchAll(PDO::FETCH_COLUMN);
        $data['team_agent_count'] = count($team_agent_ids);

        $data['team_debt'] = 0;
        $data['team_sales_month'] = 0;
        if (!empty($team_agent_ids)) {
            $placeholders = implode(',', array_fill(0, count($team_agent_ids), '?'));
            $stmt_debt = $pdo->prepare("SELECT COALESCE(SUM(user_balance), 0) FROM `users` WHERE user_id IN ($placeholders) AND user_balance < 0");
            $stmt_debt->execute($team_agent_ids);
            $data['team_debt'] = $stmt_debt->fetchColumn();

            $stmt_sales = $pdo->prepare("SELECT COALESCE(SUM(sale_price), 0) FROM `clients` WHERE client_status = 2 AND agent_id IN ($placeholders) AND updated_at >= ?");
            $stmt_sales->execute(array_merge($team_agent_ids, [$month_start]));
            $data['team_sales_month'] = $stmt_sales->fetchColumn();
        }
        $stmt_review = $pdo->prepare("SELECT COALESCE(COUNT(client_id), 0) FROM `clients` WHERE client_status = 6 AND agent_id IN (SELECT user_id FROM users WHERE user_supervisor = :user_id)");
        $stmt_review->execute([':user_id' => $user_id]);
        $data['team_clients_on_review'] = $stmt_review->fetchColumn();
    }

    // --- ЗАПРОСЫ ДЛЯ АГЕНТА ---
    if ($user_group === 4) {
        $stmt_sales = $pdo->prepare("SELECT COALESCE(SUM(sale_price), 0) FROM `clients` WHERE client_status = 2 AND agent_id = :user_id AND updated_at >= :month_start");
        $stmt_sales->execute([':user_id' => $user_id, ':month_start' => $month_start]);
        $data['my_sales_month'] = $stmt_sales->fetchColumn();
        
        $stmt_rejected = $pdo->prepare("SELECT COALESCE(COUNT(client_id), 0) FROM `clients` WHERE client_status = 3 AND agent_id = :user_id AND rejection_reason IS NOT NULL AND rejection_reason != ''");
        $stmt_rejected->execute([':user_id' => $user_id]);
        $data['my_rejected_clients'] = $stmt_rejected->fetchColumn();

        $stmt_active = $pdo->prepare("SELECT COALESCE(COUNT(client_id), 0) FROM `clients` WHERE client_status = 1 AND agent_id = :user_id");
        $stmt_active->execute([':user_id' => $user_id]);
        $data['my_active_clients'] = $stmt_active->fetchColumn();
    }

} catch (PDOException $e) {
    error_log('DB Error on Dashboard: ' . $e->getMessage());
    $data = []; // В случае ошибки оставляем массив пустым, чтобы страница не "упала"
}
?>

<body>
        <!-- Begin page -->
        <div class="wrapper">

        <?php
        require_once SYSTEM . '/layouts/menu.php';
        ?>
            
            

            <!-- ============================================================== -->
            <!-- Start Page Content here -->
            <!-- ============================================================== -->

            <div class="content-page">
                <div class="content">

                    <!-- Start Content-->
                    <div class="container-fluid">

                        <!-- start page title -->
                        <div class="row">
                            <div class="col-12">
                                <div class="page-title-box">
                                    <h4 class="page-title">Статистика</h4>
                                </div>
                            </div>
                        </div>
                        <!-- end page title -->

                        <?php if ($user_data['user_group'] == 1) : // ================= ДЛЯ ДИРЕКТОРА ================= ?>
                        <div class="row">
                            <div class="col-xl-3 col-lg-6">
                                <div class="card widget-flat">
                                    <div class="card-body">
                                        <div class="float-end">
                                            <i class="uil-wallet widget-icon bg-primary-lighten text-primary"></i>
                                        </div>
                                        <h5 class="text-muted fw-normal mt-0" title="Общая сумма в кассах">В кассах</h5>
                                        <h3 class="mt-3 mb-3"><?= number_format($data['total_cash_balance'] ?? 0, 2, '.', ' ') ?> $</h3>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-3 col-lg-6">
                                <div class="card widget-flat">
                                    <div class="card-body">
                                        <div class="float-end">
                                            <i class="uil-bill widget-icon bg-danger-lighten text-danger"></i>
                                        </div>
                                        <h5 class="text-muted fw-normal mt-0" title="Общий долг агентов">Долг агентов</h5>
                                        <h3 class="mt-3 mb-3"><?= number_format($data['total_agents_debt'] ?? 0, 2, '.', ' ') ?> $</h3>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-3 col-lg-6">
                                <div class="card widget-flat">
                                    <div class="card-body">
                                        <div class="float-end">
                                            <i class="uil-file-question-alt widget-icon bg-warning-lighten text-warning"></i>
                                        </div>
                                        <h5 class="text-muted fw-normal mt-0" title="Анкеты на рассмотрении">На рассмотрении</h5>
                                        <h3 class="mt-3 mb-3"><?= $data['clients_on_review'] ?? 0 ?></h3>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-3 col-lg-6">
                                <div class="card widget-flat">
                                    <div class="card-body">
                                        <div class="float-end">
                                            <i class="uil-chart-line widget-icon bg-success-lighten text-success"></i>
                                        </div>
                                        <h5 class="text-muted fw-normal mt-0" title="Продажи за сегодня">Продажи сегодня</h5>
                                        <h3 class="mt-3 mb-3"><?= number_format($data['sales_today'] ?? 0, 2, '.', ' ') ?> $</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-xl-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="header-title mb-3"><i class="uil-truck me-1"></i>Балансы поставщиков</h4>
                                        
                                        <?php if (!empty($data['suppliers_balance'])): ?>
                                            <div class="table-responsive">
                                                <table class="table table-centered table-nowrap table-hover mb-0">
                                                    <tbody>
                                                        <?php foreach($data['suppliers_balance'] as $supplier): ?>
                                                        <tr>
                                                            <td>
                                                                <h5 class="font-14 my-1 fw-normal"><?= valid($supplier['name']) ?></h5>
                                                            </td>
                                                            <td>
                                                                <span class="font-13 fw-semibold <?= ($supplier['balance'] >= 0) ? 'text-success' : 'text-danger' ?>">
                                                                    <?= number_format($supplier['balance'], 2, '.', ' ') ?> $
                                                                </span>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div> <!-- end table-responsive-->
                                        <?php else: ?>
                                            <p class="text-muted">Поставщики не найдены.</p>
                                        <?php endif; ?>
                                    </div> <!-- end card-body-->
                                </div> <!-- end card-->
                            </div> <!-- end col-->
                        </div>

                        <?php elseif ($user_data['user_group'] == 2) : // ================= ДЛЯ БУХГАЛТЕРА ================= ?>
                        <div class="row">
                            <div class="col-xl-3 col-lg-6">
                                <div class="card widget-flat">
                                    <div class="card-body">
                                        <div class="float-end">
                                            <i class="uil-wallet widget-icon bg-primary-lighten text-primary"></i>
                                        </div>
                                        <h5 class="text-muted fw-normal mt-0" title="Общая сумма в кассах">В кассах</h5>
                                        <h3 class="mt-3 mb-3"><?= number_format($data['total_cash_balance'] ?? 0, 2, '.', ' ') ?> $</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-6">
                                <div class="card widget-flat">
                                    <div class="card-body">
                                        <div class="float-end">
                                            <i class="uil-bill widget-icon bg-danger-lighten text-danger"></i>
                                        </div>
                                        <h5 class="text-muted fw-normal mt-0" title="Общий долг агентов">Долг агентов</h5>
                                        <h3 class="mt-3 mb-3"><?= number_format($data['total_agents_debt'] ?? 0, 2, '.', ' ') ?> $</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-6">
                                <div class="card widget-flat">
                                    <div class="card-body">
                                        <div class="float-end">
                                            <i class="uil-truck widget-icon bg-info-lighten text-info"></i>
                                        </div>
                                        <h5 class="text-muted fw-normal mt-0" title="Общий баланс поставщиков">Баланс поставщиков</h5>
                                        <h3 class="mt-3 mb-3"><?= number_format($data['total_suppliers_balance'] ?? 0, 2, '.', ' ') ?> $</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-6">
                                <div class="card widget-flat">
                                    <div class="card-body">
                                        <div class="float-end">
                                            <i class="uil-exchange-alt widget-icon bg-secondary-lighten text-secondary"></i>
                                        </div>
                                        <h5 class="text-muted fw-normal mt-0" title="Транзакций за сегодня">Транзакций сегодня</h5>
                                        <h3 class="mt-3 mb-3"><?= $data['transactions_today'] ?? 0 ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php elseif ($user_data['user_group'] == 3) : // ================= ДЛЯ МЕНЕДЖЕРА ================= ?>
                        <div class="row">
                             <div class="col-xl-3 col-lg-6">
                                <div class="card widget-flat">
                                    <div class="card-body">
                                        <div class="float-end">
                                            <i class="uil-users-alt widget-icon bg-primary-lighten text-primary"></i>
                                        </div>
                                        <h5 class="text-muted fw-normal mt-0" title="Агентов в команде">Агентов в команде</h5>
                                        <h3 class="mt-3 mb-3"><?= $data['team_agent_count'] ?? 0 ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-6">
                                <div class="card widget-flat">
                                    <div class="card-body">
                                        <div class="float-end">
                                            <i class="uil-bill widget-icon bg-danger-lighten text-danger"></i>
                                        </div>
                                        <h5 class="text-muted fw-normal mt-0" title="Общий долг команды">Долг команды</h5>
                                        <h3 class="mt-3 mb-3"><?= number_format($data['team_debt'] ?? 0, 2, '.', ' ') ?> $</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-6">
                                <div class="card widget-flat">
                                    <div class="card-body">
                                        <div class="float-end">
                                            <i class="uil-chart-pie widget-icon bg-success-lighten text-success"></i>
                                        </div>
                                        <h5 class="text-muted fw-normal mt-0" title="Продажи команды (за месяц)">Продажи (мес.)</h5>
                                        <h3 class="mt-3 mb-3"><?= number_format($data['team_sales_month'] ?? 0, 2, '.', ' ') ?> $</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-6">
                                <div class="card widget-flat">
                                    <div class="card-body">
                                        <div class="float-end">
                                            <i class="uil-file-question-alt widget-icon bg-warning-lighten text-warning"></i>
                                        </div>
                                        <h5 class="text-muted fw-normal mt-0" title="Анкеты на рассмотрении">На рассмотрении</h5>
                                        <h3 class="mt-3 mb-3"><?= $data['team_clients_on_review'] ?? 0 ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php elseif ($user_data['user_group'] == 4) : // ================= ДЛЯ АГЕНТА ================= ?>
                        <div class="row">
                            <div class="col-xl-3 col-lg-6">
                                <div class="card widget-flat">
                                    <div class="card-body">
                                        <div class="float-end">
                                            <i class="uil-money-stack widget-icon <?= ($user_data['user_balance'] < 0) ? 'bg-danger-lighten text-danger' : 'bg-success-lighten text-success' ?>"></i>
                                        </div>
                                        <h5 class="text-muted fw-normal mt-0" title="Мой баланс">Мой баланс</h5>
                                        <h3 class="mt-3 mb-3"><?= number_format($user_data['user_balance'], 2, '.', ' ') ?> $</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-6">
                                <div class="card widget-flat">
                                    <div class="card-body">
                                        <div class="float-end">
                                            <i class="uil-chart widget-icon bg-primary-lighten text-primary"></i>
                                        </div>
                                        <h5 class="text-muted fw-normal mt-0" title="Продаж за месяц">Продаж (мес.)</h5>
                                        <h3 class="mt-3 mb-3"><?= number_format($data['my_sales_month'] ?? 0, 2, '.', ' ') ?> $</h3>
                                    </div>
                                </div>
                            </div>
                             <div class="col-xl-3 col-lg-6">
                                <div class="card widget-flat">
                                    <div class="card-body">
                                        <div class="float-end">
                                            <i class="uil-file-times-alt widget-icon bg-danger-lighten text-danger"></i>
                                        </div>
                                        <h5 class="text-muted fw-normal mt-0" title="Отклоненные анкеты">Отклонено</h5>
                                        <h3 class="mt-3 mb-3"><?= $data['my_rejected_clients'] ?? 0 ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-6">
                                <div class="card widget-flat">
                                    <div class="card-body">
                                        <div class="float-end">
                                            <i class="uil-file-edit-alt widget-icon bg-info-lighten text-info"></i>
                                        </div>
                                        <h5 class="text-muted fw-normal mt-0" title="Анкеты в работе">В работе</h5>
                                        <h3 class="mt-3 mb-3"><?= $data['my_active_clients'] ?? 0 ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                    </div>
                    <!-- container -->

                </div>
                <!-- content -->

                <?php require_once SYSTEM . '/layouts/footer.php'; ?>

                

            </div>

            <!-- ============================================================== -->
            <!-- End Page content -->
            <!-- ============================================================== -->

        </div>
        <!-- END wrapper -->



        <?php
        require_once SYSTEM . '/layouts/scripts.php';
        ?>
        </body>
</html>