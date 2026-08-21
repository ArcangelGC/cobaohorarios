<?php
// includes/header.php - Header del sistema (CON LOGO SVG - SIN TEXTO COBAO)
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COBAO - Sistema de Gestión de Horarios</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        /* ============================================
           ESTILOS GENERALES
           ============================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f5f5;
        }

        /* ============================================
           SIDEBAR - ROJO CARMESÍ CON LOGO
           ============================================ */
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #8B0000 0%, #5C0000 100%);
            color: white;
            padding: 0;
            position: sticky;
            top: 0;
            box-shadow: 4px 0 20px rgba(139, 0, 0, 0.3);
            z-index: 100;
        }

        .sidebar .brand-sidebar {
            padding: 25px 15px 20px 15px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.15);
            text-align: center;
            background: rgba(0, 0, 0, 0.2);
        }

        .sidebar .brand-sidebar .brand-logo {
            margin-bottom: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .sidebar .brand-sidebar .brand-logo img {
            max-height: 90px;
            width: auto;
            filter: brightness(0) invert(1);
            transition: all 0.3s ease;
        }

        .sidebar .brand-sidebar .brand-logo img:hover {
            transform: scale(1.05);
        }

        .sidebar .brand-sidebar small {
            opacity: 0.6;
            font-size: 0.75rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            display: block;
            color: #ffffff;
            font-weight: 300;
        }

        .sidebar .nav {
            padding: 10px 0;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.7);
            padding: 12px 22px;
            border-radius: 0;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            font-weight: 500;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border-left-color: #ffffff;
            padding-left: 26px;
        }

        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border-left-color: #ffffff;
            font-weight: 600;
        }

        .sidebar .nav-link i {
            width: 22px;
            font-size: 1rem;
            text-align: center;
            color: rgba(255, 255, 255, 0.5);
        }

        .sidebar .nav-link.active i {
            color: #ffffff;
        }

        .sidebar .nav-link:hover i {
            color: #ffffff;
        }

        .sidebar .nav-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.08);
            margin: 8px 20px;
        }

        /* ============================================
           MAIN CONTENT
           ============================================ */
        .main-content {
            padding: 25px 30px;
            min-height: 100vh;
            background: #f5f5f5;
        }

        /* ============================================
           TOP BAR - BLANCO CON DETALLES ROJOS
           ============================================ */
        .top-bar {
            background: white;
            padding: 18px 28px;
            border-radius: 16px;
            margin-bottom: 25px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.06);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            border-left: 5px solid #8B0000;
            transition: all 0.3s ease;
        }

        .top-bar:hover {
            box-shadow: 0 4px 25px rgba(139, 0, 0, 0.1);
        }

        .top-bar .page-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .top-bar .page-title .title-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, #8B0000, #5C0000);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.2rem;
        }

        .top-bar .page-title h4 {
            margin: 0;
            font-weight: 700;
            color: #1a1a2e;
            font-size: 1.2rem;
        }

        .top-bar .page-title h4 small {
            font-weight: 400;
            font-size: 0.75rem;
            color: #8B0000;
            display: block;
            margin-top: 2px;
        }

        .top-bar .fecha-actual {
            color: #6c757d;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .top-bar .fecha-actual i {
            color: #8B0000;
        }

        .top-bar .top-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .top-bar .top-actions .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .top-bar .top-actions .badge-status.online {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .top-bar .top-actions .badge-status.online .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #4caf50;
            animation: pulse-dot 2s infinite;
        }

        .top-bar .top-actions .badge-user {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            border-radius: 20px;
            background: linear-gradient(135deg, #8B0000, #5C0000);
            color: white;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .top-bar .top-actions .badge-user i {
            font-size: 0.9rem;
        }

        @keyframes pulse-dot {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.3;
            }
        }

        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 992px) {
            .sidebar {
                min-height: auto;
                margin-bottom: 20px;
                position: relative;
            }

            .main-content {
                padding: 15px;
            }

            .top-bar {
                padding: 15px 20px;
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }

            .top-bar .top-actions {
                justify-content: flex-start;
            }

            .sidebar .brand-sidebar {
                padding: 15px;
            }

            .sidebar .brand-sidebar .brand-logo img {
                max-height: 70px;
            }
        }

        @media (max-width: 576px) {
            .top-bar .page-title h4 {
                font-size: 1rem;
            }

            .top-bar .page-title .title-icon {
                width: 34px;
                height: 34px;
                font-size: 1rem;
            }

            .top-bar .top-actions .badge-status,
            .top-bar .top-actions .badge-user {
                font-size: 0.65rem;
                padding: 4px 12px;
            }

            .sidebar .nav-link {
                padding: 10px 16px;
                font-size: 0.8rem;
            }

            .sidebar .nav-link i {
                width: 18px;
                font-size: 0.85rem;
            }

            .sidebar .brand-sidebar .brand-logo img {
                max-height: 55px;
            }
        }

        /* ============================================
           SCROLLBAR PERSONALIZADA
           ============================================ */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f5f5f5;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #8B0000, #5C0000);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #6d0000;
        }
    </style>
