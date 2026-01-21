<!-- ========== Topbar Start ========== -->
<div class="navbar-custom">
                <div class="topbar container-fluid">
                    <div class="d-flex align-items-center gap-lg-2 gap-1">

                        <!-- Topbar Brand Logo -->
                        <div class="logo-topbar d-none">
                            <!-- Logo light -->
                            <a href="/?page=dashboard" class="logo-light">
                                <span class="logo-lg">
                                    <img src="assets/images/logo.png" alt="logo">
                                </span>
                                <span class="logo-sm">
                                    <img src="assets/images/logo-sm.png" alt="small logo">
                                </span>
                            </a>

                            <!-- Logo Dark -->
                            <a href="/?page=dashboard" class="logo-dark">
                                <span class="logo-lg">
                                    <img src="assets/images/logo-dark.png" alt="dark logo">
                                </span>
                                <span class="logo-sm">
                                    <img src="assets/images/logo-dark-sm.png" alt="small logo">
                                </span>
                            </a>
                        </div>

                        <!-- Sidebar Menu Toggle Button -->
                        <button class="button-toggle-menu">
                            <i class="mdi mdi-menu"></i>
                        </button>

                        <!-- Horizontal Menu Toggle Button -->
                        <button class="navbar-toggle" data-bs-toggle="collapse" data-bs-target="#topnav-menu-content">
                            <div class="lines">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                        </button>

                        <!-- Topbar Search Form -->
                        <!-- <div class="app-search dropdown d-none d-lg-block">
                            <form>
                                <div class="input-group">
                                    <input type="search" class="form-control dropdown-toggle" placeholder="Search..." id="top-search">
                                    <span class="mdi mdi-magnify search-icon"></span>
                                    <button class="input-group-text btn btn-primary" type="submit">Search</button>
                                </div>
                            </form>

                            <div class="dropdown-menu dropdown-menu-animated dropdown-lg" id="search-dropdown">
                                
                                <div class="dropdown-header noti-title">
                                    <h5 class="text-overflow mb-2">Found <span class="text-danger">17</span> results</h5>
                                </div>

                                
                                <a href="javascript:void(0);" class="dropdown-item notify-item">
                                    <i class="uil-notes font-16 me-1"></i>
                                    <span>Analytics Report</span>
                                </a>

                                
                                <a href="javascript:void(0);" class="dropdown-item notify-item">
                                    <i class="uil-life-ring font-16 me-1"></i>
                                    <span>How can I help you?</span>
                                </a>

                                
                                <a href="javascript:void(0);" class="dropdown-item notify-item">
                                    <i class="uil-cog font-16 me-1"></i>
                                    <span>User profile settings</span>
                                </a>

                                
                                <div class="dropdown-header noti-title">
                                    <h6 class="text-overflow mb-2 text-uppercase">Users</h6>
                                </div>

                                <div class="notification-list">
                                    
                                    <a href="javascript:void(0);" class="dropdown-item notify-item">
                                        <div class="d-flex">
                                            <img class="d-flex me-2 rounded-circle" src="assets/images/users/avatar-2.jpg" alt="Generic placeholder image" height="32">
                                            <div class="w-100">
                                                <h5 class="m-0 font-14">Erwin Brown</h5>
                                                <span class="font-12 mb-0">UI Designer</span>
                                            </div>
                                        </div>
                                    </a>

                                    
                                    <a href="javascript:void(0);" class="dropdown-item notify-item">
                                        <div class="d-flex">
                                            <img class="d-flex me-2 rounded-circle" src="assets/images/users/avatar-5.jpg" alt="Generic placeholder image" height="32">
                                            <div class="w-100">
                                                <h5 class="m-0 font-14">Jacob Deo</h5>
                                                <span class="font-12 mb-0">Developer</span>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div> -->
                    </div>

                    <ul class="topbar-menu d-flex align-items-center gap-3">
                        <!-- <li class="dropdown d-lg-none">
                            <a class="nav-link dropdown-toggle arrow-none" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                                <i class="ri-search-line font-22"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-animated dropdown-lg p-0">
                                <form class="p-3">
                                    <input type="search" class="form-control" placeholder="Search ..." aria-label="Recipient's username">
                                </form>
                            </div>
                        </li> -->

                        <li class="dropdown">
                            <a class="nav-link dropdown-toggle arrow-none" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                                <img src="assets/images/flags/<?= $current_lang ?>.jpg" alt="user-image" class="me-0 me-sm-1" height="12">
                                <span class="align-middle d-none d-lg-inline-block"><?= strtoupper($current_lang) ?></span> <i class="mdi mdi-chevron-down d-none d-sm-inline-block align-middle"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end dropdown-menu-animated">
                                <?php foreach (AVAILABLE_LANGUAGES as $lng): ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['lang' => $lng])) ?>" class="dropdown-item">
                                        <img src="assets/images/flags/<?= $lng ?>.jpg" alt="user-image" class="me-1" height="12"> 
                                        <span class="align-middle"><?= strtoupper($lng) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </li>

                        <!-- Notification Menu -->
                        <li class="dropdown notification-list">
                            <a class="nav-link arrow-none" href="/?page=notifications" role="button">
                                <i class="ri-notification-3-line font-22"></i>
                                <!-- Красная точка (по умолчанию скрыта d-none) -->
                                <span class="noti-icon-badge d-none" id="global-noti-badge"></span>
                            </a>
                        </li>
                        <!-- End Notification Menu -->


                        <li class="d-sm-inline-block">
                            <div class="nav-link" id="light-dark-mode" data-bs-toggle="tooltip" data-bs-placement="left" title="Переключить тему">
                                <i class="ri-moon-line font-22"></i>
                            </div>
                        </li>





                        <?php if($user_data['user_group'] === 4):?>

                        <li class="d-sm-inline-block">
                            <?php
                            [$balance_css] = match (true) {
                                $user_data['user_balance'] < 0  => ['danger'],
                                $user_data['user_balance'] > 0  => ['success'],
                                default        => ['secondary']
                            };
                            ?>
                        <h5><span class="text-<?= $balance_css ?> fw-semibold"><i class="mdi mdi-currency-usd"></i><?= number_format($user_data['user_balance'], 2, '.', ' ') ?></span></h5>
                        </li>



                        <?php endif; ?>





                        <li class="dropdown">
                            <a class="nav-link dropdown-toggle arrow-none nav-user px-2" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                                <span class="account-user-avatar">
                                <i class="ri-user-line font-22"></i>
                                </span>
                                <span class="d-lg-flex flex-column gap-1 d-none">
                                    <h5 class="my-0"><?= $user_data['user_firstname'] ?> <?= $user_data['user_lastname'] ?></h5>
                                    <h6 class="my-0 fw-normal"><?= $user_group_text ?></h6>
                                </span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end dropdown-menu-animated profile-dropdown">

                                <!-- item-->
                                <a href="/?page=profile" class="dropdown-item">
                                    <i class="mdi mdi-account-circle me-1"></i>
                                    <span>Профиль</span>
                                </a>

                                <!-- item-->
                                <!-- <a href="javascript:void(0);" class="dropdown-item">
                                    <i class="mdi mdi-account-edit me-1"></i>
                                    <span>Settings</span>
                                </a> -->

                                <!-- item-->
                                <a href="/?page=logout" class="dropdown-item">
                                    <i class="mdi mdi-logout me-1"></i>
                                    <span>Выход</span>
                                </a>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            <!-- ========== Topbar End ========== -->
























            <!-- ========== Left Sidebar Start ========== -->
<div class="leftside-menu">

<!-- Brand Logo Light -->
<a href="/?page=dashboard" class="logo logo-light">
    <span class="logo-lg">
        <img src="assets/images/logo.png?v=<?= $crm_version ?>" alt="logo">
    </span>
    <span class="logo-sm">
        <img src="assets/images/logo-sm.png?v=<?= $crm_version ?>" alt="small logo">
    </span>
</a>

<!-- Brand Logo Dark -->
<a href="/?page=dashboard" class="logo logo-dark">
    <span class="logo-lg">
        <img src="assets/images/logo-dark.png?v=<?= $crm_version ?>" alt="dark logo">
    </span>
    <span class="logo-sm">
        <img src="assets/images/logo-dark-sm.png?v=<?= $crm_version ?>" alt="small logo">
    </span>
</a>

<!-- Sidebar Hover Menu Toggle Button -->
<div class="button-sm-hover" data-bs-toggle="tooltip" data-bs-placement="right" title="Show Full Sidebar">
    <i class="ri-checkbox-blank-circle-line align-middle"></i>
</div>

<!-- Full Sidebar Menu Close Button -->
<div class="button-close-fullsidebar">
    <i class="ri-close-fill align-middle"></i>
</div>

<!-- Sidebar -left -->
<div class="h-100" id="leftside-menu-container" data-simplebar>

    <!--- Sidemenu -->
    <ul class="side-nav">

        <li class="side-nav-title">Меню</li>

        <li class="side-nav-item <?= ($page == 'dashboard') ? 'menuitem-active' : '' ?>">
            <a href="/?page=dashboard" class="side-nav-link <?= ($page == 'dashboard') ? 'active' : '' ?>">
                <i class="uil-home-alt"></i>
                <!-- <span class="badge bg-success float-end">5</span> -->
                <span>Статистика</span>
            </a>
        </li>

        <?php if ($user_data['user_group'] == 1): // Только Директор видит загрузчик ?>
        <li class="side-nav-item <?= ($page == 'pdf-upload') ? 'menuitem-active' : '' ?>">
            <a href="/?page=pdf-upload" class="side-nav-link <?= ($page == 'pdf-upload') ? 'active' : '' ?>">
                <i class="uil-file-upload-alt"></i>
                <span>Загрузка PDF</span>
            </a>
        </li>
        <?php endif; ?>


        <?php if (in_array($user_data['user_group'], [1, 2, 3])): // Только Директор, Руководитель и Менеджер видят сотрудников ?>
        <li class="side-nav-item <?= ($page == 'customers' or $page == 'new-customer' or $page == 'edit-customer') ? 'menuitem-active' : '' ?>">
            <a href="/?page=customers" class="side-nav-link <?= ($page == 'customers' or $page == 'new-customer' or $page == 'edit-customer') ? 'active' : '' ?>">
                <i class="uil-users-alt"></i>
                <!-- <span class="badge bg-success float-end">5</span> -->
                <span>Сотрудники</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (in_array($user_data['user_group'], [1, 2])): // Только Директор и Руководитель видят финансы ?>
        <li class="side-nav-item <?= ($page == 'finance') ? 'menuitem-active' : '' ?>">
            <a href="/?page=finance" class="side-nav-link <?= ($page == 'finance') ? 'active' : '' ?>">
                <i class="uil-dollar-alt"></i>
                <!-- <span class="badge bg-success float-end">5</span> -->
                <span>Финансы</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if ($user_data['user_group'] == 1): // Только Директор видит настройки ?>
        <li class="side-nav-item <?= ($page == 'settings-centers' or $page == 'settings-countries' or $page == 'settings-cities' or $page == 'settings-inputs' or $page == 'settings-pdf-rules' or $page == 'new-city' or $page == 'edit-city') ? 'menuitem-active' : '' ?>">
            <a data-bs-toggle="collapse" href="#sidebar-1" aria-expanded="<?= ($page == 'settings-centers' or $page == 'settings-countries' or $page == 'settings-cities' or $page == 'settings-inputs' or $page == 'settings-pdf-rules' or $page == 'new-city' or $page == 'edit-city') ? 'true' : 'false' ?>" aria-controls="sidebarPages" class="side-nav-link"><i class="uil-bright"></i><span>Настройки</span><span class="menu-arrow"></span></a>
            <div class="collapse <?= ($page == 'settings-centers' or $page == 'settings-countries' or $page == 'settings-cities' or $page == 'settings-inputs' or $page == 'settings-pdf-rules' or $page == 'new-city' or $page == 'edit-city') ? 'show' : '' ?>" id="sidebar-1">
                <ul class="side-nav-second-level">
                    <li class="<?= ($page == 'settings-centers') ? 'menuitem-active' : '' ?>"><a href="/?page=settings-centers">Визовые центры</a></li>
                    <li class="<?= ($page == 'settings-countries') ? 'menuitem-active' : '' ?>"><a href="/?page=settings-countries">Страны</a></li>
                    <li class="<?= ($page == 'settings-cities' or $page == 'new-city' or $page == 'edit-city') ? 'menuitem-active' : '' ?>"><a href="/?page=settings-cities">Города</a></li>
                    <li class="<?= ($page == 'settings-inputs') ? 'menuitem-active' : '' ?>"><a href="/?page=settings-inputs">Дополнительные поля</a></li>
                    <li class="<?= ($page == 'settings-pdf-rules') ? 'menuitem-active' : '' ?>"><a href="/?page=settings-pdf-rules">Правила обработки PDF</a></li>
                </ul>
            </div>
        </li>
        <?php endif; ?>

        <?php if (!empty($grouped_menu_data)): ?>
            <li class="side-nav-title">Направления</li>

            <?php foreach ($grouped_menu_data as $country_id => $country_data): ?>
                
                <?php if (!empty($country_data['centers'])): ?>
                    <?php
                    $is_country_active = false;
                    if (isset($current_center) && $current_center['country_id'] == $country_id) {
                        $is_country_active = true;
                    }
                    ?>
                    <li class="side-nav-item <?= $is_country_active ? 'menuitem-active' : '' ?>">
                        <a data-bs-toggle="collapse" href="#sidebarCountry-<?= $country_id ?>" aria-expanded="<?= $is_country_active ? 'true' : 'false' ?>" aria-controls="sidebarCountry-<?= $country_id ?>" class="side-nav-link">
                            <i class="uil-globe"></i>
                            <span> <?= $country_data['country_name'] ?> </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse <?= $is_country_active ? 'show' : '' ?>" id="sidebarCountry-<?= $country_id ?>">
                            <ul class="side-nav-second-level">
                                <?php foreach ($country_data['centers'] as $center): ?>
                                    <?php
                                    $is_center_active = isset($current_center) && $current_center['center_id'] == $center['center_id'];
                                    ?>
                                    <li class="<?= $is_center_active ? 'menuitem-active' : '' ?>">
                                        <a href="/?page=clients&center=<?= $center['center_id'] ?>" class="<?= $is_center_active ? 'active' : '' ?>"><?= $center['center_name'] ?></a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </li>
                <?php endif; ?>

            <?php endforeach; ?>
        <?php endif; ?>

    </ul>
    <!--- End Sidemenu -->

    <div class="clearfix"></div>
</div>
</div>
<!-- ========== Left Sidebar End ========== -->