</head>

<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <!-- ==========================================
            SIDEBAR - ROJO CARMESÍ CON LOGO SVG
            ========================================== -->
            <div class="col-md-2 sidebar" id="sidebar">
                <div class="brand-sidebar">
                    <!-- LOGO SVG - MÁS GRANDE -->
                    <div class="brand-logo">
                        <img src="/cobaohorarios/assets/img/cobao.svg" alt="COBAO Logo" class="img-fluid">
                    </div>
                    <!-- SOLO SISTEMA DE HORARIOS -->
                    <small>Sistema de Horarios</small>
                </div>

                <nav class="nav flex-column">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>"
                        href="/cobaohorarios/index.php">
                        <i class="fas fa-chart-pie"></i> Dashboard
                    </a>

                    <div class="nav-divider"></div>

                    <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'grupos') !== false ? 'active' : ''; ?>"
                        href="/cobaohorarios/modules/grupos/">
                        <i class="fas fa-users"></i> Grupos
                    </a>

                    <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'materias') !== false ? 'active' : ''; ?>"
                        href="/cobaohorarios/modules/materias/">
                        <i class="fas fa-book"></i> Materias
                    </a>

                    <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'asignaciones') !== false ? 'active' : ''; ?>"
                        href="/cobaohorarios/modules/asignaciones/">
                        <i class="fas fa-tasks"></i> Asignaciones
                    </a>

                    <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'maestros') !== false ? 'active' : ''; ?>"
                        href="/cobaohorarios/modules/maestros/">
                        <i class="fas fa-chalkboard-teacher"></i> Maestros
                    </a>

                    <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'salones') !== false ? 'active' : ''; ?>"
                        href="/cobaohorarios/modules/salones/">
                        <i class="fas fa-door-open"></i> Salones
                    </a>

                    <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'especialidades') !== false ? 'active' : ''; ?>"
                        href="/cobaohorarios/modules/especialidades/">
                        <i class="fas fa-tags"></i> Especialidades
                    </a>

                    <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'nucleos') !== false ? 'active' : ''; ?>"
                        href="/cobaohorarios/modules/nucleos/">
                        <i class="fas fa-layer-group"></i> Núcleos
                    </a>

                    <div class="nav-divider"></div>

                    <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'horarios') !== false ? 'active' : ''; ?>"
                        href="/cobaohorarios/modules/horarios/">
                        <i class="fas fa-calendar-alt"></i> Horarios
                    </a>

                    <a class="nav-link" href="/cobaohorarios/modules/horarios/generar.php">
                        <i class="fas fa-magic"></i> Generar Horarios
                    </a>

                    <a class="nav-link" href="/cobaohorarios/reports/">
                        <i class="fas fa-file-alt"></i> Reportes
                    </a>
                </nav>
            </div>

            <!-- ==========================================
            MAIN CONTENT
            ========================================== -->
            <div class="col-md-10 main-content">
                <!-- ==========================================
                TOP BAR - BLANCO CON DETALLES ROJOS
                ========================================== -->
                <div class="top-bar">
                    <div class="page-title">
                        <div class="title-icon">
                            <i class="fas fa-<?php echo $page_icon ?? 'chart-line'; ?>"></i>
                        </div>
                        <div>
                            <h4>
                                <?php echo $page_title ?? 'Panel de Control'; ?>
                                <small><?php echo $page_subtitle ?? 'COBAO - Colegio de Bachilleres del Estado de Oaxaca'; ?></small>
                            </h4>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <span class="fecha-actual">
                            <i class="far fa-calendar-alt"></i>
                            <?php echo date('l, d \d\e F \d\e Y'); ?>
                        </span>

                        <div class="top-actions">
                            <span class="badge-status online">
                                <span class="dot"></span>
                                Sistema Activo
                            </span>
                            <span class="badge-user">
                                <i class="fas fa-user-shield"></i>
                                Admin
                            </span>
                        </div>
                    </div>
                </div>